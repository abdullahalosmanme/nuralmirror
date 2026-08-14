document.addEventListener('DOMContentLoaded', () => {
    // Check if lucide is available for icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    window.app = {
        currentState: 'idle',
        videoStream: null,
        canvas: null,
        ctx: null,
        particles: [],
        animFrame: null,
        mouseX: 0,
        mouseY: 0,
        currentAnalysis: null, // Renamed from currentEmotion — holds dynamic AI analysis
        resetTimer: null,
        
        // Multi-language support — all field labels included
        lang: 'bn', // Default Bengali as requested
        i18n: {
            en: {
                title: "NEURAL MIRROR",
                subtitle: "Discover the story behind your expression.",
                start: "START EXPERIENCE",
                how_it_works: "HOW IT WORKS",
                step1: "LOOK",
                step2: "EXPRESS",
                step3: "DISCOVER",
                intro_desc: "Step into an interactive journey where artificial intelligence transforms your facial expression into a visual experience.",
                continue: "CONTINUE",
                pos_instruct: "Position yourself inside the frame.",
                pos_detected: "Face detected. Hold still...",
                simulate: "ANALYZE FACE",
                disclaimer: "AI interpretation — not a psychological diagnosis.",
                lbl_age: "ESTIMATED AGE:",
                lbl_gender: "GENDER:",
                lbl_vibe: "APPEARANCE & VIBE:",
                learn_science: "LEARN THE SCIENCE",
                try_another: "TRY ANOTHER EXPRESSION",
                how_works_title: "How does Neural Mirror work?",
                sci_1_title: "01 CAPTURE",
                sci_2_title: "02 DETECT",
                sci_3_title: "03 ANALYZE",
                sci_4_title: "04 INTERPRET",
                return_home: "RETURN HOME",
                analysis_error: "Analysis could not be completed. Please try again.",
                no_person: "No person detected in the frame. Please step into view!"
            },
            bn: {
                title: "নিউরাল মিরর",
                subtitle: "আপনার অভিব্যক্তির পেছনের গল্পটি আবিষ্কার করুন।",
                start: "শুরু করুন",
                how_it_works: "কীভাবে কাজ করে",
                step1: "তাকান",
                step2: "অভিব্যক্তি",
                step3: "আবিষ্কার",
                intro_desc: "এমন একটি ইন্টারেক্টিভ জার্নিতে প্রবেশ করুন যেখানে কৃত্রিম বুদ্ধিমত্তা আপনার মুখের অভিব্যক্তিকে একটি ভিজ্যুয়াল অভিজ্ঞতায় রূপান্তরিত করবে।",
                continue: "এগিয়ে যান",
                pos_instruct: "ফ্রেমের ভেতর নিজের মুখমণ্ডল রাখুন।",
                pos_detected: "মুখমণ্ডল শনাক্ত হয়েছে। স্থির থাকুন...",
                simulate: "চেহারা বিশ্লেষণ করুন",
                disclaimer: "এটি এআই বিশ্লেষণ — কোনো মনস্তাত্ত্বিক ডায়াগনোসিস নয়।",
                lbl_age: "আনুমানিক বয়স:",
                lbl_gender: "লিঙ্গ:",
                lbl_vibe: "উপস্থিতি ও পরিবেশ:",
                learn_science: "বিজ্ঞানটি জানুন",
                try_another: "অন্য কোনো অভিব্যক্তি চেষ্টা করুন",
                how_works_title: "নিউরাল মিরর কীভাবে কাজ করে?",
                sci_1_title: "০১ ছবি তোলা",
                sci_2_title: "০২ শনাক্তকরণ",
                sci_3_title: "০৩ বিশ্লেষণ",
                sci_4_title: "০৪ ফলাফল",
                return_home: "হোমে ফিরে যান",
                analysis_error: "বিশ্লেষণ সম্পন্ন করা যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।",
                no_person: "ক্যামেরায় কাউকে পাওয়া যাচ্ছে না! দয়া করে সামনে আসুন।"
            }
        },

        isAccessibleMode: false,

        init() {
            this.initTheme();
            this.bindEvents();
            this.initCanvas();
            this.updateUIStrings();
            
            // Handle keyboard navigation escape to home
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.currentState !== 'idle') {
                    this.setState('idle');
                }
            });
        },

        bindEvents() {
            const btnTheme = document.getElementById('btn-theme');
            if (btnTheme) btnTheme.addEventListener('click', () => this.toggleTheme());
            
            const btnLanguage = document.getElementById('btn-language');
            if (btnLanguage) btnLanguage.addEventListener('click', () => this.toggleLanguage());
            
            const btnAccess = document.getElementById('btn-access');
            if (btnAccess) btnAccess.addEventListener('click', () => this.toggleAccessibility());
        },

        setState(stateId) {
            // Hide current
            document.querySelectorAll('.state-view').forEach(el => el.classList.remove('active'));
            
            // Show new
            this.currentState = stateId;
            const nextState = document.getElementById(`state-${stateId}`);
            if (nextState) nextState.classList.add('active');

            // State specific logic
            if (stateId === 'position') {
                document.getElementById('camera-frame').classList.remove('detected');
                document.getElementById('position-feedback').innerText = this.i18n[this.lang].pos_instruct;
                document.getElementById('btn-manual-detect').style.display = 'block';
                this.startCamera();
            } else {
                this.stopCamera();
            }

            if (stateId === 'result') {
                this.startVisualization();
                // Re-initialize Lucide icons for newly added elements
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            } else {
                this.stopVisualization();
            }
        },

        async startCamera() {
            const video = document.getElementById('webcam');
            if (!video) return;
            
            // Ensure video element has proper attributes for all browsers
            video.setAttribute('autoplay', '');
            video.setAttribute('playsinline', '');
            video.setAttribute('muted', '');
            
            const constraints = [
                { video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }, audio: false },
                { video: { facingMode: 'environment' }, audio: false },
                { video: true, audio: false }
            ];
            
            for (const constraint of constraints) {
                try {
                    this.videoStream = await navigator.mediaDevices.getUserMedia(constraint);
                    video.srcObject = this.videoStream;
                    
                    // Wait for video to be ready before continuing
                    await new Promise((resolve, reject) => {
                        video.onloadedmetadata = () => {
                            video.play().then(resolve).catch(resolve);
                        };
                        setTimeout(reject, 5000); // 5 second timeout
                    });
                    
                    console.log("Camera started successfully");
                    return; // Success, exit
                } catch (err) {
                    console.warn("Camera constraint failed, trying next:", constraint, err);
                    // Stop any partial stream before trying next
                    if (this.videoStream) {
                        this.videoStream.getTracks().forEach(track => track.stop());
                        this.videoStream = null;
                    }
                }
            }
            
            // All constraints failed - show user-friendly feedback
            console.error("All camera access attempts failed");
            const feedback = document.getElementById('position-feedback');
            if (feedback) {
                feedback.innerText = this.lang === 'bn' 
                    ? 'ক্যামেরা চালু করা যায়নি। অনুমতি দিন অথবা অন্য ব্রাউজার ব্যবহার করুন।' 
                    : 'Camera access failed. Please allow camera permission or try another browser.';
            }
        },

        stopCamera() {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(track => track.stop());
                this.videoStream = null;
            }
        },

        captureAndDetect() {
            const frame = document.getElementById('camera-frame');
            const feedback = document.getElementById('position-feedback');
            const btn = document.getElementById('btn-manual-detect');
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('capture-canvas');
            
            let imageData = '';
            
            // Capture Image if video is running
            if (this.videoStream && video.videoWidth) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                imageData = canvas.toDataURL('image/jpeg');
            }
            
            btn.style.display = 'none';
            feedback.innerText = this.lang === 'bn' ? "অ্যালাইনমেন্ট বিশ্লেষণ করা হচ্ছে..." : "Analyzing alignment...";
            
            // Visual effect of detection
            setTimeout(() => {
                frame.classList.add('detected');
                feedback.innerText = this.i18n[this.lang].pos_detected;
                
                let count = 2;
                const interval = setInterval(() => {
                    feedback.innerText = `${count}...`;
                    count--;
                    if (count < 0) {
                        clearInterval(interval);
                        this.startAnalysis(imageData); // Pass image to backend
                    }
                }, 800);
            }, 1000);
        },

        startAnalysis(imageData) {
            this.setState('analysis');
            
            // Set the captured snapshot image so the user can see what the AI saw
            const snapshotEl = document.getElementById('captured-snapshot');
            if (snapshotEl) {
                snapshotEl.src = imageData;
                snapshotEl.style.display = 'block';
            }
            
            // Bilingual analysis step labels
            const labels = this.lang === 'bn' 
                ? ["ফেসিয়াল ল্যান্ডমার্ক", "এক্সপ্রেশন প্যাটার্ন", "নিউরাল বিশ্লেষণ", "ফলাফল তৈরি"]
                : ["FACIAL LANDMARKS", "EXPRESSION PATTERNS", "NEURAL ANALYSIS", "GENERATING INSIGHTS"];
            
            const labelEl = document.getElementById('analysis-label');
            const progressEl = document.getElementById('analysis-progress');
            
            let step = 0;
            progressEl.style.width = '0%';
            
            // Send AJAX Request to WordPress Backend
            const formData = new FormData();
            formData.append('action', 'nm_detect');
            formData.append('nonce', nm_ajax_obj.nonce);
            formData.append('image', imageData);
            formData.append('lang', this.lang);
            
            let apiComplete = false;
            let apiError = false;

            fetch(nm_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                apiComplete = true;
                if (response.success && response.data) {
                    // All fields come from AI — simplified to 5 visible areas
                    this.currentAnalysis = {
                        emotion: response.data.emotion || '',
                        emoji: response.data.emoji || '✨',
                        emotion_detail: response.data.emotion_detail || '',
                        age: response.data.age || '',
                        gender: response.data.gender || '',
                        vibe: (response.data.current_state || '') + ' ' + (response.data.background || ''),
                        fun_message: response.data.fun_message || '',
                        accent_color: response.data.accent_color || '#38BDF8',
                        // Store individual pieces for email if needed
                        current_state: response.data.current_state || '',
                        background: response.data.background || ''
                    };
                } else {
                    // API returned an error — show error message, NOT fake results
                    apiError = response.data || 'Unknown API Error';
                    this.currentAnalysis = null;
                    console.error("API Error:", response.data || response);
                }
            })
            .catch(err => {
                apiComplete = true;
                apiError = err.message || 'AJAX Request Failed';
                this.currentAnalysis = null;
                console.error("AJAX Error:", err);
            });
            
            // Simulate UI Progress
            const interval = setInterval(() => {
                if (step < labels.length) {
                    labelEl.innerText = labels[step];
                    progressEl.style.width = `${(step + 1) * 25}%`;
                    step++;
                } else {
                    if (apiComplete) {
                        clearInterval(interval);
                        if (apiError) {
                            this.showError(apiError);
                        } else {
                            this.showResult();
                        }
                    }
                }
            }, 1000);
        },

        // Show error state — NO hardcoded fake results
        showError(errorMsg) {
            alert(this.i18n[this.lang].analysis_error + "\n\nError Detail: " + (typeof errorMsg === 'string' ? errorMsg : JSON.stringify(errorMsg)));
            this.setState('idle');
        },

        showResult() {
            // Check for empty frame / no person
            if (this.currentAnalysis && this.currentAnalysis.emotion === 'NO_FACE') {
                alert(this.i18n[this.lang].no_person);
                this.setState('idle');
                return;
            }

            // If analysis is null (shouldn't happen at this point, but safety check)
            if (!this.currentAnalysis) {
                this.showError();
                return;
            }
            
            const analysis = this.currentAnalysis;
            
            // Set AI-generated title and emoji
            const titleEl = document.getElementById('result-title');
            const emojiEl = document.getElementById('result-emoji');
            if (titleEl) {
                titleEl.innerText = analysis.emotion;
                titleEl.style.color = analysis.accent_color;
            }
            if (emojiEl) {
                emojiEl.innerText = analysis.emoji;
            }
            
            // Set captured snapshot border color to match accent
            const snapshotEl = document.getElementById('captured-snapshot');
            if (snapshotEl) {
                snapshotEl.style.borderColor = analysis.accent_color;
            }
            
            // Populate all dynamic fields
            const fieldMap = {
                'detail-emotion-desc': analysis.emotion_detail,
                'detail-age': analysis.age,
                'detail-gender': analysis.gender,
                'detail-vibe': analysis.vibe,
                'detail-fun': analysis.fun_message
            };
            
            Object.entries(fieldMap).forEach(([id, value]) => {
                const el = document.getElementById(id);
                if (el) {
                    if (value && value.trim()) {
                        el.innerText = value;
                        // Show parent detail-group
                        const parent = el.closest('.detail-group');
                        if (parent) parent.style.display = '';
                    } else {
                        // Hide empty fields gracefully
                        const parent = el.closest('.detail-group');
                        if (parent) parent.style.display = 'none';
                    }
                }
            });
            
            this.setState('result');
            
            // Auto Reset Timeout Logic
            if (this.resetTimer) {
                clearTimeout(this.resetTimer);
            }
            
            const timeoutSeconds = parseInt(nm_ajax_obj.timeout, 10);
            if (timeoutSeconds > 0) {
                this.resetTimer = setTimeout(() => {
                    if (this.currentState === 'result') {
                        this.setState('idle');
                    }
                }, timeoutSeconds * 1000);
            }
        },

        // Email Modal Functions
        openEmailModal() {
            const modal = document.getElementById('email-modal');
            if (modal) modal.classList.add('active');
            
            // Pause reset timer while modal is open
            if (this.resetTimer) clearTimeout(this.resetTimer);
        },

        closeEmailModal() {
            const modal = document.getElementById('email-modal');
            if (modal) modal.classList.remove('active');
            
            // Resume reset timer
            const timeoutSeconds = parseInt(nm_ajax_obj.timeout, 10);
            if (timeoutSeconds > 0 && this.currentState === 'result') {
                this.resetTimer = setTimeout(() => {
                    this.setState('idle');
                }, timeoutSeconds * 1000);
            }
        },

        sendEmailReport() {
            const emailInput = document.getElementById('user-email');
            const statusDiv = document.getElementById('email-status');
            const btn = document.getElementById('send-email-btn');
            
            if (!emailInput.value || !emailInput.checkValidity()) {
                statusDiv.innerText = this.lang === 'en' ? "Please enter a valid email address." : "অনুগ্রহ করে সঠিক ইমেইল দিন।";
                statusDiv.className = 'email-status error';
                return;
            }

            btn.disabled = true;
            btn.innerText = this.lang === 'en' ? "SENDING..." : "পাঠানো হচ্ছে...";
            statusDiv.innerText = "";

            const analysis = this.currentAnalysis;
            const formData = new FormData();
            formData.append('action', 'nm_send_email');
            formData.append('nonce', nm_ajax_obj.nonce);
            formData.append('email', emailInput.value);
            formData.append('emotion', analysis.emotion || '');
            formData.append('emoji', analysis.emoji || '');
            formData.append('emotion_detail', analysis.emotion_detail || '');
            formData.append('age', analysis.age || '');
            formData.append('gender', analysis.gender || '');
            formData.append('current_state', analysis.current_state || '');
            formData.append('background', analysis.background || '');
            formData.append('fun_message', analysis.fun_message || '');

            fetch(nm_ajax_obj.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    statusDiv.innerText = this.lang === 'en' ? "Report sent successfully! Check your inbox." : "রিপোর্ট পাঠানো হয়েছে! আপনার ইনবক্স চেক করুন।";
                    statusDiv.className = 'email-status success';
                    setTimeout(() => {
                        this.closeEmailModal();
                        emailInput.value = '';
                        statusDiv.innerText = '';
                        btn.disabled = false;
                        btn.innerText = this.lang === 'en' ? "SEND EMAIL" : "ইমেইল পাঠান";
                    }, 3000);
                } else {
                    statusDiv.innerText = response.data || (this.lang === 'en' ? "Failed to send email." : "ইমেইল পাঠানো যায়নি।");
                    statusDiv.className = 'email-status error';
                    btn.disabled = false;
                    btn.innerText = this.lang === 'en' ? "SEND EMAIL" : "ইমেইল পাঠান";
                }
            })
            .catch(err => {
                console.error(err);
                statusDiv.innerText = this.lang === 'en' ? "Network Error." : "নেটওয়ার্ক সমস্যা।";
                statusDiv.className = 'email-status error';
                btn.disabled = false;
                btn.innerText = this.lang === 'en' ? "SEND EMAIL" : "ইমেইল পাঠান";
            });
        },

        // Theme Management
        initTheme() {
            const savedTheme = localStorage.getItem('nm_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            this.updateThemeIcons(savedTheme);
        },

        toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('nm_theme', next);
            this.updateThemeIcons(next);
        },

        updateThemeIcons(theme) {
            const sun = document.getElementById('icon-light');
            const moon = document.getElementById('icon-dark');
            if(sun && moon) {
                if (theme === 'dark') {
                    sun.style.display = 'block';
                    moon.style.display = 'none';
                } else {
                    sun.style.display = 'none';
                    moon.style.display = 'block';
                }
            }
        },

        // Language Management
        toggleLanguage() {
            this.lang = this.lang === 'en' ? 'bn' : 'en';
            this.updateUIStrings();
        },

        updateUIStrings() {
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (this.i18n[this.lang][key]) {
                    el.innerText = this.i18n[this.lang][key];
                }
            });
            
            // No need to update result title on language toggle since it's AI-generated per-language
            // The title is already in the correct language from the API response
        },

        // Accessibility
        toggleAccessibility() {
            this.isAccessibleMode = !this.isAccessibleMode;
            if (this.isAccessibleMode) {
                document.body.classList.add('high-contrast', 'large-text');
            } else {
                document.body.classList.remove('high-contrast', 'large-text');
            }
        },

        // Canvas Visualization (Interactive Particle System)
        initCanvas() {
            this.canvas = document.getElementById('visualization-canvas');
            if (!this.canvas) return;
            
            this.ctx = this.canvas.getContext('2d');
            this.particles = [];
            this.animFrame = null;
            this.mouseX = window.innerWidth / 2;
            this.mouseY = window.innerHeight / 2;

            window.addEventListener('resize', () => this.resizeCanvas());
            this.canvas.addEventListener('mousemove', (e) => {
                const rect = this.canvas.getBoundingClientRect();
                this.mouseX = e.clientX - rect.left;
                this.mouseY = e.clientY - rect.top;
            });
            // Touch support
            this.canvas.addEventListener('touchmove', (e) => {
                const rect = this.canvas.getBoundingClientRect();
                this.mouseX = e.touches[0].clientX - rect.left;
                this.mouseY = e.touches[0].clientY - rect.top;
            });
            
            this.resizeCanvas();
        },

        resizeCanvas() {
            if(this.canvas) {
                this.canvas.width = window.innerWidth;
                this.canvas.height = window.innerHeight;
            }
        },

        startVisualization() {
            if(!this.canvas) return;
            this.resizeCanvas();
            this.particles = [];
            const pCount = window.innerWidth < 768 ? 50 : 120;
            
            for(let i = 0; i < pCount; i++) {
                this.particles.push({
                    x: Math.random() * this.canvas.width,
                    y: Math.random() * this.canvas.height,
                    vx: (Math.random() - 0.5) * 2,
                    vy: (Math.random() - 0.5) * 2,
                    size: Math.random() * 3 + 1
                });
            }
            
            this.animateCanvas();
        },

        stopVisualization() {
            if (this.animFrame) {
                cancelAnimationFrame(this.animFrame);
                this.animFrame = null;
            }
        },

        animateCanvas() {
            if(!this.canvas || !this.ctx) return;
            // Stop animation if user prefers reduced motion
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduceMotion) {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                return;
            }

            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            
            const theme = document.documentElement.getAttribute('data-theme');
            const isDark = theme === 'dark';
            
            // Base color off AI-determined accent color
            const hexToRgb = hex => hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i,(m, r, g, b) => '#' + r + r + g + g + b + b)
                .substring(1).match(/.{2}/g).map(x => parseInt(x, 16));
            
            let r=56, g=189, b=248; // default cyan
            if (this.currentAnalysis && this.currentAnalysis.accent_color) {
                try {
                    [r,g,b] = hexToRgb(this.currentAnalysis.accent_color);
                } catch(e) {
                    // Keep default if color parsing fails
                }
            }

            this.particles.forEach(p => {
                // Move towards mouse slightly
                const dx = this.mouseX - p.x;
                const dy = this.mouseY - p.y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                
                if (dist < 300) {
                    p.vx += dx * 0.0001;
                    p.vy += dy * 0.0001;
                }

                // Add friction and velocity
                p.vx *= 0.99;
                p.vy *= 0.99;
                p.x += p.vx;
                p.y += p.vy;

                // Bounce off walls
                if(p.x < 0 || p.x > this.canvas.width) p.vx *= -1;
                if(p.y < 0 || p.y > this.canvas.height) p.vy *= -1;

                // Draw Particle
                this.ctx.beginPath();
                this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                this.ctx.fillStyle = `rgba(${r}, ${g}, ${b}, ${isDark ? 0.6 : 0.8})`;
                this.ctx.fill();
            });

            // Draw connections
            this.ctx.lineWidth = 1;
            for(let i=0; i<this.particles.length; i++) {
                for(let j=i+1; j<this.particles.length; j++) {
                    const dx = this.particles[i].x - this.particles[j].x;
                    const dy = this.particles[i].y - this.particles[j].y;
                    const dist = Math.sqrt(dx*dx + dy*dy);

                    if(dist < 150) {
                        const opacity = 1 - (dist / 150);
                        this.ctx.beginPath();
                        this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
                        this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
                        this.ctx.strokeStyle = `rgba(${r}, ${g}, ${b}, ${opacity * (isDark ? 0.2 : 0.4)})`;
                        this.ctx.stroke();
                    }
                }
            }

            this.animFrame = requestAnimationFrame(() => this.animateCanvas());
        }
    };

    // Initialize the app immediately
    app.init();
});
