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

        <form class="login-form" id="loginForm">
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

                    btn.classList.remove('loading');
                    btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
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
                    btn.classList.remove('loading');
                    btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
                    return;
                }

                if (userData.auth_provider && userData.auth_provider !== 'google') {
                    await showCustomAlert({
                        title: 'Wrong Login Method',
                        text: `You registered using ${userData.auth_provider}. Please login with the same provider.`,
                        icon: 'error'
                    });
                    await auth.signOut();
                    btn.classList.remove('loading');
                    btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'firebase-google-login');
                formData.append('uid', user.uid);
                formData.append('email', user.email);
                formData.append('name', user.displayName || 'Google User');
                formData.append('picture', user.photoURL || '');

                const response = await fetch('google/login-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

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
                    throw new Error(data.message || 'Account does not exist');
                }

            } catch (error) {
                console.error("Login error:", error);
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

                btn.classList.remove('loading');
                btn.innerHTML = '<div class="google-icon"></div>Continue with Google';
            }
        });

        // ✅ Renamed to avoid conflict
        async function deleteFirebaseUser(user) {
            try {
                await firebaseDeleteUser(user);
            } catch (error) {
                console.error("Error deleting user:", error);
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('login-btn');

            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';

            try {
                const userCredential = await signInWithEmailAndPassword(auth, email, password);
                const user = userCredential.user;

                await checkUserInFirestore(user.uid, 'email');

                const formData = new FormData();
                formData.append('action', 'firebase-email-login');
                formData.append('uid', user.uid);
                formData.append('email', user.email);

                const response = await fetch('google/login-handler.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

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
                    throw new Error(data.message || 'Account does not exist');
                }

            } catch (error) {
                console.error("Login error:", error);
                await auth.signOut();

                let errorMessage = error.message;
                let alertTitle = 'Login Error';
                let alertIcon = 'error';

                if (error.code === 'auth/user-not-found') {
                    errorMessage = "Account does not exist. Please register first.";
                } else if (error.code === 'auth/wrong-password') {
                    errorMessage = "Incorrect password. Please try again.";
                } else if (error.code === 'auth/too-many-requests') {
                    errorMessage = "Too many failed attempts. Account temporarily locked.";
                    alertTitle = 'Account Locked';
                } else if (error.message.includes('Account not verified') || error.message.includes('Registered with')) {
                    btn.classList.remove('loading');
                    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign in';
                    return;
                }

                await showCustomAlert({
                    title: alertTitle,
                    text: errorMessage,
                    icon: alertIcon
                });

                btn.classList.remove('loading');
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign in';
            }
        });

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
