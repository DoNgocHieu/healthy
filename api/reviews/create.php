<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/Auth.php';
require_once __DIR__ . '/../../config/Review.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $auth->getCurrentUser()['id'];
$review = new Review($userId);

if (!isset($_POST['order_id']) || !isset($_POST['item_id']) || !isset($_POST['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$data = [
    'order_id' => intval($_POST['order_id']),
    'item_id' => intval($_POST['item_id']),
    'rating' => intval($_POST['rating']),
    'comment' => $_POST['comment'] ?? null
];

// Handle image uploads
if (!empty($_FILES['images'])) {
    $uploadDir = __DIR__ . '/../../uploads/reviews/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $data['images'] = [];

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
            $uploadFile = $uploadDir . $filename;

            if (move_uploaded_file($tmp_name, $uploadFile)) {
                $data['images'][] = $filename;
            }
        }
    }
}

$result = $review->create($data);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'review_id' => $result['review_id'],
        'message' => $result['message']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}
