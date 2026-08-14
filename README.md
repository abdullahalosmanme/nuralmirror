# 🧠 Neural Mirror (Facial Emotion Tracking Kiosk)

![AI Powered](https://img.shields.io/badge/Powered%20by-Google%20Gemini%20AI-orange)
![Platform](https://img.shields.io/badge/Platform-WordPress%20Theme-green)

**Neural Mirror** is an interactive, AI-powered museum kiosk application that analyzes visitors' facial expressions in real-time and visualizes their emotional state through an immersive digital experience. The project demonstrates the seamless convergence of artificial intelligence, computer vision, and modern web technologies to create an engaging public installation.

---

## ✨ Features

- **Live Facial Emotion Detection:** Uses WebRTC to capture face data and analyzes micro-expressions using Google's Gemini Multimodal AI.
- **5 Core Analytical Fields:**
  - 🎭 **Emotion:** Highly accurate emotion detection with matching emoji.
  - 🎂 **Estimated Age:** AI estimation based on facial features.
  - 🚻 **Apparent Gender:** Visual presentation estimation.
  - 🌆 **Appearance & Vibe:** Contextual observation of the subject and their background.
  - 💬 **Fun Comment:** A highly personalized, witty comment generated to entertain the user.
- **Interactive Visualization:** Dynamic HTML5 Canvas particle system that changes color based on the detected emotion.
- **Bilingual Support (i18n):** One-tap toggle between **English** and **Bengali (বাংলা)**.
- **Email Report System:** Visitors can email their AI analysis report directly to themselves with a beautifully formatted HTML template.
- **Admin Configuration:** Built-in WordPress settings panel to configure Gemini API keys, select AI models, set kiosk timeout limits, and customize brand colors.
- **Accessible & Responsive:** High-contrast dark mode, ADA-compliant touch targets, and cross-device compatibility.

---

## 🛠️ Technology Stack

- **Frontend:** HTML5, CSS3 (Glassmorphism, CSS Variables), Vanilla JavaScript (ES6+ SPA architecture)
- **Backend:** PHP 8.x (Custom WordPress Theme), WP AJAX API
- **AI Engine:** Google Gemini (1.5 Flash / Pro) via REST API
- **Icons & Fonts:** Lucide Icons, Space Grotesk, Noto Sans Bengali

---

## 🚀 Installation & Setup

Since Neural Mirror is packaged as a **WordPress Theme**, you need a running WordPress installation (local or live) to host it.

1. **Download the Repository:** Download this repository as a `.zip` file or clone it.
2. **Install Theme in WordPress:**
   - Go to your WordPress Admin Dashboard.
   - Navigate to **Appearance** > **Themes** > **Add New** > **Upload Theme**.
   - Upload the `.zip` file containing this repository's root files and click **Install Now**.
   - **Activate** the theme.
3. **Configure API Key:**
   - In the WordPress Admin menu, click on **Neural Mirror Settings**.
   - Enter your **Google Gemini API Key** (You can enter multiple keys separated by commas for load-balancing).
   - Click **Save Settings**.
4. **Run the Kiosk:**
   - Simply visit your WordPress site's homepage to launch the interactive kiosk interface.
   - *Note: Camera access requires HTTPS (or localhost for local testing).*

---

## 📂 Project Documentation

The complete project documentation, including research, wireframes, user personas, and CO-PO mapping for the UI/UX Design course, is available in the `docs/` folder of this repository.

- `docs/REDY REPORT.docx` - Full Mini Project Report

---

## 👨‍💻 Developer

**Abdullah AL Osman**
