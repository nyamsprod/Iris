<?php

namespace Iris;

use SplQueue;
use InvalidArgumentException;

/**
 * A class to queue {@link Message} objects
 */
class MessageQueue extends SplQueue
{

    /**
     * Attach a cURL handler and its event
     *
     * @param \Iris\Message $obj
     *
     * @return self
     *
     * @throws \InvalidArgumentException If $obj is not an instance of \Iris\Message
     */
    public function enqueue($obj)
    {
        if (! $obj instanceof Message) {
            throw new InvalidArgumentException('your object should be of type \Iris\Message');
        }
        parent::enqueue($obj);

        return $this;
    }
}
