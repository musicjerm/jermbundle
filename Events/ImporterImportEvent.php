<?php

namespace Musicjerm\Bundle\JermBundle\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * jermbundle.importer_import event
 */
class ImporterImportEvent extends Event
{
    public const NAME = 'jermbundle.importer_import';

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