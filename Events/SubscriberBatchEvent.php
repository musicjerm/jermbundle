<?php

namespace Musicjerm\Bundle\JermBundle\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * jermbundle.subscriber_batch event
 */
class SubscriberBatchEvent extends Event
{
    const NAME = 'jermbundle.subscriber_batch';

    protected $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public function getObject()
    {
        return $this->object;
    }
}