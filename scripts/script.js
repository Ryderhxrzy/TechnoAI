const textarea = document.getElementById('user-input');
textarea.addEventListener('input', function () {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

textarea.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

function detectLanguage(code) {
  const patterns = {
    javascript: /(?:function|const|let|var|=>|console\.log|document\.|window\.)/i,
    python: /(?:def |import |from |print\(|if __name__|class |\.py)/i,
    java: /(?:public class|public static void|import java\.|System\.out)/i,
    html: /(?:<html|<div|<body|<head|<script|<!DOCTYPE)/i,
    css: /(?:\.[\w-]+\s*\{|#[\w-]+\s*\{|@media|background:|color:)/i,
    sql: /(?:SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE TABLE)/i,
    php: /(?:<\?php|\$\w+|function\s+\w+|echo\s+)/i,
    cpp: /(?:#include|int main\(|std::|cout <<|cin >>)/i,
    csharp: /(?:using System|public class|static void Main|Console\.WriteLine)/i
  };
  
  for (const [lang, pattern] of Object.entries(patterns)) {
    if (pattern.test(code)) {
      return lang;
    }
  }
  return 'text';
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    const copyBtns = document.querySelectorAll('.copy-btn');
    copyBtns.forEach(btn => {
      if (btn.onclick && btn.onclick.toString().includes(text.substring(0, 20))) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
          btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        }, 2000);
      }
    });
  }).catch(err => {
    console.error('Failed to copy text: ', err);
  });
}

function generateResponseTitle(userQuestion) {
  if (userQuestion.length > 50) {
    userQuestion = userQuestion.substring(0, 50) + '...';
  }
  
  const patterns = {
    code: /(gumawa ng code|paano gawin ang|how to code|write a code|create a|function|class)/i,
    explain: /(ano ang|what is|explain|ipaliwanag|meaning of|define)/i,
    compare: /(difference between|vs\.?|compare|pagkakaiba)/i,
    tutorial: /(paano|how to|steps|tutorial|guide|gabay)/i,
    error: /(error|fix|problem|issue|not working|ayaw gumana)/i
  };
  
  if (patterns.code.test(userQuestion)) {
    return "Code Implementation";
  } else if (patterns.explain.test(userQuestion)) {
    return "Explanation";
  } else if (patterns.compare.test(userQuestion)) {
    return "Comparison";
  } else if (patterns.tutorial.test(userQuestion)) {
    return "Step-by-Step Guide";
  } else if (patterns.error.test(userQuestion)) {
    return "Solution";
  }
  
  return "Response";
}

function highlightImportantTerms(text) {
  // Only highlight truly important terms, not common words
  const importantTerms = [
    'IMPORTANT', 'NOTE', 'WARNING', 'REMEMBER', 'CRITICAL',
    'MUST', 'NEVER', 'ALWAYS', 'REQUIRED', 'ESSENTIAL'
  ];
  
  importantTerms.forEach(term => {
    const regex = new RegExp(`\\b${term}\\b`, 'g'); // Removed 'i' flag to match exact case
    text = text.replace(regex, match => 
      `<span class="highlight-term">${match}</span>`
    );
  });
  
  return text;
}

function formatBotResponse(text, userQuestion) {
  const responseTitle = generateResponseTitle(userQuestion);
  
  let formattedText = `
    <div class="response-header">
      <div class="response-title-container">
        <h4 class="response-title">${responseTitle}</h4>
        <div class="response-divider"></div>
      </div>
    </div>
    <div class="response-content">
  `;

  // Handle code blocks first
  const codeBlocks = [];
  let codeBlockIndex = 0;
  
  text = text.replace(/```(\w+)?\n?([\s\S]*?)```/g, (match, language, code) => {
    const detectedLang = language || detectLanguage(code);
    const langDisplay = detectedLang.charAt(0).toUpperCase() + detectedLang.slice(1);

    const placeholder = `__CODE_BLOCK_${codeBlockIndex}__`;
    codeBlocks[codeBlockIndex] = `
    <div class="code-block-wrapper">
      <div class="code-block">
        <div class="code-header">
          <span class="code-language">${langDisplay}</span>
          <button class="copy-btn" onclick="copyToClipboard('${escapeHtml(code).replace(/'/g, "\\'")}')">
            <i class="fas fa-copy"></i> Copy
          </button>
        </div>
        <div class="code-content">
          <pre><code class="language-${detectedLang}">${escapeHtml(code)}</code></pre>
        </div>
      </div>
    </div>`;
    
    codeBlockIndex++;
    return placeholder;
  });

  // Apply highlighting to important terms (only truly important ones)
  text = highlightImportantTerms(text);

  // More selective markdown formatting
  text = text
    .replace(/\n\n/g, '</p><p class="response-paragraph">')
    .replace(/\n(?!\n)/g, '<br>')
    // Only bold text that's explicitly marked with ** 
    .replace(/\*\*(.*?)\*\*/g, '<strong class="response-bold">$1</strong>')
    // Only italic text that's explicitly marked with single *
    .replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em class="response-italic">$1</em>')
    // Headers
    .replace(/^#\s(.*$)/gm, '<h3 class="response-h3">$1</h3>')
    .replace(/^##\s(.*$)/gm, '<h4 class="response-h4">$1</h4>')
    // Lists
    .replace(/^\s*[-*]\s(.*$)/gm, '<li class="response-li">$1</li>')
    // Blockquotes
    .replace(/^>\s(.*$)/gm, '<blockquote class="response-quote">$1</blockquote>');

  // Add dividers after headers
  text = text.replace(/<\/h3>/g, '</h3><div class="section-divider"></div>');
  text = text.replace(/<\/h4>/g, '</h4><div class="section-divider"></div>');

  // Wrap in paragraph if no special formatting
  if (!text.includes('<h3') && !text.includes('<h4') && !text.includes('<blockquote') && !text.includes('<li')) {
    text = `<p class="response-paragraph">${text}</p>`;
  }

  // Group list items into ul tags
  text = text.replace(/(<li class="response-li">.*?<\/li>\s*)+/g, (match) => {
    return `<ul class="response-ul">${match}</ul>`;
  });

  formattedText += text + '</div>';

  // Replace code block placeholders
  codeBlocks.forEach((codeBlock, index) => {
    formattedText = formattedText.replace(`__CODE_BLOCK_${index}__`, codeBlock);
  });

  return formattedText;
}

function formatMarkdownElements(text) {
  return text
    .replace(/\n\n/g, '</p><p class="response-paragraph">')
    .replace(/\n(?!\n)/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<strong class="response-bold">$1</strong>')
    .replace(/(?<!\*)\*([^*\n]+?)\*(?!\*)/g, '<em class="response-italic">$1</em>')
    .replace(/^#\s(.*$)/gm, '<h3 class="response-h3">$1</h3>')
    .replace(/^##\s(.*$)/gm, '<h4 class="response-h4">$1</h4>')
    .replace(/^\s*-\s(.*$)/gm, '<li class="response-li">$1</li>')
    .replace(/^\s*\*\s(.*$)/gm, '<li class="response-li">$1</li>')
    .replace(/^>\s(.*$)/gm, '<blockquote class="response-quote">$1</blockquote>')
    .replace(/<\/h3>/g, '</h3><div class="section-divider"></div>')
    .replace(/<\/h4>/g, '</h4><div class="section-divider"></div>');
}

function createCodeBlock(code, language) {
  const langDisplay = language.charAt(0).toUpperCase() + language.slice(1);
  return `
  <div class="code-block-wrapper">
    <div class="code-block">
      <div class="code-header">
        <span class="code-language">${langDisplay}</span>
        <button class="copy-btn" onclick="copyToClipboard('${escapeHtml(code).replace(/'/g, "\\'")}')">
          <i class="fas fa-copy"></i> Copy
        </button>
      </div>
      <div class="code-content">
        <pre><code class="language-${language}">${escapeHtml(code)}</code></pre>
      </div>
    </div>
  </div>`;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function showTypingIndicator() {
  const indicator = document.getElementById('typing-indicator');
  const chatBox = document.getElementById('chat-box');
  indicator.style.display = 'flex';
  chatBox.appendChild(indicator);
  chatBox.scrollTop = chatBox.scrollHeight;
}

function hideTypingIndicator() {
  const indicator = document.getElementById('typing-indicator');
  indicator.style.display = 'none';
}

async function typeMessage(element, text, speed = 30) {
  return new Promise((resolve) => {
    let i = 0;
    element.innerHTML = '';
    
    // Extract code blocks and replace with placeholders
    const codeBlocks = [];
    let codeBlockIndex = 0;
    text = text.replace(/<div class="code-block-wrapper">[\s\S]*?<\/div>/g, (match) => {
      const placeholder = `__CODE_BLOCK_${codeBlockIndex}__`;
      codeBlocks[codeBlockIndex] = match;
      codeBlockIndex++;
      return placeholder;
    });

    function typeChar() {
      if (i < text.length) {
        let currentText = text.slice(0, i + 1);
        
        // Insert any complete code blocks that are in the current text
        for (let j = 0; j < codeBlocks.length; j++) {
          const placeholder = `__CODE_BLOCK_${j}__`;
          if (currentText.includes(placeholder)) {
            currentText = currentText.replace(placeholder, codeBlocks[j]);
          }
        }
        
        element.innerHTML = currentText;
        i++;
        
        // Highlight any newly inserted code blocks
        if (window.Prism) {
          const newCodeBlocks = element.querySelectorAll('pre code');
          newCodeBlocks.forEach(block => {
            if (!block.hasAttribute('data-prism-highlighted')) {
              Prism.highlightElement(block);
            }
          });
        }
        
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
        setTimeout(typeChar, speed);
      } else {
        resolve();
      }
    }
    
    typeChar();
  });
}

// Update the autoResize function in your script.js
function autoResize() {
  const textarea = document.getElementById('user-input');
  const wrapper = document.getElementById('chat-input-wrapper');
  
  // Reset height to calculate scroll height properly
  textarea.style.height = 'auto';
  wrapper.style.height = 'auto';
  
  // Calculate heights
  const minHeight = 130; // Minimum height of wrapper
  const maxHeight = 300; // Maximum height of wrapper
  const textareaScrollHeight = textarea.scrollHeight;
  
  // Calculate new height for textarea (limited to show scrollbar when needed)
  const textareaHeight = Math.min(Math.max(textareaScrollHeight, 24), 144);
  
  // Calculate new height for wrapper
  const wrapperHeight = Math.min(Math.max(textareaScrollHeight + 32, minHeight), maxHeight);
  
  // Apply heights
  textarea.style.height = `${textareaHeight}px`;
  wrapper.style.height = `${wrapperHeight}px`;
  
  // Show scrollbar if content exceeds max height
  textarea.style.overflowY = textareaScrollHeight > 144 ? 'auto' : 'hidden';
}

// Event listeners for auto-resize
document.getElementById('user-input').addEventListener('input', autoResize);
document.getElementById('user-input').addEventListener('paste', () => {
  setTimeout(autoResize, 10);
});

// Initialize on load
window.addEventListener('load', autoResize);

async function sendMessage() {
  const userInput = textarea.value.trim();
  const sendBtn = document.getElementById('send-btn');
  if (userInput === "") return;

  const chatBox = document.getElementById('chat-box');

  const welcomeMessage = document.querySelector('.welcome-message');
  if (welcomeMessage) {
    welcomeMessage.remove();
  }

  const userMessage = document.createElement('div');
  userMessage.className = 'message user-message';
  userMessage.textContent = userInput;
  chatBox.appendChild(userMessage);

  textarea.value = '';
  textarea.style.height = 'auto';
  sendBtn.disabled = true;
  autoResize();
  showTypingIndicator();
  chatBox.scrollTop = chatBox.scrollHeight;

  try {
    const response = await fetch("dwa4P1/chatbot.php", {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: userInput })
    });
    
    const data = await response.json();
    hideTypingIndicator();
    
    const botMessage = document.createElement('div');
    botMessage.className = 'message bot-message';
    chatBox.appendChild(botMessage);

    if (data.error) {
      botMessage.innerHTML = `<div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <span>${data.error}</span>
      </div>`;
    } else {
      const formattedResponse = formatBotResponse(data.response, userInput);
      await typeMessage(botMessage, formattedResponse, 20);
    }

    chatBox.scrollTop = chatBox.scrollHeight;
    sendBtn.disabled = false;
    
  } catch (error) {
    hideTypingIndicator();
    const errorMessage = document.createElement('div');
    errorMessage.className = 'message bot-message error-message';
    errorMessage.innerHTML = `
      <div class="error-message">
        <i class="fas fa-wifi"></i>
        <span>Connection error. Please try again.</span>
      </div>
    `;
    chatBox.appendChild(errorMessage);
    chatBox.scrollTop = chatBox.scrollHeight;
    sendBtn.disabled = false;
  }
}


function startNewChat() {
  const chatBox = document.getElementById('chat-box');
  chatBox.innerHTML = `
    <div class="welcome-message">
      <div class="welcome-icon">
        <i class="fas fa-robot"></i>
      </div>
      <h3>New Conversation Started!</h3>
      <p>What would you like to talk about today?</p>
    </div>
  `;
  
  document.querySelectorAll('.chat-item').forEach(item => {
    item.classList.remove('active');
  });
  
  textarea.focus();
}

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (window.innerWidth <= 768) {
    const isOpening = !sidebar.classList.contains('open');
    sidebar.classList.toggle('open');
    
    if (isOpening) {
      sidebar.style.transform = 'translateX(0)';
      if (overlay) {
        overlay.style.display = 'block';
        overlay.offsetHeight;
        overlay.style.opacity = '1';
      }
    } else {
      sidebar.style.transform = 'translateX(-100%)';
      if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
          if (!sidebar.classList.contains('open')) {
            overlay.style.display = 'none';
          }
        }, 300);
      }
    }
  } else {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
  }
  
  updateChevronIcons();
}

function updateChevronIcons() {
  const sidebar = document.getElementById('sidebar');
  const headerMinimizer = document.querySelector('.header-minimizer i');
  const sidebarMinimizer = document.querySelector('.sidebar-minimizer i');
  
  if (window.innerWidth <= 768) {
    const isOpen = sidebar.classList.contains('open');
    
    if (headerMinimizer) {
      headerMinimizer.className = isOpen ? 'fas fa-chevron-left' : 'fas fa-chevron-right';
    }
    if (sidebarMinimizer) {
      sidebarMinimizer.className = 'fas fa-chevron-left';
    }
  } else {
    const isCollapsed = sidebar.classList.contains('collapsed');
    
    if (headerMinimizer) {
      headerMinimizer.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    }
    if (sidebarMinimizer) {
      sidebarMinimizer.className = isCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    }
  }
}

document.querySelectorAll('.chat-item').forEach(item => {
  item.addEventListener('click', function() {
    document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
    
    const chatBox = document.getElementById('chat-box');
    const chatTitle = this.querySelector('.chat-title').textContent;
    chatBox.innerHTML = `
      <div class="welcome-message">
        <div class="welcome-icon">
          <i class="fas fa-robot"></i>
        </div>
        <h3>${chatTitle}</h3>
        <p>This is a static demo. In a real implementation, this would load the chat history.</p>
      </div>
    `;
  });
});

window.addEventListener('load', () => textarea.focus());

function toggleTheme() {
  const html = document.documentElement;
  const themeBtn = document.getElementById('theme-toggle-btn');
  const currentTheme = html.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);

  if (newTheme === 'dark') {
    themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
  } else {
    themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
  }
}

