<?php

namespace Iris;

use InvalidArgumentException;
use RuntimeException;

/**
 * A Wrapper around curl_multi* functions
 */
class Envelope extends CurlAbstract implements CurlInterface
{

    /**
     * Time in second to wait for an answer
     *
     * @var float
     */
    private $selectTimeout = 1.0;

    /**
     * Time in second for the execution
     *
     * @var integer
     */
    private $execTimeout = 100;

    /**
     * The number of connection to open simultanously
     *
     * @var integer
     */
    const MAXCONNECTS = 6;

    /**
     * $selectTimeout setter
     *
     * @param float $timeout
     *
     * @return self
     */
    public function setSelectTimeout($timeout)
    {
        if (false === filter_var($timeout, FILTER_VALIDATE_FLOAT) || 0 > $timeout) {
            throw new InvalidArgumentException('$timeout must be a valid positive float');
        }
        $this->selectTimeout = $timeout;

        return $this;
    }

    /**
     * $selectTimeout getter
     *
     * @return float
     */
    public function getSelectTimeout()
    {
        return $this->selectTimeout;
    }

    /**
     * $execTimeout setter
     *
     * @param integer $timeout
     *
     * @return self
     */
    public function setExecTimeout($timeout)
    {
        if (false === filter_var($timeout, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)))) {
            throw new InvalidArgumentException('$timeout must be a valid positive integer');
        }
        $this->execTimeout = $timeout;

        return $this;
    }

    /**
     * $execTimeout getter
     *
     * @return integer
     */
    public function getExecTimeout()
    {
        return $this->execTimeout;
    }

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->handle = curl_multi_init();
        $this->options[self::MAXCONNECTS] = 10;

        return $this;
    }

    /**
     * Add a cURL handler to the cURL Envelope Handler
     *
     * @param Message $request
     *
     * @return self
     *
     * @throws \RuntimeException If the handler options are invalid
     * @throws \RuntimeException If the handler can not be add
     */
    public function add(Message $request)
    {
        if (! $request->applyOptions()) {
            throw new RuntimeException("applying options to the added cURL Handler did not work");
        } elseif (! 0 === curl_multi_add_handle($this->handle, $request->getHandler())) {
            throw new RuntimeException('The Handler could not be add');
        }

        return $this;
    }

    /**
     * Remove a cURL handler to the cURL Envelope Handler
     *
     * @param Message $request
     *
     * @return self
     *
     * @throws \RuntimeException If The Handler can not be remove
     */
    public function remove(Message $request)
    {
        if (! 0 === curl_multi_remove_handle($this->handle, $request->getHandler())) {
            throw new RuntimeException('The Handler could not be add');
        }

        return $this;
    }

    /**
     * Wait for activity on any curl_Envelope connection
     *
     * @param Message $request
     *
     * @return boolean
     */

    public function select()
    {
        return curl_multi_select($this->handle, $this->selectTimeout);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        curl_multi_close($this->handle);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo($name = null)
    {
        return curl_multi_info_read($this->handle);
    }

    /**
     * {@inheritdoc}
     */
    public function applyOptions()
    {
        if (! function_exists('curl_multi_setopt')) {
            return true;
        }
        foreach ($this->options as $key => $value) {
            if (! curl_multi_setopt($this->handle, $key, $value)) {
                $this->reset();

                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (! $this->applyOptions()) {
            throw new RuntimeException("applying options did not work");
        }
        $running = null;
        do {
            $res = curl_multi_exec($this->handle, $running);
            if (CURLM_OK !== $res) {
                throw new RuntimeException("curl Envelope error  code : " . $res);
            }
            usleep($this->execTimeout);
        } while ($running > 0);

        return $this;
    }
}
