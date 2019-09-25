<?php

declare(strict_types=1);

namespace Cutlery;

use Closure;
use Exception;
use ReflectionObject;
use Throwable;

class Fork
{
    public const ACTION_RETURN = 'return';
    public const ACTION_THROW  = 'throw';
    public const DELIMITER     = "\n__CUTLERY_FORK_END_DELIMITER__\n";

    public const SOCKET_BUFFER_SIZE = 16777216;
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
                    $data   = self::ACTION_RETURN . ',' . serialize(self::ensureSerializable($result));
                } catch (Throwable $t) {
                    $status = 1;
                    try {
                        $data = self::ACTION_THROW . ',' . serialize(self::ensureSerializable($t));
                    } catch (Throwable $t2) {
                        try {
                            $exception = new Exception($t->getMessage(), $t->getCode(), $t);
                            $data = self::ACTION_THROW . ',' . serialize($exception);
                        } catch (Throwable $t3) {
                            $data = self::ACTION_THROW . ',' . serialize(null);
                        }
                    }
                } finally {
                    $data .= self::DELIMITER;
                    socket_set_option($this->socketPair[0], SOL_SOCKET, SO_SNDBUF, strlen($data));

                    $read   = [];
                    $write  = [$this->socketPair[0]];
                    $except = [];

                    $socketCount = socket_select($read, $write, $except, 1);
                    if ($socketCount) {
                        if (false === socket_write($this->socketPair[0], $data)) {
                            throw new ForkException(
                                'Failed to write to socket: ' . socket_strerror(socket_last_error())
                            );
                        }
                    } else {
                        throw new ForkException(
                            'Parent stopped listening on socket: ' . socket_strerror(socket_last_error())
                        );
                    }

                    socket_close($this->socketPair[0]);
                    $this->socketPair[0] = null;
                }

                exit($status);
                break;

            default:
                break;
        }
    }

    public function __destruct()
    {
        try {
            foreach ($this->socketPair as $socket) {
                if (null !== $socket) {
                    socket_close($socket);
                }
            }
        } catch (Throwable $t) {
            // Ignore.
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
        $buffer = '';

        socket_set_nonblock($this->socketPair[1]);

        do {
            $read   = [$this->socketPair[1]];
            $write  = [];
            $except = [];

            $socketCount = socket_select($read, $write, $except, 1);
            if ($socketCount) {
                while(($data = socket_read($this->socketPair[1], self::BUFFER_SIZE))) {
                    if ($data) {
                        $buffer .= $data;
                    }
                }

                $delimiterPosition = strpos($buffer, self::DELIMITER);
                if (false !== $delimiterPosition) {
                    $buffer = substr($buffer, 0, $delimiterPosition);
                }
            } elseif (false === $socketCount) {
                throw new ForkException(
                    'socket_select() failed: ' . socket_strerror(socket_last_error($this->socketPair[1]))
                );
            } else {
                $pid = pcntl_waitpid($this->pid, $status, WNOHANG);
                if (-1 == $pid) {
                    throw new ForkException("Child process (pid {$this->pid}) exited too early: " . $pid);
                }
            }
        } while (false === $delimiterPosition);

        // Wait for child process to finish.
        pcntl_waitpid($this->pid, $status);

        socket_close($this->socketPair[1]);
        $this->socketPair[1] = null;

        [$action, $result] = explode(',', $buffer, 2);

        switch ($action) {
            case self::ACTION_RETURN:
                return unserialize($result);

            case self::ACTION_THROW:
                $throwable = unserialize($result);
                if (null === $throwable) {
                    $throwable = new ForkException("Invalid Throwable recieved.");
                }
                throw $throwable;

            default:
                throw new ForkException("Invalid action");
        }
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
        if ($var instanceof Closure || is_resource($var) || $var instanceof \PDO) {

            // Remove most common unserializable objects.
            return null;

        } elseif (is_object($var)) {

            $reflectionObject = new ReflectionObject($var);

            if ($reflectionObject->isCloneable()) {
                $clone = clone $var;
            } else {
                $clone = $var;
            }

            // Iterate through properties and recursively call this method on them.
            foreach ($reflectionObject->getProperties() as $property) {
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
