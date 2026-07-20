/**
 * Live selfie capture via getUserMedia with a file-upload fallback. Crops to
 * a square and compresses client-side before handing off to novixUpload(),
 * which the parent (wizard or profile page) calls directly via
 * Alpine.$data(el) — no event ping-pong needed.
 */
export default function cameraCapture({ uploadUrl, existingPreviewUrl = null, csrfToken }) {
    return {
        stream: null,
        capturedDataUrl: existingPreviewUrl,
        hasCaptured: !!existingPreviewUrl,
        // The browser's camera-permission prompt fires the instant
        // getUserMedia() is called — starting the camera on init() meant
        // asking for it the moment this step merely became visible, before
        // the person had done anything. Now it only starts on an explicit
        // "Turn on camera" click, so the permission prompt lines up with a
        // real user action instead of ambushing them on page load.
        cameraStarted: false,
        cameraError: null,
        uploading: false,
        uploadError: null,
        justSaved: false,

        async startCamera() {
            this.cameraStarted = true;
            this.cameraError = null;

            if (!navigator.mediaDevices?.getUserMedia) {
                this.cameraError = 'Camera not supported on this device — please upload a photo instead.';
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 480 } },
                    audio: false,
                });
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
            } catch (e) {
                this.cameraError = 'Camera access denied — please upload a photo instead.';
            }
        },

        stopCamera() {
            this.stream?.getTracks().forEach((t) => t.stop());
            this.stream = null;
        },

        capture() {
            const video = this.$refs.video;
            const size = Math.min(video.videoWidth, video.videoHeight);
            const canvas = document.createElement('canvas');
            canvas.width = 480;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');
            const sx = (video.videoWidth - size) / 2;
            const sy = (video.videoHeight - size) / 2;
            ctx.drawImage(video, sx, sy, size, size, 0, 0, 480, 480);
            this.capturedDataUrl = canvas.toDataURL('image/jpeg', 0.85);
            this.hasCaptured = true;
            this.stopCamera();
        },

        retake() {
            this.capturedDataUrl = null;
            this.hasCaptured = false;
            this.uploadError = null;
            this.startCamera();
        },

        onFileSelected(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const size = Math.min(img.width, img.height);
                    const canvas = document.createElement('canvas');
                    canvas.width = 480;
                    canvas.height = 480;
                    const ctx = canvas.getContext('2d');
                    const sx = (img.width - size) / 2;
                    const sy = (img.height - size) / 2;
                    ctx.drawImage(img, sx, sy, size, size, 0, 0, 480, 480);
                    this.capturedDataUrl = canvas.toDataURL('image/jpeg', 0.85);
                    this.hasCaptured = true;
                    this.stopCamera();
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        dataUrlToBlob(dataUrl) {
            const [meta, b64] = dataUrl.split(',');
            const mime = meta.match(/:(.*?);/)[1];
            const bin = atob(b64);
            const arr = new Uint8Array(bin.length);
            for (let i = 0; i < bin.length; i++) arr[i] = bin.charCodeAt(i);
            return new Blob([arr], { type: mime });
        },

        /** Called by the parent component. Returns true on success. */
        async novixUpload() {
            if (!this.capturedDataUrl || this.capturedDataUrl === existingPreviewUrl) {
                return !!this.capturedDataUrl;
            }

            this.uploading = true;
            this.uploadError = null;

            try {
                const blob = this.dataUrlToBlob(this.capturedDataUrl);
                const form = new FormData();
                form.append('photo', blob, 'photo.jpg');

                const res = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: form,
                });
                const json = await res.json();

                if (!res.ok || !json.success) {
                    this.uploadError = json.message || 'Could not save your photo — please try again.';
                    return false;
                }

                this.justSaved = true;
                setTimeout(() => (this.justSaved = false), 2000);

                return true;
            } catch (e) {
                this.uploadError = 'Network error — please try again.';
                return false;
            } finally {
                this.uploading = false;
            }
        },
    };
}