window.addEventListener('DOMContentLoaded', () => {
  // Theme initialization
  const html = document.documentElement;
  const themeBtn = document.getElementById('theme-toggle-btn');
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) {
    html.setAttribute('data-theme', savedTheme);
  }
  const currentTheme = html.getAttribute('data-theme');
  if (currentTheme === 'dark') {
    themeBtn.innerHTML = '<i class="fas fa-sun"></i>';
  } else {
    themeBtn.innerHTML = '<i class="fas fa-moon"></i>';
  }
  
  // Initialize sidebar functionality
  initSidebar();
});

function initSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const headerMinimizer = document.querySelector('.header-minimizer');
  const sidebarMinimizer = document.querySelector('.sidebar-minimizer');

  // Set initial state based on screen size
  if (window.innerWidth <= 768) {
    sidebar.classList.remove('collapsed', 'open');
    sidebar.style.transform = 'translateX(-100%)';
    if (overlay) overlay.style.display = 'none';
  } else {
    sidebar.style.transform = '';
    if (overlay) overlay.style.display = 'none';
    
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
    } else {
      sidebar.classList.remove('collapsed');
    }
  }

  updateChevronIcons();

  // Event listeners
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (headerMinimizer) {
    headerMinimizer.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (sidebarMinimizer) {
    sidebarMinimizer.addEventListener('click', function(e) {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function() {
      if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
        toggleSidebar();
      }
    });
  }

  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
      sidebar.classList.remove('open');
      sidebar.style.transform = '';
      if (overlay) {
        overlay.style.display = 'none';
        overlay.style.opacity = '';
      }
      
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      if (isCollapsed) {
        sidebar.classList.add('collapsed');
      } else {
        sidebar.classList.remove('collapsed');
      }
    } else {
      sidebar.classList.remove('collapsed');
      if (!sidebar.classList.contains('open')) {
        sidebar.style.transform = 'translateX(-100%)';
      }
    }
    
    updateChevronIcons();
  });
}

