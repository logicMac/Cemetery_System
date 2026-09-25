<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Voice Test</title>
</head>
<body style="font-family: sans-serif; padding: 40px;">
    <h2>Voice Recognition Test</h2>
    
    <p><strong>Browser:</strong> <span id="browser"></span></p>
    <p><strong>SpeechRecognition supported:</strong> <span id="supported"></span></p>
    <p><strong>Protocol:</strong> <span id="protocol"></span></p>
    <p><strong>SpeechSynthesis supported:</strong> <span id="ttsSupported"></span></p>
    
    <hr>
    
    <button id="micBtn" onclick="toggleMic()" style="padding: 15px 30px; font-size: 16px; cursor: pointer; border: 2px solid #10b981; border-radius: 10px; background: white;">
        🎤 Start Voice Input
    </button>
    
    <p><strong>Status:</strong> <span id="status">Not started</span></p>
    <p><strong>Heard:</strong> <span id="heard" style="color: #10b981; font-weight: bold;"></span></p>
    
    <div id="error" style="color: red; margin-top: 20px; padding: 10px; border: 1px solid red; border-radius: 8px; display: none;"></div>

    <script>
        document.getElementById('browser').textContent = navigator.userAgent;
        document.getElementById('supported').textContent = !!(window.SpeechRecognition || window.webkitSpeechRecognition) ? 'YES' : 'NO';
        document.getElementById('protocol').textContent = window.location.protocol;
        document.getElementById('ttsSupported').textContent = ('speechSynthesis' in window) ? 'YES' : 'NO';

        let recognition = null;
        let isListening = false;

        function toggleMic() {
            const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SR) {
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').textContent = 'SpeechRecognition is NOT supported in this browser. Use Chrome, Edge, or Safari.';
                return;
            }

            if (isListening) {
                recognition.stop();
                return;
            }

            recognition = new SR();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-US';

            recognition.onstart = function() {
                isListening = true;
                document.getElementById('status').textContent = 'LISTENING...';
                document.getElementById('micBtn').textContent = '⏹ Stop';
                document.getElementById('micBtn').style.background = '#fee2e2';
                document.getElementById('error').style.display = 'none';
            };

            recognition.onresult = function(e) {
                let final = '';
                let interim = '';
                for (let i = e.resultIndex; i < e.results.length; i++) {
                    if (e.results[i].isFinal) final += e.results[i][0].transcript;
                    else interim += e.results[i][0].transcript;
                }
                document.getElementById('heard').textContent = final + interim;
                document.getElementById('status').textContent = final ? 'Final: ' + final : 'Heard: ' + interim;
            };

            recognition.onerror = function(e) {
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').textContent = 'ERROR: ' + e.error;
                isListening = false;
                document.getElementById('status').textContent = 'Error: ' + e.error;
                document.getElementById('micBtn').textContent = '🎤 Start Voice Input';
                document.getElementById('micBtn').style.background = 'white';
            };

            recognition.onend = function() {
                isListening = false;
                document.getElementById('status').textContent = 'Stopped';
                document.getElementById('micBtn').textContent = '🎤 Start Voice Input';
                document.getElementById('micBtn').style.background = 'white';
            };

            try {
                recognition.start();
            } catch (err) {
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').textContent = 'Start error: ' + err.message;
            }
        }
    </script>
</body>
</html>
