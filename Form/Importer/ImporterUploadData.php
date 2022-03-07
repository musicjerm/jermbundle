<?php

namespace Musicjerm\Bundle\JermBundle\Form\Importer;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

class ImporterUploadData
{
    /**
     * @var UploadedFile
     * @Assert\NotBlank()
     * @Assert\File(
     *     mimeTypes={"text/csv"},
     *     mimeTypesMessage="Please select a valid CSV file",
     *     maxSize="100M"
     * )
     */
    public $file;
}