document.getElementById('theme-toggle-btn').addEventListener('click', toggleTheme);

// Enhanced Logout functionality
function setupLogout() {
  const userProfile = document.querySelector('.user-profile');
  const profileAvatar = userProfile.querySelector('.profile-avatar');
  const profileInfo = userProfile.querySelector('.profile-info');
  
  // Create logout dropdown
  const logoutDropdown = document.createElement('div');
  logoutDropdown.className = 'logout-dropdown';
  logoutDropdown.innerHTML = `
    
    <div class="items profile-link">
      <i class="fas fa-settings"></i>
      <span>Settings</span>
    </div>
    <div class="items">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </div>
    
  `;
  
  // Toggle dropdown when clicking profile
  profileAvatar.addEventListener('click', function(e) {
    e.stopPropagation();
    logoutDropdown.style.display = logoutDropdown.style.display === 'block' ? 'none' : 'block';
  });
  
  profileInfo.addEventListener('click', function(e) {
    e.stopPropagation();
    logoutDropdown.style.display = logoutDropdown.style.display === 'block' ? 'none' : 'block';
  });
  
  // Add dropdown to profile
  userProfile.appendChild(logoutDropdown);
  
  logoutDropdown.querySelector('.profile-link').addEventListener('click', function(e) {
    e.stopPropagation();
    window.location.href = 'settings.php';
  });

  // Handle logout click
  logoutDropdown.querySelector('.logout-item').addEventListener('click', function(e) {
    e.stopPropagation();
    showLogoutConfirmation();
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function() {
    logoutDropdown.style.display = 'none';
  });
  
  // Close dropdown when scrolling
  window.addEventListener('scroll', function() {
    logoutDropdown.style.display = 'none';
  }, true);
}

// Enhanced SweetAlert confirmation
function showLogoutConfirmation() {
  const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
  
  Swal.fire({
    title: 'Logout?',
    html: '<div style="margin-bottom:15px;">Are you sure you want to sign out?</div>',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Logout',
    cancelButtonText: 'Cancel',
    background: isDarkMode ? 'var(--bg-elevated)' : '#ffffff',
    color: isDarkMode ? 'var(--text-primary)' : '#1a1a1a',
    customClass: {
      popup: 'logout-swal',
      title: 'logout-swal-title',
      htmlContainer: 'logout-swal-content',
      actions: 'logout-swal-actions',
      confirmButton: 'logout-swal-confirm',
      cancelButton: 'logout-swal-cancel'
    },
    buttonsStyling: false,
    showClass: {
      popup: 'swal2-show',
      backdrop: 'swal2-backdrop-show',
      icon: 'swal2-icon-show'
    },
    hideClass: {
      popup: 'swal2-hide',
      backdrop: 'swal2-backdrop-hide',
      icon: 'swal2-icon-hide'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      //window.location.href = '/google/authentication/auth_handler.php?action=logout'; deployed
      window.location.href = '../logout.php';
    }
  });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  setupLogout();
});