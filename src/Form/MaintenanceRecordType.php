<?php

namespace App\Form;

use App\Entity\MaintenanceRecord;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('maintenanceType', ChoiceType::class, [
                'choices' => [
                    'Preventive' => 'preventive',
                    'Corrective' => 'corrective',
                    'Inspection' => 'inspection',
                    'Calibration' => 'calibration',
                    'Other' => 'other',
                ],
                'label' => 'Type of Maintenance',
            ])
            ->add('maintenanceDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Maintenance Date',
            ])
            ->add('nextMaintenanceDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Next Maintenance Date',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description of Work',
                'attr' => ['rows' => 4],
            ])
            ->add('cost', MoneyType::class, [
                'label' => 'Cost',
                'required' => false,
                'currency' => 'EUR',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Completed' => 'completed',
                    'In Progress' => 'in_progress',
                    'Scheduled' => 'scheduled',
                    'Cancelled' => 'cancelled',
                ],
                'label' => 'Status',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MaintenanceRecord::class,
        ]);
    }
}
