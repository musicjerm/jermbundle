<?php

namespace Musicjerm\Bundle\JermBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class DtConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tooltipOptions = array('none' => -1);
        $sortOptions = array();
        foreach ($options['columns'] as $key=>$column){
            $tooltipOptions[$column] = $key;
            $sortOptions[$column] = $key;
        }

        $builder
            ->add('name', null, array('label' => 'Preset Name'))
            ->add('view', ChoiceType::class, array(
                'choices' => array_keys($options['columns']),
                'multiple' => true,
                'expanded' => true,
                'choice_label' => false
            ))
            ->add('dataDump', ChoiceType::class, array(
                'choices' => array_keys($options['columns']),
                'multiple' => true,
                'expanded' => true,
                'choice_label' => false
            ))
            ->add('tooltip', CollectionType::class, array(
                'entry_type' => ChoiceType::class,
                'entry_options' => array(
                    'choices' => $tooltipOptions,
                    'label' => false,
                    'attr' => ['class' => 'form-control']
                )
            ))
            ->add('colOrder', CollectionType::class, array(
                'entry_type' => HiddenType::class,
                'data' => array_keys($options['columns'])
            ))
            ->add('sortId', ChoiceType::class, array(
                'choices' => $sortOptions,
                'attr' => ['class' => 'form-control'],
                'label' => 'Sort By'
            ))
            ->add('sortDir', ChoiceType::class, array(
                'choices' => ['Ascending' => 'asc', 'Descending' => 'desc'],
                'attr' => ['class' => 'form-control'],
                'label' => 'Sort Direction'
            ))
            ->add('isPrimary', null, ['label' => 'Yes']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Musicjerm\Bundle\JermBundle\Entity\DtConfig',
            'columns' => null
        ));
    }
}