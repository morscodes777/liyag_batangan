<?php
require_once __DIR__ . '/../models/accountModels.php';

class AccountController {
    private $accountModel;

    public function __construct() {
        $this->accountModel = new AccountModel();
    }

    public function profile() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $userId = $_SESSION['user']['user_id'];
        $userData = $this->accountModel->getUserById($userId);

        include __DIR__ . '/../views/account_management.php';
    }

    public function updateProfile() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Invalid request method"]);
            return;
        }

        if (!isset($_SESSION['user'])) {
            echo json_encode(["success" => false, "message" => "Unauthorized"]);
            return;
        }

        $userId = $_SESSION['user']['user_id'];
        $name = trim(strip_tags($_POST['name'] ?? ''));
        $phone = trim($_POST['phone_number'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $profilePicturePath = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = realpath(__DIR__ . '/../../uploads/') . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'profile_' . uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $targetFile = $uploadDir . $filename;
            $relativePath = 'uploads/' . $filename;

            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($imageFileType, $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                    $profilePicturePath = $relativePath;
                }
            }
        }

        $result = $this->accountModel->updateUserInfo($userId, $name, $phone, $address, $profilePicturePath);

        if ($result === true) {
            $_SESSION['user'] = $this->accountModel->getUserById($userId);
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => $result]);
        }
    }
}