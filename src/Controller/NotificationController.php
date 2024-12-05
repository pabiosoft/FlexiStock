<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\Equipment;
use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger
    ) {}

    #[Route('/fetch', name: 'notifications_fetch', methods: ['GET'])]
    public function fetch(Request $request): JsonResponse
    {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, min($request->query->getInt('limit', 3), 10));
            $level = $request->query->get('level');
            $fromDate = $request->query->get('fromDate');
            $toDate = $request->query->get('toDate');

            $this->logger->info('Fetching notifications with filters', [
                'page' => $page,
                'limit' => $limit,
                'level' => $level,
                'fromDate' => $fromDate,
                'toDate' => $toDate
            ]);

            $alerts = $this->notificationService->getUnreadAlerts(
                $page,
                $limit,
                $level,
                $fromDate ? new \DateTime($fromDate) : null,
                $toDate ? new \DateTime($toDate) : null
            );
            
            if (!isset($alerts['items'])) {
                $alerts['items'] = [];
            }

            $notifications = array_map(function(Alert $alert) {
                return [
                    'id' => $alert->getId(),
                    'message' => $alert->getMessage(),
                    'level' => $alert->getLevel(),
                    'priority' => $alert->getPriority(),
                    'persistent' => $alert->isPersistent(),
                    'createdAt' => $alert->getCreatedAt()->format('c'),
                    'readAt' => $alert->getReadAt()?->format('c'),
                    'category' => $alert->getCategory(),
                    'equipment' => $alert->getEquipment() ? [
                        'id' => $alert->getEquipment()->getId(),
                        'name' => $alert->getEquipment()->getName(),
                        'status' => $alert->getEquipment()->getStatus(),
                        'location' => $alert->getEquipment()->getLocation()
                    ] : null,
                    'metadata' => [
                        'isNew' => !$alert->getViewedAt(),
                        'type' => $alert->getType(),
                        'source' => $alert->getSource()
                    ]
                ];
            }, $alerts['items']);

            $pagination = [
                'currentPage' => $page,
                'totalPages' => $alerts['totalPages'] ?? 0,
                'totalItems' => $alerts['totalItems'] ?? 0,
                'itemsPerPage' => $limit,
                'hasNextPage' => ($page < ($alerts['totalPages'] ?? 0)),
                'hasPreviousPage' => ($page > 1)
            ];

            return $this->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'pagination' => $pagination,
                    'summary' => [
                        'unreadCount' => $alerts['totalItems'] ?? 0,
                        'highPriorityCount' => count(array_filter($notifications, fn($n) => $n['priority'] === 'high')),
                        'categories' => array_count_values(array_column($notifications, 'category'))
                    ]
                ],
                // Keep these for backward compatibility
                'notifications' => $notifications,
                'pagination' => $pagination
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching notifications: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorResponse = [
                'success' => false,
                'error' => 'An error occurred while fetching notifications',
                'data' => [
                    'notifications' => [],
                    'pagination' => [
                        'currentPage' => $page ?? 1,
                        'totalPages' => 0,
                        'totalItems' => 0,
                        'itemsPerPage' => $limit ?? 3,
                        'hasNextPage' => false,
                        'hasPreviousPage' => false
                    ],
                    'summary' => [
                        'unreadCount' => 0,
                        'highPriorityCount' => 0,
                        'categories' => []
                    ]
                ]
            ];

            return $this->json($errorResponse, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/mark-read', name: 'notifications_mark_read', methods: ['POST'])]
    public function markAsRead(Alert $alert): JsonResponse
    {
        $this->logger->info('Marking notification {id} as read', ['id' => $alert->getId()]);
        $this->notificationService->markAsRead($alert);
        return $this->json(['success' => true]);
    }

    #[Route('/bulk-mark-read', name: 'notifications_bulk_mark_read', methods: ['POST'])]
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        try {
            $ids = json_decode($request->getContent(), true)['ids'] ?? [];
            
            if (empty($ids) || !is_array($ids)) {
                throw new BadRequestHttpException('Invalid or empty notification IDs provided');
            }

            $this->logger->info('Marking multiple notifications as read', ['ids' => $ids]);
            $markedCount = $this->notificationService->bulkMarkAsRead($ids);

            return $this->json([
                'success' => true,
                'data' => [
                    'markedCount' => $markedCount
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error marking notifications as read: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => $e instanceof BadRequestHttpException ? $e->getMessage() : 'An error occurred'
            ], $e instanceof BadRequestHttpException ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/clear-all', name: 'notifications_clear_all', methods: ['POST'])]
    public function clearAll(): JsonResponse
    {
        $this->logger->info('Clearing all non-persistent notifications');
        $this->notificationService->clearAllNonPersistent();
        return $this->json(['success' => true]);
    }

    #[Route('/count', name: 'notifications_count', methods: ['GET'])]
    public function getCount(Request $request): JsonResponse
    {
        try {
            $level = $request->query->get('level');
            $count = $this->notificationService->getUnreadCount($level);

            return $this->json([
                'success' => true,
                'data' => [
                    'count' => $count,
                    'level' => $level
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting notification count: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'An error occurred while getting notification count'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/priority/{priority}', name: 'notifications_by_priority', methods: ['GET'])]
    public function getByPriority(string $priority): JsonResponse
    {
        $this->logger->info('Fetching notifications by priority {priority}', ['priority' => $priority]);
        $alerts = $this->notificationService->getAlertsByPriority($priority);
        
        $notifications = array_map(function(Alert $alert) {
            return [
                'id' => $alert->getId(),
                'message' => $alert->getMessage(),
                'level' => $alert->getLevel(),
                'priority' => $alert->getPriority(),
                'persistent' => $alert->isPersistent(),
                'createdAt' => $alert->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $alerts);

        $this->logger->info('Found {count} notifications for priority {priority}', [
            'count' => count($notifications),
            'priority' => $priority
        ]);

        return $this->json([
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
    }

    #[Route('/equipment/{id}', name: 'notifications_equipment', methods: ['GET'])]
    public function getEquipmentNotifications(
        Equipment $equipment,
        Request $request
    ): JsonResponse {
        try {
            $page = max(1, $request->query->getInt('page', 1));
            $limit = max(1, min($request->query->getInt('limit', 10), 100));
            $category = $request->query->get('category');
            $level = $request->query->get('level');
            $fromDate = $request->query->get('fromDate');
            $toDate = $request->query->get('toDate');

            $this->logger->info('Fetching equipment notifications', [
                'equipmentId' => $equipment->getId(),
                'category' => $category,
                'level' => $level
            ]);

            $alerts = $this->notificationService->getEquipmentAlerts(
                $equipment,
                $category,
                $page,
                $limit,
                $level,
                $fromDate ? new \DateTime($fromDate) : null,
                $toDate ? new \DateTime($toDate) : null
            );

            $notifications = array_map(function(Alert $alert) {
                return [
                    'id' => $alert->getId(),
                    'message' => $alert->getMessage(),
                    'level' => $alert->getLevel(),
                    'priority' => $alert->getPriority(),
                    'category' => $alert->getCategory(),
                    'equipment' => [
                        'id' => $alert->getEquipment()->getId(),
                        'name' => $alert->getEquipment()->getName(),
                        'serialNumber' => $alert->getEquipment()->getSerialNumber()
                    ],
                    'persistent' => $alert->isPersistent(),
                    'createdAt' => $alert->getCreatedAt()->format('c')
                ];
            }, $alerts['items']);

            return $this->json([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'pagination' => [
                        'currentPage' => $page,
                        'totalPages' => $alerts['totalPages'],
                        'totalItems' => $alerts['totalItems'],
                        'itemsPerPage' => $limit
                    ]
                ],
                'notifications' => $notifications,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $alerts['totalPages'],
                    'totalItems' => $alerts['totalItems'],
                    'itemsPerPage' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching equipment notifications: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'An error occurred while fetching equipment notifications'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/equipment/{id}/count', name: 'notifications_equipment_count', methods: ['GET'])]
    public function getEquipmentNotificationCount(
        Equipment $equipment,
        Request $request
    ): JsonResponse {
        try {
            $category = $request->query->get('category');
            $level = $request->query->get('level');

            $count = $this->notificationService->getEquipmentAlertsCount(
                $equipment,
                $category,
                $level
            );

            return $this->json([
                'success' => true,
                'data' => [
                    'count' => $count,
                    'equipment' => [
                        'id' => $equipment->getId(),
                        'name' => $equipment->getName()
                    ],
                    'category' => $category,
                    'level' => $level
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting equipment notification count: ' . $e->getMessage());
            return $this->json([
                'success' => false,
                'error' => 'An error occurred while getting equipment notification count'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
