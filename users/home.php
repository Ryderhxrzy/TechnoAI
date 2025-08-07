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
          <div class="input-wrapper">
              <textarea id="user-input" placeholder="Message Techno.ai" rows="1"></textarea>
              <button id="send-btn" onclick="sendMessage()">
                <i class="fas fa-arrow-up"></i>
              </button>
          </div>
      </div>

  <script src="../scripts/script.js"></script>