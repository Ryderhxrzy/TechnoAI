<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'firebase-google-login') {
    
    // Get user data from POST
    $uid = $_POST['uid'] ?? '';
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? 'Google User';
    $picture = $_POST['picture'] ?? '';
    
    // Validate required fields
    if (empty($uid) || empty($email)) {
        http_response_code(400);
        exit('Missing required data');
    }
    
    // Store user in session
    $_SESSION['user'] = [
        'id' => $uid,
        'email' => $email,
        'name' => $name,
        'picture' => $picture,
        'login_time' => time(),
        'auth_method' => 'google'
    ];
    
    // Success - redirect to home page
    header('Location: ../users/home.php');
    exit;
    
} else {
    http_response_code(400);
    exit('Invalid request');
}
?>