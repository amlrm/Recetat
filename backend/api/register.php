<?php
/**
 * Registration API Endpoint
 * Handles user registration requests
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth/Auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST requests allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
    echo json_encode(['error' => 'Username, email, and password are required']);
    exit;
}

// Register user
$auth = new Auth();
$result = $auth->register(
    $input['username'],
    $input['email'],
    $input['password'],
    $input['firstName'] ?? '',
    $input['lastName'] ?? ''
);

echo json_encode($result);
?>
