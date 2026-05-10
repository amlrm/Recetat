<?php
/**
 * Logout API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth/Auth.php';

$auth = new Auth();
$result = $auth->logout();

echo json_encode($result);
?>
