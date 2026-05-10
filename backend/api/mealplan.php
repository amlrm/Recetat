<?php
/**
 * Meal Plan API Endpoint
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
    case 'create':
        $planName = $input['plan_name'] ?? 'My Meal Plan';
        $startDate = $input['start_date'] ?? date('Y-m-d');
        $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
        $dailyCalories = $input['daily_calories'] ?? 2000;
        $mealsPerDay = $input['meals_per_day'] ?? 3;

        $planId = $db->insert('meal_plans', [
            'user_id' => $userId,
            'plan_name' => $planName,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'daily_calories' => (int)$dailyCalories,
            'meals_per_day' => (int)$mealsPerDay
        ]);

        // If meals are provided, insert them
        if (!empty($input['meals'])) {
            foreach ($input['meals'] as $meal) {
                $db->insert('meal_plan_details', [
                    'meal_plan_id' => $planId,
                    'date' => $meal['date'] ?? $startDate,
                    'meal_type' => $meal['meal_type'] ?? 'lunch',
                    'spoonacular_recipe_id' => $meal['recipe_id'] ?? null,
                    'recipe_title' => $meal['title'] ?? '',
                    'calories' => $meal['calories'] ?? 0,
                    'protein_g' => $meal['protein'] ?? 0,
                    'carbs_g' => $meal['carbs'] ?? 0,
                    'fat_g' => $meal['fat'] ?? 0
                ]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Meal plan created', 'plan_id' => $planId]);
        break;

    case 'addMeal':
        $planId = $input['plan_id'] ?? null;
        if (!$planId) { echo json_encode(['success' => false, 'message' => 'Plan ID required']); break; }

        $db->insert('meal_plan_details', [
            'meal_plan_id' => (int)$planId,
            'date' => $input['date'] ?? date('Y-m-d'),
            'meal_type' => $input['meal_type'] ?? 'lunch',
            'spoonacular_recipe_id' => $input['recipe_id'] ?? null,
            'recipe_title' => $input['title'] ?? '',
            'calories' => (int)($input['calories'] ?? 0),
            'protein_g' => (float)($input['protein'] ?? 0),
            'carbs_g' => (float)($input['carbs'] ?? 0),
            'fat_g' => (float)($input['fat'] ?? 0)
        ]);

        echo json_encode(['success' => true, 'message' => 'Meal added to plan']);
        break;

    case 'removeMeal':
        $mealId = $input['meal_id'] ?? null;
        if (!$mealId) { echo json_encode(['success' => false, 'message' => 'Meal ID required']); break; }
        $db->delete('meal_plan_details', ['id' => (int)$mealId]);
        echo json_encode(['success' => true, 'message' => 'Meal removed']);
        break;

    case 'list':
        $plans = $db->fetchAll(
            'SELECT * FROM meal_plans WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
        echo json_encode(['success' => true, 'plans' => $plans]);
        break;

    case 'get':
        $planId = $_GET['id'] ?? $input['id'] ?? null;
        if (!$planId) { echo json_encode(['success' => false, 'message' => 'Plan ID required']); break; }

        $plan = $db->fetchOne(
            'SELECT * FROM meal_plans WHERE id = ? AND user_id = ?',
            [(int)$planId, $userId]
        );

        if (!$plan) { echo json_encode(['success' => false, 'message' => 'Plan not found']); break; }

        $meals = $db->fetchAll(
            'SELECT * FROM meal_plan_details WHERE meal_plan_id = ? ORDER BY date, FIELD(meal_type, "breakfast", "lunch", "dinner", "snack")',
            [(int)$planId]
        );

        $plan['meals'] = $meals;
        echo json_encode(['success' => true, 'plan' => $plan]);
        break;

    case 'delete':
        $planId = $input['plan_id'] ?? null;
        if (!$planId) { echo json_encode(['success' => false, 'message' => 'Plan ID required']); break; }
        $db->delete('meal_plans', ['id' => (int)$planId, 'user_id' => $userId]);
        echo json_encode(['success' => true, 'message' => 'Plan deleted']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
