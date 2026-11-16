<div class="container py-4 flex-grow-1">
    
    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-7">
            
            <!-- Header -->
            <div class="text-center mb-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 mb-2">
                    <i class="bi bi-cpu-fill me-1"></i> Model AI: FaceAbsen
                </span>
                <h3 class="fw-bold text-dark mb-1">Pengenalan Wajah (Face Recognition)</h3>
                <p class="text-muted small mb-0" id="instructionText">
                    <i class="bi bi-info-circle text-primary me-1"></i> Posisikan wajah Anda di depan kamera.
                </p>
            </div>

            <!-- Error Banner Container -->
            <div id="scannerAlert" class="alert alert-danger d-none shadow-sm rounded-4 mb-3" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 flex-shrink-0"></i>
                    <div id="scannerAlertMsg" class="small fw-medium"></div>
                </div>
            </div>

            <!-- Camera Viewport Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3 bg-white">
                <div class="card-body p-3 p-sm-4 text-center">
                    
                    <div class="scanner-viewport position-relative mb-3" id="viewportContainer">
                        <!-- Live Video Element -->
                        <video id="webcamVideo" class="scanner-video" playsinline autoplay muted></video>

                        <!-- Visual Overlays -->
                        <div class="face-frame-guide" id="faceFrameGuide"></div>
                        <div class="scanner-beam d-none" id="scannerBeam"></div>

                        <!-- Loading State Overlay -->
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-dark text-white p-4 text-center" id="loadingOverlay">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h6 class="fw-bold mb-1" id="loadingTitle">Menyiapkan Kamera & Model AI...</h6>
                            <p class="text-white-50 small mb-0" id="loadingSubtitle">Memuat library Teachable Machine</p>
                        </div>
                    </div>

                    <!-- Camera Action Controls -->
                    <div class="d-flex justify-content-center gap-2 mb-2">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 btn-sm" id="btnSwitchCamera" title="Ganti Kamera">
                            <i class="bi bi-arrow-repeat me-1"></i> Ganti Kamera
                        </button>
                        <button type="button" class="btn btn-outline-danger rounded-pill px-3 btn-sm d-none" id="btnStopCamera">
                            <i class="bi bi-camera-video-off me-1"></i> Matikan Kamera
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-3 btn-sm d-none" id="btnStartCamera">
                            <i class="bi bi-camera-video me-1"></i> Nyalakan Kamera
                        </button>
                    </div>

                </div>
            </div>

            <!-- Real-Time Recognition Status Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
                <div class="card-body p-4">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-indicator idle" id="statusPulse"></span>
                            <h6 class="fw-bold mb-0 text-dark" id="statusTitle">Menunggu Wajah...</h6>
                        </div>
                        <div>
                            <span class="badge bg-light text-muted border small" id="thresholdBadge">
                                Ambang Batas: <?= htmlspecialchars($min_confidence) ?>%
                            </span>
                        </div>
                    </div>

                    <!-- Recognized Identity Box -->
                    <div class="p-3 bg-light rounded-3 mb-3" id="resultBox">
                        <div class="row align-items-center g-2">
                            <div class="col-8">
                                <span class="text-muted small d-block">Identitas Siswa (NIS):</span>
                                <h5 class="fw-bold text-dark mb-0" id="recognizedName">-</h5>
                                <small class="text-muted" id="recognizedDetail">Belum ada wajah terverifikasi</small>
                            </div>
                            <div class="col-4 text-end">
                                <span class="text-muted small d-block">Akurasi AI:</span>
                                <h4 class="fw-bold text-primary mb-0" id="confidencePercent">0.0%</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Confidence Progress Bar -->
                    <div class="progress" style="height: 10px;" role="progressbar" aria-label="Confidence Level" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-secondary" id="confidenceBar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>

            <!-- Footer Notes & Settings Shortcut for Super User -->
            <div class="d-flex justify-content-between align-items-center text-muted small px-2">
                <span><i class="bi bi-shield-check text-success me-1"></i> Pemrosesan AI Lokal di Browser</span>
                <?php if (Auth::role() === 'super_user'): ?>
                    <a href="<?= BASE_URL ?>scan/settings" class="text-decoration-none small text-primary fw-medium">
                        <i class="bi bi-gear me-1"></i> Konfigurasi Model
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- TensorFlow.js & Teachable Machine Image Scripts via Official CDN -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@teachablemachine/image@latest/dist/teachablemachine-image.min.js"></script>

