# VYRAL STUDIO
> **Turn Ideas Into Stories.**
> *AI-Powered Short-Form Video Generation SaaS Studio*

---

## 🌟 Overview

**VYRAL STUDIO** is a production-grade SaaS platform designed to transform rough text prompts, written scripts, and article URLs into cinematic vertical videos for **TikTok**, **Instagram Reels**, and **YouTube Shorts**.

Built with an **Enchanted Digital Forest** design system (obsidian charcoal, bioluminescent emerald, arcane violet, and hardware-accelerated ambient firefly particles), the studio prioritizes human-like storytelling and creator agency over generic AI dashboards.

---

## 🚀 Key Differentiating Features

- **Feature A: Content DNA**: A persistent brand identity system saving signature visual aesthetics (Cinematic, Minimalist, Documentary, Anime), narration tones, and custom subtitle palettes. Automatically injected into all future prompts and renders.
- **Feature B: Hook Lab**: Generates 3 distinct psychological opening variations (*Curiosity-Driven*, *Story-Driven*, and *Educational/Provocative*) before rendering full videos to ensure maximum viewer retention in the first 3 seconds.
- **Feature C: Multi-Platform Adaptation**: Tailors script pacing, aspect ratio (9:16 Vertical, 16:9 Landscape, 1:1 Square), and duration natively per platform.
- **Feature D: Smart Scene Editor**: Granular scene-level timeline. Edit narration scripts, modify subtitle timestamps, change visual image prompts, and re-generate individual scenes in isolation without having to re-render the entire video.
- **Credit & Ledger System**: Granular credit tracking with transparent consumption logging.

---

## 🛠️ Technology Stack

- **Frontend**: Vanilla ES6+ JavaScript, HTML5, CSS3 Custom Properties, Canvas Particle Engine. No bulky frameworks. Hosted on **Netlify**.
- **Backend**: Modular **PHP 8.2+** REST API architecture with prepared PDO statements, JWT verification, and service connectors. Hosted on **Render** (Dockerized).
- **Database & Auth**: **Supabase** PostgreSQL (10 normalized tables with full Row-Level Security) and Supabase Auth.
- **AI Engine**: Google Gemini API integration (structured JSON schema mode) with intelligent fallback generator for standalone development.
- **Video Processing**: **FFmpeg** background rendering pipeline supporting transitions, vertical composition (1080x1920), synthetic visual canvas, and audio mixing.

---

## 📁 Repository Directory Structure

```text
VYRAL-STUDIO/
├── frontend/                     # Vanilla JS Client (Netlify)
│   ├── index.html                # Landing page (hero, demo, features, pricing)
│   ├── login.html                # Supabase auth login
│   ├── register.html             # User registration (50 free credits grant)
│   ├── dashboard.html            # Main studio dashboard
│   ├── netlify.toml              # Netlify build and API proxy config
│   ├── pages/
│   │   ├── generator.html        # Video Generator & Hook Lab
│   │   ├── editor.html           # Smart Scene Editor (timeline & cards)
│   │   ├── library.html          # Project archive & MP4 downloads
│   │   ├── brand-studio.html     # Content DNA configuration
│   │   ├── credits.html          # Credit ledger & top-up
│   │   └── admin.html            # Admin telemetry & worker job queue
│   ├── assets/
│   │   ├── css/                  # main.css, components.css, responsive.css
│   │   └── js/                   # config.js, ui-effects.js (fireflies)
│   └── services/                 # api.js, auth.js, projects.js, generation.js
│
├── backend/                      # PHP 8.2 REST API (Render / Docker)
│   ├── public/
│   │   ├── index.php             # Unified REST gateway & router
│   │   └── .htaccess
│   ├── src/
│   │   ├── Config/Config.php
│   │   ├── Database/Database.php # PDO SQLite / PostgreSQL adapter
│   │   ├── Middleware/           # AuthMiddleware.php
│   │   └── Services/             # GeminiService.php, VideoRendererService.php
│   ├── Dockerfile
│   └── composer.json
│
├── worker/                       # Asynchronous Rendering Worker
│   └── worker.php                # CLI daemon for queued jobs
│
├── supabase/                     # Database Migrations & Policies
│   ├── migrations/
│   │   ├── 001_initial_schema.sql# Complete 10-table schema
│   │   └── 002_rls_policies.sql  # Row Level Security & Triggers
│   └── seed.sql
│
├── docs/                         # Documentation
│   └── deployment.md             # Complete Netlify + Render + Supabase guide
├── .env.example
└── README.md
```

---

## 🏃 Local Development Setup

### 1. Frontend
Serve the `frontend/` directory with any static web server:

**Option A (Using PHP):**
```bash
cd frontend
php -S localhost:3000
```

**Option B (Using Node / npx):**
```bash
npx serve frontend -p 3000
```

**Option C (Using VS Code Live Server):**
Right-click `frontend/index.html` and select **"Open with Live Server"**.

Open `http://localhost:3000` in your browser.

### 2. Backend API
Run the PHP backend using the built-in server or Docker:
```bash
cd backend/public
php -S localhost:8000
```
Or with Docker:
```bash
docker build -t vyral-backend backend/
docker run -p 8000:80 vyral-backend
```

### 3. Background Worker
To run the background queue listener:
```bash
php worker/worker.php
```

---

## 🧪 Testing and Verification

- **Authentication**: Test sign up at `/register.html`, log in at `/login.html`, session persistence in `localStorage`, and auto-redirection on unauthorized endpoints.
- **Hook Lab**: Test generating 3 hook variations from a raw text prompt in the generator.
- **Video Pipeline**: Submit a project to trigger scene segmentation, prompt building, and FFmpeg video generation with live stage progress updates.
- **Smart Scene Editor**: Navigate to `/pages/editor.html`, modify scene texts, and trigger single-scene regeneration.

---

## 📄 License
MIT License. Built for creators and developers by VYRAL STUDIO.

