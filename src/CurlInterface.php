<?php

namespace Iris;

/**
 * Interface for Iris cURL classes
 */
interface CurlInterface
{

    const IRIS_VERSION = 2.0;

    /**
     * On succes callback
     */
    const EVENT_ON_SUCCESS = 1;

    /**
     *  On error callback
     */
    const EVENT_ON_ERROR = 2;

    /**
     * Add a callable to a given event
     *
     * @param scalar   $event    the event the listener is attach to
     * @param callable $listener
     *
     * @return self
     */
    public function addListener($event, $listener);

    /**
     * Remove a callable from a given event
     *
     * @param scalar   $event    the event the listener is attach to
     * @param callable $listener
     */
    public function removeListener($event, $listener);

    /**
     * return the listeners attach to the a given event
     *
     * @param scalar $event the event the listener is attach to
     *
     * @return array
     */
    public function getListeners($event);

    /**
     * Dispatch arguments to all the listener register to a given event
     *
     * @param scalar $event the event the listener is attach to
     * @param arrat  $args  the arguments to pass to all the listeners
     */
    public function dispatch($event, array $args = array());

    /**
    * set options
    *
    * @param integer $name
    * @param mixed $value
    *
    * @return self
    */
    public function setOption($name, $value = null);

    /**
     * Return the options set
     *
     * @param integer|null $name a valid cURL options or null
     *
     * @return mixed when $name is null returns the whole options
     */
    public function getOption($name = null);

    /**
     * apply the options to the cURL handler
     *
     * @return boolean
     */
    public function applyOptions();

    /**
     * return the current handler
     *
     * @return resource
     */
    public function getHandler();

    /**
    * Init The Handler
    *
    * @param string $url url to fetch
    *
    * @return self
    */
    public function init();

    /**
    * Execute the request
    *
    * @return self
    */
    public function execute();

    /**
    * Close the currently open handler
    *
    * @return self
    */
    public function close();

    /**
    * return the info related to the executed cURL request
    *
    * @param integer $name
    *
    * @return mixed
    */
    public function getInfo($name = null);

    /**
     * return the state of the handler if it is initiated or not
     *
     * @return boolean
     */
    public function isAvailable();
}
