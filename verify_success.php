<?php
require_once __DIR__.'/config/env_loader.php';

$firebaseConfig = [
    'apiKey' => $_ENV['FIREBASE_API_KEY'],
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'],
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'],
    'appId' => $_ENV['FIREBASE_APP_ID']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification - Techno.ai</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/login.css">
    <link rel="stylesheet" href="assets/styles.css">
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore-compat.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/sweetalert.css">
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <div class="verification-container">
        <!-- Logo -->
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-cog gear"></i>
                <i class="fas fa-robot robot"></i>
            </div>
            <span class="logo-text">Techno.ai</span>
        </div>

        <!-- Loading State (shown by default) -->
        <div class="verification-state" id="loading-state">
            <div class="verification-icon-container">
                <div class="verification-icon">
                    <div class="spinner"></div>
                    <i class="fas fa-envelope-open icon" id="icons"></i>
                </div>
            </div>
            <h2 class="status-title">Verifying Your Email</h2>
            <p class="status-message">Please wait while we verify your account...</p>
        </div>

        <!-- Success State (hidden by default) -->
        <div class="verification-state" id="success-state" style="display: none;">
            <div class="verification-icon success-icon">
                <i class="fas fa-check-circle" ></i>
            </div>
            <h2 class="status-title">Email Verified Successfully!</h2>
            <p class="status-message">Your account has been successfully verified.</p>
            
            <div class="success-details">
                <p style="margin-bottom: 8px;">Welcome to <strong>Techno.ai</strong>!</p>
                <p>You can now access all features of our platform.</p>
            </div>

            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                Continue to Login
            </a>
        </div>

        <!-- Error State (hidden by default) -->
        <div class="verification-state" id="error-state" style="display: none;">
            <div class="verification-icon-error error-icon">
                <i class="fas fa-exclamation-triangle" class="error_icon"></i>
            </div>
            <h2 class="status-title">Verification Failed</h2>
            <p class="status-message" id="error-message">This verification link is invalid or has expired.</p>
            
            <div class="info-box">
                <p><i class="fas fa-info-circle"></i> Need help? Try logging in - if your email is already verified, you should be able to access your account.</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Back to Login
                </a>
                <button class="btn btn-secondary" onclick="resendVerification()">
                    <i class="fas fa-envelope"></i>
                    Resend Verification Email
                </button>
            </div>
        </div>
    </div>

    <script src="scripts/scripts.js"></script>
    <script>
        // Initialize Firebase
        firebase.initializeApp({
            apiKey: "<?= $firebaseConfig['apiKey'] ?>",
            authDomain: "<?= $firebaseConfig['authDomain'] ?>",
            projectId: "<?= $firebaseConfig['projectId'] ?>",
            appId: "<?= $firebaseConfig['appId'] ?>"
        });

        const auth = firebase.auth();
        const db = firebase.firestore();

        // Function to show different states
        function showState(state, message = '') {
            document.querySelectorAll('.verification-state').forEach(el => {
                el.style.display = 'none';
            });
            const stateElement = document.getElementById(state + '-state');
            stateElement.style.display = 'block';
            
            if (message && state === 'error') {
                document.getElementById('error-message').textContent = message;
            }
        }

        // Function to resend verification
        async function resendVerification() {
            try {
                // Show loading state while resending
                showState('loading');
                
                // Get current user (if logged in)
                const user = auth.currentUser;
                if (user) {
                    await user.sendEmailVerification({
                        url: window.location.origin + '/verify_success.php'
                    });
                    showCustomAlert({
                        icon: 'success',
                        title: 'Verification Sent',
                        text: 'A new verification email has been sent to your email address.'
                    });
                } else {
                    showCustomAlert({
                        icon: 'info',
                        title: 'Please Login',
                        text: 'You need to be logged in to resend verification.'
                    });
                }
            } catch (error) {
                showCustomAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to resend verification email: ' + error.message
                });
            } finally {
                showState('error');
            }
        }

        // Universal SweetAlert Function with custom design
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

            const finalOptions = { ...defaultOptions, ...options };
            if (options.customClass) {
                finalOptions.customClass = { ...defaultOptions.customClass, ...options.customClass };
            }

            return Swal.fire(finalOptions);
        }

        // Handle the verification when page loads
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const oobCode = urlParams.get('oobCode');
                const mode = urlParams.get('mode');
                
                if (!oobCode || mode !== 'verifyEmail') {
                    throw { code: 'auth/invalid-action-code', message: 'Invalid verification link' };
                }
                
                // Show loading state with a slight delay for better UX
                await new Promise(resolve => setTimeout(resolve, 500));
                
                // 1. First, check the action code to get the email
                const actionCodeInfo = await auth.checkActionCode(oobCode);
                const email = actionCodeInfo.data.email;
                
                if (!email) {
                    throw { code: 'auth/invalid-action-code', message: 'Could not determine email from verification link' };
                }
                
                // 2. Apply the action code to verify the email
                await auth.applyActionCode(oobCode);
                
                // 3. Update Firestore if needed
                const usersRef = db.collection('users');
                const querySnapshot = await usersRef.where('email', '==', email).get();
                
                if (!querySnapshot.empty) {
                    const batch = db.batch();
                    querySnapshot.forEach(doc => {
                        batch.update(doc.ref, {
                            is_verified: true,
                            email_verified_at: firebase.firestore.FieldValue.serverTimestamp()
                        });
                    });
                    await batch.commit();
                }
                
                // 4. If user is logged in, reload their auth state
                const currentUser = auth.currentUser;
                if (currentUser && currentUser.email === email) {
                    await currentUser.reload();
                }
                
                // 5. Show success state with a slight delay
                setTimeout(() => {
                    showState('success');
                }, 1000);
                
            } catch (error) {
                console.error('Verification error:', error);
                
                const errorMessages = {
                    'auth/expired-action-code': 'This verification link has expired.',
                    'auth/invalid-action-code': 'This verification link is invalid or has already been used.',
                    'auth/user-disabled': 'Your account has been disabled.'
                };
                
                const message = errorMessages[error.code] || error.message || 'Verification failed. Please try again.';
                showState('error', message);
            }
        });
    </script>
</body>
</html>