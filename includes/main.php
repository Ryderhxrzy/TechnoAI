<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

$user = $_SESSION['user'];

$fullName = $user['name'] ?? $user['email'];
$firstName = explode(' ', $fullName)[0];

$nameParts = explode(' ', $fullName);
$initials = '';
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
} else {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}
?>

<!DOCTYPE html>
<html data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Techno.ai</title>
  <link rel="stylesheet" href="../assets/styles.css">
  <link rel="stylesheet" href="../assets/sweetalert.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
  <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
  <div id="app-container">
    <div class="sidebar" id="sidebar">
      <button class="sidebar-minimizer">
        <i class="fas fa-chevron-right"></i>
      </button>
      <div class="sidebar-logo">
        <div class="logo-icon">
          <img src="../assets/images/logo.png" class="logo-icon" alt="">
        </div>
        <span class="logo-text">Techno.ai</span>
      </div>

      <button class="new-chat-btn" onclick="startNewChat()">
        <i class="fas fa-plus"></i>
        <span>New Chat</span>
      </button>

      <div class="chat-history" id="chat-history">
        <div class="chat-item active" data-chat-id="1">
          <div class="chat-title">Welcome Chat</div>
          <div class="chat-preview">How can I help you today?</div>
          <div class="chat-time">Just now</div>
        </div>
      </div>
      
      <div class="user-profile">
        <div class="profile-avatar">
          <?php if (!empty($user['picture'])): ?>
            <img src="<?php echo htmlspecialchars($user['picture']); ?>" 
                alt="<?php echo htmlspecialchars($user['name']); ?>"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="profile-avatar-fallback" style="display: none;">
              <?php echo $initials; ?>
            </div>
          <?php else: ?>
            <div class="profile-avatar-fallback">
              <?php echo $initials; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
          <div class="profile-status">
            <div class="status-dot"></div>
            Online
          </div>
        </div>
      </div>
    </div>

    <div class="chat-container">
      <div class="chat-header">
        <div class="header-info">
          <h2>
            <div class="header-minimizer" id="header-minimizer">
              <i class="fas fa-chevron-left"></i>
            </div>
            Minimize
          </h2>
        </div>
        <div class="header-actions">
          <button class="action-btn" id="theme-toggle-btn" title="Toggle dark/light mode">
            <i class="fas fa-moon"></i>
          </button>
        </div>
      </div>