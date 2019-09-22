<?php

namespace Mockery;

use Mockery\MockInterface;
use Cutlery\Fork;
use Cutlery\Mockery\MockStubs;
use Cutlery\Synchronizer;
use Cutlery\SynchronizesObject;

class Mock implements MockInterface
{
    use MockStubs;
    use SynchronizesObject;

    /**
     * Contains the parent PID so we can check whether we're in the parent or child process.
     *
     * @var int
     */
    public static $_cutlery_parentPid;

    /**
     * Mock container containing this mock object
     *
     * @var \Mockery\Container
     */
    protected $_mockery_container = null;

    /**
     * When enabled will cause the synchronizer to sync calls.
     *
     * @var int
     */
    protected $_cutlery_synchronizer_enabled = 0;

    /**
     * The variable that we're wrapping.
     *
     * @var mixed
     */
    protected $_cutlery_synchronizer_target;

    /**
     * Contains a socket pair which is used to communicate with the parent process.
     *
     * @var resource[]
     */
    protected $_cutlery_synchronizer_socketPair;

    /**
     * @var resource|null
     */
    protected $_cutlery_synchronizer_socket = null;

}
