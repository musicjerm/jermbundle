<?php

namespace Musicjerm\Bundle\JermBundle\Form\Importer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImporterUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', null, ['label' => 'Select CSV file']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(ImporterUploadData::class, null);
    }
}