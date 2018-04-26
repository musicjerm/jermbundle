<?php

namespace Musicjerm\Bundle\JermBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateSubscriberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', null, ['label' => 'Description (notes)'])
            ->add('email', null, ['label' => 'E-mail notification'])
            ->add('system', null, ['label' => 'System notification']);
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Exception
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(array(
                'data_class' => \Musicjerm\Bundle\JermBundle\Entity\Subscriber::class
            ));
    }
}