<!-- Modular FaceAbsen Recognizer Engine -->
<script src="<?= BASE_URL ?>js/face-recognizer.js"></script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    const videoElement = document.getElementById('webcamVideo');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingTitle = document.getElementById('loadingTitle');
    const loadingSubtitle = document.getElementById('loadingSubtitle');
    const scannerBeam = document.getElementById('scannerBeam');
    const faceFrameGuide = document.getElementById('faceFrameGuide');
    const scannerAlert = document.getElementById('scannerAlert');
    const scannerAlertMsg = document.getElementById('scannerAlertMsg');

    const statusPulse = document.getElementById('statusPulse');
    const statusTitle = document.getElementById('statusTitle');
    const recognizedName = document.getElementById('recognizedName');
    const recognizedDetail = document.getElementById('recognizedDetail');
    const confidencePercent = document.getElementById('confidencePercent');
    const confidenceBar = document.getElementById('confidenceBar');

    const btnSwitchCamera = document.getElementById('btnSwitchCamera');
    const btnStopCamera = document.getElementById('btnStopCamera');
    const btnStartCamera = document.getElementById('btnStartCamera');

    let recognizer = null;
    let scanConfig = null;
    let isSubmitting = false;
    let hasCompleted = false;

    async function submitAttendanceToServer(identityData) {
        if (isSubmitting || hasCompleted) {
            return;
        }

        if (!identityData || !identityData.student || !identityData.student.user_id) {
            showAlert('Identitas wajah tidak dapat diverifikasi.');
            return;
        }

        isSubmitting = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('<?= BASE_URL ?>scan/record', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    user_id: <?= (int)Auth::id() ?>,
                    identifier: identityData.className,
                    confidence_score: identityData.confidencePercent,
                    csrf_token: csrfToken
                })
            });

            const result = await response.json();

            if (result.duplicate) {
                hasCompleted = true;
                if (recognizer) {
                    recognizer.stopRecognition();
                    recognizer.stopCamera();
                }
                const params = new URLSearchParams({
                    status: 'duplicate',
                    name: '<?= htmlspecialchars(Auth::user()['name'] ?? '') ?>',
                    message: result.message || 'Anda sudah melakukan absensi hari ini.'
                });
                window.location.href = '<?= BASE_URL ?>scan/result?' + params.toString();
                return;
            }

            if (!result.success) {
                showAlert(result.message || 'Identitas wajah tidak dapat diverifikasi.');
                setTimeout(() => {
                    isSubmitting = false;
                }, 1500);
                return;
            }

            hasCompleted = true;
            if (recognizer) {
                recognizer.stopRecognition();
                recognizer.stopCamera();
            }

            const attendance = result.attendance || {};
            const params = new URLSearchParams({
                status: 'success',
                name: attendance.name || identityData.student.name,
                date: attendance.date || new Date().toLocaleDateString('id-ID'),
                time: attendance.time || new Date().toLocaleTimeString('id-ID', { hour12: false }),
                attendance_status: attendance.status || 'Hadir',
                confidence: attendance.confidence_score || `${identityData.confidencePercent.toFixed(2)}%`,
                message: 'ABSENSI BERHASIL'
            });

            window.location.href = '<?= BASE_URL ?>scan/result?' + params.toString();
        } catch (err) {
            showAlert('Terjadi kesalahan jaringan atau server: ' + err.message);
            setTimeout(() => {
                isSubmitting = false;
            }, 2000);
        }
    }

    // Helper Tampilkan Alert
    function showAlert(message) {
        scannerAlertMsg.textContent = message;
        scannerAlert.classList.remove('d-none');
    }

    function hideAlert() {
        scannerAlert.classList.add('d-none');
    }

    try {
        // 1. Fetch Konfigurasi Model & Mapping dari Backend
        loadingTitle.textContent = 'Mengambil Konfigurasi Scanner...';
        const configResp = await fetch('<?= BASE_URL ?>scan/config');
        scanConfig = await configResp.json();

        if (!scanConfig.success || !scanConfig.model_url) {
            throw new Error('Konfigurasi URL model Teachable Machine tidak ditemukan.');
        }

        // 2. Inisialisasi Instance FaceAbsenRecognizer
        recognizer = new FaceAbsenRecognizer({
            modelUrl: scanConfig.model_url,
            threshold: scanConfig.min_confidence_ratio || 0.80,
            studentMappings: scanConfig.student_mappings || {}
        });

        // 3. Register Event Listeners
        recognizer.on('onModelLoading', (data) => {
            loadingTitle.textContent = 'Memuat Model AI Teachable Machine...';
            loadingSubtitle.textContent = 'Mengunduh bobot neural network (' + data.url + ')';
        });

        recognizer.on('onModelLoaded', (data) => {
            loadingTitle.textContent = 'Model Berhasil Dimuat!';
            loadingSubtitle.textContent = 'Total ' + data.totalClasses + ' kelas label terdaftar';
        });

        recognizer.on('onCameraReady', () => {
            loadingOverlay.classList.add('d-none');
            scannerBeam.classList.remove('d-none');
            btnStopCamera.classList.remove('d-none');
            btnStartCamera.classList.add('d-none');
            hideAlert();
        });

        // Real-Time Frame Tracking Hook
        recognizer.on('onFaceTracking', (data) => {
            const top = data.top;
            const percent = top.confidencePercent;

            confidencePercent.textContent = percent + '%';
            confidenceBar.style.width = percent + '%';

            if (top.isFaceRecognized) {
                // Wajah Dikenali & Melebihi Threshold
                statusPulse.className = 'pulse-indicator active';
                statusTitle.textContent = 'Wajah Terverifikasi!';
                statusTitle.className = 'fw-bold mb-0 text-success';
                
                faceFrameGuide.className = 'face-frame-guide status-recognized';
                confidenceBar.className = 'progress-bar bg-success';

                if (top.student) {
                    recognizedName.textContent = top.student.name + ' (' + top.student.nis + ')';
                    recognizedDetail.textContent = 'NIS: ' + top.student.nis + ' | Kelas: ' + top.student.class_name;
                    submitAttendanceToServer({
                        student: top.student,
                        className: top.className,
                        confidencePercent: percent
                    });
                } else {
                    recognizedName.textContent = 'NIS/Label: ' + top.className;
                    recognizedDetail.textContent = 'Kelas Model Terdeteksi: ' + top.className;
                }
            } else if (!top.isBackground && top.probability > 0.3) {
                // Ada wajah tetapi confidence di bawah ambang batas
                statusPulse.className = 'pulse-indicator searching';
                statusTitle.textContent = 'Menganalisis Wajah (Akurasi Rendah)...';
                statusTitle.className = 'fw-bold mb-0 text-warning';

                faceFrameGuide.className = 'face-frame-guide status-low-confidence';
                confidenceBar.className = 'progress-bar bg-warning';

                recognizedName.textContent = top.className;
                recognizedDetail.textContent = 'Confidence di bawah ambang batas (' + (data.threshold * 100) + '%)';
            } else {
                // Background / Tidak ada wajah
                statusPulse.className = 'pulse-indicator idle';
                statusTitle.textContent = 'Posisikan Wajah di Depan Kamera';
                statusTitle.className = 'fw-bold mb-0 text-dark';

                faceFrameGuide.className = 'face-frame-guide';
                confidenceBar.className = 'progress-bar bg-secondary';

                recognizedName.textContent = '-';
                recognizedDetail.textContent = 'Mencari objek wajah...';
            }
        });

        // Error Hook
        recognizer.on('onError', (errData) => {
            loadingOverlay.classList.add('d-none');
            showAlert(errData.error.message);
        });

        // 4. Load Model (Hanya sekali di-load)
        await recognizer.loadModel();

        // 5. Nyalakan Kamera Depan Smartphone
        loadingTitle.textContent = 'Menyalakan Kamera...';
        loadingSubtitle.textContent = 'Mohon izinkan akses kamera pada browser';
        await recognizer.startCamera(videoElement);

        // 6. Jalankan Loop Pengenalan Real-Time
        recognizer.startRecognition(videoElement);

    } catch (error) {
        loadingOverlay.classList.add('d-none');
        showAlert(error.message);
    }

    // Button Switch Camera Handlers
    btnSwitchCamera.addEventListener('click', async () => {
        if (recognizer) {
            try {
                await recognizer.switchCamera(videoElement);
            } catch (e) {
                showAlert('Gagal mengganti kamera: ' + e.message);
            }
        }
    });

    btnStopCamera.addEventListener('click', () => {
        if (recognizer) {
            recognizer.stopRecognition();
            recognizer.stopCamera();
            scannerBeam.classList.add('d-none');
            btnStopCamera.classList.add('d-none');
            btnStartCamera.classList.remove('d-none');
            statusPulse.className = 'pulse-indicator idle';
            statusTitle.textContent = 'Kamera Dimatikan';
        }
    });

    btnStartCamera.addEventListener('click', async () => {
        if (recognizer) {
            try {
                await recognizer.startCamera(videoElement);
                recognizer.startRecognition(videoElement);
            } catch (e) {
                showAlert('Gagal menyalakan kamera: ' + e.message);
            }
        }
    });
});
</script>
