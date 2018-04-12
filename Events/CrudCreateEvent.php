<?php

namespace Musicjerm\Bundle\JermBundle\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * jermbundle.crud_create event
 */
class CrudCreateEvent extends Event
{
    const NAME = 'jermbundle.crud_create';

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