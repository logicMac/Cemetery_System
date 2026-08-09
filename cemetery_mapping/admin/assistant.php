<?php
session_start();
require_once 'includes/header.php';
?>

<?php require_once 'includes/sidebar.php'; ?>

<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <img src="../assets/images/ai-assistant-logo.svg" alt="AI Assistant" style="width: 48px; height: 48px;">
            <div>
                <h2 style="margin: 0 0 8px 0;">AI Assistant</h2>
                <p style="color: var(--zinc-400); margin: 0;">Ask questions about cemetery statistics, operations, and analytics</p>
            </div>
        </div>
        <button onclick="clearChat()" class="btn-secondary">
            <svg style="display: inline-block; width: 20px; height: 20px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Clear Chat
        </button>
    </div>
    
    <!-- Chat Container -->
    <div style="background: rgba(0, 0, 0, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 20px; min-height: 500px; max-height: 600px; overflow-y: auto;" id="chatContainer">
        <div class="chat-message assistant" style="background: rgba(102, 126, 234, 0.1); padding: 16px; border-radius: 8px; margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
            <img src="../assets/images/ai-assistant-logo.svg" alt="AI" style="width: 40px; height: 40px; flex-shrink: 0; margin-top: 2px;">
            <div>
                <p style="margin: 0; font-weight: 600; margin-bottom: 8px;">Hello, Admin!</p>
                <p style="margin: 0;">I'm your AI assistant. I can help you with:</p>
                <ul style="margin: 12px 0 0 20px; color: var(--zinc-400);">
                    <li>Cemetery statistics and analytics</li>
                    <li>Burial record queries</li>
                    <li>Available plot information</li>
                    <li>Operational insights and trends</li>
                    <li>Data analysis and reporting</li>
                </ul>
                <p style="margin: 12px 0 0 0; color: var(--zinc-400); font-size: 0.9rem;">
                    Try asking: "How many burials this month?" or "Show me premium plot statistics"
                </p>
            </div>
        </div>
    </div>
    
    <!-- Input Area -->
    <div style="display: flex; gap: 12px;">
        <input 
            type="text" 
            id="assistantInput" 
            class="input-field" 
            placeholder="Ask me anything about the cemetery data..."
            onkeypress="handleKeyPress(event)"
            style="flex: 1;"
        >
        <button onclick="sendMessage()" class="btn-primary" id="sendBtn">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
        </button>
    </div>
    
    <!-- Quick Actions -->
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--glass-border);">
        <p style="color: var(--zinc-400); font-size: 0.9rem; margin-bottom: 12px;">Quick Questions:</p>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <button onclick="askQuestion('How many total burial records do we have?')" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
                Total Records
            </button>
            <button onclick="askQuestion('Show me statistics by barangay')" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
                By Barangay
            </button>
            <button onclick="askQuestion('How many available plots are there?')" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
                Available Plots
            </button>
            <button onclick="askQuestion('What are the burial trends this year?')" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
                Yearly Trends
            </button>
            <button onclick="askQuestion('Show me premium vs standard plot ratio')" class="btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
                Premium Stats
            </button>
        </div>
    </div>
