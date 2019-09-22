<?php

declare(strict_types=1);

namespace Cutlery;

use Mockery\Container;
use Mockery\Loader\EvalLoader;
use ReflectionObject;
use Cutlery\Mockery\Generator\ObjectSynchronizerGenerator;

/**
 * This class wraps a variable so all operations on the variable are always sent to the parent process in forked
 * processes. For this to work properly the variable needs to be wrapper _before_ the process is forked.
 *
 * There are currently some limitations:
 *
 *   - Static calls can't be forwarded.
 *   - Unserializable variables, such as resources and instances of `\Closure` and `\PDO` will be filtered out.
 *
 * @todo Add support for arrays.
 */
class Synchronizer
{
    public const ACTION_GET       = 'get';
    public const ACTION_SET       = 'set';
    public const ACTION_ISSET     = 'isset';
    public const ACTION_UNSET     = 'unset';
    public const ACTION_CALL      = 'call';
    public const ACTION_TO_STRING = 'to_string';
    public const ACTION_INVOKE    = 'invoke';

    public const BUFFER_SIZE = 1024;

    public const DELIMITER = "\n__CUTLERY_SYNC_DELIMITER__\n";

    /**
     * @var \Mockery\Container
     */
    protected static $container;

    /**
     * @param  object  $delegate
     * @return mixed
     *
     * @throws \Mockery\Exception\RuntimeException
     */
    public static function synchronize($delegate)
    {
        $reflectionObject = new ReflectionObject($delegate);

        $args = [$delegate];

        // If the $delegate's class is final, at least extend any parent class and implement interfaces.
        if ($reflectionObject->isFinal()) {
            $parentClass = $reflectionObject->getParentClass();

            if ($parentClass) {
                $args[] = $parentClass->getName();
            }

            $interfaceNames = $reflectionObject->getInterfaceNames();
            if ($interfaceNames) {
                $args = array_merge($args, $interfaceNames);
            }
        }

        $mock = static::getContainer()->mock(...$args);

        Fork::registerCallback(function (Fork $fork) use ($mock) {
            $mock->_cutlery_synchronizer_enable();
        });

        return $mock;
    }

    /**
     * Lazy loader and getter for
     * the container property.
     *
     * @return \Mockery\Container
     */
    public static function getContainer()
    {
        if (is_null(self::$container)) {
            self::$container = new Container(ObjectSynchronizerGenerator::withDefaultPasses(), new EvalLoader());
        }

        return self::$container;
    }
}
