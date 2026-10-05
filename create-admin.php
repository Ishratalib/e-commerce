<?php

require_once 'config/database.php';

$name = 'Administrator';
$email = 'admin@ecommerce.com';
$password = 'Admin@12345';
$role = 'admin';

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
    exit("Admin account already exists.");
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password, role, is_active)
    VALUES (?, ?, ?, ?, 1)
");

$stmt->execute([
    $name,
    $email,
    $hashedPassword,
    $role
]);

echo "Admin account created successfully.";