<?php

namespace App\Command;

use App\Entity\MaintenanceRecord;
use App\Repository\EquipmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-maintenance',
    description: 'Creates a test maintenance record for email testing'
)]
class CreateTestMaintenanceCommand extends Command
{
    private $entityManager;
    private $equipmentRepository;
    private $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EquipmentRepository $equipmentRepository,
        UserRepository $userRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->equipmentRepository = $equipmentRepository;
        $this->userRepository = $userRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get the first equipment
        $equipment = $this->equipmentRepository->findOneBy([]);
        if (!$equipment) {
            $io->error('No equipment found in the database. Please create an equipment first.');
            return Command::FAILURE;
        }

        // Get the first user
        $user = $this->userRepository->findOneBy([]);
        if (!$user) {
            $io->error('No user found in the database. Please create a user first.');
            return Command::FAILURE;
        }

        try {
            $maintenanceRecord = new MaintenanceRecord();
            $maintenanceRecord->setEquipment($equipment);
            $maintenanceRecord->setPerformedBy($user);
            $maintenanceRecord->setMaintenanceType('Preventive');
            $maintenanceRecord->setDescription('Test maintenance record for email notification testing');
            $maintenanceRecord->setStatus('pending');
            $maintenanceRecord->setCost(100.00);
            
            // Set maintenance dates
            $maintenanceDate = new \DateTime();
            $nextMaintenanceDate = new \DateTime('+3 days'); // Due in 3 days
            
            $maintenanceRecord->setMaintenanceDate($maintenanceDate);
            $maintenanceRecord->setNextMaintenanceDate($nextMaintenanceDate);

            $this->entityManager->persist($maintenanceRecord);
            $this->entityManager->flush();

            $io->success(sprintf(
                'Created test maintenance record for equipment "%s" (ID: %d) due on %s',
                $equipment->getName(),
                $equipment->getId(),
                $nextMaintenanceDate->format('Y-m-d')
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating test maintenance record: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
