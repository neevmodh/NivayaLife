export default function assistantChat({ familyMemberId, sendUrl, csrfToken, initialMessages }) {
    return {
        messages: initialMessages,
        input: '',
        sending: false,

        init() {
            this.scrollToBottom();
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.sending) return;

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
                    content: json.reply || "Sorry, something went wrong on my end — please try again.",
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

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.scrollArea;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
    };
}
