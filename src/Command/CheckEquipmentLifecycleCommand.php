<?php

namespace App\Command;

use App\Service\EquipmentLifecycleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-equipment-lifecycle',
    description: 'Check equipment for expiration dates, maintenance schedules and generate notifications',
)]
class CheckEquipmentLifecycleCommand extends Command
{
    public function __construct(
        private readonly EquipmentLifecycleService $equipmentLifecycleService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->section('Starting Equipment Lifecycle Checks');

            // Check expiration and warranty
            $io->info('Checking equipment expiration and warranty status...');
            $this->equipmentLifecycleService->checkEquipmentLifecycle();
            $io->success('Equipment expiration and warranty checks completed.');

            // Check maintenance schedules
            $io->info('Checking equipment maintenance schedules...');
            $this->equipmentLifecycleService->checkMaintenanceSchedule();
            $io->success('Equipment maintenance checks completed.');

            $io->success([
                'All equipment lifecycle checks completed successfully.',
                'Run "php bin/console app:check-equipment-lifecycle" to check again.'
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'An error occurred while checking equipment lifecycle',
                $e->getMessage(),
                'Stack trace:',
                $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
