<?php


namespace App\Form;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Enum\EquipmentStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\ORM\EntityRepository;

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('brand')
            ->add('model')
            ->add('serialNumber')
            ->add('price')
            ->add('salePrice')
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
                'choices' => array_flip([
                    'Active' => EquipmentStatus::ACTIVE,
                    'Expired' => EquipmentStatus::EXPIRED,
                    'Obsolete' => EquipmentStatus::OBSOLETE,
                ]),
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('quantity')
            ->add('minThreshold')
            ->add('images', FileType::class, [
                'label' => 'Images',
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*',
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'group_by' => 'name',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
