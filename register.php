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
    <title>Register - AI ChatTest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/login.css">
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/sweetalert.css">
    <style>
        .loading {
            position: relative;
            pointer-events: none;
        }
        .loading::after {
            content: "";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
        .google-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: url('https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg') no-repeat center;
            background-size: contain;
            margin-right: 8px;
            vertical-align: middle;
        }
        .error-message {
            color: #ff4444;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <!-- Register Container -->
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cog gear"></i>
                    <i class="fas fa-robot robot"></i>
                </div>
                <span class="logo-text">Techno.ai</span>
            </div>
            <h1 class="login-title">Create Account</h1>
            <p class="login-subtitle">Get started with your new account</p>
            
            <?php if (isset($_SESSION['register_error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_SESSION['register_error']); unset($_SESSION['register_error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form -->
        <form class="login-form" id="register-form">
            <!-- Google Register Button -->
            <button type="button" class="btn btn-google" id="google-register-btn">
                <div class="google-icon"></div>
                Continue with Google
            </button>
            
            <!-- Divider -->
            <div class="divider">
                <span>or</span>
            </div>

            <!-- Full Name Field -->
            <div class="form-group">
                <label for="fullname" class="form-label">Full Name</label>
                <input 
                    type="text" 
                    id="fullname" 
                    name="fullname" 
                    class="form-input" 
                    placeholder="Enter your full name"
                    required
                >
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
                        minlength="6"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="form-group">
                <label for="confirm-password" class="form-label">Confirm Password</label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="confirm-password" 
                        name="confirm-password" 
                        class="form-input" 
                        placeholder="Confirm your password"
                        required
                        minlength="6"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm-password')">
                        <i class="fas fa-eye" id="confirm-password-icon"></i>
                    </button>
                </div>
                <div id="password-match-error" class="error-message" style="display: none;">
                    Passwords do not match
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" id="register-btn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>

            <!-- Login Link -->
            <div class="register-link">
                Already have an account? <a href="index.php">Sign in</a>
            </div>
        </form>
    </div>

    <!-- Add Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore-compat.js"></script>

    <script>
        // Initialize Firebase
        const firebaseConfig = {
            apiKey: "<?php echo $firebaseConfig['apiKey']; ?>",
            authDomain: "<?php echo $firebaseConfig['authDomain']; ?>",
            projectId: "<?php echo $firebaseConfig['projectId']; ?>",
            storageBucket: "<?php echo $firebaseConfig['storageBucket']; ?>",
            messagingSenderId: "<?php echo $firebaseConfig['messagingSenderId']; ?>",
            appId: "<?php echo $firebaseConfig['appId']; ?>"
        };
        
        const app = firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        const db = firebase.firestore();

        // Universal SweetAlert Function with your custom design
        function showCustomAlert(options) {
            const defaultOptions = {
                customClass: {
                    popup: 'logout-swal',
                    title: 'logout-swal-title',
                    htmlContainer: 'logout-swal-content',
                    actions: 'logout-swal-actions',
                    confirmButton: 'logout-swal-confirm',
                    cancelButton: 'logout-swal-cancel'
                },
                showConfirmButton: true,
                showCancelButton: false,
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                allowOutsideClick: true,
                allowEscapeKey: true
            };

            // Merge custom options with defaults
            const finalOptions = { ...defaultOptions, ...options };
            
            // Merge custom classes if provided
            if (options.customClass) {
                finalOptions.customClass = { ...defaultOptions.customClass, ...options.customClass };
            }

            return Swal.fire(finalOptions);
        }

        // Theme Management
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            
            const themeIcon = document.getElementById('theme-icon');
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        }

        // Password Toggle
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(`${fieldId}-icon`);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Email Verification Monitoring
        function setupVerificationMonitoring() {
            auth.onAuthStateChanged(async (user) => {
                if (user) {
                    // Always reload user to get fresh verification status
                    await user.reload();
                    const currentUser = auth.currentUser;
                    
                    // Get current Firestore data
                    const userDoc = await db.collection('users').doc(user.uid).get();
                    if (userDoc.exists) {
                        const userData = userDoc.data();
                        
                        // If Firebase Auth says verified but Firestore says not verified
                        if (currentUser.emailVerified && !userData.is_verified) {
                            await db.collection('users').doc(user.uid).update({
                                is_verified: true,
                                email_verified_at: firebase.firestore.FieldValue.serverTimestamp()
                            });
                            
                            console.log('✅ Verification status synced!');
                            
                            // Optional: Show success message
                            showCustomAlert({
                                icon: 'success',
                                title: 'Email Verified!',
                                text: 'Your email has been successfully verified.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    }
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            setupVerificationMonitoring();
        });

        // Password match validation
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const errorElement = document.getElementById('password-match-error');
            
            if (password !== confirmPassword && confirmPassword.length > 0) {
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        });

        // Email/Password Registration - Updated with custom SweetAlert
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const fullName = document.getElementById('fullname').value;

            // Validate passwords match
            if (password !== document.getElementById('confirm-password').value) {
                showCustomAlert({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match. Please try again.'
                });
                return;
            }

            // Show loading state
            const btn = document.getElementById('register-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';

            try {
                // 1. Create user account
                const userCredential = await auth.createUserWithEmailAndPassword(email, password);
                const user = userCredential.user;
                
                // 2. Update profile
                await user.updateProfile({ displayName: fullName });

                // 3. Save to Firestore with is_verified: false initially
                await db.collection('users').doc(user.uid).set({
                    email: email,
                    full_name: fullName,
                    is_verified: false, // Initially false
                    email_verified_at: null,
                    auth_provider: 'email',
                    created_at: firebase.firestore.FieldValue.serverTimestamp(),
                    role: 'user'
                });

                // 4. Send verification email
                await user.sendEmailVerification({
                    url: window.location.origin + '/verify_success.php',
                    handleCodeInApp: false
                });

                // 5. Sign out the user (they need to verify first)
                await auth.signOut();

                // 6. Show success message with custom design
                await showCustomAlert({
                    icon: 'success',
                    title: 'Account Created!',
                    html: `
                        <p>We've sent a verification link to <strong>${email}</strong></p>
                        <p style="color:#e74c3c; margin-top:10px;">⚠️ Please check your spam folder if you don't see it!</p>
                        <p style="margin-top:15px;">You must verify your email before you can sign in.</p>
                    `,
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                });

                // 7. Redirect to login
                window.location.href = 'index.php';

            } catch (error) {
                console.error('Registration error:', error);
                
                let errorMessage = 'Registration failed. Please try again.';
                
                switch(error.code) {
                    case 'auth/email-already-in-use':
                        errorMessage = 'This email is already registered.';
                        break;
                    case 'auth/weak-password':
                        errorMessage = 'Password should be at least 6 characters.';
                        break;
                    case 'auth/invalid-email':
                        errorMessage = 'Please enter a valid email address.';
                        break;
                    case 'auth/operation-not-allowed':
                        errorMessage = 'Email/password registration is not enabled. Please contact support.';
                        break;
                    default:
                        errorMessage = error.message || 'An unexpected error occurred.';
                }
                
                await showCustomAlert({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            }
        });

        // Google Sign-In - Updated with custom SweetAlert
        document.getElementById('google-register-btn').addEventListener('click', async () => {
            const btn = document.getElementById('google-register-btn');
            btn.disabled = true;
            btn.innerHTML = '<div class="google-icon"></div> Signing in...';

            try {
                const provider = new firebase.auth.GoogleAuthProvider();
                const result = await auth.signInWithPopup(provider);
                const user = result.user;

                // Google users are automatically verified
                const userData = {
                    email: user.email,
                    full_name: user.displayName,
                    is_verified: user.emailVerified, // Usually true for Google
                    email_verified_at: user.emailVerified ? firebase.firestore.FieldValue.serverTimestamp() : null,
                    auth_provider: 'google',
                    profile_pic: user.photoURL || '',
                    created_at: firebase.firestore.FieldValue.serverTimestamp(),
                    role: 'user'
                };

                // Save user data
                await db.collection('users').doc(user.uid).set(userData, { merge: true });
                
                if (user.emailVerified) {
                    // Google users are typically pre-verified
                    window.location.href = 'users/home.php';
                } else {
                    // Rare case: Google user not verified
                    await user.sendEmailVerification({
                        url: window.location.origin + '/verify_success.php'
                    });

                    await showCustomAlert({
                        icon: 'info',
                        title: 'Verify Your Email',
                        html: `We've sent a verification link to <strong>${user.email}</strong>.<br>
                            Please verify to access all features.`,
                        confirmButtonText: 'OK'
                    });

                    window.location.href = 'index.php';
                }

            } catch (error) {
                console.error('Google sign-in error:', error);
                
                await showCustomAlert({
                    icon: 'error',
                    title: 'Google Sign-In Failed',
                    text: error.message || 'Failed to sign in with Google.',
                    confirmButtonText: 'OK'
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<div class="google-icon"></div> Continue with Google';
            }
        });

        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            
            const header = document.querySelector('.login-header');
            header.appendChild(errorDiv);
            
            setTimeout(() => errorDiv.remove(), 5000);
        }
    </script>
</body>
</html>