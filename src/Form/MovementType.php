<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Movement;
use App\Entity\Equipment;
use App\Enum\MovementChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class MovementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'IN' => MovementChoice::IN->value,
                    'OUT' => MovementChoice::OUT->value,
                ],
                'label' => 'Movement Type',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'class' => 'form-input'
                ]
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Reason',
                'required' => false,
                'attr' => ['class' => 'form-textarea']
            ])
            ->add('movementDate', DateType::class, [
                'label' => 'Movement Date',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-input']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select Category',
                'attr' => [
                    'class' => 'form-select',
                    'data-category-target' => 'true'
                ]
            ])
            ->add('equipment', EntityType::class, [
                'class' => Equipment::class,
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Select Equipment',
                'attr' => [
                    'class' => 'form-select',
                    'data-equipment-target' => 'true'
                ]
            ])
            ->add('reference', TextType::class, [
                'label' => 'Reference',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Movement::class,
            'validation_groups' => ['Default']
        ]);
    }
}