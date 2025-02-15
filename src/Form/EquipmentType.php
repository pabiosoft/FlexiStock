<?php

namespace App\Form;

use App\Entity\User;
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
                'attr' => [
                    'class' => 'col-span-1'
                ]
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
                    'class' => 'col-span-1',
                    'data-category-target' => true, // For JavaScript targeting if needed
                ],
            ])
            ->add('brand', null, [
                'label' => 'Marque',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('model', null, [
                'label' => 'Modèle',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('serialNumber', null, [
                'label' => 'Numéro de Série',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('location', null, [
                'label' => 'Emplacement',
                'required' => false,
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('description', null, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'col-span-2',
                    'rows' => 3
                ]
            ])
            ->add('price', NumberType::class, [
                'required' => true,
                'scale' => 2,
                'label' => 'Prix',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('purchaseDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date d\'achat',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('warrantyDate', DateType::class, [
                'label' => 'Date de fin de garantie',
                'widget' => 'single_text',
                'required' => false
            ])
            // ->add('expirationDate', DateType::class, [
            //     'label' => 'Date d\'expiration',
            //     'widget' => 'single_text',
            //     'required' => false,
            //     'help' => 'Date à laquelle l\'équipement doit être retiré de l\'inventaire'
            // ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => EquipmentStatus::ACTIVE,
                    'Expired' => EquipmentStatus::EXPIRED,
                    'Obsolete' => EquipmentStatus::OBSOLETE,
                ],
                'expanded' => false,
                'multiple' => false,
                'label' => 'Statut',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('minThreshold', null, [
                'label' => 'Seuil Minimum',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            ->add('images', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false, // Non mappé pour traitement manuel
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'col-span-2'
                ]
            ])
            ->add('stockQuantity', null, [
                'label' => 'Quantité en Stock',
                'attr' => [
                    'class' => 'col-span-1'
                ]
            ])
            // add submit button
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
