<?php

namespace P\Iris;

use RuntimeException;
use InvalidArgumentException;
use Traversable;

/**
 * A class performing simple cURL requests
 */
class Message extends CurlAbstract implements CurlInterface
{
    /**
     * cURL user agent
     * @var string
     */
    private $user_agent;

    /**
     * The constructor
     * @param string $url the url to issue the request to
     */
    public function __construct($url = null)
    {
        $res = curl_version();
        $this->user_agent = 'Iris/'.self::IRIS_VERSION.' curl/'.$res['version']. ' PHP/'.phpversion();
        if (! is_null($url)) {
            $this->options[CURLOPT_URL] = $url;
        }
    }

    /**
     * User Agent Setter
     *
     * @param string $str
     *
     * @return self
     */
    public function setUserAgent($str)
    {
        $str = filter_var($str, FILTER_SANITIZE_STRING, array('flags' => FILTER_FLAG_STRIP_LOW));
        if (! empty($str)) {
            $this->user_agent = $str;
        }

        return $this;
    }

    /**
     * User Agent Getter
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * {@inheritdoc}
     */
    public function applyOptions()
    {
        if (! $this->isAvailable()) {
            $this->init();
        }
        $this->options[CURLOPT_USERAGENT] = $this->user_agent;
        if (! curl_setopt_array($this->handle, $this->options)) {
            $this->reset();

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->handle = curl_init();

        return $this;
    }

    /**
     * reset the current curl handler
     *
     * @return self
     */
    public function reset()
    {
        $this->options = array();
        if (! $this->isAvailable()) {
            return $this;
        }
        if (function_exists('curl_reset')) {
            curl_reset($this->handle);

            return $this;
        }
        $this->close();

        return $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (! $this->applyOptions()) {
            throw new RuntimeException("applying options did not work");
        }

        $res = curl_exec($this->handle);
        $event = self::EVENT_ON_SUCCESS;
        if ($this->getErrorCode()) {
            $event = self::EVENT_ON_FAIL;
        }
        $this->dispatch($event, [$res, $this]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo($name = null)
    {
        if (! $this->isAvailable()) {
            return null;
        } elseif (is_null($name)) {
            return curl_getinfo($this->handle);
        }

        return curl_getinfo($this->handle, $name);
    }

    /**
     * Return the content if CURLOPT_RETURN_TRANSFER is true
     *
     * @return string
     */
    public function getResponse()
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return curl_multi_getcontent($this->handle);
    }

    /**
     * return cURL error code
     *
     * @return integer
     */
    public function getErrorCode()
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return curl_errno($this->handle);
    }

    /**
     * return cURL error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if (! $this->isAvailable()) {
            return null;
        }

        return curl_error($this->handle);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->isAvailable()) {
            curl_close($this->handle);
        }

        return $this;
    }

    /**
     * Perform a simple custom HTTP request (PUT, DELETE)
     *
     * @param string  $action the request to perform
     * @param string  $url    the URL to request
     * @param array   $data   the data to be send
     * @param boolean $delay  if true the call is not executed
     *
     * @return self
     */
    private function makeCustomRequest($action, $url, $data = null, $delay = false)
    {
        $this->options[CURLOPT_URL] = $url;
        $this->options[CURLOPT_RETURNTRANSFER] = true;
        $this->options[CURLOPT_CUSTOMREQUEST] = $action;
        $data = $this->formatData($data);
        if (! empty($data)) {
            $this->options[CURLOPT_POSTFIELDS] = $data;
        }
        if (! array_key_exists(CURLOPT_HTTPHEADER, $this->options)) {
            $this->options[CURLOPT_HTTPHEADER] = array();
        }
        $this->options[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: '.$action;
        if (! filter_var($delay, FILTER_VALIDATE_BOOLEAN)) {
            return $this->execute();
        }

        return $this;
    }

    /**
     * format user data to add to cURL request
     *
     * @param  mixed  $data
     * @return string
     *
     * @throws new InvalidArgumentException If the data could not be formatted
     */
    private function formatData($data)
    {
        if (is_null($data) || is_scalar($data)) {
            return $data;
        } elseif (is_array($data)) {
            return http_build_query($data);
        } elseif ($data instanceof Traversable) {
            return http_build_query(iterator_to_array($data));
        } elseif (is_object($data) && method_exists($data, '__toString')) {
            return (string) $data;
        }
        throw new InvalidArgumentException('the provided data could not be formatted');
    }

    /**
     * Perform a simple HTTP GET request
     *
     * @param string  $url   the URL to request
     * @param mixed   $data  the data to be send
     * @param boolean $delay if true the call is not executed
     *
     * @return self
     */
    public function get($url, $data = null, $delay = false)
    {
        $data = $this->formatData($data);
        if (! empty($data)) {
            $query = parse_url($url, PHP_URL_QUERY);
            $separator = '?';
            if (! empty($query)) {
                $separator = '&';
            }
            $url .= $data;
        }
        $this->options[CURLOPT_HTTPGET] = true;
        $this->options[CURLOPT_URL] = $url;
        $this->options[CURLOPT_RETURNTRANSFER] = true;
        if (! filter_var($delay, FILTER_VALIDATE_BOOLEAN)) {
            return $this->execute();
        }

        return $this;
    }

    /**
     * Perform a simple HTTP POST request
     *
     * @param string  $url   the URL to request
     * @param mixed   $data  the data to be send
     * @param boolean $delay if true the call is not executed
     *
     * @return self
     */
    public function post($url, $data = null, $delay = false)
    {
        $this->options[CURLOPT_URL] = $url;
        $this->options[CURLOPT_RETURNTRANSFER] = true;
        $this->options[CURLOPT_POST] = true;
        $data = $this->formatData($data);
        if (! empty($data)) {
            $this->options[CURLOPT_POSTFIELDS] = $data;
        }
        if (! filter_var($delay, FILTER_VALIDATE_BOOLEAN)) {
            return $this->execute();
        }

        return $this;
    }

    /**
     * Perform a simple HTTP PUT request
     *
     * @param string  $url   the URL to request
     * @param mixed   $data  the data to be send
     * @param boolean $delay if true the call is not executed
     *
     * @return self
     */
    public function put($url, $data = null, $delay = false)
    {
        return $this->makeCustomRequest('PUT', $url, $data, $delay);
    }

    /**
     * Perform a simple HTTP DELETE request
     *
     * @param string  $url   the URL to request
     * @param mixed   $data  the data to be send
     * @param boolean $delay if true the call is not executed
     *
     * @return self
     */
    public function delete($url, $data = null, $delay = false)
    {
        return $this->makeCustomRequest('DELETE', $url, $data, $delay);
    }

    /**
     * Perform a simple HTTP HEAD request
     *
     * @param string  $url   the URL to request
     * @param mixed   $data  the data to be send
     * @param boolean $delay if true the call is not executed
     *
     * @return self
     */
    public function head($url, $data = null, $delay = false)
    {
        $this->options[CURLOPT_HEADER] = true;
        $this->options[CURLOPT_NOBODY] = true;

        return $this->makeCustomRequest('HEAD', $url, $data, $delay);
    }
}
