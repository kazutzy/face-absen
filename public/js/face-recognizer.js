/**
 * ==============================================================================
 * FaceAbsen Face Recognition Engine
 * Powered by Google Teachable Machine (TensorFlow.js)
 * Modular OOP Architecture for Real-Time Mobile & Desktop Recognition
 * ==============================================================================
 */

class FaceAbsenRecognizer {
    constructor(options = {}) {
        this.modelUrl = options.modelUrl || '';
        this.threshold = typeof options.threshold === 'number' ? options.threshold : 0.80; // 80% default
        this.studentMappings = options.studentMappings || {}; // { identifier: { name, class_name, nis } }

        this.model = null;
        this.maxPredictions = 0;
        this.mediaStream = null;
        this.isRunning = false;
        this.animationFrameId = null;
        this.currentFacingMode = options.facingMode || 'user'; // 'user' (front camera) or 'environment' (back)
        this.isModelLoading = false;

        // Event callbacks registry
        this.callbacks = {
            onModelLoading: [],
            onModelLoaded: [],
            onCameraReady: [],
            onFaceTracking: [],
            onFaceRecognized: [],
            onError: []
        };
    }

    /**
     * Daftarkan event listener
     */
    on(event, callback) {
        if (this.callbacks[event] && typeof callback === 'function') {
            this.callbacks[event].push(callback);
        }
        return this;
    }

