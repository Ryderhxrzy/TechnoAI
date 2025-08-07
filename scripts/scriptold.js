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

function formatBotResponse(text) {
  if (!text) return '';

  const escapeHtml = (unsafe) => {
    return unsafe.replace(/[&<>"']/g, (match) => {
      switch (match) {
        case '&': return '&amp;';
        case '<': return '&lt;';
        case '>': return '&gt;';
        case '"': return '&quot;';
        case "'": return '&#39;';
        default: return match;
      }
    });
  };

  const codeBlocks = [];
  let codeBlockIndex = 0;
  
  // First handle standard markdown code blocks (```language\ncode```)
  text = text.replace(/```([\w-]+)?\n?([\s\S]*?)```/g, (match, language, code) => {
    // Clean up the code content
    code = code.trim();
    const detectedLang = language || detectLanguage(code);
    const langDisplay = detectedLang.charAt(0).toUpperCase() + detectedLang.slice(1);
    
    const placeholder = `__CODE_BLOCK_${codeBlockIndex}__`;
    codeBlocks[codeBlockIndex] = `<div class="code-block">
      <div class="code-header">
        <span>${langDisplay}</span>
        <button class="copy-btn" data-code="${encodeURIComponent(code)}">
          <i class="fas fa-copy"></i> Copy
        </button>
      </div>
      <div class="code-content">
        <pre><code class="language-${detectedLang}">${escapeHtml(code)}</code></pre>
      </div>
    </div>`;
    
    codeBlockIndex++;
    return placeholder;
  });

  // Handle _CODEBLOCKX_ style placeholders (more robust extraction)
  text = text.replace(/_CODEBLOCK(\d+)_/g, (match, blockNum, offset) => {
    // Find the content after this placeholder until the next _CODEBLOCK or end of text
    const afterPlaceholder = text.substring(offset + match.length);
    const nextCodeBlockMatch = afterPlaceholder.match(/_CODEBLOCK\d+_/);
    
    let codeContent;
    if (nextCodeBlockMatch) {
      // Extract content between this and next _CODEBLOCK
      codeContent = afterPlaceholder.substring(0, nextCodeBlockMatch.index).trim();
    } else {
      // Extract content from here to end, but stop at common markdown patterns
      const endPatterns = /(?:\n\n[^`\s]|$)/;
      const endMatch = afterPlaceholder.match(endPatterns);
      codeContent = afterPlaceholder.substring(0, endMatch ? endMatch.index : afterPlaceholder.length).trim();
    }
    
    // Clean up the code content - remove explanatory text that might follow
    const lines = codeContent.split('\n');
    const codeLines = [];
    let inCode = true;
    
    for (let line of lines) {
      // If we hit an empty line followed by explanatory text, stop
      if (line.trim() === '' && codeLines.length > 0) {
        const nextNonEmptyIndex = lines.indexOf(line) + 1;
        if (nextNonEmptyIndex < lines.length) {
          const nextLine = lines[nextNonEmptyIndex];
          // Check if next line looks like explanation rather than code
          if (nextLine && !nextLine.match(/^[\s]*[{}();#\/\*\w\-\.\[\]<>=&|!+\-\*\/\\'"`:]/)) {
            inCode = false;
          }
        }
      }
      
      if (inCode && line.trim() !== '') {
        codeLines.push(line);
      } else if (line.trim() === '' && inCode) {
        codeLines.push(line); // Keep empty lines within code
      }
    }
    
    codeContent = codeLines.join('\n').trim();
    
    // Fallback if no content found
    if (!codeContent) {
      codeContent = `// Code block ${blockNum} - content extraction failed\n// Please check the original response`;
    }
    
    const detectedLang = detectLanguage(codeContent);
    const langDisplay = detectedLang.charAt(0).toUpperCase() + detectedLang.slice(1);
    
    const placeholder = `__CODE_BLOCK_${codeBlockIndex}__`;
    codeBlocks[codeBlockIndex] = `<div class="code-block">
      <div class="code-header">
        <span>${langDisplay}</span>
        <button class="copy-btn" data-code="${encodeURIComponent(codeContent)}">
          <i class="fas fa-copy"></i> Copy
        </button>
      </div>
      <div class="code-content">
        <pre><code class="language-${detectedLang}">${escapeHtml(codeContent)}</code></pre>
      </div>
    </div>`;
    
    codeBlockIndex++;
    return placeholder;
  });

  // Remove any remaining _CODEBLOCKX_ patterns and their content to avoid duplication
  text = text.replace(/_CODEBLOCK\d+_[\s\S]*?(?=_CODEBLOCK\d+_|$)/g, '');

  // Process inline code
  text = text.replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>');

  // Process tables
  text = text.replace(/^\|(.+)\|[\r\n]^\|([-:| ]+)+\|[\r\n]((?:^\|.+?\|[\r\n])+)/gm, (match, headers, alignments, rows) => {
    const headerCells = headers.split('|').map(h => h.trim());
    const rowLines = rows.trim().split('\n');
    
    let tableHtml = '<div class="table-container"><table class="markdown-table">';
    
    tableHtml += '<thead><tr>';
    headerCells.forEach(header => {
      if (header) tableHtml += `<th>${header}</th>`;
    });
    tableHtml += '</tr></thead>';
    
    tableHtml += '<tbody>';
    rowLines.forEach(row => {
      const cells = row.split('|').map(c => c.trim());
      tableHtml += '<tr>';
      cells.forEach(cell => {
        if (cell) tableHtml += `<td>${cell}</td>`;
      });
      tableHtml += '</tr>';
    });
    tableHtml += '</tbody></table></div>';
    
    return tableHtml;
  });

  // Process other markdown elements
  text = text.replace(/!\[(.*?)\]\((.*?)\)/g, '<img src="$2" alt="$1" class="markdown-image">');
  text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="markdown-link">$1</a>');
  text = text.replace(/^(\d+)\.\s+(.*)$/gm, '<li class="numbered">$2</li>');
  text = text.replace(/^[-*•]\s+(.*)$/gm, '<li class="bulleted">$1</li>');
  text = text.replace(/(<li class="numbered">[\s\S]*?<\/li>)+/g, match => 
    `<ol class="numbered-list" style="margin-left: 20px; padding-left: 20px;">${match}</ol>`);
  text = text.replace(/(<li class="bulleted">[\s\S]*?<\/li>)+/g, match => 
    `<ul class="bulleted-list" style="margin-left: 20px; padding-left: 20px; list-style-type: disc;">${match}</ul>`);
  text = text.replace(/^#\s+(.*)$/gm, '<h3 class="heading">$1</h3>');
  text = text.replace(/^##\s+(.*)$/gm, '<h4 class="subheading">$1</h4>');
  text = text.replace(/^###\s+(.*)$/gm, '<h5 class="subsubheading">$1</h5>');
  text = text.replace(/^>\s+(.*)$/gm, '<blockquote class="quote">$1</blockquote>');
  text = text.replace(/^[-*]{3,}$/gm, '<hr class="horizontal-rule">');
  text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
  text = text.replace(/_([^_]+)_/g, '<em>$1</em>');
  text = text.replace(/__([^_]+)__/g, '<strong>$1</strong>');
  text = text.replace(/\n\n+/g, '</p><p class="paragraph">');
  text = text.replace(/\n(?!\n)/g, ' ');

  // Wrap in paragraph if no block elements found
  if (!text.match(/<(h[1-6]|ol|ul|blockquote|table|div|hr|pre)/)) {
    text = `<p class="paragraph">${text}</p>`;
  }

  // Restore code blocks
  codeBlocks.forEach((codeBlock, index) => {
    text = text.replace(`__CODE_BLOCK_${index}__`, codeBlock);
  });

  // Clean up empty paragraphs and spacing
  text = text.replace(/<p class="paragraph"><\/p>/g, '');
  text = text.replace(/<p class="paragraph">\s*<\/p>/g, '');
  text = text.replace(/(<\/?(ul|ol|li)[^>]*>)\s+/g, '$1');
  text = text.replace(/\s+(<\/?(ul|ol|li)[^>]*>)/g, '$1');

  return text;
}

// Rest of your existing functions (sendMessage, typeMessage, etc.) remain unchanged
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('copy-btn') || e.target.closest('.copy-btn')) {
    const btn = e.target.closest('.copy-btn');
    const code = decodeURIComponent(btn.getAttribute('data-code'));
    navigator.clipboard.writeText(code).then(() => {
      const originalText = btn.textContent;
      btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
      }, 2000);
    });
  }
});

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

