<?php

declare(strict_types=1);

namespace Cutlery;

use Closure;
use PDO;
use ReflectionObject;
use Throwable;

class Fork
{
    public const DELIMITER = "\n__CUTLERY_FORK_END_DELIMITER__\n";

    public const BUFFER_SIZE = 1024;

    /**
     * @var callable[]
     */
    protected static $callbacks = [];

    /**
     * @var int
     */
    protected $pid;

    /**
     * @var resource[]
     */
    protected $socketPair;

    /**
     * @param  callable  $callable
     * @return void
     *
     * @throws \Exception
     */
    public function __construct($callable)
    {
        socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd);
        $this->socketPair = $fd;
        $this->pid        = pcntl_fork();

        foreach (self::$callbacks as $callback) {
            $callback($this);
        }

        $status = 0;

        switch ($this->pid) {
            case -1:
                throw new ForkException('Failed to fork process for Scenario');

            case 0:
                try {
                    // We're in the child process, run the $callable.
                    $result = $callable();

                    // Write results to socket.
                    $buffers = str_split(
                        serialize(self::ensureSerializable($result)) . self::DELIMITER,
                        self::BUFFER_SIZE
                    );

                    foreach ($buffers as $buffer) {
                        socket_write($this->socketPair[0], $buffer, self::BUFFER_SIZE);
                    }
                } catch (Throwable $t) {
                    $status = 1;
                } finally {
                    socket_close($this->socketPair[0]);
                }

                exit($status);
                break;

            default:
                break;
        }
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return mixed
     * @throws \Cutlery\ForkException
     */
    public function wait()
    {
        $delimiterPosition = false;
        $data = '';

        do {
            $read   = [$this->socketPair[1]];
            $write  = [];
            $except = [];
            $buffer = '';

            /** @noinspection PhpAssignmentInConditionInspection */
            while($socketCount = socket_select($read, $write, $except, 1)) {
                $data = socket_read($this->socketPair[1], self::BUFFER_SIZE);

                if ($data) {
                    $delimiterPosition = strpos($data, self::DELIMITER);
                    if (false !== $delimiterPosition) {
                        $data = substr($data, 0, $delimiterPosition);
                    }

                    $buffer .= $data;
                }
            }

            if (false === $socketCount) {
                throw new ForkException("socket_select() failed");
            }
        } while (false !== $data && false === $delimiterPosition);

        socket_close($this->socketPair[1]);

        // Wait for the child to finish.
//        pcntl_waitpid($this->pid, $status);

        return unserialize($buffer);
    }

    /**
     * @param $callable
     * @return mixed
     * @throws \Exception
     */
    public static function run($callable)
    {
        return (new static($callable))->wait();
    }

    /**
     * Register a callback that will be called when a Fork is created.
     *
     * @param  callable  $callback
     */
    public static function registerCallback(callable $callback)
    {
        self::$callbacks[] = $callback;
    }

    /**
     * @param  mixed  $var
     * @return mixed
     */
    public static function ensureSerializable($var)
    {
        if ($var instanceof Closure || $var instanceof PDO || is_resource($var)) {

            // Remove most common unserializable objects.
            return null;

        } elseif (is_object($var)) {

            $clone = clone $var;

            // Iterate through properties and recursively call this method on them.
            foreach ((new ReflectionObject($var))->getProperties() as $property) {
                $property->setAccessible(true);

                $value = $property->getValue($var);

                // Prevent recursion.
                if ($value === $var) {
                    continue;
                }

                $propertyName = $property->getName();

                // Depending on whether the property is public and/or static we use different methods.
                if ($property->isPublic()) {
                    if ($property->isStatic()) {
                        $clone::$$propertyName = self::ensureSerializable($var::$$propertyName);
                    } else {
                        $clone->{$propertyName} = self::ensureSerializable($var->{$propertyName});
                    }
                } else {
                    if ($property->isStatic()) {
                        $property->setValue(self::ensureSerializable($value));
                    } else {
                        $property->setValue($clone, self::ensureSerializable($value));
                    }
                }
            }

            $var = $clone;

        } elseif (is_array($var)) {

            foreach ($var as $key => $value) {
                $var[$key] = self::ensureSerializable($value);
            }

        }

        // If despite all efforts we're still unable to serialize $var, just set return null.
        try {
            serialize($var);
        } catch (Throwable $t) {
            return null;
        }

        return $var;
    }
}
