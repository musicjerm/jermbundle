<?php

namespace Musicjerm\Bundle\JermBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * jermbundle.crud_delete event
 */
class CrudDeleteEvent extends Event
{
    public const NAME = 'jermbundle.crud_delete';

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