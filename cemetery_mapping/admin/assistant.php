<?php
session_start();
require_once 'includes/header.php';
require_once '../config/database.php';

// Fetch quick stats for the welcome panel
try {
    $totalRecords = $pdo->query("SELECT COUNT(*) FROM burial_records")->fetchColumn();
    $totalPlots = $pdo->query("SELECT COUNT(*) FROM available_plots")->fetchColumn();
    $totalReservations = $pdo->query("SELECT COUNT(*) FROM plot_reservations")->fetchColumn();
} catch (PDOException $e) {
    $totalRecords = $totalPlots = $totalReservations = 0;
}
?>

<?php require_once 'includes/sidebar.php'; ?>

<style>
.admin-layout { background: #ffffff; }
.admin-layout::after { display: none; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.05); opacity: 0.9; } }
@keyframes typing { 0%, 60%, 100% { opacity: 0.3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
.animate-fade { animation: fadeUp 0.5s ease both; }
.chat-message { animation: slideIn 0.3s ease; }
.typing-dot { animation: typing 1.4s infinite; }
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
.status-online { animation: blink 2s infinite; }
button svg, a svg, button i, a i { pointer-events: none; }
#chatContainer::-webkit-scrollbar { width: 6px; }
#chatContainer::-webkit-scrollbar-track { background: transparent; }
#chatContainer::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
#chatContainer::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.msg-actions { opacity: 0; transition: opacity 0.2s ease; }
.chat-message:hover .msg-actions { opacity: 1; }
.suggestion-card { transition: all 0.25s ease; }
.suggestion-card:hover { transform: translateY(-2px); }
.shimmer-bg { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
</style>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6 animate-fade flex-wrap gap-3">
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
            <i data-lucide="sparkles" class="w-5 h-5"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">AI Assistant <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full"><span class="status-online w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online</span></h2>
            <p class="text-sm text-slate-500">Powered by Groq AI — cemetery statistics, operations, and analytics</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="exportChat()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="download" class="w-4 h-4"></i> Export
        </button>
        <button onclick="clearChat()" class="inline-flex items-center gap-2 rounded-lg bg-white border border-slate-300 hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-slate-700 text-sm font-semibold px-4 py-2.5 transition">
            <i data-lucide="trash-2" class="w-4 h-4"></i> Clear
        </button>
    </div>
</div>

<!-- Main Layout: Chat + Sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-5 animate-fade">

    <!-- Chat Area (3 cols) -->
    <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col" style="min-height: 600px;">
        <!-- Chat Header Bar -->
        <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI" class="w-8 h-8 rounded-lg">
                <div>
                    <p class="text-sm font-bold text-slate-900">Cemetery AI</p>
                    <p class="text-xs text-slate-400">Typically responds instantly</p>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                <span id="msgCount">0</span> messages
            </div>
        </div>

        <!-- Chat Container -->
        <div id="chatContainer" class="flex-1 overflow-y-auto p-5 space-y-3" style="min-height: 420px; max-height: 520px;">
            <div class="chat-message assistant flex gap-3 items-start p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                <img src="../assets/images/ai-assistant-logo.svg" alt="AI" class="w-9 h-9 flex-shrink-0 mt-0.5 rounded-lg">
                <div class="text-sm text-slate-700 flex-1">
                    <p class="font-bold text-slate-900 mb-2">Hello, Admin! Welcome back.</p>
                    <p class="mb-3">I'm your AI assistant. I can help you with:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                        <div class="flex items-center gap-2 text-xs text-slate-600"><i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-emerald-600"></i> Cemetery statistics & analytics</div>
                        <div class="flex items-center gap-2 text-xs text-slate-600"><i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600"></i> Burial record queries</div>
                        <div class="flex items-center gap-2 text-xs text-slate-600"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-emerald-600"></i> Available plot information</div>
                        <div class="flex items-center gap-2 text-xs text-slate-600"><i data-lucide="trending-up" class="w-3.5 h-3.5 text-emerald-600"></i> Operational insights & trends</div>
                    </div>
                    <p class="text-xs text-slate-500 italic">Try asking: "How many burials this month?" or "Show me premium plot statistics"</p>
                    <p class="text-[10px] text-slate-400 mt-2" data-timestamp="<?php echo date('c'); ?>"></p>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-slate-100 p-4">
            <div class="flex gap-3 items-end">
                <div class="flex-1 relative">
                    <textarea
                        id="assistantInput"
                        rows="1"
                        placeholder="Ask me anything about the cemetery data..."
                        oninput="autoResize(this); updateCharCount()"
                        onkeydown="handleKeyDown(event)"
                        class="w-full rounded-xl border border-slate-300 pl-4 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 focus:outline-none transition resize-none"
                        style="max-height: 120px;"
                    ></textarea>
                </div>
                <button onclick="sendMessage()" id="sendBtn" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-3 transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-lucide="send" class="w-4 h-4" id="sendIcon"></i>
                    <span id="sendLabel" class="hidden sm:inline">Send</span>
                </button>
            </div>
            <div class="flex items-center justify-between mt-2 text-xs text-slate-400">
                <span class="flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-mono">Enter</kbd> to send • <kbd class="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 text-[10px] font-mono">Shift+Enter</kbd> for new line</span>
                <span id="charCount">0 / 500</span>
            </div>
        </div>
    </div>

    <!-- Sidebar (1 col) -->
    <div class="lg:col-span-1 space-y-4">
        <!-- Quick Stats -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3 flex items-center gap-1.5"><i data-lucide="database" class="w-3.5 h-3.5 text-emerald-600"></i> Data Snapshot</p>
            <div class="space-y-2.5">
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-emerald-50">
                    <span class="text-xs text-slate-600 flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600"></i> Records</span>
                    <span class="text-sm font-bold text-slate-900"><?php echo number_format($totalRecords); ?></span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-blue-50">
                    <span class="text-xs text-slate-600 flex items-center gap-1.5"><i data-lucide="map-pin" class="w-3.5 h-3.5 text-blue-600"></i> Plots</span>
                    <span class="text-sm font-bold text-slate-900"><?php echo number_format($totalPlots); ?></span>
                </div>
                <div class="flex items-center justify-between p-2.5 rounded-lg bg-amber-50">
                    <span class="text-xs text-slate-600 flex items-center gap-1.5"><i data-lucide="calendar-check" class="w-3.5 h-3.5 text-amber-600"></i> Reservations</span>
                    <span class="text-sm font-bold text-slate-900"><?php echo number_format($totalReservations); ?></span>
                </div>
            </div>
        </div>

        <!-- Suggested Prompts -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3 flex items-center gap-1.5"><i data-lucide="lightbulb" class="w-3.5 h-3.5 text-emerald-600"></i> Suggested Prompts</p>
            <div class="space-y-2">
                <button onclick="askQuestion('How many total burial records do we have?')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Total Records</p>
                            <p class="text-[11px] text-slate-500">Count all burial records</p>
                        </div>
                    </div>
                </button>
                <button onclick="askQuestion('Show me statistics by barangay')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="map" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">By Barangay</p>
                            <p class="text-[11px] text-slate-500">Breakdown by location</p>
                        </div>
                    </div>
                </button>
                <button onclick="askQuestion('How many available plots are there?')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Available Plots</p>
                            <p class="text-[11px] text-slate-500">Check plot inventory</p>
                        </div>
                    </div>
                </button>
                <button onclick="askQuestion('What are the burial trends this year?')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="trending-up" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Yearly Trends</p>
                            <p class="text-[11px] text-slate-500">Burial patterns over time</p>
                        </div>
                    </div>
                </button>
                <button onclick="askQuestion('Show me premium vs standard plot ratio')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="crown" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Premium Stats</p>
                            <p class="text-[11px] text-slate-500">Premium vs standard ratio</p>
                        </div>
                    </div>
                </button>
                <button onclick="askQuestion('Give me a summary of recent reservations')" class="suggestion-card w-full text-left p-3 rounded-lg bg-slate-50 hover:bg-emerald-50 hover:border-emerald-200 border border-slate-200 transition">
                    <div class="flex items-start gap-2">
                        <i data-lucide="calendar-check" class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Reservations</p>
                            <p class="text-[11px] text-slate-500">Recent reservation summary</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Capabilities -->
        <div class="bg-gradient-to-br from-emerald-50 to-white rounded-2xl border border-emerald-100 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 mb-3 flex items-center gap-1.5"><i data-lucide="info" class="w-3.5 h-3.5"></i> Capabilities</p>
            <ul class="space-y-2 text-xs text-slate-600">
                <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 flex-shrink-0"></i> Natural language queries</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 flex-shrink-0"></i> Real-time database access</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 flex-shrink-0"></i> Data visualization charts</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 flex-shrink-0"></i> Trend analysis & insights</li>
                <li class="flex items-start gap-2"><i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 flex-shrink-0"></i> Statistical breakdowns</li>
            </ul>
        </div>
    </div>
</div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/theme.js"></script>
    <script>
        const chatContainer = document.getElementById('chatContainer');
        const input = document.getElementById('assistantInput');
        const sendBtn = document.getElementById('sendBtn');
        let messageCount = 0;
        let isProcessing = false;

        // Auto-resize textarea
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
        }

        function updateCharCount() {
            const len = input.value.length;
            const counter = document.getElementById('charCount');
            counter.textContent = len + ' / 500';
            if (len > 500) {
                counter.classList.add('text-rose-500');
                counter.classList.remove('text-slate-400');
            } else {
                counter.classList.remove('text-rose-500');
                counter.classList.add('text-slate-400');
            }
        }

        function handleKeyDown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }

        function askQuestion(question) {
            input.value = question;
            updateCharCount();
            autoResize(input);
            sendMessage();
        }

        function formatTime(date) {
            return new Date(date).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }

        async function sendMessage() {
            if (isProcessing) return;
            const message = input.value.trim();
            if (!message || message.length > 500) return;

            isProcessing = true;
            addMessage(message, 'user');
            input.value = '';
            autoResize(input);
            updateCharCount();
            input.disabled = true;
            sendBtn.disabled = true;
            sendBtn.classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('sendIcon').className = 'w-4 h-4 animate-spin';
            document.getElementById('sendIcon').setAttribute('data-lucide', 'loader-2');

            const typingId = addMessage('', 'assistant', true);

            try {
                const response = await fetch('../api/assistant_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message })
                });
                const data = await response.json();
                document.getElementById(typingId).remove();

                if (data.success) {
                    addMessage(data.response, 'assistant');
                    if (data.chart_data) addChartVisualization(data.chart_data);
                } else if (data.response) {
                    // API returned a fallback response with stats
                    addMessage(data.response, 'assistant');
                    if (data.chart_data) addChartVisualization(data.chart_data);
                    console.warn('Assistant API error:', data.error);
                } else {
                    const errMsg = data.error || 'Unknown error';
                    console.error('Assistant API error:', errMsg, data);
                    addMessage('I encountered an error: ' + errMsg + '. Please try again.', 'assistant');
                }
            } catch (error) {
                document.getElementById(typingId).remove();
                console.error('Assistant fetch error:', error);
                addMessage('I\'m having trouble connecting right now. Please check your network connection and try again.', 'assistant');
            } finally {
                isProcessing = false;
                input.disabled = false;
                sendBtn.disabled = false;
                sendBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                document.getElementById('sendIcon').className = 'w-4 h-4';
                document.getElementById('sendIcon').setAttribute('data-lucide', 'send');
                if (typeof lucide !== 'undefined') lucide.createIcons();
                input.focus();
            }
        }

        function addMessage(text, sender, isTyping = false) {
            const messageId = 'msg-' + Date.now() + Math.random().toString(36).substr(2, 5);
            const messageDiv = document.createElement('div');
            messageDiv.id = messageId;
            messageDiv.className = 'chat-message ' + sender;

            if (sender === 'user') {
                messageDiv.classList.add('flex', 'justify-end', 'mb-3');
                const wrapper = document.createElement('div');
                wrapper.className = 'max-w-[80%]';

                const bubble = document.createElement('div');
                bubble.className = 'rounded-xl rounded-tr-sm bg-emerald-600 text-white px-4 py-3 text-sm';
                bubble.innerHTML = formatMessage(text);
                wrapper.appendChild(bubble);

                const meta = document.createElement('div');
                meta.className = 'flex items-center justify-end gap-2 mt-1 msg-actions';
                meta.innerHTML = '<span class="text-[10px] text-slate-400">' + formatTime(new Date()) + '</span>';
                wrapper.appendChild(meta);

                messageDiv.appendChild(wrapper);
            } else {
                messageDiv.classList.add('flex', 'gap-3', 'items-start', 'mb-3');
                if (isTyping) messageDiv.classList.add('opacity-80');

                const logo = document.createElement('img');
                logo.src = '../assets/images/ai-assistant-logo.svg';
                logo.alt = 'AI';
                logo.className = 'w-8 h-8 flex-shrink-0 mt-0.5 rounded-lg';
                messageDiv.appendChild(logo);

                const wrapper = document.createElement('div');
                wrapper.className = 'max-w-[85%]';

                const textDiv = document.createElement('div');
                textDiv.className = 'rounded-xl rounded-tl-sm bg-white border border-slate-200 px-4 py-3 text-sm text-slate-700';

                if (isTyping) {
                    textDiv.innerHTML = '<div class="flex items-center gap-2"><span class="text-slate-400 text-xs">Analyzing</span><span class="flex gap-1"><span class="typing-dot w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span><span class="typing-dot w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span><span class="typing-dot w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span></span></div>';
                } else {
                    textDiv.innerHTML = formatMessage(text);
                }
                wrapper.appendChild(textDiv);

                if (!isTyping) {
                    const meta = document.createElement('div');
                    meta.className = 'flex items-center gap-2 mt-1 msg-actions';
                    meta.innerHTML = '<span class="text-[10px] text-slate-400">' + formatTime(new Date()) + '</span>' +
                        '<button onclick="copyMessage(this)" class="text-[10px] text-slate-400 hover:text-emerald-600 inline-flex items-center gap-1 transition"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy</button>';
                    wrapper.appendChild(meta);
                }
                messageDiv.appendChild(wrapper);
            }

            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
            if (!isTyping) {
                messageCount++;
                document.getElementById('msgCount').textContent = messageCount;
            }
            return messageId;
        }

        function copyMessage(btn) {
            const text = btn.closest('.max-w-\\[85\\%\\]').querySelector('.text-sm').innerText;
            navigator.clipboard.writeText(text).then(() => {
                btn.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied';
                setTimeout(() => {
                    btn.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy';
                }, 2000);
            });
        }

        function formatMessage(text) {
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
            if (text.includes('<li>')) {
                text = text.replace(/(<li>.*<\/li>)/s, '<ul class="ml-5 list-disc my-2 space-y-0.5">$1</ul>');
            }
            // Format numbers with commas
            text = text.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
            return text;
        }

        function addChartVisualization(chartData) {
            const chartDiv = document.createElement('div');
            chartDiv.className = 'chat-message mb-3 ml-11';
            const inner = document.createElement('div');
            inner.className = 'max-w-[85%] rounded-xl bg-slate-50 border border-slate-200 px-4 py-3';

            const title = document.createElement('div');
            title.className = 'text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3 flex items-center gap-1.5';
            title.innerHTML = '<svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M9 17V9m4 8V5m4 12v-8"/></svg> Data Visualization';
            inner.appendChild(title);

            const chartWrap = document.createElement('div');
            const maxValue = Math.max(...Object.values(chartData));
            for (const [label, value] of Object.entries(chartData)) {
                const percentage = (value / maxValue) * 100;
                const row = document.createElement('div');
                row.className = 'mb-2.5 last:mb-0';
                row.innerHTML = `
                    <div class="flex justify-between mb-1 text-xs">
                        <span class="text-slate-600">${label}</span>
                        <span class="text-slate-800 font-semibold">${value}</span>
                    </div>
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-700" style="width: ${percentage}%;"></div>
                    </div>
                `;
                chartWrap.appendChild(row);
            }
            inner.appendChild(chartWrap);
            chartDiv.appendChild(inner);
            chatContainer.appendChild(chartDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function exportChat() {
            const messages = chatContainer.querySelectorAll('.chat-message');
            if (messages.length <= 1) {
                themeUtils.showAlert('No chat messages to export.', 'info');
                return;
            }
            let text = 'Cemetery AI Assistant - Chat Export\n';
            text += 'Date: ' + new Date().toLocaleString() + '\n';
            text += '='.repeat(50) + '\n\n';
            messages.forEach(msg => {
                const isUser = msg.classList.contains('user');
                const content = msg.querySelector('.text-sm');
                if (content) {
                    text += (isUser ? '[You]' : '[AI]') + ': ' + content.innerText + '\n\n';
                }
            });
            const blob = new Blob([text], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'chat_export_' + Date.now() + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            themeUtils.showAlert('Chat exported successfully!', 'success');
        }

        function clearChat() {
            themeUtils.confirm(
                'Are you sure you want to clear the chat history?',
                () => {
                    chatContainer.innerHTML = `
                        <div class="chat-message assistant flex gap-3 items-start mb-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                            <img src="../assets/images/ai-assistant-logo.svg" alt="AI" class="w-8 h-8 flex-shrink-0 mt-0.5 rounded-lg">
                            <div class="text-sm text-slate-700">
                                <p>Chat cleared. How can I help you?</p>
                            </div>
                        </div>
                    `;
                    messageCount = 0;
                    document.getElementById('msgCount').textContent = '0';
                }
            );
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            input.focus();
        });
    </script>
</body>
</html>
