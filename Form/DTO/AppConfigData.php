<?php

namespace Musicjerm\Bundle\JermBundle\Form\DTO;

class AppConfigData
{
    /** @var bool */
    public $fileMode;

    /** @var bool */
    public $existingFileMode;

    /** @var bool */
    public $sslVerify;

    /** @var bool */
    public $existingSslVerify;

    /** @var string */
    public $remoteOriginUrl;

    /** @var string */
    public $existingRemoteOriginUrl;

    /** @var string */
    public $configuredUrl;
}