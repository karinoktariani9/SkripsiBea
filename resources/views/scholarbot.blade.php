<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarBot - Chatbot Informasi Beasiswa</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-glass: rgba(255, 255, 255, 0.8);
            --bg-gradient: linear-gradient(135deg, #f5f7ff 0%, #e0e7ff 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .container {
            width: 100%;
            height: 100vh;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 0;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: relative;
            animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 768px) {
            .container {
                width: 90%;
                max-width: 1000px;
                height: 85vh;
                border: 1px solid rgba(255, 255, 255, 0.3);
                border-radius: 32px;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @media (min-width: 768px) {
            header {
                padding: 24px 32px;
            }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 45px;
            height: 45px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }

        .brand-text h1 {
            font-size: 20px;
            color: #1e293b;
            font-weight: 700;
        }

        .brand-text p {
            font-size: 12px;
            color: #64748b;
        }

        /* Chat Area */
        #chat-window {
            flex: 1;
            overflow-y: auto;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            scroll-behavior: smooth;
        }

        #chat-window::-webkit-scrollbar {
            width: 6px;
        }

        #chat-window::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .message {
            max-width: 80%;
            padding: 16px 20px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.6;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bot-message {
            align-self: flex-start;
            background: white;
            color: #334155;
            border-bottom-left-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .user-message {
            align-self: flex-end;
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        /* Markdown Styling */
        .bot-message p {
            margin-bottom: 12px;
        }

        .bot-message p:last-child {
            margin-bottom: 0;
        }

        .bot-message ul, .bot-message ol {
            margin-left: 20px;
            margin-bottom: 12px;
        }

        .bot-message li {
            margin-bottom: 6px;
        }

        .bot-message strong {
            font-weight: 700;
            color: #1e293b;
        }

        .user-message strong {
            color: white;
        }

        .bot-message h1, .bot-message h2, .bot-message h3 {
            font-size: 16px;
            margin-top: 16px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        /* Input Area */
        .input-area {
            padding: 16px 20px;
            background: white;
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        @media (min-width: 768px) {
            .input-area {
                padding: 24px 32px;
                border-bottom-left-radius: 32px;
                border-bottom-right-radius: 32px;
                gap: 16px;
            }
        }

        input {
            flex: 1;
            padding: 16px 24px;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            outline: none;
            font-size: 15px;
            transition: all 0.3s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        button:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        /* Typing Indicator */
        .typing {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 10px;
            display: none;
        }

        .dot { animation: blink 1.4s infinite; }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }
        /* RAG Toggle Custom Styles */
        .bg-indigo-600 { background-color: #4f46e5 !important; }
        .bg-slate-300 { background-color: #cbd5e1 !important; }
        .translate-x-4 { transform: translateX(1rem); }
        .translate-x-0 { transform: translateX(0); }
        .text-indigo-600 { color: #4f46e5; }
        .text-slate-400 { color: #94a3b8; }
    </style>
</head>
<body>

    <div class="container">
        <header x-data="{ ragEnabled: true }">
            <div class="brand">
                <a href="{{ url('/') }}" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
                    <div class="logo-icon">S</div>
                    <div class="brand-text">
                        <h1 style="display: flex; align-items: center; gap: 6px;">
                            ScholarBot 
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>
                        </h1>
                        <p>Skripsi RAG Chatbot - Beasiswa AI</p>
                    </div>
                </a>
            </div>
            <div class="header-actions" style="display: flex; align-items: center; gap: 16px;">
                <!-- RAG Toggle -->
                <div class="rag-toggle-wrapper" 
                     x-data="{ ragEnabled: true }"
                     x-init="$watch('ragEnabled', value => window.ragMode = value); window.ragMode = true"
                     style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.5); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(0,0,0,0.05);">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Mode RAG</span>
                    <button @click="ragEnabled = !ragEnabled" 
                            :class="ragEnabled ? 'bg-indigo-600' : 'bg-slate-300'"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            type="button" role="switch">
                        <span :class="ragEnabled ? 'translate-x-4' : 'translate-x-0'"
                              class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                    <span :class="ragEnabled ? 'text-indigo-600' : 'text-slate-400'" class="text-[10px] font-bold" x-text="ragEnabled ? 'ON' : 'OFF'"></span>
                </div>
                <a href="{{ url('/') }}" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 12px; border-radius: 8px; background: rgba(0,0,0,0.03);">Kembali</a>
            </div>
        </header>

        <div id="chat-window">
            <div class="message bot-message">
                Halo! Saya <b>ScholarBot</b> ✨. Ada yang bisa saya bantu terkait informasi beasiswa hari ini?
            </div>
        </div>

        <div id="typing-indicator" class="typing" style="padding: 0 32px;">
            ScholarBot sedang mengetik<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
        </div>

        <form class="input-area" id="chat-form">
            <input type="text" id="user-input" placeholder="Tanyakan tentang beasiswa (misal: Beasiswa S2 Korea)..." autocomplete="off">
            <button type="submit" id="send-btn">
                <span>Kirim</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
    </div>

    <script>
        const chatWindow = document.getElementById('chat-window');
        const chatForm = document.getElementById('chat-form');
        const userInput = document.getElementById('user-input');
        const typingIndicator = document.getElementById('typing-indicator');
        const sendBtn = document.getElementById('send-btn');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function appendMessage(text, isUser = false) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
            
            if (isUser) {
                msgDiv.textContent = text;
            } else {
                // Gunakan marked untuk render markdown dari bot
                msgDiv.innerHTML = marked.parse(text);
            }
            
            chatWindow.appendChild(msgDiv);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = userInput.value.trim();
            if (!message) return;

            // UI Changes
            appendMessage(message, true);
            userInput.value = '';
            userInput.disabled = true;
            sendBtn.disabled = true;
            typingIndicator.style.display = 'block';

            try {
                const response = await fetch('/chatbot/ask', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        message,
                        rag_enabled: window.ragMode 
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    appendMessage(data.answer);
                } else {
                    appendMessage("Maaf, saya sedang mengalami gangguan. Silakan coba lagi nanti.");
                }
            } catch (error) {
                appendMessage("Terjadi kesalahan koneksi.");
            } finally {
                typingIndicator.style.display = 'none';
                userInput.disabled = false;
                sendBtn.disabled = false;
                userInput.focus();
            }
        });
    </script>
</body>
</html>
