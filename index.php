<?php
session_start();
require_once 'config/env_loader.php';

if (isset($_SESSION['user'])) {
    header('Location: users/home.php');
    exit;
}

$firebaseConfig = [
    'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? '',
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? '',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? '',
    'storageBucket' => $_ENV['FIREBASE_STORAGE_BUCKET'] ?? '',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? ''
];

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
    <link rel="stylesheet" href="assets/login.css">
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="assets/sweetalert.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">
                    <img src="assets/images/logo.png" class="logo-icon" alt="">
                </div>
                <span class="logo-text">Techno.ai</span>
            </div>
            <h1 class="login-title">Welcome back</h1>
            <p class="login-subtitle">Sign in to your account to continue</p>
        </div>

        <!-- Changed to POST method and removed action attribute since we're handling with JavaScript -->
        <form class="login-form" id="loginForm" method="post">
            <button type="button" class="btn btn-google" id="google-login-btn">
                <div class="google-icon"></div>
                Continue with Google
            </button>

            <div class="divider"><span>or</span></div>

            <div class="form-group">
                <label for="email" class="form-label">Email address</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary" id="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign in
            </button>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create account</a>
            </div>
        </form>
    </div>

    <script>window.firebaseConfig = <?php echo json_encode($firebaseConfig); ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-app.js";
        import { getAuth, GoogleAuthProvider, signInWithPopup, signInWithEmailAndPassword, deleteUser as firebaseDeleteUser } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-auth.js";
        import { getFirestore, doc, getDoc } from "https://www.gstatic.com/firebasejs/12.0.0/firebase-firestore.js";

        const app = initializeApp(window.firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);

        async function checkUserInFirestore(uid, provider) {
            try {
                const userDocRef = doc(db, "users", uid);
                const userDoc = await getDoc(userDocRef);

                if (!userDoc.exists()) {
                    throw new Error("User account not found in database. Please register first.");
                }

                const userData = userDoc.data();

                if (!userData.is_verified) {
                    await showCustomAlert({
                        title: 'Account Not Verified',
                        text: 'Your account is not yet verified. Please check your email for verification instructions.',
                        icon: 'warning'
                    });
                    throw new Error("Account not verified");
                }

                if (provider && userData.auth_provider && userData.auth_provider !== provider) {
                    await showCustomAlert({
                        title: 'Wrong Login Method',
                        text: `You registered using ${userData.auth_provider}. Please login with the same provider.`,
                        icon: 'error'
                    });
                    throw new Error(`Registered with ${userData.auth_provider}`);
                }

                return userData;
            } catch (error) {
                console.error("Firestore check error:", error);
                throw error;
            }
        }

        // Email/Password Login Handler
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const btn = document.getElementById('login-btn');

    // Validate inputs
    if (!email || !password) {
        await showCustomAlert({
            title: 'Validation Error',
            text: 'Please fill in all required fields',
            icon: 'warning'
        });
        return;
    }

    btn.classList.add('loading');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';

    try {
        // First, authenticate with Firebase
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;

        // Get user data from Firestore
        const userDocRef = doc(db, "users", user.uid);
        const userDoc = await getDoc(userDocRef);
        
        if (!userDoc.exists()) {
            throw new Error("User account not found in database. Please register first.");
        }

        const userData = userDoc.data();

        if (!userData.is_verified) {
            throw new Error("Account not verified. Please check your email for verification instructions.");
        }

        // Prepare data for backend
        const loginData = {
            action: 'firebase-email-login',
            uid: user.uid,
            email: user.email,
            name: userData.full_name || user.displayName || user.email,
            picture: userData.picture || user.photoURL || ''
        };

        console.log('Sending login data:', loginData); // Debug log

        // Send to backend using fetch with proper error handling
        const response = await fetch('google/login_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });

        // Log response details for debugging
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));

        // Get response text first to check what we received
        const responseText = await response.text();
        console.log('Response text:', responseText); // Debug log

        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response was:', responseText);
            throw new Error('Server returned invalid response. Please try again.');
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Login failed');
        }

        // Success
        await showCustomAlert({
            title: 'Login Successful',
            text: 'You are being redirected to your dashboard...',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        
        window.location.href = 'users/home.php';

    } catch (error) {
        console.error("Login error:", error);
        
        // Sign out from Firebase on error
        try {
            await auth.signOut();
        } catch (signOutError) {
            console.error('Sign out error:', signOutError);
        }

        let errorMessage = error.message;
        let alertTitle = 'Login Error';
        let alertIcon = 'error';

        // Handle specific Firebase auth errors
        if (error.code === 'auth/user-not-found') {
            errorMessage = "Account does not exist. Please register first.";
        } else if (error.code === 'auth/wrong-password') {
            errorMessage = "Incorrect email or password. Please try again.";
        } else if (error.code === 'auth/invalid-email') {
            errorMessage = "Please enter a valid email address.";
        } else if (error.code === 'auth/user-disabled') {
            errorMessage = "This account has been disabled. Please contact support.";
        } else if (error.code === 'auth/too-many-requests') {
            errorMessage = "Too many failed attempts. Please try again later.";
        }

        await showCustomAlert({
            title: alertTitle,
            text: errorMessage,
            icon: alertIcon
        });

    } finally {
        // Reset button state
        btn.classList.remove('loading');
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign in';
    }
});

