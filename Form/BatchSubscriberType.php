<?php

namespace Musicjerm\Bundle\JermBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class BatchSubscriberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_manager']){
            $builder->add('users', EntityType::class, array(
                'choice_label' => 'username',
                'class' => 'App:User',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('u')
                        ->where('u.isActive = ?0')
                        ->setParameter(0, true);
                },
                'multiple' => true,
                'expanded' => false,
                'required' => true,
                'constraints' => [new NotBlank()],
                'attr'=>['class' => 'select2', 'style' => 'width: 100%']
            ));
        }

        $builder
            ->add('id', CollectionType::class, array(
                'entry_type' => HiddenType::class,
                'label' => false
            ))
            ->add('email', CheckboxType::class, ['required' => false])
            ->add('system', CheckboxType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'data_class' => BatchSubscriberModel::class,
            'is_manager' => false
        ));
    }
}