function typeMessage(element, text, speed = 30) {
  return new Promise((resolve) => {
    let i = 0;
    element.innerHTML = '';
    
    function typeChar() {
      if (i < text.length) {
        element.innerHTML = text.slice(0, i + 1);
        i++;
        const chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
        setTimeout(typeChar, speed);
      } else {
        if (window.Prism) {
          Prism.highlightAllUnder(element);
        }
        resolve();
      }
    }
    
    typeChar();
  });
}

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
  showTypingIndicator();
  chatBox.scrollTop = chatBox.scrollHeight;

  try {
    const response = await fetch("chatbot.php", {
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
      const formattedResponse = formatBotResponse(data.response);
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

// FIXED: Unified toggle function with proper chevron handling
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  
  if (window.innerWidth <= 768) {
    // Mobile behavior with smooth animation
    const isOpening = !sidebar.classList.contains('open');
    sidebar.classList.toggle('open');
    
    if (isOpening) {
      // Opening sidebar
      sidebar.style.transform = 'translateX(0)';
      if (overlay) {
        overlay.style.display = 'block';
        // Trigger reflow for smooth fade-in
        overlay.offsetHeight;
        overlay.style.opacity = '1';
      }
    } else {
      // Closing sidebar
      sidebar.style.transform = 'translateX(-100%)';
      if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => {
          if (!sidebar.classList.contains('open')) {
            overlay.style.display = 'none';
          }
        }, 300); // Match CSS transition duration
      }
    }
  } else {
    // Desktop behavior
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
  }
  
  // Update chevron icons after state change
  updateChevronIcons();
}