// Google Login Handler (also updated for consistency)
document.getElementById('google-login-btn').addEventListener('click', async () => {
    const btn = document.getElementById('google-login-btn');
    btn.classList.add('loading');
    btn.innerHTML = '<div class="google-icon"></div>Signing in...';

    try {
        const provider = new GoogleAuthProvider();
        const result = await signInWithPopup(auth, provider);
        const user = result.user;

        const userDocRef = doc(db, "users", user.uid);
        const userDoc = await getDoc(userDocRef);

        if (!userDoc.exists()) {
            await auth.signOut();
            await deleteFirebaseUser(user);

            await showCustomAlert({
                title: 'Account Not Found',
                html: 'User account not found in database. <a style="color: #0085d1;" href="register.php">Register</a> first.',
                icon: 'error'
            });

            return;
        }

        const userData = userDoc.data();

        if (!userData.is_verified) {
            await showCustomAlert({
                title: 'Account Not Verified',
                text: 'Your account is not yet verified. Please check your email for verification instructions.',
                icon: 'warning'
            });
            await auth.signOut();
            return;
        }

        if (userData.auth_provider && userData.auth_provider !== 'google') {
            await showCustomAlert({
                title: 'Wrong Login Method',
                text: `You registered using ${userData.auth_provider}. Please login with the same provider.`,
                icon: 'error'
            });
            await auth.signOut();
            return;
        }

        // Prepare data for backend
        const loginData = {
            action: 'firebase-google-login',
            uid: user.uid,
            email: user.email,
            name: user.displayName || userData.full_name || user.email,
            picture: user.photoURL || userData.picture || ''
        };

        const response = await fetch('google/login_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(loginData)
        });

        const responseText = await response.text();
        const data = JSON.parse(responseText);

        if (response.ok && data.success) {
            await showCustomAlert({
                title: 'Login Successful',
                text: 'You are being redirected to your dashboard...',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            window.location.href = 'users/home.php';
        } else {
            throw new Error(data.message || 'Login failed');
        }

    } catch (error) {
        console.error("Google login error:", error);
        await auth.signOut();

        let errorMessage = error.message;
        let alertTitle = 'Login Error';
        let alertIcon = 'error';

        if (error.code === 'auth/popup-closed-by-user') {
            errorMessage = "Login cancelled by user";
            alertTitle = 'Login Cancelled';
            alertIcon = 'info';
        } else if (error.code === 'auth/popup-blocked') {
            errorMessage = "Popup blocked by browser. Please allow popups for this site.";
            alertTitle = 'Popup Blocked';
        }

        await showCustomAlert({
            title: alertTitle,
            text: errorMessage,
            icon: alertIcon
        });
    } finally {
        btn.classList.remove('loading');
        btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
    }
});

        async function deleteFirebaseUser(user) {
            try {
                await firebaseDeleteUser(user);
            } catch (error) {
                console.error("Error deleting user:", error);
            }
        }

        async function showCustomAlert(options) {
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
            const finalOptions = { ...defaultOptions, ...options };
            if (options.customClass) {
                finalOptions.customClass = { ...defaultOptions.customClass, ...options.customClass };
            }
            return await Swal.fire(finalOptions);
        }
    </script>
    <script src="scripts/scripts.js"></script>
</body>
</html>