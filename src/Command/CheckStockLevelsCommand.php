<?php

namespace App\Command;

use App\Service\StockAlertService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckStockLevelsCommand extends Command
{
    protected static $defaultName = 'app:check-stock-levels';
    private StockAlertService $stockAlertService;

    public function __construct(StockAlertService $stockAlertService)
    {
        parent::__construct();
        $this->stockAlertService = $stockAlertService;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:check-stock-levels') // Explicitly set the name
            ->setDescription('Checks stock levels and creates alerts for low stock items.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->stockAlertService->checkLowStockLevels();
            $this->stockAlertService->checkExpiringItems();

            $io->success('Stock levels checked successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred while checking stock levels: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}