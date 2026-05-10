<?php
/**
 * Progress API Endpoint
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
    case 'log':
        $weight = $input['weight'] ?? null;
        $calories = $input['calories'] ?? null;
        $workout = $input['workout'] ?? null;
        $notes = $input['notes'] ?? '';
        $logDate = $input['date'] ?? date('Y-m-d');

        // Check if log exists for today, update if so
        $existing = $db->fetchOne(
            'SELECT id FROM progress_logs WHERE user_id = ? AND log_date = ?',
            [$userId, $logDate]
        );

        if ($existing) {
            $data = ['notes' => $notes];
            if ($weight !== null) $data['weight_kg'] = (float)$weight;
            if ($calories !== null) $data['calories_consumed'] = (int)$calories;
            if ($workout !== null) $data['workout_minutes'] = (int)$workout;
            $db->update('progress_logs', $data, ['id' => $existing['id']]);
        } else {
            $data = ['user_id' => $userId, 'log_date' => $logDate, 'notes' => $notes];
            if ($weight !== null) $data['weight_kg'] = (float)$weight;
            if ($calories !== null) $data['calories_consumed'] = (int)$calories;
            if ($workout !== null) $data['workout_minutes'] = (int)$workout;
            $db->insert('progress_logs', $data);
        }

        echo json_encode(['success' => true, 'message' => 'Progress logged successfully']);
        break;

    case 'history':
        $limit = (int)($_GET['limit'] ?? 30);
        $logs = $db->fetchAll(
            'SELECT * FROM progress_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT ?',
            [$userId, $limit]
        );
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;

    case 'today':
        $today = $db->fetchOne(
            'SELECT * FROM progress_logs WHERE user_id = ? AND log_date = ?',
            [$userId, date('Y-m-d')]
        );
        echo json_encode(['success' => true, 'log' => $today]);
        break;

    case 'stats':
        // Get streak
        $logs = $db->fetchAll(
            'SELECT log_date FROM progress_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 60',
            [$userId]
        );

        $streak = 0;
        $checkDate = date('Y-m-d');
        foreach ($logs as $log) {
            if ($log['log_date'] === $checkDate) {
                $streak++;
                $checkDate = date('Y-m-d', strtotime($checkDate . ' -1 day'));
            } else {
                break;
            }
        }

        // Today's calories
        $today = $db->fetchOne(
            'SELECT calories_consumed FROM progress_logs WHERE user_id = ? AND log_date = ?',
            [$userId, date('Y-m-d')]
        );

        echo json_encode([
            'success' => true,
            'streak' => $streak,
            'todayCalories' => $today['calories_consumed'] ?? 0,
            'totalLogs' => count($logs)
        ]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
