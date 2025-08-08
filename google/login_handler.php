<?php
// Suppress all output and errors that might corrupt JSON
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering and clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

try {
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Include config file with error suppression
    $config_path = __DIR__ . '/../../config/env_loader.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    }

    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get input data - handle both JSON and form data
    $input = [];
    
    // Try to get JSON input first
    $json_input = file_get_contents('php://input');
    if (!empty($json_input)) {
        $decoded = json_decode($json_input, true);
        if ($decoded !== null) {
            $input = $decoded;
        }
    }
    
    // If no JSON, use POST data
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }
    
    // Validate action
    if (empty($input['action'])) {
        throw new Exception('Missing action parameter');
    }
    
    $action = trim($input['action']);
    
    // Handle different login actions
    if ($action === 'firebase-google-login' || $action === 'firebase-email-login') {
        // Validate required fields
        $requiredFields = ['uid', 'email', 'name'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field]) || !is_string($input[$field])) {
                throw new Exception("Missing or invalid required field: $field");
            }
        }
        
        // Sanitize input data
        $userData = [
            'uid' => trim($input['uid']),
            'email' => trim($input['email']),
            'name' => trim($input['name']),
            'picture' => isset($input['picture']) ? trim($input['picture']) : '',
            'auth_provider' => ($action === 'firebase-google-login') ? 'google' : 'email'
        ];
        
        // Validate email format
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Validate UID (should not be empty and reasonable length)
        if (strlen($userData['uid']) < 10) {
            throw new Exception('Invalid user ID');
        }
        
        // Set session data
        $_SESSION['user'] = $userData;
        
        // Clear any buffered output
        ob_clean();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $userData
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    // Clear any buffered output
    ob_clean();
    
    // Set error status code
    http_response_code(400);
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'LOGIN_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}

// End output buffering and flush
ob_end_flush();
exit;
?>