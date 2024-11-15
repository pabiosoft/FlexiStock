<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Doctrine\ORM\EntityRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class EquipmentType extends AbstractType
{
    private $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom',
            ])
            ->add('brand', null, [
                'label' => 'Marque',
            ])
            ->add('model', null, [
                'label' => 'Modèle',
            ])
            ->add('serialNumber', null, [
                'label' => 'Numéro de Série',
            ])
            ->add('price', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'label' => 'Prix',
            ])
            ->add('salePrice', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'label' => 'Prix de Vente',
            ])
            ->add('purchaseDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date d\'achat',
            ])
            ->add('warrantyDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de garantie',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => EquipmentStatus::ACTIVE,
                    'Expired' => EquipmentStatus::EXPIRED,
                    'Obsolete' => EquipmentStatus::OBSOLETE,
                ],
                'expanded' => false,
                'multiple' => false,
                'label' => 'Statut',
            ])
            ->add('quantity', null, [
                'label' => 'Quantité',
            ])
            ->add('minThreshold', null, [
                'label' => 'Seuil Minimum',
            ])
            ->add('images', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false, // Non mappé pour traitement manuel
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => function (Category $category) {
                    $prefix = $category->getParent() ? $category->getParent()->getName() . ' > ' : '';
                    return $prefix . $category->getName();
                },
                'label' => 'Catégorie',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.parent', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                        
                },
                'group_by' => function (Category $category) {
                    return $category->getParent()
                        ? $category->getParent()->getName()
                        : 'Root Categories';
                },
                'attr' => [
                    'class' => 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                    'data-category-target' => true, // For JavaScript targeting if needed
                ],
            ])
            
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}



// ->add('category', EntityType::class, [
//     'class' => Category::class,
//     'choice_label' => function (Category $category) {
//         return $category->getParent()
//             ? $category->getParent()->getName() . ' > ' . $category->getName()
//             : $category->getName();
//     },
//     'label' => 'Catégorie',
//     'placeholder' => 'Sélectionnez une catégorie',
//     'query_builder' => function (EntityRepository $repository) {
//         return $repository->createQueryBuilder('c')
//             ->leftJoin('c.parent', 'parent')
//             ->addSelect('parent')
//             ->orderBy('parent.name', 'ASC') // Order parent categories first
//             ->addOrderBy('c.name', 'ASC'); // Then order child categories
//     },
//     'group_by' => function (Category $category) {
//         return $category->getParent()
//             ? $category->getParent()->getName()
//             : 'Catégories Principales'; // Group unparented categories under a default group
//     },
// ])
