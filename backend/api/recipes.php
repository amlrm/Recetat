<?php
/**
 * Recipe API Handler
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class RecipeAPI {
    private $db;
    private $apiKey;

    public function __construct() {
        $this->db = new Database();
        $this->apiKey = SPOONACULAR_API_KEY;
    }

    public function searchRecipes($query, $filters = []) {
        // If no API key, return built-in sample recipes
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY') {
            return $this->getSampleRecipes($query, $filters);
        }

        $url = 'https://api.spoonacular.com/recipes/complexSearch?';
        $params = [
            'query' => $query,
            'apiKey' => $this->apiKey,
            'number' => $filters['number'] ?? 12,
            'addRecipeInformation' => 'true',
            'addRecipeNutrition' => 'true'
        ];

        if (!empty($filters['diet'])) $params['diet'] = $filters['diet'];
        if (!empty($filters['minCalories'])) $params['minCalories'] = $filters['minCalories'];
        if (!empty($filters['maxCalories'])) $params['maxCalories'] = $filters['maxCalories'];
        if (!empty($filters['cuisines'])) $params['cuisine'] = $filters['cuisines'];
        if (!empty($filters['type'])) $params['type'] = $filters['type'];

        $url .= http_build_query($params);

        try {
            $response = @file_get_contents($url);
            if ($response === false) return ['error' => 'Failed to fetch recipes'];
            return json_decode($response, true);
        } catch (Exception $e) {
            return ['error' => 'API error'];
        }
    }

    public function getRecipeDetails($recipeId) {
        // Check if it's a sample recipe
        if ($recipeId >= 90000) {
            return $this->getSampleRecipeDetail($recipeId);
        }

        if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY') {
            return $this->getSampleRecipeDetail($recipeId);
        }

        $url = "https://api.spoonacular.com/recipes/$recipeId/information?apiKey={$this->apiKey}&includeNutrition=true";
        try {
            $response = @file_get_contents($url);
            if ($response === false) return ['error' => 'Failed to fetch recipe'];
            return json_decode($response, true);
        } catch (Exception $e) {
            return ['error' => 'API error'];
        }
    }

    public function saveRecipe($userId, $recipeId, $title, $image) {
        try {
            $existing = $this->db->fetchOne(
                'SELECT id FROM saved_recipes WHERE user_id = ? AND spoonacular_recipe_id = ?',
                [$userId, $recipeId]
            );
            if ($existing) return ['success' => false, 'message' => 'Recipe already saved'];

            $this->db->insert('saved_recipes', [
                'user_id' => $userId,
                'spoonacular_recipe_id' => $recipeId,
                'recipe_title' => $title,
                'recipe_image' => $image
            ]);
            return ['success' => true, 'message' => 'Recipe saved successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error saving recipe'];
        }
    }

    public function getSavedRecipes($userId) {
        try {
            $recipes = $this->db->fetchAll(
                'SELECT * FROM saved_recipes WHERE user_id = ? ORDER BY saved_at DESC',
                [$userId]
            );
            return ['success' => true, 'recipes' => $recipes];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching recipes'];
        }
    }

    public function removeRecipe($userId, $recipeId) {
        try {
            $this->db->delete('saved_recipes', [
                'user_id' => $userId,
                'spoonacular_recipe_id' => $recipeId
            ]);
            return ['success' => true, 'message' => 'Recipe removed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error removing recipe'];
        }
    }

    /**
     * Built-in sample recipes when no API key is set
     */
    private function getSampleRecipes($query, $filters = []) {
        $all = [
            ['id' => 90001, 'title' => 'Grilled Chicken Salad', 'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&q=80', 'readyInMinutes' => 20, 'servings' => 2, 'calories' => 350, 'diets' => ['gluten free'], 'dishTypes' => ['lunch','salad']],
            ['id' => 90002, 'title' => 'Vegetable Stir Fry', 'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=500&q=80', 'readyInMinutes' => 15, 'servings' => 3, 'calories' => 280, 'diets' => ['vegan','vegetarian','gluten free'], 'dishTypes' => ['dinner','main course']],
            ['id' => 90003, 'title' => 'Avocado Toast with Eggs', 'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=500&q=80', 'readyInMinutes' => 10, 'servings' => 1, 'calories' => 420, 'diets' => ['vegetarian'], 'dishTypes' => ['breakfast']],
            ['id' => 90004, 'title' => 'Salmon with Roasted Vegetables', 'image' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=500&q=80', 'readyInMinutes' => 35, 'servings' => 2, 'calories' => 480, 'diets' => ['gluten free','paleo'], 'dishTypes' => ['dinner','main course']],
            ['id' => 90005, 'title' => 'Greek Yogurt Parfait', 'image' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=500&q=80', 'readyInMinutes' => 5, 'servings' => 1, 'calories' => 250, 'diets' => ['vegetarian'], 'dishTypes' => ['breakfast','snack']],
            ['id' => 90006, 'title' => 'Quinoa Buddha Bowl', 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=500&q=80', 'readyInMinutes' => 25, 'servings' => 2, 'calories' => 380, 'diets' => ['vegan','vegetarian','gluten free'], 'dishTypes' => ['lunch','dinner']],
            ['id' => 90007, 'title' => 'Chicken Caesar Wrap', 'image' => 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=500&q=80', 'readyInMinutes' => 15, 'servings' => 2, 'calories' => 450, 'diets' => [], 'dishTypes' => ['lunch']],
            ['id' => 90008, 'title' => 'Berry Smoothie Bowl', 'image' => 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=500&q=80', 'readyInMinutes' => 8, 'servings' => 1, 'calories' => 310, 'diets' => ['vegan','vegetarian','gluten free'], 'dishTypes' => ['breakfast','snack']],
            ['id' => 90009, 'title' => 'Pasta Primavera', 'image' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=500&q=80', 'readyInMinutes' => 25, 'servings' => 4, 'calories' => 520, 'diets' => ['vegetarian'], 'dishTypes' => ['dinner','main course']],
            ['id' => 90010, 'title' => 'Turkey Meatball Soup', 'image' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=500&q=80', 'readyInMinutes' => 40, 'servings' => 4, 'calories' => 320, 'diets' => ['gluten free'], 'dishTypes' => ['lunch','dinner','soup']],
            ['id' => 90011, 'title' => 'Mediterranean Hummus Plate', 'image' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=500&q=80', 'readyInMinutes' => 10, 'servings' => 2, 'calories' => 290, 'diets' => ['vegan','vegetarian'], 'dishTypes' => ['lunch','snack']],
            ['id' => 90012, 'title' => 'Grilled Steak with Sweet Potato', 'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500&q=80', 'readyInMinutes' => 30, 'servings' => 2, 'calories' => 580, 'diets' => ['gluten free','paleo'], 'dishTypes' => ['dinner','main course']],
            ['id' => 90013, 'title' => 'Overnight Oats', 'image' => 'https://images.unsplash.com/photo-1517673400267-0251440c45dc?w=500&q=80', 'readyInMinutes' => 5, 'servings' => 1, 'calories' => 340, 'diets' => ['vegetarian','vegan'], 'dishTypes' => ['breakfast']],
            ['id' => 90014, 'title' => 'Shrimp Tacos', 'image' => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=500&q=80', 'readyInMinutes' => 20, 'servings' => 3, 'calories' => 410, 'diets' => ['gluten free'], 'dishTypes' => ['lunch','dinner']],
            ['id' => 90015, 'title' => 'Kale and Apple Salad', 'image' => 'https://images.unsplash.com/photo-1515543237350-b3eea1ec8082?w=500&q=80', 'readyInMinutes' => 10, 'servings' => 2, 'calories' => 220, 'diets' => ['vegan','vegetarian','gluten free'], 'dishTypes' => ['lunch','salad']],
            ['id' => 90016, 'title' => 'Chicken Tikka Masala', 'image' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=500&q=80', 'readyInMinutes' => 45, 'servings' => 4, 'calories' => 490, 'diets' => ['gluten free'], 'dishTypes' => ['dinner','main course']],
        ];

        $query = strtolower(trim($query));
        $results = [];

        foreach ($all as $r) {
            $match = false;
            if (empty($query) || $query === 'all') {
                $match = true;
            } else {
                $words = explode(' ', $query);
                foreach ($words as $word) {
                    if (stripos($r['title'], $word) !== false) { $match = true; break; }
                    foreach ($r['dishTypes'] as $dt) {
                        if (stripos($dt, $word) !== false) { $match = true; break 2; }
                    }
                }
            }

            if (!$match) continue;

            // Apply diet filter
            if (!empty($filters['diet'])) {
                $diet = strtolower($filters['diet']);
                if (!in_array($diet, $r['diets'])) continue;
            }

            // Apply calorie filter
            if (!empty($filters['maxCalories']) && $r['calories'] > (int)$filters['maxCalories']) continue;

            $results[] = $r;
        }

        if (empty($results) && !empty($query)) {
            $results = array_slice($all, 0, 6);
        }

        return ['results' => $results, 'totalResults' => count($results)];
    }

    private function getSampleRecipeDetail($recipeId) {
        $details = [
            90001 => ['title' => 'Grilled Chicken Salad', 'image' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80', 'readyInMinutes' => 20, 'servings' => 2, 'healthScore' => 85, 'summary' => 'A light and refreshing grilled chicken salad with mixed greens, cherry tomatoes, cucumbers, and a lemon vinaigrette. Perfect for a healthy lunch.', 'extendedIngredients' => [['original' => '2 chicken breasts'], ['original' => '4 cups mixed greens'], ['original' => '1 cup cherry tomatoes, halved'], ['original' => '1 cucumber, sliced'], ['original' => '1/4 red onion, thinly sliced'], ['original' => '2 tbsp olive oil'], ['original' => '1 lemon, juiced'], ['original' => 'Salt and pepper to taste']], 'instructions' => '1. Season chicken breasts with salt, pepper, and olive oil. Grill for 6-7 minutes per side until cooked through. Let rest 5 minutes, then slice. 2. In a large bowl, combine mixed greens, cherry tomatoes, cucumber, and red onion. 3. Whisk together olive oil, lemon juice, salt and pepper for the dressing. 4. Top salad with sliced chicken and drizzle with dressing. Serve immediately.'],
            90002 => ['title' => 'Vegetable Stir Fry', 'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=800&q=80', 'readyInMinutes' => 15, 'servings' => 3, 'healthScore' => 90, 'summary' => 'A quick and colorful vegetable stir fry with bell peppers, broccoli, snap peas, and a savory soy-ginger sauce.', 'extendedIngredients' => [['original' => '2 cups broccoli florets'], ['original' => '1 red bell pepper, sliced'], ['original' => '1 cup snap peas'], ['original' => '2 carrots, julienned'], ['original' => '3 tbsp soy sauce'], ['original' => '1 tbsp sesame oil'], ['original' => '1 tsp fresh ginger, minced'], ['original' => '2 cloves garlic, minced']], 'instructions' => '1. Heat sesame oil in a large wok over high heat. 2. Add garlic and ginger, stir for 30 seconds. 3. Add broccoli and carrots, stir fry 3 minutes. 4. Add bell pepper and snap peas, cook 2 more minutes. 5. Pour in soy sauce and toss everything together. 6. Serve over rice or noodles.'],
            90003 => ['title' => 'Avocado Toast with Eggs', 'image' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=800&q=80', 'readyInMinutes' => 10, 'servings' => 1, 'healthScore' => 78, 'summary' => 'Classic avocado toast topped with perfectly poached eggs, red pepper flakes, and everything bagel seasoning.', 'extendedIngredients' => [['original' => '1 ripe avocado'], ['original' => '2 slices sourdough bread'], ['original' => '2 eggs'], ['original' => '1 tbsp lemon juice'], ['original' => 'Red pepper flakes'], ['original' => 'Everything bagel seasoning'], ['original' => 'Salt and pepper']], 'instructions' => '1. Toast bread until golden brown. 2. Mash avocado with lemon juice, salt, and pepper. 3. Poach eggs in simmering water for 3-4 minutes. 4. Spread mashed avocado on toast. 5. Top with poached eggs, red pepper flakes, and everything bagel seasoning.'],
        ];

        if (isset($details[$recipeId])) {
            $d = $details[$recipeId];
            $d['id'] = $recipeId;
            return $d;
        }

        // Generic detail for recipes without specific details
        $samples = $this->getSampleRecipes('all')['results'];
        foreach ($samples as $s) {
            if ($s['id'] == $recipeId) {
                return [
                    'id' => $s['id'],
                    'title' => $s['title'],
                    'image' => str_replace('w=500', 'w=800', $s['image']),
                    'readyInMinutes' => $s['readyInMinutes'],
                    'servings' => $s['servings'],
                    'healthScore' => rand(65, 95),
                    'summary' => 'A delicious and nutritious recipe ready in ' . $s['readyInMinutes'] . ' minutes. About ' . $s['calories'] . ' calories per serving.',
                    'extendedIngredients' => [['original' => 'See full recipe for ingredients list']],
                    'instructions' => 'Detailed instructions coming soon. This recipe takes approximately ' . $s['readyInMinutes'] . ' minutes and serves ' . $s['servings'] . '.'
                ];
            }
        }

        return ['error' => 'Recipe not found'];
    }
}

// Handle API requests
$recipeAPI = new RecipeAPI();
$action = $_GET['action'] ?? null;

// For POST requests, also read JSON body
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($action)) $action = $input['action'] ?? null;
}

$userId = $_SESSION['user_id'] ?? null;

switch ($action) {
    case 'search':
        $query = $_GET['query'] ?? $input['query'] ?? '';
        $filters = [
            'diet' => $_GET['diet'] ?? $input['diet'] ?? '',
            'minCalories' => $_GET['minCalories'] ?? $input['minCalories'] ?? '',
            'maxCalories' => $_GET['maxCalories'] ?? $input['maxCalories'] ?? '',
            'cuisines' => $_GET['cuisines'] ?? $input['cuisines'] ?? '',
            'type' => $_GET['type'] ?? $input['type'] ?? ''
        ];
        echo json_encode($recipeAPI->searchRecipes($query, $filters));
        break;

    case 'details':
        $recipeId = $_GET['id'] ?? $input['id'] ?? null;
        echo json_encode($recipeAPI->getRecipeDetails((int)$recipeId));
        break;

    case 'save':
        if (!$userId) { echo json_encode(['success' => false, 'message' => 'Please log in first']); break; }
        $recipeId = $input['recipeId'] ?? null;
        $title = $input['title'] ?? '';
        $image = $input['image'] ?? '';
        echo json_encode($recipeAPI->saveRecipe($userId, (int)$recipeId, $title, $image));
        break;

    case 'getSaved':
        if (!$userId) { echo json_encode(['success' => true, 'recipes' => []]); break; }
        echo json_encode($recipeAPI->getSavedRecipes($userId));
        break;

    case 'remove':
        if (!$userId) { echo json_encode(['success' => false, 'message' => 'Please log in first']); break; }
        $recipeId = $input['recipeId'] ?? null;
        echo json_encode($recipeAPI->removeRecipe($userId, (int)$recipeId));
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
