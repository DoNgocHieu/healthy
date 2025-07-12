<?php
session_start();
require_once 'config/config.php';
$pdo = getDb();

echo "=== CREATING TEST SETUP ===\n";

// Create test user if not exists
$email = 'test@test.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "Creating test user...\n";
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, fullname, role) VALUES (?, ?, ?, ?, 'customer')");
    $stmt->execute(['testuser', $email, password_hash('123456', PASSWORD_DEFAULT), 'Test User']);
    $userId = $pdo->lastInsertId();
    echo "Created user ID: $userId\n";
} else {
    $userId = $user['id'];
    echo "Using existing user ID: $userId\n";
}

// Set session
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = 'customer';

// Create test address if not exists
$stmt = $pdo->prepare("SELECT id FROM user_addresses WHERE user_id = ?");
$stmt->execute([$userId]);
$addr = $stmt->fetch();

if (!$addr) {
    echo "Creating test address...\n";
    $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, fullname, phone, address, is_default) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$userId, 'Test User', '0123456789', '123 Test Street, Test City']);
    $addressId = $pdo->lastInsertId();
    echo "Created address ID: $addressId\n";
} else {
    $addressId = $addr['id'];
    echo "Using existing address ID: $addressId\n";
}

$_SESSION['selected_address_id'] = $addressId;

// Add test item to cart if empty
$stmt = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$userId]);
$cartCount = $stmt->fetchColumn();

if ($cartCount == 0) {
    echo "Adding test item to cart...\n";
    // Get a random item
    $stmt = $pdo->query("SELECT id FROM items LIMIT 1");
    $item = $stmt->fetch();
    if ($item) {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, item_id, quantity) VALUES (?, ?, 2)");
        $stmt->execute([$userId, $item['id'], 2]);
        echo "Added item ID: " . $item['id'] . " to cart\n";
    }
}

echo "\n=== TEST SETUP COMPLETE ===\n";
echo "User ID: $userId\n";
echo "Address ID: $addressId\n";
echo "Cart items: " . ($cartCount > 0 ? $cartCount : 1) . "\n";
echo "\nNow you can test: http://localhost/healthy/layout.php?page=order_confirm\n";
?>
