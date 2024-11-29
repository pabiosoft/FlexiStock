<?php

namespace App\Command;

use App\Repository\MaintenanceRecordRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:check-maintenance',
    description: 'Check for upcoming maintenance and send notifications'
)]
class CheckMaintenanceCommand extends Command
{
    private $maintenanceRepository;
    private $mailer;
    private $params;

    public function __construct(
        MaintenanceRecordRepository $maintenanceRepository,
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        parent::__construct();
        $this->maintenanceRepository = $maintenanceRepository;
        $this->mailer = $mailer;
        $this->params = $params;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Get maintenance due in the next 7 days
            $upcomingMaintenance = $this->maintenanceRepository->findUpcomingMaintenanceInDays(7);
            
            if (empty($upcomingMaintenance)) {
                $io->success('No upcoming maintenance needed in the next 7 days.');
                return Command::SUCCESS;
            }

            $emailContent = "The following equipment requires maintenance in the next 7 days:\n\n";
            
            foreach ($upcomingMaintenance as $maintenance) {
                $equipment = $maintenance->getEquipment();
                $emailContent .= sprintf(
                    "- %s (ID: %d)\n  Type: %s\n  Due: %s\n  Last Maintenance: %s\n\n",
                    $equipment->getName(),
                    $equipment->getId(),
                    $maintenance->getMaintenanceType(),
                    $maintenance->getNextMaintenanceDate()->format('Y-m-d'),
                    $maintenance->getMaintenanceDate()->format('Y-m-d')
                );
            }

            // Send email notification
            $email = (new Email())
                ->from($this->params->get('app.mail_from'))
                ->to($this->params->get('app.maintenance_notification_email'))
                ->subject('FlexiStock: Upcoming Equipment Maintenance Required')
                ->text($emailContent);

            $this->mailer->send($email);

            $io->success(sprintf('Found %d maintenance tasks due soon. Notification email sent.', count($upcomingMaintenance)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error sending maintenance notification: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
