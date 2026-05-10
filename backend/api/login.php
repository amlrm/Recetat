<?php
/**
 * Login API Endpoint
 * Handles user login requests
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
if (empty($input['email']) || empty($input['password'])) {
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

// Login user
$auth = new Auth();
$result = $auth->login($input['email'], $input['password']);

echo json_encode($result);
?>
