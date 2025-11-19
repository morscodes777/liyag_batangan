<?php
require_once 'app/models/notificationModels.php';

class NotificationController {
    private $model;

    public function __construct($db_connection) {
        $this->model = new NotificationModel($db_connection);
    }

    public function getNotificationsApi() {
        header('Content-Type: application/json');
        
        $user_id = $_GET['user_id'] ?? null;

        if (!$user_id || !is_numeric($user_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user ID']);
            return;
        }

        $notifications = $this->model->getNotificationsByUserId($user_id);
        echo json_encode($notifications);
    }

    public function markAsReadApi() {
        header('Content-Type: application/json');
        
        $notification_id = $_GET['id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$notification_id || !is_numeric($notification_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $success = $this->model->markAsRead($notification_id);
        echo json_encode(['success' => $success]);
    }

    public function deleteNotificationApi() {
        header('Content-Type: application/json');
        
        $notification_id = $_GET['id'] ?? null;
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$notification_id || !is_numeric($notification_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $success = $this->model->deleteNotification($notification_id);
        echo json_encode(['success' => $success]);
    }

    public function createNewNotification($user_id, $title, $message, $link) {
        return $this->model->createNotification($user_id, $title, $message, $link);
    }
}