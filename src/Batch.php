<?php

namespace Iris;

use RuntimeException;

/**
 * A class to perform parallel requests using {@link Message} an {@link Envelope} objects
 */
class Batch
{
    /**
     * Envelope curl handler
     *
     * @var \Iris\Envelope
     */
    private $handle;

    /**
     * contain all the \Iris\Message objects
     *
     * @var \Iris\MessageQueue
     */
    private $container;

    /**
     * contain the currently use \Iris\Message object
     *
     * @var array
     */
    private $queue = array();

    /**
     * The Constructor
     *
     * @param \Iris\Envelope $handle The main handler
     */
    public function __construct(Envelope $handle)
    {
        $this->handle = $handle;
        $this->container = new MessageQueue;
    }

    /**
     * Add a Collection of {@link Message} objects
     *
     * @param \Iris\MessageQueue $pool
     *
     * @return self
     */
    public function addPool(MessageQueue $pool)
    {
        foreach ($pool as $data) {
            $this->add($data);
        }

        return $this;
    }

    /**
     * Add a {@link Message} object
     *
     * @param \Iris\Message $data
     *
     * @return self
     */
    public function add(Message $data)
    {
        $this->container->enqueue($data);

        return $this;
    }

    /**
     * Queue and Execute all the requests
     *
     * @return self
     */
    public function execute()
    {
        $index = 0;
        $maxConnection = $this->handle->getOption(Envelope::MAXCONNECTS);
        $this->container->rewind();
        while ($this->container->valid() && $index < $maxConnection) {
            $this->queue[] = $this->container->dequeue();
            $this->container->next();
            $index++;
        }

        $this->fetch();
        if (count($this->container)) {
            $this->execute();
        }

        return $this;
    }

    /**
     * performs the request in the queue
     */
    private function fetch()
    {
        foreach ($this->queue as $curl) {
            $this->handle->add($curl);
        }

        $this->process();
    }

    /**
     * find the cURL handler index in the queue
     *
     * @return integer
     *
     * @throws RuntimeException If no resource is found
     */
    private function find($resource)
    {
        foreach ($this->queue as $key => $curl) {
            if ($resource === $curl->getHandler()) {
                return $key;
            }
        }

        throw new RuntimeException('The Handler was not found');
    }

    /**
     * Process the result of the Envelope cURL request
     */
    private function process()
    {
        $this->handle->execute();
        while ($result = $this->handle->getInfo()) {
            $key = $this->find($result['handle']);
            $curl = $this->queue[$key];
            unset($this->queue[$key]);
            $this->handle->remove($curl);

            $event = CurlInterface::EVENT_ON_ERROR;
            if ($result['result'] === CURLE_OK) {
                $event = CurlInterface::EVENT_ON_SUCCESS;
            }
            $curl->dispatch($event, [$result, $curl]);
            $this->handle->select();
        }
    }
}