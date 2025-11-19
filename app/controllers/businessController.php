<?php
class BusinessController {
    public function createBusinessForm() {

        if (!isset($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }
        $user = $_SESSION['user'];
        $displayUserName = $user['name'] ?? 'User';
        $userProfilePicture = $user['profile_picture'] ?? null;

        include __DIR__ . '/../views/create_business.php';
    }
}
?>