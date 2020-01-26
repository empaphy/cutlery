<?php

/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace Cutlery\Mockery;

trait MockStubs
{
    /**
     * @param  mixed  $something  String method name or map of method => return
     * @return $this
     */
    public function allows($something = [])
    {
        return $this;
    }

    /**
     * @param  mixed  $something  String method name (optional)
     * @return $this
     */
    public function expects($something = null)
    {
        return null;
    }

    /**
     * Set expected method calls
     *
     * @param  array  ...$methodNames  one or many methods that are expected to be called in this mock
     *
     * @return \Mockery\ExpectationInterface|\Mockery\Expectation|\Mockery\HigherOrderMessage
     */
    public function shouldReceive(...$methodNames)
    {
        return null;
    }

    /**
     * Shortcut method for setting an expectation that a method should not be called.
     *
     * @param  array  ...$methodNames  one or many methods that are expected not to be called in this mock
     * @return \Mockery\ExpectationInterface|\Mockery\Expectation|\Mockery\HigherOrderMessage
     */
    public function shouldNotReceive(...$methodNames)
    {
        return null;
    }

    /**
     * Allows additional methods to be mocked that do not explicitly exist on mocked class
     *
     * @param  String  $method  name of the method to be mocked
     * @return $this
     */
    public function shouldAllowMockingMethod($method)
    {
        return $this;
    }

    /**
     * Set mock to ignore unexpected methods and return Undefined class
     * @param  mixed  $returnValue  the default return value for calls to missing functions on this mock
     * @return $this
     */
    public function shouldIgnoreMissing($returnValue = null)
    {
        return $this;
    }

    /**
     * @return $this
     */
    public function shouldAllowMockingProtectedMethods()
    {
        return $this;
    }

    /**
     * Set mock to defer unexpected methods to its parent if possible
     *
     * @deprecated 2.0.0 Please use makePartial() instead
     *
     * @return $this
     */
    public function shouldDeferMissing()
    {
        return $this;
    }

    /**
     * Set mock to defer unexpected methods to its parent if possible
     *
     * @return $this
     */
    public function makePartial()
    {
        return $this;
    }

    /**
     * @param  null|string  $method
     * @param  null         $args
     * @return mixed
     */
    public function shouldHaveReceived($method, $args = null)
    {
        return null;
    }

    /**
     * @param  null|string  $method
     * @param  null         $args
     * @return mixed
     */
    public function shouldNotHaveReceived($method, $args = null)
    {
        return null;
    }

    /**
     * In the event shouldReceive() accepting an array of methods/returns
     * this method will switch them from normal expectations to default
     * expectations
     *
     * @return $this
     */
    public function byDefault()
    {
        return $this;
    }

    /**
     * Iterate across all expectation directors and validate each
     *
     * @throws \Mockery\CountValidator\Exception
     * @return void
     */
    public function mockery_verify()
    {
        return;
    }

    /**
     * Tear down tasks for this mock
     *
     * @return void
     */
    public function mockery_teardown()
    {
        return;
    }

    /**
     * Fetch the next available allocation order number
     *
     * @return int
     */
    public function mockery_allocateOrder()
    {
        return 1;
    }

    /**
     * Set ordering for a group
     *
     * @param  mixed  $group
     * @param  int    $order
     * @return void
     */
    public function mockery_setGroup($group, $order)
    {
        return;
    }

    /**
     * Fetch array of ordered groups
     *
     * @return array
     */
    public function mockery_getGroups()
    {
        return [];
    }

    /**
     * Set current ordered number
     *
     * @param  int  $order
     * @return int
     */
    public function mockery_setCurrentOrder($order)
    {
        return 0;
    }

    /**
     * Get current ordered number
     *
     * @return int
     */
    public function mockery_getCurrentOrder()
    {
        return 0;
    }

    /**
     * Validate the current mock's ordering
     *
     * @param  string  $method
     * @param  int     $order
     * @throws \Mockery\Exception
     * @return void
     */
    public function mockery_validateOrder($method, $order)
    {
        return;
    }

    /**
     * Gets the count of expectations for this mock
     *
     * @return int
     */
    public function mockery_getExpectationCount()
    {
        return 0;
    }

    /**
     * Return the expectations director for the given method
     *
     * @param  string                        $method
     * @param  \Mockery\ExpectationDirector  $director
     * @return void
     */
    public function mockery_setExpectationsFor($method, \Mockery\ExpectationDirector $director)
    {
        return;
    }

    /**
     * Return the expectations director for the given method
     *
     * @var string $method
     * @return null
     */
    public function mockery_getExpectationsFor($method)
    {
        return null;
    }

    /**
     * Find an expectation matching the given method and arguments
     *
     * @var string $method
     * @var array  $args
     * @return null
     */
    public function mockery_findExpectation($method, array $args)
    {
        return null;
    }

    /**
     * Return the container for this mock
     *
     * @return null
     */
    public function mockery_getContainer()
    {
        return null;
    }

    /**
     * Return the name for this mock
     *
     * @return string
     */
    public function mockery_getName()
    {
        return __CLASS__;
    }

    /**
     * @return array
     */
    public function mockery_getMockableProperties()
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function mockery_getMockableMethods()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function mockery_isAnonymous()
    {
        return false;
    }
}
