<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', options: ['label' => 
            'Nom', 'attr' => ['placeholder' => 'Nom de la catégorie', 'class' => 'form-control ']])
            ->add('description', options: ['label' => 
            'Description', 'attr' => ['placeholder' => 'Description de la catégorie']])
            ->add('categoryOrder', options: ['label' => 'Ordre', 'attr' => ['placeholder' => 'Ordre de la catégorie']])
            // ->add('parentCategory', options: ['label' => 'Catégorie parente', 'class' => Category::class])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
