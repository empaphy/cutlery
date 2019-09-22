<?php

declare(strict_types=1);

namespace Cutlery;

trait SynchronizesInvokableObject
{
    use SynchronizesObject {
        SynchronizesObject::_cutlery_synchronizer_performAction as __cutlery_synchronizer_performAction;
    }

    /**
     * @param  mixed  ...$arguments
     * @return mixed
     */
    public function __invoke(...$arguments)
    {
        $this->_cutlery_synchronizer_sync();

        $buffer = serialize(Fork::ensureSerializable($arguments));

        socket_write(
            $this->_cutlery_synchronizer_getSocket(),
            Synchronizer::ACTION_INVOKE . ",{$buffer}" . Synchronizer::DELIMITER
        );

        // TODO get results from parent method call using pctnl signals.
        return call_user_func_array($this->_cutlery_synchronizer_target, $arguments);
    }

    /**
     * @param  string  $action
     * @param  string  $data
     * @return mixed
     *
     * @throws \Cutlery\SynchronizerException
     */
    protected function _cutlery_synchronizer_performAction($action, $data)
    {
        if (Synchronizer::ACTION_INVOKE == $action) {
            return call_user_func_array($this->_cutlery_synchronizer_target, unserialize($data));
        }

        return $this->__cutlery_synchronizer_performAction($action, $data);
    }
}
