<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('brand')
            ->add('model')
            ->add('serialNumber')
            ->add('purchaseDate')
            ->add('warrantyDate')
            ->add('status', ChoiceType::class, [
                'choices' => array_flip(EquipmentStatus::getAllStatuses()),
                'expanded' => false, // Dropdown
                'multiple' => false,
            ])
            ->add('quantity')
            ->add('minThreshold')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}