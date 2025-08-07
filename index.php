<?php
session_start();

// Load environment variables
require_once 'config/env_loader.php';

if (isset($_SESSION['user'])) {
    header('Location: users/home.php');
    exit;
}

// Get Firebase config from environment
$firebaseConfig = [
    'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? '',
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? '',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? '',
    'storageBucket' => $_ENV['FIREBASE_STORAGE_BUCKET'] ?? '',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? ''
];

// Validate that we have the required config
$requiredKeys = ['apiKey', 'authDomain', 'projectId', 'appId'];
foreach ($requiredKeys as $key) {
    if (empty($firebaseConfig[$key])) {
        die("Missing Firebase configuration: $key");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI ChatTest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/login.css">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <!-- Login Container -->
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <img src="assets/images/logo.png" class="logo-icon" alt="">
                </div>
                <span class="logo-text">Techno.ai</span>
            </div>
            <h1 class="login-title">Welcome back</h1>
            <p class="login-subtitle">Sign in to your account to continue</p>
            
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form -->
        <form class="login-form" onsubmit="handleLogin(event)">
            <!-- Google Login Button -->
            <button type="button" class="btn btn-google" id="google-login-btn">
                <div class="google-icon"></div>
                Continue with Google
            </button>
            
            <?php if (isset($_SESSION['auth_error'])): ?>
                <div class="error-message">
                    <?= htmlspecialchars($_SESSION['auth_error']); 
                    unset($_SESSION['auth_error']); ?>
                </div>
            <?php endif; ?>

            <!-- Divider -->
            <div class="divider">
                <span>or</span>
            </div>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    placeholder="Enter your email"
                    required
                >
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Enter your password"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <!-- Form Options -->
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" id="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign in
            </button>

            <!-- Register Link -->
            <div class="register-link">
                Don't have an account? <a href="register.php">Create account</a>
            </div>
        </form>
    </div>

    <!-- Firebase Configuration from PHP -->
    <script>
        window.firebaseConfig = <?php echo json_encode($firebaseConfig); ?>;
    </script>

    <!-- Firebase v12 Implementation -->
    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-app.js";
        import { 
            getAuth, 
            GoogleAuthProvider, 
            signInWithPopup 
        } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";

        // Use config from PHP
        const app = initializeApp(window.firebaseConfig);
        const auth = getAuth(app);

        document.getElementById('google-login-btn').addEventListener('click', async () => {
            const btn = document.getElementById('google-login-btn');
            btn.classList.add('loading');
            btn.innerHTML = '<div class="google-icon"></div>Signing in...';
            
            try {
                const provider = new GoogleAuthProvider();
                const result = await signInWithPopup(auth, provider);
                const user = result.user;
                
                console.log("Firebase user:", user);
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'firebase-google-login');
                formData.append('uid', user.uid);
                formData.append('email', user.email);
                formData.append('name', user.displayName || 'Google User');
                formData.append('picture', user.photoURL || '');
                
                const response = await fetch('google/auth_handler.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.redirected || response.ok) {
                    window.location.href = 'users/home.php';
                } else {
                    throw new Error('Login failed');
                }
                
            } catch (error) {
                console.error("Login error:", error);
                
                let errorMessage = "Login failed: " + error.message;
                if (error.message.includes('popup')) {
                    errorMessage = "Login cancelled or blocked by popup blocker";
                }
                
                alert(errorMessage);
                
                btn.classList.remove('loading');
                btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
            }
        });
    </script>

    <script src="scripts/scripts.js"></script>
</body>
</html>