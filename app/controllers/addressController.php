<?php
// app/controllers/AddressController.php

class AddressController {
    private $addressModel;

    public function __construct($addressModel) {
        $this->addressModel = $addressModel;
    }

    public function handleNewAddressFromCheckout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['status' => 'error', 'message' => 'Invalid request method.'];
        }

        // 1. Basic input validation
        if (empty($_SESSION['user_id'])) {
            return ['status' => 'error', 'message' => 'User not logged in.'];
        }

        $fullAddress = trim($_POST['new_full_address'] ?? '');
        if (empty($fullAddress)) {
            return ['status' => 'error', 'message' => 'Full address is required.'];
        }

        $userId = $_SESSION['user_id'];
        $label = trim($_POST['new_address_label'] ?? 'New Address');
        $latitude = $_POST['new_latitude'] ?? null;
        $longitude = $_POST['new_longitude'] ?? null;
        
        // 2. Save the address
        $newAddressId = $this->addressModel->saveAddress(
            $userId, 
            $label, 
            $fullAddress, 
            $latitude, 
            $longitude
        );

        if ($newAddressId) {
            // Optional: If this is the user's first address, set it as default
            if (count($this->addressModel->getUserAddresses($userId)) === 1) {
                $this->addressModel->setDefaultAddress($newAddressId, $userId);
            }

            return [
                'status' => 'success', 
                'message' => 'Address successfully saved.',
                'address_id' => $newAddressId,
                'label' => $label,
                'full_address' => $fullAddress
            ];
        } else {
            return ['status' => 'error', 'message' => 'Failed to save address to database.'];
        }
    }
}