    /**
     * Trigger event internal
     */
    emit(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(cb => {
                try {
                    cb(data);
                } catch (err) {
                    console.error(`Error in callback for ${event}:`, err);
                }
            });
        }
    }

    /**
     * Memuat Model Teachable Machine (Hanya sekali di-load ke memory)
     */
    async loadModel(customUrl = null) {
        if (this.model && !customUrl) {
            return this.model; // Sudah dimuat (cached)
        }

        const url = customUrl || this.modelUrl;
        if (!url) {
            throw new Error('URL model Teachable Machine belum dikonfigurasikan.');
        }

        // Pastikan format URL diakhiri dengan slash
        const formattedUrl = url.endsWith('/') ? url : url + '/';
        const modelURL = formattedUrl + 'model.json';
        const metadataURL = formattedUrl + 'metadata.json';

        this.emit('onModelLoading', { url: formattedUrl });
        this.isModelLoading = true;

        try {
            if (typeof tmImage === 'undefined') {
                throw new Error('Library Teachable Machine (@teachablemachine/image) belum terpasang atau gagal dimuat.');
            }

            this.model = await tmImage.load(modelURL, metadataURL);
            this.maxPredictions = this.model.getTotalClasses();
            this.isModelLoading = false;

            const classLabels = this.model.getClassLabels();
            this.emit('onModelLoaded', {
                totalClasses: this.maxPredictions,
                classLabels: classLabels
            });

            return this.model;
        } catch (error) {
            this.isModelLoading = false;
            const errObj = new Error(`Gagal memuat model AI Teachable Machine: ${error.message}`);
            this.emit('onError', { type: 'MODEL_LOAD_ERROR', error: errObj });
            throw errObj;
        }
    }

    /**
     * Menyalakan Kamera Depan Smartphone / Webcam Desktop
     */
    async startCamera(videoElement, facingMode = null) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            const err = new Error('Browser Anda tidak mendukung akses kamera (MediaDevices API). Gunakan Chrome/Safari terbaru dengan HTTPS/localhost.');
            this.emit('onError', { type: 'BROWSER_UNSUPPORTED', error: err });
            throw err;
        }

        if (facingMode) {
            this.currentFacingMode = facingMode;
        }

        // Hentikan stream kamera lama jika ada
        this.stopCamera();

        const constraints = {
            audio: false,
            video: {
                facingMode: { ideal: this.currentFacingMode },
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        };

        try {
            this.mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
            videoElement.srcObject = this.mediaStream;

            return new Promise((resolve) => {
                videoElement.onloadedmetadata = () => {
                    videoElement.play();
                    this.emit('onCameraReady', {
                        videoWidth: videoElement.videoWidth,
                        videoHeight: videoElement.videoHeight,
                        facingMode: this.currentFacingMode
                    });
                    resolve(this.mediaStream);
                };
            });
        } catch (error) {
            let errorType = 'CAMERA_ERROR';
            let message = error.message;

            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                errorType = 'PERMISSION_DENIED';
                message = 'Izin kamera ditolak. Mohon izinkan akses kamera pada browser Anda.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                errorType = 'CAMERA_NOT_FOUND';
                message = 'Kamera tidak ditemukan pada perangkat ini.';
            } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                errorType = 'CAMERA_IN_USE';
                message = 'Kamera sedang digunakan oleh aplikasi lain.';
            }

            const errObj = new Error(message);
            this.emit('onError', { type: errorType, error: errObj });
            throw errObj;
        }
    }

    /**
     * Ganti Kamera (Depan <-> Belakang)
     */
    async switchCamera(videoElement) {
        this.currentFacingMode = this.currentFacingMode === 'user' ? 'environment' : 'user';
        return this.startCamera(videoElement, this.currentFacingMode);
    }

    /**
     * Hentikan stream kamera
     */
    stopCamera() {
        if (this.mediaStream) {
            this.mediaStream.getTracks().forEach(track => track.stop());
            this.mediaStream = null;
        }
    }

    /**
     * Memulai Real-Time Recognition Loop
     */
    startRecognition(videoElement) {
        if (!this.model) {
            throw new Error('Model belum dimuat. Panggil loadModel() terlebih dahulu.');
        }

        this.isRunning = true;

        const loop = async () => {
            if (!this.isRunning) return;

            if (videoElement && videoElement.readyState >= 2) {
                await this.predictFrame(videoElement);
            }

            this.animationFrameId = window.requestAnimationFrame(loop);
        };

        this.animationFrameId = window.requestAnimationFrame(loop);
    }

    /**
     * Menghentikan Loop Prediksi
     */
    stopRecognition() {
        this.isRunning = false;
        if (this.animationFrameId) {
            window.cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    }

    /**
     * Melakukan inferensi satu frame video
     */
    async predictFrame(videoElement) {
        try {
            // Prediksi menggunakan Teachable Machine model
            const predictions = await this.model.predict(videoElement);

            // Urutkan probabilitas dari tertinggi ke terendah
            predictions.sort((a, b) => b.probability - a.probability);

            const topPrediction = predictions[0];
            const className = topPrediction.className;
            const probability = topPrediction.probability; // 0.0 - 1.0
            const confidencePercent = (probability * 100).toFixed(1);

            // Cek apakah label ini adalah background / tidak ada orang
            const isBackground = ['background', 'latar belakang', 'none', 'unknown', 'kosong', 'tidak dikenal'].includes(className.toLowerCase().trim());
            const isValidConfidence = probability >= this.threshold;
            const isFaceRecognized = !isBackground && isValidConfidence;

            // Cari mapping profil siswa jika ada
            const studentInfo = this.studentMappings[className] || null;

            const trackingData = {
                predictions: predictions,
                top: {
                    className: className,
                    probability: probability,
                    confidencePercent: parseFloat(confidencePercent),
                    isBackground: isBackground,
                    isValidConfidence: isValidConfidence,
                    isFaceRecognized: isFaceRecognized,
                    student: studentInfo
                },
                threshold: this.threshold
            };

            // Trigger event tracking setiap frame
            this.emit('onFaceTracking', trackingData);

            // Jika wajah terverifikasi memenuhi threshold, trigger event recognized
            if (isFaceRecognized) {
                this.emit('onFaceRecognized', trackingData.top);
            }

            return trackingData;
        } catch (error) {
            console.error('Prediction loop error:', error);
        }
    }

    /**
     * Update threshold secara dinamis saat runtime
     */
    setThreshold(newThreshold) {
        this.threshold = parseFloat(newThreshold);
    }
}

// Export ke window global
window.FaceAbsenRecognizer = FaceAbsenRecognizer;
