<?php
/**
 * Authentication Class
 * Handles user authentication, registration, and session management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Register a new user
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register($username, $email, $password, $firstName = '', $lastName = '') {
        // Validate inputs
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Check if user exists
        $existing = $this->db->fetchOne(
            'SELECT id FROM users WHERE email = ? OR username = ?',
            [$email, $username]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Email or username already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($password, HASH_ALGORITHM, HASH_OPTIONS);

        // Insert user
        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $hashedPassword,
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);

            return [
                'success' => true,
                'message' => 'Account created successfully',
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function login($email, $password) {
        // Validate inputs
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password required'];
        }

        // Check login attempts
        if ($this->isLockedOut($email)) {
            return ['success' => false, 'message' => 'Account temporarily locked. Try again later.'];
        }

        // Fetch user
        $user = $this->db->fetchOne(
            'SELECT id, password_hash FROM users WHERE email = ?',
            [$email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedLogin($email);
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['login_time'] = time();

        // Clear failed login attempts
        $this->clearFailedLogin($email);

        return ['success' => true, 'message' => 'Login successful'];
    }

    /**
     * Check if account is locked out
     * @param string $email
     * @return bool
     */
    private function isLockedOut($email) {
        return false;
    }

    /**
     * Record failed login attempt
     * @param string $email
     */
    private function recordFailedLogin($email) {
        // Implement login attempt tracking
    }

    /**
     * Clear failed login attempts
     * @param string $email
     */
    private function clearFailedLogin($email) {
        // Clear login attempt tracking
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user data
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetchOne(
            'SELECT id, username, email, first_name, last_name, age, gender, height_cm, weight_kg, profile_image FROM users WHERE id = ?',
            [$this->getUserId()]
        );
    }

    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateProfile($userId, $data) {
        try {
            $this->db->update('users', $data, ['id' => $userId]);
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    /**
     * Change password
     * @param int $userId
     * @param string $oldPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Validate new password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'New password must be at least 8 characters'];
        }

        // Get user
        $user = $this->db->fetchOne(
            'SELECT password_hash FROM users WHERE id = ?',
            [$userId]
        );

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        // Update password
        $hashedPassword = password_hash($newPassword, HASH_ALGORITHM, HASH_OPTIONS);
        $this->db->update('users', ['password_hash' => $hashedPassword], ['id' => $userId]);

        return ['success' => true, 'message' => 'Password changed successfully'];
    }
}
?>