</div>

        </main>
    </div>
    
    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <style>
        /* AI Logo Animations */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.9;
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Animate AI logo in header */
        .glass-card > div:first-child img {
            animation: pulse 3s ease-in-out infinite;
        }
        
        /* Chat message styling */
        .chat-message {
            animation: slideIn 0.3s ease;
        }
        
        /* AI logo in messages subtle hover effect */
        .chat-message.assistant img {
            transition: transform 0.3s ease;
        }
        
        .chat-message.assistant:hover img {
            transform: scale(1.1);
        }
    </style>
    <script>
        const chatContainer = document.getElementById('chatContainer');
        const input = document.getElementById('assistantInput');
        const sendBtn = document.getElementById('sendBtn');
        
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        function askQuestion(question) {
            input.value = question;
            sendMessage();
        }
        
        async function sendMessage() {
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            addMessage(message, 'user');
            input.value = '';
            
            // Disable input while processing
            input.disabled = true;
            sendBtn.disabled = true;
            
            // Add typing indicator
            const typingId = addMessage('Analyzing data...', 'assistant', true);
            
            try {
                const response = await fetch('../api/assistant_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                
                const data = await response.json();
                
                // Remove typing indicator
                document.getElementById(typingId).remove();
                
                if (data.success) {
                    addMessage(data.response, 'assistant');
                    
                    // If there's data visualization, add it
                    if (data.chart_data) {
                        addChartVisualization(data.chart_data);
                    }
                } else {
                    addMessage('I apologize, but I encountered an error processing your request. Please try again.', 'assistant');
                }
            } catch (error) {
                document.getElementById(typingId).remove();
                addMessage('I\'m having trouble connecting right now. Please check your Groq API configuration.', 'assistant');
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                input.focus();
            }
        }
        
        function addMessage(text, sender, isTyping = false) {
            const messageId = 'msg-' + Date.now();
            const messageDiv = document.createElement('div');
            messageDiv.id = messageId;
            messageDiv.className = 'chat-message ' + sender;
            
            const bgColor = sender === 'user' 
                ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' 
                : 'rgba(255, 255, 255, 0.05)';
            
            messageDiv.style.cssText = `
                background: ${bgColor};
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 12px;
                animation: slideIn 0.3s ease;
                ${isTyping ? 'opacity: 0.7;' : ''}
                ${sender === 'assistant' ? 'display: flex; gap: 12px; align-items: start;' : ''}
            `;
            
            // For assistant messages, add logo
            if (sender === 'assistant') {
                const logo = document.createElement('img');
                logo.src = '../assets/images/ai-assistant-logo.svg';
                logo.alt = 'AI';
                logo.style.cssText = 'width: 32px; height: 32px; flex-shrink: 0; margin-top: 2px;';
                messageDiv.appendChild(logo);
                
                const textDiv = document.createElement('div');
                textDiv.innerHTML = formatMessage(text);
                messageDiv.appendChild(textDiv);
            } else {
                // Format text with markdown-like syntax
                const formattedText = formatMessage(text);
                messageDiv.innerHTML = formattedText;
            }
            
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
            
            return messageId;
        }
        
        function formatMessage(text) {
            // Simple markdown-like formatting
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/\n/g, '<br>');
            
            // Format lists
            text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
            if (text.includes('<li>')) {
                text = text.replace(/(<li>.*<\/li>)/s, '<ul style="margin: 8px 0 8px 20px;">$1</ul>');
            }
            
            return text;
        }
        
        function addChartVisualization(chartData) {
            const chartDiv = document.createElement('div');
            chartDiv.style.cssText = `
                background: rgba(255, 255, 255, 0.05);
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 12px;
            `;
            
            // Simple bar chart visualization
            let chartHTML = '<div style="margin-top: 12px;">';
            const maxValue = Math.max(...Object.values(chartData));
            
            for (const [label, value] of Object.entries(chartData)) {
                const percentage = (value / maxValue) * 100;
                chartHTML += `
                    <div style="margin-bottom: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span style="font-size: 0.9rem;">${label}</span>
                            <span style="font-size: 0.9rem; font-weight: 600;">${value}</span>
                        </div>
                        <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                            <div style="width: ${percentage}%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                        </div>
                    </div>
                `;
            }
            chartHTML += '</div>';
            
            chartDiv.innerHTML = chartHTML;
            chatContainer.appendChild(chartDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        function clearChat() {
            themeUtils.confirm(
                'Are you sure you want to clear the chat history?',
                () => {
                    chatContainer.innerHTML = `
                        <div class="chat-message assistant" style="background: rgba(102, 126, 234, 0.1); padding: 16px; border-radius: 8px; margin-bottom: 12px; display: flex; gap: 12px; align-items: start;">
                            <img src="../assets/images/ai-assistant-logo.svg" alt="AI" style="width: 32px; height: 32px; flex-shrink: 0; margin-top: 2px;">
                            <div>
                                <p style="margin: 0;">Chat cleared. How can I help you?</p>
                            </div>
                        </div>
                    `;
                }
            );
        }
    </script>
</body>
</html>
