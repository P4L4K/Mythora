# VYRAL STUDIO

Turn Ideas Into Stories.

VYRAL STUDIO is a lightweight SaaS tool for turning prompts, scripts, and article URLs into vertical short-form videos for TikTok, Instagram Reels, and YouTube Shorts.

## What's Inside

- **Hook Lab**: Generates 3 different opening hooks (Curiosity, Story, Educational) before full video rendering.
- **Content DNA**: Set your brand colors, voice tone, and visual styles once; they apply across all future projects.
- **Smart Scene Editor**: Video is split into distinct scenes. You can edit text, swap image prompts, and regenerate single scenes without rebuilding the entire video.
- **Multi-Format Output**: Default 9:16 vertical video with animated subtitles, with options for 16:9 and 1:1.
- **Built-in Credit System**: Tracks usage for generation and single-scene regenerations.

## Tech Stack

- **Frontend**: Plain HTML5, CSS3, and Vanilla JavaScript (ES6+). No frameworks.
- **Backend**: PHP 8.2+ REST API with PDO and JWT authentication.
- **Video Engine**: FFmpeg for video rendering, text overlay, and audio composition.
- **Database & Auth**: Supabase (PostgreSQL + Auth + Storage).

## Project Structure

```
├── frontend/             # Static UI (deploy to Netlify)
│   ├── index.html        # Landing page
│   ├── dashboard.html    # User project overview
│   ├── pages/            # generator, editor, library, brand-studio, credits, admin
│   ├── assets/           # CSS, JS, ambient firefly effects
│   └── services/         # api.js, auth.js, projects.js, generation.js
├── backend/              # PHP REST API (deploy to Render via Docker)
│   ├── public/index.php  # API router & controllers
│   ├── src/              # Config, DB PDO, Middleware, Services
│   └── Dockerfile        # Container with PHP 8.2, Apache, and FFmpeg
├── worker/               # Background rendering worker (worker.php)
├── supabase/             # PostgreSQL migrations & RLS policies
│   ├── migrations/       # Schema and security rules
│   └── seed.sql
└── docs/deployment.md    # Deployment steps
```

## Running Locally

### 1. Database Setup (Supabase)
1. Go to your Supabase project's **SQL Editor**.
2. Run `supabase/migrations/001_initial_schema.sql`.
3. Run `supabase/migrations/002_rls_policies.sql`.
4. Create a public storage bucket named `vyral-media`.

### 2. Backend Setup
1. Copy environment template:
   ```bash
   cp .env.example .env
   ```
2. Fill in your database and API credentials in `.env`.
3. Start the PHP server:
   ```bash
   cd backend/public
   php -S localhost:8000
   ```
   *(If testing without Postgres, the app falls back to a local SQLite file in `storage/` automatically).*

### 3. Frontend Setup
Run any static file server inside `frontend/`:
```bash
cd frontend
php -S localhost:3000
```
Open `http://localhost:3000` in your browser.

### 4. Background Worker (Optional)
If you want to run rendering jobs asynchronously via the CLI queue:
```bash
php worker/worker.php
```

## Production Deployment Summary

- **Frontend**: Netlify (Root directory: `frontend/`, publish directory: `frontend/`). Proxy routing is pre-configured in `frontend/netlify.toml`.
- **Backend**: Render Web Service using `backend/Dockerfile` (includes PHP 8.2 + FFmpeg).
- **Database**: Supabase.

Detailed instructions are in [`docs/deployment.md`](docs/deployment.md).
