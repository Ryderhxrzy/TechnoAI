<?php include_once '../includes/main.php'; ?>

<div id="chat-box">
  <div class="welcome-message">
    <div class="welcome-icon">
      <i class="fas fa-robot"></i>
    </div>
    <h3>Welcome to Techno.ai, <?php echo htmlspecialchars($firstName); ?>!</h3>
    <p>I'm here to help you with coding, questions, and tasks. Ask me anything!</p>
  </div>
</div>

<div class="typing-indicator" id="typing-indicator">
  <div class="typing-dots">
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

<div class="input-container">
  <div class="input-wrapper" id="chat-input-wrapper">
    <textarea id="user-input" placeholder="Message Techno.ai" rows="1" aria-label="Message"></textarea>
    
    <div class="button-group">
      <button type="button" class="icon-btn" id="mic-btn" aria-label="Voice input">
        <i class="fas fa-microphone"></i>
      </button>
      
      <button type="button" class="icon-btn" id="send-btn" onclick="sendMessage()" aria-label="Send message">
        <i class="fas fa-arrow-up"></i>
      </button>
    </div>
    
    <div class="status" id="status"></div>
  </div>
</div>

<script src="../scripts/script.js"></script>