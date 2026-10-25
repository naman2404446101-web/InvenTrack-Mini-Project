<?php
session_start();   // ✅ VERY IMPORTANT

// ============================================================
// api/auth.php
// Handles login, registration, and logout via POST/GET
// ============================================================

require_once '../includes/config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ----------------------------------------------------------
    // REGISTER
    // ----------------------------------------------------------
    case 'register':
        $name     = trim($_POST['name']  ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password =       $_POST['password'] ?? '';
        $confirm  =       $_POST['confirm']  ?? '';

        // --- Validation ---
        $errors = [];
        if (strlen($name) < 2)               $errors[] = 'Name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < 6)           $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm)          $errors[] = 'Passwords do not match.';

        if ($errors) {
            jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }

        $db = getDB();

        // Check email uniqueness
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Email already registered.']);
        }

        // Hash password with bcrypt (cost 12)
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash]);

        jsonResponse(['success' => true, 'message' => 'Account created! You can now log in.']);
        break;

    // ----------------------------------------------------------
    // LOGIN
    // ----------------------------------------------------------
    case 'login':
        $email    = trim($_POST['email']    ?? '');
        $password =       $_POST['password'] ?? '';

        if (!$email || !$password) {
            jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
        }

        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify password hash (prevents timing attacks vs plain compare)
        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
        }

        // Set session variables
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        jsonResponse(['success' => true, 'message' => 'Login successful!', 'name' => $user['name']]);
        break;

    // ----------------------------------------------------------
    // LOGOUT
    // ----------------------------------------------------------
    case 'logout':
        $_SESSION = [];
        session_destroy();
        header('Location: ' . APP_BASE_URL . '/index.php');
        exit;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
