<?php

namespace App\Command;

use App\Service\ReservationAlertService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckReservationsCommand extends Command
{
    protected static $defaultName = 'app:check-reservations';
    private ReservationAlertService $reservationAlertService;

    public function __construct(ReservationAlertService $reservationAlertService)
    {
        parent::__construct();
        $this->reservationAlertService = $reservationAlertService;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-reservations')
            ->setDescription('Checks for upcoming and overdue equipment returns.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Checking upcoming equipment returns...');
            $this->reservationAlertService->checkUpcomingReturns();
            
            $io->info('Checking overdue reservations...');
            $this->reservationAlertService->checkOverdueReservations();

            $io->success('Reservation checks completed successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while checking reservations: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
