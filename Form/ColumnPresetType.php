<?php

namespace Musicjerm\Bundle\JermBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ColumnPresetType extends AbstractType
{
    private $user;
    private $entity;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];
        $this->entity = $options['entity'];

        $builder
            ->add('selectLayout', EntityType::class, array(
                'class' => 'Musicjerm\Bundle\JermBundle\Entity\DtConfig',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('dt')
                        ->where('dt.user = ?0')
                        ->andWhere('dt.entity = ?1')
                        ->setParameters(array(
                            $this->user,
                            $this->entity
                        ));
                },
                'expanded' => true
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'user' => null,
            'entity' => null
        ));
    }
}