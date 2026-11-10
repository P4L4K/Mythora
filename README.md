# MYTHORA

Turn Ideas Into Stories.

MYTHORA is an AI-powered content studio that converts text prompts, written scripts, and article URLs into short-form vertical videos for TikTok, Instagram Reels, and YouTube Shorts.

## Core Features

- **Hook Lab**: Generates 3 psychological opening hook variations (Curiosity, Story, Educational) before full video rendering.
- **Content DNA**: Configurable brand identity system (colors, narration tone, fonts, subtitle styling) automatically reused across projects.
- **Smart Scene Editor**: Video is organized into discrete scenes. Edit narration scripts, modify subtitle text, adjust visual prompts, and re-generate individual scenes in isolation.
- **Multi-Platform Outputs**: 9:16 vertical, 16:9 landscape, and 1:1 square video formats with styled subtitle burning.
- **PostgreSQL Job Queue**: Asynchronous generation managed through Supabase PostgreSQL (`generation_jobs`) without Redis.
- **Credit Ledger**: Transparent credit balance and consumption logging.

## Tech Stack & Architecture

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+). Zero frontend frameworks. Hosted on **Render Static Site**.
- **Backend API**: PHP 8.2+ REST API with PDO, Docker, and Supabase JWT verification. Hosted on **Render Web Service**.
- **Background Processing**: Separate **Render Background Worker** running PHP CLI and FFmpeg.
- **Database & Auth**: **Supabase** (PostgreSQL with Row-Level Security, Supabase Auth).
- **Permanent File Storage**: **Supabase Storage** (`mythora-media`). Render filesystem is used only as temporary scratch space.
- **AI Integrations**: Google Gemini API (scripts, Hook Lab, scene segmentation) & ElevenLabs API (text-to-speech narration).
- **Video Processing**: FFmpeg 6/7.

## Project Structure

```
├── frontend/             # Render Static Site
│   ├── index.html        # Landing page
│   ├── dashboard.html    # Studio dashboard
│   ├── pages/            # generator, editor, library, brand-studio, credits, admin
│   ├── assets/           # CSS, JS, ambient firefly effects
│   └── services/         # api.js, auth.js, projects.js, generation.js
├── backend/              # Render Web Service (Docker container)
│   ├── public/index.php  # REST API router
│   ├── src/              # Config, DB PDO, Middleware, Services (Gemini, ElevenLabs, Storage, Video)
│   └── Dockerfile        # Container with PHP 8.2, Apache, and FFmpeg
├── worker/               # Render Background Worker
│   └── worker.php        # CLI daemon polling PostgreSQL generation_jobs queue
├── supabase/             # Database migrations & RLS policies
│   ├── migrations/       # Schema and security rules
│   └── seed.sql
└── docs/deployment.md    # Step-by-step Render + Supabase deployment guide
```

## Running Locally

### 1. Database Setup (Supabase)
1. Open your Supabase project's **SQL Editor**.
2. Run `supabase/migrations/001_initial_schema.sql`.
3. Run `supabase/migrations/002_rls_policies.sql`.
4. Create a public storage bucket named `mythora-media`.

### 2. Backend API
1. Create your local `.env`:
   ```bash
   cp .env.example .env
   ```
2. Fill in your credentials in `.env`.
3. Start the PHP server:
   ```bash
   cd backend/public
   php -S localhost:8000
   ```
   *(If testing without Postgres, the app automatically falls back to local SQLite in `storage/`).*

### 3. Frontend
Serve the `frontend/` directory with any static server:
```bash
cd frontend
php -S localhost:3000
```
Open `http://localhost:3000` in your browser.

### 4. Background Worker
To run the background video rendering queue locally:
```bash
php worker/worker.php
```

## Deployment Summary

- **Frontend**: Render Static Site (Publish directory: `frontend`).
- **Backend API**: Render Web Service using `backend/Dockerfile`.
- **Worker**: Render Background Worker using `backend/Dockerfile` with start command `php worker/worker.php`.
- **Database & Media**: Supabase PostgreSQL and Supabase Storage.

Full instructions are in [`docs/deployment.md`](docs/deployment.md).
