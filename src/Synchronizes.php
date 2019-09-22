<?php

declare(strict_types=1);

namespace Cutlery;

use Mockery\Container;

trait Synchronizes
{
    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * We want to avoid constructors since class is copied to Generator.php
     * for inclusion on extending class definitions.
     *
     * @param \Mockery\Container $container
     * @param object $partialObject
     * @return void
     */
    public function mockery_init(\Mockery\Container $container = null, $partialObject = null)
    {
        if (is_null($container)) {
            $container = new Container;
        }
        $this->_mockery_container = $container;

        $this->_cutlery_synchronizer_target = $partialObject;
        self::$_cutlery_parentPid           = getmypid();

        socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd);
        $this->_cutlery_synchronizer_socketPair = $fd;

        // Make sure sockets aren't blocking.
        socket_set_nonblock($this->_cutlery_synchronizer_socketPair[0]);
        socket_set_nonblock($this->_cutlery_synchronizer_socketPair[1]);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        socket_close($this->_cutlery_synchronizer_socketPair[0]);
        socket_close($this->_cutlery_synchronizer_socketPair[1]);
    }

    /**
     * @return void
     */
    public function _cutlery_synchronizer_enable()
    {
        $this->_cutlery_synchronizer_enabled++;
    }

    /**
     * @return void
     */
    public function _cutlery_synchronizer_disable()
    {
        $this->_cutlery_synchronizer_enabled--;
    }

    /**
     * @return resource
     */
    protected function _cutlery_synchronizer_getSocket()
    {
        if (null === $this->_cutlery_synchronizer_socket) {
            if (self::$_cutlery_parentPid === getmypid()) {
                // Parent process
                $this->_cutlery_synchronizer_socket =& $this->_cutlery_synchronizer_socketPair[1];
            } else {
                // Child process
                $this->_cutlery_synchronizer_socket =& $this->_cutlery_synchronizer_socketPair[0];
            }
        }

        return $this->_cutlery_synchronizer_socket;
    }

    /**
     * Syncs all data sent from the opposing socket.
     *
     * @return void
     */
    private function _cutlery_synchronizer_sync()
    {
        $delimiterLength = strlen(Synchronizer::DELIMITER);

        // Read all data from socket.
        $socket = $this->_cutlery_synchronizer_getSocket();
        while (($data = socket_read($socket, Synchronizer::BUFFER_SIZE)) == true) {
            $this->buffer .= $data;

            $delimiterPosition = strpos($this->buffer, Synchronizer::DELIMITER);

            if ($delimiterPosition !== false) {
                $actionBuffer    = substr($this->buffer, 0, $delimiterPosition);
                $this->buffer    = substr($this->buffer, $delimiterPosition + $delimiterLength);
                [$action, $data] = explode(',', $actionBuffer, 2);
                $this->_cutlery_synchronizer_performAction($action, $data);
            }
        }
    }
}
