<?php

namespace Musicjerm\Bundle\JermBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * jermbundle.crud_update event
 */
class CrudUpdateEvent extends Event
{
    public const NAME = 'jermbundle.crud_update';

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