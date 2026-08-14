<?php
/**
 * Neural Mirror Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Removed MPA Page Auto-Creation

// 1. Enqueue Scripts and Styles
function nm_enqueue_scripts() {
    // Enqueue Google Fonts
    wp_enqueue_style( 'nm-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Noto+Sans+Bengali:wght@300;400;500;600&family=Space+Grotesk:wght@300;400;500;600&display=swap', array(), null );
    
    // Enqueue Theme Style
    wp_enqueue_style( 'nm-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version') );
    
    // Enqueue Main App JS
    wp_enqueue_script( 'nm-app', get_template_directory_uri() . '/assets/js/app.js', array(), wp_get_theme()->get('Version'), true );
    
    // Pass Ajax Url to script
    wp_localize_script( 'nm-app', 'nm_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'nm_api_nonce' ),
        'timeout'  => get_option('nm_kiosk_timeout', '15')
    ) );
}
add_action( 'wp_enqueue_scripts', 'nm_enqueue_scripts' );

// 1.5 Custom CSS for Primary Color
function nm_custom_css() {
    $primary_color = get_option('nm_primary_color', '');
    if (!empty($primary_color)) {
        echo "<style>
            :root {
                --accent-cyan: {$primary_color} !important;
            }
            [data-theme='dark'] {
                --accent-cyan: {$primary_color} !important;
            }
        </style>";
    }
}
add_action( 'wp_head', 'nm_custom_css' );


// 2. Add Admin Menu for Settings
function nm_add_admin_menu() {
    add_menu_page(
        'Neural Mirror Settings',
        'Neural Mirror',
        'manage_options',
        'neural-mirror-settings',
        'nm_settings_page_html',
        'dashicons-visibility',
        100
    );
}
add_action( 'admin_menu', 'nm_add_admin_menu' );

// 3. Register Settings
function nm_register_settings() {
    register_setting( 'nm_settings_group', 'nm_gemini_api_key' );
    register_setting( 'nm_settings_group', 'nm_gemini_model' );
    register_setting( 'nm_settings_group', 'nm_kiosk_timeout' );
    register_setting( 'nm_settings_group', 'nm_primary_color' );
}
add_action( 'admin_init', 'nm_register_settings' );

// 4. Settings Page HTML
function nm_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Show success messages
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'nm_messages', 'nm_message', 'Settings Saved', 'updated' );
    }
    settings_errors( 'nm_messages' );
    $current_model = get_option('nm_gemini_model', 'gemini-3.6-flash');
    $timeout = get_option('nm_kiosk_timeout', '15');
    $primary_color = get_option('nm_primary_color', '#0284C7');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>Configure the Google Gemini API for dynamic image analysis, and customize your Kiosk.</p>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'nm_settings_group' );
            do_settings_sections( 'nm_settings_group' );
            ?>
            
            <h2>API Integration</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Gemini API Keys</th>
                    <td>
                        <input type="text" name="nm_gemini_api_key" style="width: 100%; max-width: 600px;" value="<?php echo esc_attr( get_option('nm_gemini_api_key') ); ?>" />
                        <p class="description">Get your API keys from Google AI Studio. <strong>You can enter multiple API keys separated by commas (e.g. <code>key1,key2,key3</code>)</strong>. The system will randomly pick one per request to distribute the load.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Gemini Model</th>
                    <td>
                        <select name="nm_gemini_model" style="max-width: 400px;">
                            <option value="gemini-3.5-flash-lite" <?php selected( $current_model, 'gemini-3.5-flash-lite' ); ?>>Gemini 3.5 Flash-Lite (Fastest & Most Cost-Effective)</option>
                            <option value="gemini-3.6-flash" <?php selected( $current_model, 'gemini-3.6-flash' ); ?>>Gemini 3.6 Flash (Recommended — Best Balance)</option>
                            <option value="gemini-3.5-flash" <?php selected( $current_model, 'gemini-3.5-flash' ); ?>>Gemini 3.5 Flash (Frontier Performance)</option>
                            <option value="gemini-3.1-pro" <?php selected( $current_model, 'gemini-3.1-pro' ); ?>>Gemini 3.1 Pro (Highest Accuracy — Flagship)</option>
                        </select>
                        <p class="description">Select the Gemini model to use for image analysis.</p>
                    </td>
                </tr>
            </table>

            <hr>
            <h2>Kiosk Customization</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Reset Timeout (Seconds)</th>
                    <td>
                        <input type="number" name="nm_kiosk_timeout" min="0" max="300" style="width: 100px;" value="<?php echo esc_attr( $timeout ); ?>" />
                        <p class="description">How long should the result screen show before automatically returning to the home screen? (Set to 0 to disable auto-reset).</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Primary Brand Color</th>
                    <td>
                        <input type="color" name="nm_primary_color" value="<?php echo esc_attr( $primary_color ); ?>" />
                        <p class="description">Select the primary accent color for buttons and highlights.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}

// 5. AJAX Handler — Single Batch API Call for All Analysis Fields
function nm_handle_detection() {
    // Verify Nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nm_api_nonce' ) ) {
        wp_send_json_error( 'Invalid Nonce' );
    }
    
    // Get image data
    $image_data = isset( $_POST['image'] ) ? $_POST['image'] : '';
    
    if ( empty( $image_data ) ) {
        wp_send_json_error( 'No image provided' );
    }
    
    // Get language preference from frontend
    $lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'bn';
    
    // Get Settings
    $api_keys_string = get_option( 'nm_gemini_api_key' );
    $model = get_option( 'nm_gemini_model', 'gemini-3.6-flash' );
    
    // No API key configured — return error
    if ( empty( $api_keys_string ) ) {
        wp_send_json_error( $lang === 'en' 
            ? 'API key is not configured. Please add your Gemini API key in Neural Mirror Settings.' 
            : 'API কী সেট করা হয়নি। অনুগ্রহ করে Neural Mirror Settings-এ আপনার Gemini API কী যোগ করুন।' 
        );
    }

    // Handle multiple API keys
    $api_keys_array = array_filter(array_map('trim', explode(',', $api_keys_string)));
    if (empty($api_keys_array)) {
        wp_send_json_error( 'API keys are missing. Please configure them in settings.' );
    }
    $api_key = $api_keys_array[array_rand($api_keys_array)];
    
    // Strip the "data:image/jpeg;base64," prefix
    $base64_img = preg_replace('/^data:image\/\w+;base64,/', '', $image_data);
    
    // Validate Base64 payload length
    if (strlen($base64_img) < 100) {
        wp_send_json_error( 'Invalid or corrupted image data.' );
    }

    // Language instruction — AI generates directly in the chosen language
    $lang_instruction = ($lang === 'en')
        ? 'Write ALL field values in natural English only.'
        : 'সব field-এর value সম্পূর্ণ স্বাভাবিক বাংলায় লিখবে। সরাসরি বাংলায় চিন্তা করে লিখবে, ইংরেজি থেকে অনুবাদ করবে না।';

    // ========================================================================
    // SINGLE BATCH PROMPT — All fields analyzed in ONE API call
    // Each field has its own DETAILED instruction for accurate detection
    // ========================================================================
    $prompt_text = "You are Neural Mirror — an advanced AI face analysis system installed in a museum kiosk. A visitor just took a photo. Analyze the image thoroughly and return accurate results for ALL fields in a single response.

LANGUAGE: {$lang_instruction}

CRITICAL RULES:
1. If NO human face is visible in the image, set emotion to 'NO_FACE' and explain in other fields.
2. Be ACCURATE — look very carefully at every facial feature before deciding the emotion.
3. Do NOT always default to 'Calm' or 'Happy'. Most people show subtle expressions — look harder.
4. Be confident and clear in your detection. Users want to know their emotion, not vague guesses.
5. Make every response UNIQUE to this specific person and image.

=== DETAILED FIELD INSTRUCTIONS ===

■ emotion (MOST IMPORTANT — be very accurate):
This is the PRIMARY emotion you detect on the person's face. To determine this accurately, follow this step-by-step analysis:

Step 1 — EYES: Are they wide open (surprise/excitement), relaxed (calm/content), squinted (joy/suspicion), droopy (tired/sad), bright/sparkling (happy/excited), or tense (angry/worried)?

Step 2 — EYEBROWS: Are they raised high (surprise), slightly raised (interest), furrowed/pulled together (anger/concentration/worry), one raised (skepticism/curiosity), or neutral/relaxed?

Step 3 — MOUTH & LIPS: Is there a smile? If yes — is it a full toothy smile (very happy), a closed-lip smile (content/polite), a slight upturn (mild happiness), a smirk (playful/confident)? Are the lips pressed tight (tense/determined), turned down (sad/disappointed), open (surprise/shock), or relaxed?

Step 4 — CHEEKS & JAW: Are the cheeks raised (genuine smile), tense (anger), puffed (holding breath/annoyed), or relaxed? Is the jaw clenched (tense) or loose (relaxed)?

Step 5 — OVERALL FACE TENSION: Is the face relaxed (calm/happy/content), tense (angry/worried/stressed), animated (excited/surprised), or flat (bored/tired/neutral)?

Step 6 — COMBINE ALL CUES: Based on Steps 1-5, determine the single most accurate emotion. Name it with a clear word or short phrase. The AI must determine the emotion itself — choose whatever emotion truly matches the visual cues. Examples of possible emotions (but not limited to these): Happy, Sad, Angry, Surprised, Calm, Excited, Tired, Bored, Confused, Focused, Shy, Proud, Nervous, Playful, Neutral, Thoughtful, Content, Worried, Amused, Serious, Confident, Cheerful, Melancholic, Dreamy, Curious, Annoyed, Relieved, etc.

Give JUST the emotion name/word. Be specific — 'Content' is better than 'Happy' if the smile is mild. 'Focused' is better than 'Calm' if the brows are slightly furrowed.

■ emoji:
A single emoji that PERFECTLY represents the detected emotion. Match it precisely.

■ emotion_detail:
Write 2-3 vivid, specific sentences explaining EXACTLY what you see in the face that led to your emotion detection. Reference specific facial features: eyes, eyebrows, mouth, cheeks. Example quality: 'Your eyes are slightly crinkled at the corners with a warm sparkle, and your lips are curved into a gentle, genuine smile. The relaxed position of your eyebrows and soft cheeks suggest this happiness is natural, not forced.' Make it personal and detailed — this is the main analysis the user reads.

■ age:
Estimate the person's age by analyzing: skin smoothness/texture (smooth = younger), facial fat distribution (rounder = younger), wrinkle presence and depth (forehead lines, crow's feet, nasolabial folds), jawline definition, and overall facial maturity. Give a specific range like '22-26' or '৩০-৩৫ বছর'. Keep the range narrow (4-5 years). If image quality makes it hard, mention that briefly.

■ gender:
Determine the apparent gender from facial features: jawline shape (angular vs. rounded), facial hair presence, brow ridge prominence, lip fullness, facial proportions, and overall presentation. State it clearly and directly. In Bengali use পুরুষ/মহিলা, in English use Male/Female.

■ current_state:
In 1-2 short sentences, describe what you observe about the person's CURRENT overall state RIGHT NOW in this photo. Consider: Are they sitting or standing? What's their posture like? Are they wearing anything notable (glasses, headphones, hat, jewelry)? Do they look like they're at work, relaxing, posing for the camera, or caught off-guard? What's the overall vibe of this moment? Be observational and natural.

■ background:
In ONE short sentence, briefly describe what is visible in the background behind the person. Mention colors, objects, setting (indoor/outdoor), lighting. Keep it concise.

■ fun_message:
Write a LONG, HILARIOUS, highly personalized paragraph (4-5 sentences). This is the entertainment highlight — make the user LAUGH or at least smile big. Rules for the fun_message:
- Reference their SPECIFIC expression, accessories, clothing, posture, or background
- Use creative comparisons, metaphors, or situational humor
- Include 3-4 relevant emojis naturally within the text
- Make it feel like a witty friend commenting on their photo
- Be playful, warm, and entertaining — never mean or offensive
- Each person should get a COMPLETELY different style of humor
- This should be the longest field in the response

■ accent_color:
A vibrant hex color code that matches the detected emotion's energy. Happy = warm colors, Sad = cool blues, Angry = reds, Calm = soft teals, Excited = bright oranges/yellows, etc. Do NOT always return the same color.";

    $payload = array(
        "contents" => array(
            array(
                "parts" => array(
                    array("text" => $prompt_text),
                    array(
                        "inline_data" => array(
                            "mime_type" => "image/jpeg",
                            "data" => $base64_img
                        )
                    )
                )
            )
        ),
        "safetySettings" => array(
            array("category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"),
            array("category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"),
            array("category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"),
            array("category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"),
        ),
        "generationConfig" => array(
            "temperature" => 0.9,
            "responseMimeType" => "application/json",
            "responseSchema" => array(
                "type" => "OBJECT",
                "properties" => array(
                    "emotion"        => array("type" => "STRING"),
                    "emoji"          => array("type" => "STRING"),
                    "emotion_detail" => array("type" => "STRING"),
                    "age"            => array("type" => "STRING"),
                    "gender"         => array("type" => "STRING"),
                    "current_state"  => array("type" => "STRING"),
                    "background"     => array("type" => "STRING"),
                    "fun_message"    => array("type" => "STRING"),
                    "accent_color"   => array("type" => "STRING")
                ),
                "required" => ["emotion", "emoji", "emotion_detail", "age", "gender", "current_state", "background", "fun_message", "accent_color"]
            )
        )
    );

    // List of models to try in order (Fallback chain)
    $models_to_try = array($model, 'gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-2.5-flash');
    $models_to_try = array_unique($models_to_try);

    $json_result = null;
    $last_error = '';

    foreach ($models_to_try as $current_try_model) {
        $api_key = $api_keys_array[array_rand($api_keys_array)];
        $api_endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$current_try_model}:generateContent?key={$api_key}";

        $args = array(
            'method'      => 'POST',
            'timeout'     => 30,
            'headers'     => array('Content-Type' => 'application/json'),
            'body'        => wp_json_encode( $payload )
        );

        $response = wp_remote_post( $api_endpoint, $args );

        if ( is_wp_error( $response ) ) {
            $last_error = $response->get_error_message();
        } else {
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if (isset($data['error'])) {
                $last_error = json_encode($data['error']);
            } elseif (isset($data['candidates']) && !empty($data['candidates'])) {
                $text_response = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                $clean_text = $text_response;
                if (preg_match('/\{[\s\S]*\}/', $text_response, $matches)) {
                    $clean_text = $matches[0];
                }

                $parsed = json_decode($clean_text, true);
                if ($parsed && isset($parsed['emotion'])) {
                    $json_result = $parsed;
                    break;
                } else {
                    $last_error = 'JSON parse failed or missing emotion field. Raw text: ' . $text_response;
                }
            } else {
                $last_error = 'No candidates in response. Body: ' . $body;
            }
        }
    }

    if ($json_result && isset($json_result['emotion'])) {
        wp_send_json_success(array(
            'emotion'        => trim($json_result['emotion'] ?? ''),
            'emoji'          => trim($json_result['emoji'] ?? '✨'),
            'emotion_detail' => trim($json_result['emotion_detail'] ?? ''),
            'age'            => trim($json_result['age'] ?? ''),
            'gender'         => trim($json_result['gender'] ?? ''),
            'current_state'  => trim($json_result['current_state'] ?? ''),
            'background'     => trim($json_result['background'] ?? ''),
            'fun_message'    => trim($json_result['fun_message'] ?? ''),
            'accent_color'   => trim($json_result['accent_color'] ?? '#38BDF8')
        ));
    } else {
        wp_send_json_error( 'Analysis failed. Detail: ' . $last_error );
    }
}
add_action( 'wp_ajax_nm_detect', 'nm_handle_detection' );
add_action( 'wp_ajax_nopriv_nm_detect', 'nm_handle_detection' );

// 6. AJAX Handler for Email Report
function nm_send_report_email() {
    // Verify Nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'nm_api_nonce' ) ) {
        wp_send_json_error( 'Invalid Nonce' );
    }

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $emotion = isset($_POST['emotion']) ? sanitize_text_field($_POST['emotion']) : '';
    $emoji = isset($_POST['emoji']) ? sanitize_text_field($_POST['emoji']) : '';
    $emotion_detail = isset($_POST['emotion_detail']) ? sanitize_text_field($_POST['emotion_detail']) : '';
    $age = isset($_POST['age']) ? sanitize_text_field($_POST['age']) : '';
    $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
    $current_state = isset($_POST['current_state']) ? sanitize_text_field($_POST['current_state']) : '';
    $background = isset($_POST['background']) ? sanitize_text_field($_POST['background']) : '';
    $fun_message = isset($_POST['fun_message']) ? sanitize_textarea_field($_POST['fun_message']) : '';

    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Invalid email address.' );
    }

    $subject = "Neural Mirror Analysis: " . $emoji . " " . $emotion;
    
    $message = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>";
    $message .= "<h1 style='text-align: center; color: #111827;'>🔬 Neural Mirror AI Analysis</h1>";
    $message .= "<hr style='border: 1px solid #E5E7EB; margin: 20px 0;'>";
    
    $message .= "<h2 style='text-align: center; font-size: 2em; margin: 10px 0;'>{$emoji} " . esc_html($emotion) . "</h2>";
    
    if (!empty($emotion_detail)) {
        $message .= "<p style='background: #F3F4F6; padding: 15px; border-radius: 8px; line-height: 1.6;'>" . esc_html($emotion_detail) . "</p>";
    }
    
    $message .= "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
    if (!empty($age)) {
        $message .= "<tr><td style='padding: 8px; font-weight: bold; color: #6B7280;'>Age / বয়স</td><td style='padding: 8px;'>" . esc_html($age) . "</td></tr>";
    }
    if (!empty($gender)) {
        $message .= "<tr><td style='padding: 8px; font-weight: bold; color: #6B7280;'>Gender / লিঙ্গ</td><td style='padding: 8px;'>" . esc_html($gender) . "</td></tr>";
    }
    if (!empty($current_state)) {
        $message .= "<tr><td style='padding: 8px; font-weight: bold; color: #6B7280;'>State / অবস্থা</td><td style='padding: 8px;'>" . esc_html($current_state) . "</td></tr>";
    }
    if (!empty($background)) {
        $message .= "<tr><td style='padding: 8px; font-weight: bold; color: #6B7280;'>Background / ব্যাকগ্রাউন্ড</td><td style='padding: 8px;'>" . esc_html($background) . "</td></tr>";
    }
    $message .= "</table>";
    
    if (!empty($fun_message)) {
        $message .= "<div style='background: linear-gradient(135deg, #EEF2FF, #FDF2F8); padding: 20px; border-radius: 12px; border-left: 4px solid #6366F1; margin: 15px 0;'>";
        $message .= "<p style='font-size: 1.1em; line-height: 1.7; margin: 0;'>✨ " . nl2br(esc_html($fun_message)) . "</p>";
        $message .= "</div>";
    }
    
    $message .= "<hr style='border: 1px solid #E5E7EB; margin: 20px 0;'>";
    $message .= "<p style='text-align: center; color: #9CA3AF; font-size: 0.85em;'>Thank you for interacting with Neural Mirror! 🔬<br>This is an AI-generated analysis — not a psychological diagnosis.</p>";
    $message .= "</div>";

    $headers = array('Content-Type: text/html; charset=UTF-8');

    $sent = wp_mail( $email, $subject, $message, $headers );

    if ( $sent ) {
        wp_send_json_success( 'Email sent successfully!' );
    } else {
        wp_send_json_error( 'Failed to send email. Ensure server mail configuration is active.' );
    }
}
add_action( 'wp_ajax_nm_send_email', 'nm_send_report_email' );
add_action( 'wp_ajax_nopriv_nm_send_email', 'nm_send_report_email' );

// 7. Custom Email Sender Name & Address
add_filter( 'wp_mail_from_name', function( $original_email_from ) {
    return 'Neural Mirror';
} );

add_filter( 'wp_mail_from', function( $original_email_address ) {
    return $original_email_address;
} );
