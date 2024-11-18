<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Entity\OrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('equipment', EntityType::class, [
            'class' => Equipment::class,
            'choices' => $options['equipment_choices'], // Access passed equipment choices
            'choice_label' => 'name', // Adjust to your Equipment entity's property
            'placeholder' => 'Select Equipment',
        ])
            ->add('quantity')
            ->add('unitPrice')
            // Add other fields as necessary
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
{
    $resolver->setDefaults([
        'data_class' => OrderItem::class,
        'equipment_choices' => [], // Default empty array for equipment choices
    ]);
}
}