// FIXED: Proper chevron icon updates for all states
function updateChevronIcons() {
  const sidebar = document.getElementById('sidebar');
  const headerMinimizer = document.querySelector('.header-minimizer i');
  const sidebarMinimizer = document.querySelector('.sidebar-minimizer i');
  
  if (window.innerWidth <= 768) {
    // Mobile view - chevron direction based on open/closed state
    const isOpen = sidebar.classList.contains('open');
    
    if (headerMinimizer) {
      headerMinimizer.className = isOpen ? 'fas fa-chevron-left' : 'fas fa-chevron-right';
    }
    if (sidebarMinimizer) {
      sidebarMinimizer.className = 'fas fa-chevron-left'; // Always left on mobile when visible
    }
  } else {
    // Desktop view - chevron direction based on collapsed state
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

// FIXED: Simplified and corrected sidebar initialization
function initSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const headerMinimizer = document.querySelector('.header-minimizer');
  const sidebarMinimizer = document.querySelector('.sidebar-minimizer');

  // Set initial state based on screen size
  if (window.innerWidth <= 768) {
    // Mobile: closed by default, reset any desktop styles
    sidebar.classList.remove('collapsed', 'open');
    sidebar.style.transform = 'translateX(-100%)';
    if (overlay) overlay.style.display = 'none';
  } else {
    // Desktop: check localStorage for collapsed state
    sidebar.style.transform = ''; // Reset mobile transform
    if (overlay) overlay.style.display = 'none';
    
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
    } else {
      sidebar.classList.remove('collapsed');
    }
  }

  // Update chevron icons for initial state
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

  // Overlay click to close mobile sidebar
  if (overlay) {
    overlay.addEventListener('click', function() {
      if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
        toggleSidebar();
      }
    });
  }

  // Window resize handler with proper state management
  window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
      // Switching to desktop
      sidebar.classList.remove('open');
      sidebar.style.transform = '';
      if (overlay) {
        overlay.style.display = 'none';
        overlay.style.opacity = '';
      }
      
      // Restore desktop collapsed state
      const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
      if (isCollapsed) {
        sidebar.classList.add('collapsed');
      } else {
        sidebar.classList.remove('collapsed');
      }
    } else {
      // Switching to mobile
      sidebar.classList.remove('collapsed');
      if (!sidebar.classList.contains('open')) {
        sidebar.style.transform = 'translateX(-100%)';
      }
    }
    
    updateChevronIcons();
  });
}

document.getElementById('theme-toggle-btn').addEventListener('click', toggleTheme);

// Theme Toggle (cleaned up duplicate)
function toggleTheme1() {
  const body = document.body;
  const themeIcon = document.getElementById('theme-icon');
  
  if (body.getAttribute('data-theme') === 'dark') {
    body.removeAttribute('data-theme');
    themeIcon.className = 'fas fa-moon';
    localStorage.setItem('theme', 'light');
  } else {
    body.setAttribute('data-theme', 'dark');
    themeIcon.className = 'fas fa-sun';
    localStorage.setItem('theme', 'dark');
  }
}

// Initialize theme
function initTheme() {
  const savedTheme = localStorage.getItem('theme');
  const themeIcon = document.getElementById('theme-icon');
  
  if (savedTheme === 'dark') {
    document.body.setAttribute('data-theme', 'dark');
    themeIcon.className = 'fas fa-sun';
  } else {
    themeIcon.className = 'fas fa-moon';
  }
}