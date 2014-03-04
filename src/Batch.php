<?php

namespace P\Iris;

use RuntimeException;
use Traversable;
use InvalidArgumentException;
use Countable;

/**
 * A class to perform parallel requests using {@link Message} an {@link Envelope} objects
 */
class Batch implements Countable
{
    /**
     * Envelope curl handler
     *
     * @var \P\Iris\Envelope
     */
    private $handle;

    /**
     * contain all the {@link Message} objects
     *
     * @var \P\Iris\MessageQueue
     */
    private $container;

    /**
     * contain the currently use {@link Message} object
     *
     * @var array
     */
    private $queue = array();

    /**
     * The Constructor
     *
     * @param \P\Iris\Envelope $handle The main handler
     */
    public function __construct(Envelope $handle)
    {
        $this->handle = $handle;
        $this->container = new MessageQueue;
    }

    /**
     * returns the numbers of {@link Message} objects present
     *
     * @return integer
     */
    public function count()
    {
        return count($this->container);
    }

    /**
     * Add a Collection of {@link Message} objects
     *
     * @param \Traversable $pool
     *
     * @return self
     */
    public function addMany($pool)
    {
        if (! is_array($pool) && ! $pool instanceof Traversable) {
            throw new InvalidArgumentException(
                'the `\P\Iris\Message` object must be provided using a Traversable object or an array'
            );
        }
        foreach ($pool as $data) {
            $this->addOne($data);
        }

        return $this;
    }

    /**
     * Add a {@link Message} object
     *
     * @param \P\Iris\Message $data
     *
     * @return self
     */
    public function addOne(Message $data)
    {
        $this->container->enqueue($data);

        return $this;
    }

    /**
     * queue and execute the {@link Message} object
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
     * find the {@link Message} index in the queue
     *
     * @return integer
     *
     * @throws \RuntimeException If no resource is found
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
     * Process the result of {@link Message} object
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
