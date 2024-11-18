<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
            ])
            // ->add('description', TextType::class, [
            //     'label' => 'Description',
            //     'required' => false,
            // ])
            ->add('categoryOrder', TextType::class, [
                'label' => 'Order',
                'required' => false,
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choices' => $options['categories'],
                'choice_label' => 'name',
                'placeholder' => 'None (Root Category)',
                'label' => 'Parent Category',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'categories' => [], // Pass categories dynamically
        ]);
    }
}
