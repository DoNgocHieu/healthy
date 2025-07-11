<?php
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/UserProfile.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$addressId = $_GET['id'] ?? 0;
$userId = $auth->getCurrentUser()['id'];

$profile = new UserProfile();
$addresses = $profile->getUserAddresses($userId);

$address = array_filter($addresses, function($addr) use ($addressId) {
    return $addr['id'] == $addressId;
});

header('Content-Type: application/json');
if (empty($address)) {
    echo json_encode([
        'success' => false,
        'message' => 'Address not found'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'address' => reset($address)
    ]);
}
