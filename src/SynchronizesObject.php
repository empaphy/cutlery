<?php

declare(strict_types=1);

namespace Cutlery;

trait SynchronizesObject
{
    use Synchronizes;

    /**
     * @param  string  $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();

            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_GET . ",{$name}" . Synchronizer::DELIMITER
            );
        }

        return $this->_cutlery_synchronizer_target->{$name};
    }

    /**
     * @param  string  $name
     * @param  string  $value
     * @return void
     */
    public function __set($name, $value)
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();

            $buffer = serialize(Fork::ensureSerializable($value));

            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_SET . ",{$name},{$buffer}" . Synchronizer::DELIMITER
            );
        }

        $this->_cutlery_synchronizer_target->{$name} = $value;
    }

    /**
     * @param  string  $name
     * @return bool
     */
    public function __isset($name)
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();

            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_ISSET . ",{$name}" . Synchronizer::DELIMITER
            );
        }

        return isset($this->_cutlery_synchronizer_target);
    }

    /**
     * @param  string  $name
     * @return void
     */
    public function __unset($name)
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();

            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_UNSET . ",{$name}" . Synchronizer::DELIMITER
            );
        }

        unset($this->_cutlery_synchronizer_target);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_TO_STRING . Synchronizer::DELIMITER
            );
        }

        return (string) $this->_cutlery_synchronizer_target;
    }

    /**
     * @param  string   $name
     * @param  mixed[]  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();

            $buffer = serialize(Fork::ensureSerializable($arguments));

            socket_write(
                $this->_cutlery_synchronizer_getSocket(),
                Synchronizer::ACTION_CALL . ",{$name},{$buffer}" . Synchronizer::DELIMITER
            );
        }

        // TODO get results from parent method call using pctnl signals.
        return call_user_func_array([$this->_cutlery_synchronizer_target, $name], $arguments);
    }

    /**
     * @return static
     */
    public function __clone()
    {
        if ($this->_cutlery_synchronizer_enabled > 0) {
            $this->_cutlery_synchronizer_sync();
        }

        $clone                               = clone $this;
        $clone->_cutlery_synchronizer_target = clone $this->_cutlery_synchronizer_target;

        return $clone;
    }

    /**
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    protected function _mockery_handleMethodCall($method, array $args)
    {
        return $this->__call($method, $args);
    }

    /**
     * @param  string  $action
     * @param  string  $data
     * @return mixed
     * @throws \Cutlery\SynchronizerException
     */
    protected function _cutlery_synchronizer_performAction($action, $data)
    {
        switch ($action) {
            case Synchronizer::ACTION_GET:
                return $this->_cutlery_synchronizer_target->{$data};

            case Synchronizer::ACTION_SET:
                [$name, $value] = explode(',', $data, 2);
                $this->_cutlery_synchronizer_target->{$name} = unserialize($value);
                return null;

            case Synchronizer::ACTION_ISSET:
                return isset($this->_cutlery_synchronizer_target->{$data});

            case Synchronizer::ACTION_UNSET:
                unset($this->_cutlery_synchronizer_target->{$data});
                return null;

            case Synchronizer::ACTION_TO_STRING:
                return (string) $this->_cutlery_synchronizer_target;

            case Synchronizer::ACTION_CALL:
                [$name, $arguments] = explode(',', $data, 2);
                return call_user_func_array([$this->_cutlery_synchronizer_target, $name], unserialize($arguments));

            default:
                throw new SynchronizerException("Unknown action '{$action}'");
        }
    }
}
