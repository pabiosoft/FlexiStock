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
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class MovementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Entrée' => MovementChoice::IN->value,
                    'Sortie' => MovementChoice::OUT->value,
                ],
                'label' => 'Type de Mouvement',
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'class' => 'form-input'
                ]
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Raison',
                'required' => false,
                'attr' => ['class' => 'form-textarea']
            ])
            ->add('movementDate', DateType::class, [
                'label' => 'Date du mouvement',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-input']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => 'Sélectionner une catégorie',
                'attr' => [
                    'class' => 'form-select',
                    'data-category-target' => 'true'
                ]
            ])
            ->add('equipment', EntityType::class, [
                'class' => Equipment::class,
                'choice_label' => 'name',
                'label' => 'Équipement',
                'required' => true,
                'placeholder' => 'Sélectionner un équipement',
                'attr' => [
                    'class' => 'form-select',
                    'data-equipment-target' => 'true'
                ]
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('location', TextType::class, [
                'label' => 'Emplacement',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('batchNumber', TextType::class, [
                'label' => 'Numéro de lot',
                'required' => false,
                'attr' => ['class' => 'form-input']
            ])
            ->add('expiryDate', DateTimeType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
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