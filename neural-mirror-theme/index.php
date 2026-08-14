<!DOCTYPE html>
<html <?php language_attributes(); ?> data-theme="dark">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    
    <!-- Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div class="app-container">
        <!-- HEADER -->
        <header>
            <div class="logo" onclick="app.setState('idle')" style="cursor:pointer;">
                <i data-lucide="scan-face"></i>
                <span>Neural Mirror</span>
            </div>
            <div class="nav-controls">
                <button class="icon-btn" id="btn-language" aria-label="Toggle Language" title="Toggle Language">
                    <i data-lucide="languages"></i>
                </button>
                <button class="icon-btn" id="btn-access" aria-label="Accessibility Settings" title="Accessibility">
                    <i data-lucide="accessibility"></i>
                </button>
                <button class="icon-btn" id="btn-theme" aria-label="Toggle Theme" title="Toggle Theme">
                    <i data-lucide="sun" id="icon-light"></i>
                    <i data-lucide="moon" id="icon-dark" style="display: none;"></i>
                </button>
            </div>
        </header>

        <!-- MAIN INTERACTIVE AREA -->
        <main>
            <!-- 01. IDLE SCREEN -->
            <section class="state-view active" id="state-idle">
                <div class="attract-visual"></div>
                <h1 class="kiosk-title" data-i18n="title">NEURAL MIRROR</h1>
                <p class="kiosk-subtitle" data-i18n="subtitle">Meet the emotion behind your expression.</p>
                <div class="action-group">
                    <button class="btn-primary" onclick="app.setState('intro')" data-i18n="start">START EXPERIENCE</button>
                    <button class="btn-secondary" onclick="app.setState('science')" data-i18n="how_it_works">HOW IT WORKS</button>
                </div>
            </section>

            <!-- 02. INTRO SCREEN -->
            <section class="state-view" id="state-intro">
                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-number">01</div>
                        <div class="step-title" data-i18n="step1">LOOK</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">02</div>
                        <div class="step-title" data-i18n="step2">EXPRESS</div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">03</div>
                        <div class="step-title" data-i18n="step3">DISCOVER</div>
                    </div>
                </div>
                <p class="kiosk-subtitle" data-i18n="intro_desc">Step into an interactive journey where artificial intelligence transforms your facial expression into a visual experience.</p>
                <button class="btn-primary" onclick="app.setState('position')" data-i18n="continue">CONTINUE</button>
            </section>

            <!-- 03 & 04. POSITION & DETECT SCREEN -->
            <section class="state-view" id="state-position">
                <div class="camera-frame" id="camera-frame">
                    <video id="webcam" autoplay playsinline muted></video>
                    <canvas id="capture-canvas" style="display:none;"></canvas>
                    <div class="face-outline"></div>
                    <div class="landmark" style="top: 40%; left: 35%;"></div>
                    <div class="landmark" style="top: 40%; right: 35%;"></div>
                    <div class="landmark" style="top: 55%; left: 50%; transform: translateX(-50%);"></div>
                    <div class="landmark" style="top: 70%; left: 40%;"></div>
                    <div class="landmark" style="top: 70%; right: 40%;"></div>
                </div>
                <div class="feedback-text" id="position-feedback" data-i18n="pos_instruct">Position yourself inside the frame.</div>
                <button class="btn-secondary" onclick="app.captureAndDetect()" id="btn-manual-detect" data-i18n="simulate">DETECT EMOTION</button>
            </section>

            <!-- 05. AI ANALYSIS SCREEN -->
            <section class="state-view" id="state-analysis">
                <div class="ai-core">
                    <div class="ring ring-1"></div>
                    <div class="ring ring-2"></div>
                    <div class="ring ring-3"></div>
                    <i data-lucide="brain" style="width: 48px; height: 48px; color: var(--text-primary);"></i>
                </div>
                <div class="analysis-label" id="analysis-label">FACIAL LANDMARKS</div>
                <div class="progress-indicator">
                    <div class="progress-bar" id="analysis-progress"></div>
                </div>
            </section>

            <!-- 06 & 07. DYNAMIC AI ANALYSIS RESULT SCREEN -->
            <section class="state-view" id="state-result">
                <canvas id="visualization-canvas"></canvas>
                <div class="result-card">
                    <div class="result-image-col">
                        <img id="captured-snapshot" src="" alt="Analyzed Face">
                        <p class="disclaimer" data-i18n="disclaimer">AI interpretation — not a psychological diagnosis.</p>
                    </div>
                    
                    <div class="result-text-col">
                        <div class="emotion-header">
                            <span id="result-emoji" class="result-emoji">✨</span>
                            <h2 class="result-title" id="result-title">Analyzing...</h2>
                        </div>
                        
                        <div class="result-details">
                            <!-- Emotion Detail -->
                            <p id="detail-emotion-desc" class="emotion-description"></p>

                            <div class="detail-divider"></div>

                            <!-- Quick Stats -->
                            <div class="detail-row">
                                <div class="detail-group detail-half">
                                    <span class="detail-label" data-i18n="lbl_age">ESTIMATED AGE:</span>
                                    <p id="detail-age" class="detail-text"></p>
                                </div>
                                <div class="detail-group detail-half">
                                    <span class="detail-label" data-i18n="lbl_gender">GENDER:</span>
                                    <p id="detail-gender" class="detail-text"></p>
                                </div>
                            </div>

                            <div class="detail-divider"></div>

                            <!-- Vibe/Appearance -->
                            <div class="detail-group">
                                <span class="detail-label" data-i18n="lbl_vibe">APPEARANCE & VIBE:</span>
                                <p id="detail-vibe" class="detail-text"></p>
                            </div>

                            <!-- Personalized AI Comment (Highlight Box) -->
                            <div class="detail-group highlight-box fun-box">
                                <i data-lucide="sparkles"></i>
                                <p id="detail-fun"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="vis-controls">
                    <button class="btn-secondary" onclick="app.setState('science')" data-i18n="learn_science">LEARN THE SCIENCE</button>
                    <button class="btn-primary" onclick="app.openEmailModal()">
                        <i data-lucide="mail"></i> EMAIL REPORT
                    </button>
                    <button class="btn-primary" onclick="app.setState('position')" data-i18n="try_another">TRY AGAIN</button>
                </div>
            </section>

            <!-- 08. SCIENCE EXPLANATION SCREEN -->
            <section class="state-view" id="state-science">
                <h2 class="kiosk-title" style="font-size: clamp(2rem, 4vw, 3rem);" data-i18n="how_works_title">How does Neural Mirror work?</h2>
                <div class="pipeline-diagram">
                    <div class="pipeline-node">
                        <i data-lucide="camera" style="margin-bottom: 0.5rem;"></i>
                        <h4 data-i18n="sci_1_title">01 CAPTURE</h4>
                    </div>
                    <i data-lucide="arrow-right" class="pipeline-arrow"></i>
                    <div class="pipeline-node">
                        <i data-lucide="scan-face" style="margin-bottom: 0.5rem;"></i>
                        <h4 data-i18n="sci_2_title">02 DETECT</h4>
                    </div>
                    <i data-lucide="arrow-right" class="pipeline-arrow"></i>
                    <div class="pipeline-node">
                        <i data-lucide="brain-circuit" style="margin-bottom: 0.5rem;"></i>
                        <h4 data-i18n="sci_3_title">03 ANALYZE</h4>
                    </div>
                    <i data-lucide="arrow-right" class="pipeline-arrow"></i>
                    <div class="pipeline-node">
                        <i data-lucide="bar-chart-2" style="margin-bottom: 0.5rem;"></i>
                        <h4 data-i18n="sci_4_title">04 INTERPRET</h4>
                    </div>
                </div>
                <button class="btn-primary" onclick="app.setState('idle')" data-i18n="return_home">RETURN HOME</button>
            </section>
        </main>

        <!-- EMAIL MODAL -->
        <div id="email-modal" class="email-modal">
            <div class="email-modal-content">
                <button class="close-modal" onclick="app.closeEmailModal()"><i data-lucide="x"></i></button>
                <h3>Get Your AI Report</h3>
                <p>Enter your email to receive your personalized Neural Mirror analysis.</p>
                <div class="email-form">
                    <input type="email" id="user-email" placeholder="your@email.com" required>
                    <button id="send-email-btn" onclick="app.sendEmailReport()">SEND EMAIL</button>
                </div>
                <div id="email-status" class="email-status"></div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer>
            <div class="footer-brand">
                <strong>NEURAL MIRROR</strong><br>
                Interactive AI Museum Experience<br>
                &copy; <?php echo date('Y'); ?> Neural Mirror
            </div>
            <div class="footer-credits" style="text-align: right;">
                Developed by <strong>Abdullah Al Osman</strong><br>
                <a href="mailto:codersosman@gmail.com">codersosman@gmail.com</a>
                <a href="https://www.abdullahalosman.site" target="_blank" rel="noopener">www.abdullahalosman.site</a>
                <a href="tel:+8801842215040">01842215040</a>
            </div>
        </footer>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
