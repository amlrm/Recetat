<?php
/**
 * Profile API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

$action = $_GET['action'] ?? null;
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($action)) $action = $input['action'] ?? null;
}

switch ($action) {
    case 'get':
        $user = $db->fetchOne(
            'SELECT id, username, email, first_name, last_name, age, gender, height_cm, weight_kg, profile_image, created_at FROM users WHERE id = ?',
            [$userId]
        );

        $goal = $db->fetchOne(
            'SELECT * FROM user_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
            [$userId]
        );

        echo json_encode(['success' => true, 'user' => $user, 'goal' => $goal]);
        break;

    case 'update':
        $allowed = ['first_name', 'last_name', 'age', 'gender', 'height_cm', 'weight_kg'];
        $data = [];
        foreach ($allowed as $field) {
            if (isset($input[$field]) && $input[$field] !== '') {
                $data[$field] = $input[$field];
            }
        }

        if (empty($data)) {
            echo json_encode(['success' => false, 'message' => 'No data to update']);
            break;
        }

        $db->update('users', $data, ['id' => $userId]);
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        break;

    case 'setGoal':
        $goalData = [
            'user_id' => $userId,
            'goal_type' => $input['goal_type'] ?? 'maintenance',
            'target_weight_kg' => $input['target_weight'] ?? null,
            'daily_calorie_target' => $input['daily_calories'] ?? 2000,
            'dietary_restrictions' => json_encode($input['restrictions'] ?? []),
            'allergies' => json_encode($input['allergies'] ?? []),
            'start_date' => date('Y-m-d'),
            'target_date' => $input['target_date'] ?? null
        ];

        $db->insert('user_goals', $goalData);
        echo json_encode(['success' => true, 'message' => 'Goal saved successfully']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
