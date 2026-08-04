/**
 * Assistant chat: message flow, dictation, and copy-to-clipboard.
 *
 * Dictation uses the browser's built-in SpeechRecognition (Chrome, Edge,
 * Safari) so nothing is recorded or uploaded by us — the browser does the
 * transcription and hands back text. The language picker matters more than
 * usual here: recognition accuracy collapses if someone speaks Hindi while
 * the recognizer is listening for English.
 */

export const DICTATION_LANGUAGES = [
    { code: 'en-IN', label: 'English' },
    { code: 'hi-IN', label: 'हिंदी' },
    { code: 'gu-IN', label: 'ગુજરાતી' },
    { code: 'mr-IN', label: 'मराठी' },
    { code: 'bn-IN', label: 'বাংলা' },
    { code: 'ta-IN', label: 'தமிழ்' },
    { code: 'te-IN', label: 'తెలుగు' },
];

const STORAGE_KEY = 'nivaya.dictation.lang';

export default function assistantChat({ familyMemberId, sendUrl, csrfToken, initialMessages }) {
    return {
        messages: initialMessages,
        input: '',
        sending: false,
        copiedIndex: null,

        // Dictation
        languages: DICTATION_LANGUAGES,
        dictationLang: 'en-IN',
        listening: false,
        speechSupported: false,
        speechError: '',
        _recognition: null,
        _finalTranscript: '',

        init() {
            this.scrollToBottom();

            const SpeechRecognition =
                window.SpeechRecognition || window.webkitSpeechRecognition;
            this.speechSupported = Boolean(SpeechRecognition);

            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved && this.languages.some((l) => l.code === saved)) {
                    this.dictationLang = saved;
                }
            } catch (e) {
                // Private mode can block storage; the default language is fine.
            }
        },

        // ---- messaging ----

        async send() {
            const text = this.input.trim();
            if (!text || this.sending) return;

            if (this.listening) this.stopDictation();

            this.messages.push({ role: 'user', content: text, created_at: new Date().toISOString() });
            this.input = '';
            this.sending = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch(sendUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ family_member_id: familyMemberId, message: text }),
                });
                const json = await response.json();

                this.messages.push({
                    role: 'assistant',
                    content: json.reply || 'Sorry, something went wrong on my end — please try again.',
                    urgent: Boolean(json.urgent),
                    created_at: json.created_at || new Date().toISOString(),
                });
            } catch (e) {
                this.messages.push({
                    role: 'assistant',
                    content: "Sorry, I couldn't reach the server — please check your connection and try again.",
                    created_at: new Date().toISOString(),
                });
            } finally {
                this.sending = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        ask(prompt) {
            this.input = prompt;
            this.send();
        },

        async copy(index) {
            try {
                await navigator.clipboard.writeText(this.messages[index].content);
                this.copiedIndex = index;
                setTimeout(() => (this.copiedIndex = null), 1600);
            } catch (e) {
                // Clipboard blocked (insecure context or denied) — stay silent
                // rather than throwing an error at someone mid-conversation.
            }
        },

        timeFor(message) {
            if (!message.created_at) return '';
            return new Date(message.created_at).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        // ---- dictation ----

        toggleDictation() {
            this.listening ? this.stopDictation() : this.startDictation();
        },

        startDictation() {
            const SpeechRecognition =
                window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) return;

            this.speechError = '';
            this._finalTranscript = this.input ? this.input.trim() + ' ' : '';

            const recognition = new SpeechRecognition();
            recognition.lang = this.dictationLang;
            recognition.continuous = true;
            recognition.interimResults = true;

            recognition.onresult = (event) => {
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const chunk = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        this._finalTranscript += chunk + ' ';
                    } else {
                        interim += chunk;
                    }
                }
                // Interim text is shown live so it feels responsive, but only
                // finalized text is kept when recognition restarts or stops.
                this.input = (this._finalTranscript + interim).trimStart();
            };

            recognition.onerror = (event) => {
                this.speechError = {
                    'not-allowed': 'Microphone access was blocked. Allow it in your browser settings to dictate.',
                    'service-not-allowed': 'Microphone access was blocked by your browser.',
                    'no-speech': "I didn't catch anything — try again a little closer to the mic.",
                    network: 'Speech recognition needs a network connection.',
                }[event.error] ?? 'Dictation stopped unexpectedly. You can type instead.';
                this.listening = false;
            };

            recognition.onend = () => {
                this.listening = false;
                this.input = this._finalTranscript.trim();
            };

            this._recognition = recognition;
            this.listening = true;

            try {
                recognition.start();
            } catch (e) {
                this.listening = false;
                this.speechError = 'Could not start dictation. You can type instead.';
            }
        },

        stopDictation() {
            try {
                this._recognition?.stop();
            } catch (e) {
                // Already stopped.
            }
            this.listening = false;
        },

        setLanguage(code) {
            this.dictationLang = code;
            try {
                localStorage.setItem(STORAGE_KEY, code);
            } catch (e) {
                // Non-fatal.
            }
            if (this.listening) {
                this.stopDictation();
                this.$nextTick(() => this.startDictation());
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.scrollArea;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
    };
}
