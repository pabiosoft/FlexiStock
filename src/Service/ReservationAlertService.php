<?php

namespace App\Service;

use App\Entity\Alert;
use App\Entity\Reservation;
use App\Enum\AlertLevel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReservationAlertService
{
    private $entityManager;
    private $mailer;
    private $params;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        ParameterBagInterface $params
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->params = $params;
    }

    public function checkUpcomingReturns(): void
    {
        $repository = $this->entityManager->getRepository(Reservation::class);
        $today = new \DateTime();
        $tomorrow = (new \DateTime())->modify('+1 day');

        $upcomingReturns = $repository->createQueryBuilder('r')
            ->where('r.returnDate BETWEEN :today AND :tomorrow')
            ->andWhere('r.status = :status')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        foreach ($upcomingReturns as $reservation) {
            $this->createUpcomingReturnAlert($reservation);
        }
    }

    public function checkOverdueReservations(): void
    {
        $repository = $this->entityManager->getRepository(Reservation::class);
        $today = new \DateTime();

        $overdueReservations = $repository->createQueryBuilder('r')
            ->where('r.returnDate < :today')
            ->andWhere('r.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        foreach ($overdueReservations as $reservation) {
            $this->createOverdueAlert($reservation);
        }
    }

    private function createUpcomingReturnAlert(Reservation $reservation): void
    {
        $alert = new Alert();
        $alert->setLevel(AlertLevel::INFO);
        $alert->setMessage(sprintf(
            'Upcoming equipment return: %s (Reserved Quantity: %d) due on %s',
            $reservation->getEquipment()->getName(),
            $reservation->getReservedQuantity(),
            $reservation->getReturnDate()->format('Y-m-d')
        ));
        $alert->setCreatedAt(new \DateTime());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        $this->sendUpcomingReturnEmail($reservation);
    }

    private function createOverdueAlert(Reservation $reservation): void
    {
        $alert = new Alert();
        $alert->setLevel(AlertLevel::WARNING);
        $alert->setMessage(sprintf(
            'Overdue equipment return: %s (Reserved Quantity: %d) was due on %s',
            $reservation->getEquipment()->getName(),
            $reservation->getReservedQuantity(),
            $reservation->getReturnDate()->format('Y-m-d')
        ));
        $alert->setCreatedAt(new \DateTime());

        $this->entityManager->persist($alert);
        $this->entityManager->flush();

        $this->sendOverdueEmail($reservation);
    }

    private function sendUpcomingReturnEmail(Reservation $reservation): void
    {
        $email = (new Email())
            ->from($this->params->get('app.mail_from'))
            ->to($this->params->get('app.maintenance_notification_email'))
            ->subject('Equipment Return Reminder')
            ->html(sprintf(
                '<p>Reminder: Equipment return due tomorrow</p>
                <ul>
                    <li>Equipment: %s</li>
                    <li>Reserved Quantity: %d</li>
                    <li>Return Date: %s</li>
                    <li>Serial Number: %s</li>
                </ul>
                <p>Please ensure the equipment is returned on time.</p>',
                $reservation->getEquipment()->getName(),
                $reservation->getReservedQuantity(),
                $reservation->getReturnDate()->format('Y-m-d'),
                $reservation->getEquipment()->getSerialNumber()
            ));

        $this->mailer->send($email);
    }

    private function sendOverdueEmail(Reservation $reservation): void
    {
        $email = (new Email())
            ->from($this->params->get('app.mail_from'))
            ->to($this->params->get('app.maintenance_notification_email'))
            ->subject('OVERDUE Equipment Return')
            ->html(sprintf(
                '<p style="color: red;">URGENT: Equipment return is overdue</p>
                <ul>
                    <li>Equipment: %s</li>
                    <li>Reserved Quantity: %d</li>
                    <li>Due Date: %s</li>
                    <li>Serial Number: %s</li>
                    <li>Days Overdue: %d</li>
                </ul>
                <p>Please follow up on this overdue return immediately.</p>',
                $reservation->getEquipment()->getName(),
                $reservation->getReservedQuantity(),
                $reservation->getReturnDate()->format('Y-m-d'),
                $reservation->getEquipment()->getSerialNumber(),
                $reservation->getReturnDate()->diff(new \DateTime())->days
            ));

        $this->mailer->send($email);
    }
}
