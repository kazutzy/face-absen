/**
 * FaceAbsen Chatbot AI Client Script
 * Powered by Node.js & NVIDIA NIM
 */

document.addEventListener('DOMContentLoaded', () => {
    const rootEl = document.getElementById('faceabsen-chatbot');
    if (!rootEl) return;

    const fabBtn = document.getElementById('chatbot-fab');
    const iconOpen = fabBtn.querySelector('.chatbot-icon-open');
    const iconClose = fabBtn.querySelector('.chatbot-icon-close');
    const tooltipBubble = document.getElementById('chatbot-tooltip-bubble');
    const closeTooltipBtn = document.getElementById('chatbot-close-tooltip');
    const chatWindow = document.getElementById('chatbot-window');
    const minimizeBtn = document.getElementById('chatbot-minimize-btn');
    const clearBtn = document.getElementById('chatbot-clear-btn');
    const messagesContainer = document.getElementById('chatbot-messages');
    const typingIndicator = document.getElementById('chatbot-typing-indicator');
    const chatForm = document.getElementById('chatbot-form');
    const chatInput = document.getElementById('chatbot-input');
    const sendBtn = document.getElementById('chatbot-send-btn');
    const quickChips = document.querySelectorAll('.quick-chip');

    const userName = rootEl.dataset.userName || 'Pengguna';
    const userRole = rootEl.dataset.userRole || 'peserta_didik';
    const baseUrl = rootEl.dataset.baseUrl || '/';
    const nodeUrl = rootEl.dataset.nodeUrl || 'http://localhost:3001';

    let conversationHistory = [];
    let isWaitingResponse = false;

    // Toggle Chat Window
    function toggleChat(open = null) {
        const isClosed = chatWindow.classList.contains('d-none');
        const shouldOpen = open !== null ? open : isClosed;

        if (shouldOpen) {
            chatWindow.classList.remove('d-none');
            iconOpen.classList.add('d-none');
            iconClose.classList.remove('d-none');
            if (tooltipBubble) tooltipBubble.classList.add('d-none');
            setTimeout(() => chatInput.focus(), 150);
            scrollToBottom();
        } else {
            chatWindow.classList.add('d-none');
            iconOpen.classList.remove('d-none');
            iconClose.classList.add('d-none');
        }
    }

    fabBtn.addEventListener('click', () => toggleChat());
    minimizeBtn.addEventListener('click', () => toggleChat(false));

    if (closeTooltipBtn) {
        closeTooltipBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            tooltipBubble.classList.add('d-none');
        });
    }

    // Scroll chat ke bawah
    function scrollToBottom() {
        setTimeout(() => {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 50);
    }

    // Format waktu sekarang (HH:mm)
    function getCurrentTime() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    // Format teks AI sederhana (ubah baris baru dan markdown bold/list ke HTML aman)
    function formatText(text) {
        // Escape HTML
        let escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Bold **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic *text*
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        // Linebreaks
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    // Tambah pesan ke container
    function appendMessage(sender, text) {
        const timeStr = getCurrentTime();
        const msgDiv = document.createElement('div');

        if (sender === 'user') {
            msgDiv.className = 'chat-message user-message d-flex mb-3';
            msgDiv.innerHTML = `
                <div class="message-bubble user-bubble">
                    <p class="mb-1">${formatText(text)}</p>
                    <small class="message-time text-white-50 d-block text-end">${timeStr}</small>
                </div>
            `;
        } else {
            msgDiv.className = 'chat-message bot-message d-flex gap-2 mb-3';
            msgDiv.innerHTML = `
                <div class="bot-avatar-mini">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="message-bubble bot-bubble">
                    <div class="mb-1">${formatText(text)}</div>
                    <small class="message-time text-muted d-block text-end">${timeStr}</small>
                </div>
            `;
        }

        messagesContainer.appendChild(msgDiv);
        scrollToBottom();
    }

    // Kirim pesan ke Backend AI
    async function sendMessage(text) {
        const trimmed = text.trim();
        if (!trimmed || isWaitingResponse) return;

        // 1. Tampilkan pesan user di UI
        appendMessage('user', trimmed);
        chatInput.value = '';

        // Sembunyikan quick chips setelah user mengirim pesan
        const chipsContainer = document.getElementById('quick-chips');
        if (chipsContainer) chipsContainer.classList.add('d-none');

        // Set state loading
        isWaitingResponse = true;
        sendBtn.disabled = true;
        typingIndicator.classList.remove('d-none');
        scrollToBottom();

        // 2. Request ke Node.js Backend terlebih dahulu
        let botReply = '';
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 65000);

            // Coba panggil Node.js Server langsung
            const nodeResponse = await fetch(`${nodeUrl}/api/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: trimmed,
                    history: conversationHistory,
                    userName: userName,
                    userRole: userRole
                }),
                signal: controller.signal
            }).catch(() => null);

            clearTimeout(timeoutId);

            if (nodeResponse && nodeResponse.ok) {
                const json = await nodeResponse.json();
                botReply = json.reply;
            } else {
                // Fallback ke PHP endpoint /chat/send
                const phpResponse = await fetch(`${baseUrl}chat/send`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: trimmed,
                        history: conversationHistory
                    })
                });

                if (phpResponse && phpResponse.ok) {
                    const json = await phpResponse.json();
                    botReply = json.reply || json.error;
                } else if (phpResponse) {
                    try {
                        const json = await phpResponse.json();
                        botReply = json.error || 'Maaf, terjadi kendala saat memproses jawaban. Silakan coba kembali.';
                    } catch (e) {
                        botReply = 'Maaf, sistem sedang mengalami kendala jaringan. Silakan coba sesaat lagi.';
                    }
                } else {
                    botReply = 'Maaf, koneksi ke server AI terganggu. Silakan coba sesaat lagi.';
                }
            }
        } catch (err) {
            console.error('Chat error:', err);
            botReply = 'Maaf, koneksi ke asisten AI terputus. Silakan coba sesaat lagi.';
        } finally {
            isWaitingResponse = false;
            sendBtn.disabled = false;
            typingIndicator.classList.add('d-none');
        }

        // 3. Tampilkan balasan bot di UI
        if (botReply) {
            appendMessage('bot', botReply);
            // Simpan ke riwayat lokal
            conversationHistory.push({ role: 'user', content: trimmed });
            conversationHistory.push({ role: 'assistant', content: botReply });
            if (conversationHistory.length > 8) {
                conversationHistory = conversationHistory.slice(-8);
            }
        }

        chatInput.focus();
    }

    // Submit form
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage(chatInput.value);
    });

    // Quick Action Chips Click
    quickChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const query = chip.dataset.query;
            if (query) {
                toggleChat(true);
                sendMessage(query);
            }
        });
    });

    // Reset Chat
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (confirm('Bersihkan percakapan chat?')) {
                conversationHistory = [];
                // Hapus semua pesan kecuali pesan pembuka pertama & chips
                const messages = messagesContainer.querySelectorAll('.chat-message');
                messages.forEach((msg, idx) => {
                    if (idx > 0) msg.remove();
                });
                const chipsContainer = document.getElementById('quick-chips');
                if (chipsContainer) chipsContainer.classList.remove('d-none');
                chatInput.focus();
            }
        });
    }
});
