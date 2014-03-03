<?php

namespace Iris;

use InvalidArgumentException;

/**
 * Abstract class implementing EventDispatching and Options Settings
 */
abstract class CurlAbstract
{

    /**
    * cUrl handler
    *
    * @var resource
    */
    protected $handle;

    /**
     * Options for the cURL handler
     *
     * @var array
     */
    protected $options = array();

    /**
     * callable container
     *
     * @var Array
     */
    protected $listeners = array();

    /**
     * The destructor
     */
    public function __destruct()
    {
        $this->close();
        $this->handle = null;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function close();

    /**
     * {@inheritdoc}
     */
    public function addListener($event, $listener)
    {
        if (! is_callable($listener)) {
            throw new InvalidArgumentException('a callable function should be attached');
        }
        if (! array_key_exists($event, $this->listeners)) {
            $this->listeners[$event] = array();
        }
        $this->listeners[$event][] = $listener;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener($event, $listener)
    {
        if (! is_callable($listener)) {
            throw new InvalidArgumentException('a callable function should be attached');
        }
        if (! array_key_exists($event, $this->listeners)) {
            return;
        }
        $index = array_search($listener, $this->listeners[$event], true);
        if (false !== $index) {
            unset($this->listeners[$event][$index]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners($event)
    {
        if (! array_key_exists($event, $this->listeners)) {
            return [];
        }

        return $this->listeners[$event];
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($event, array $args = array())
    {
        foreach ($this->getListeners($event) as $listener) {
            call_user_func_array($listener, $args);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $option_name => $option_value) {
                $this->setOption($option_name, $option_value);
            }

            return $this;
        }

        if (false === filter_var($name, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)))) {
            throw new InvalidArgumentException('options $name should be a valid integer');
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        } elseif (is_null($name)) {
            return $this->options;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandler()
    {
        return $this->handle;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return is_resource($this->handle);
    }
}
