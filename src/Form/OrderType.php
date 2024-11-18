<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Supplier;
use App\Entity\Equipment;
use App\Entity\OrderRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Add a collection of equipments and quantities
            ->add('items', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'entry_options' => [
                    'equipment_choices' => $options['equipment_choices'], // Pass equipment choices
                ],
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'Pending',
                    'Approved' => 'Approved',
                    'Rejected' => 'Rejected',
                ],
            ])
           ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
            ])
            ->add('orderDate', DateType::class, [ 
                'widget' => 'single_text', 
                'disabled' => true, 
            ]);
                    
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderRequest::class,
            'equipment_choices' => [], // Default empty array for equipment choices
        ]);
    }
}
