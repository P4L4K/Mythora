# VYRAL STUDIO - Full-Stack Deployment Guide

This guide details the complete deployment process for the **VYRAL STUDIO** MVP across Netlify (Frontend), Render (PHP Backend + FFmpeg), and Supabase (PostgreSQL, Auth & Storage).

---

## 1. Supabase Deployment (Database, Auth & Storage)

1. Create a new Supabase Project at [supabase.com](https://supabase.com).
2. Go to the **SQL Editor** in the Supabase Dashboard.
3. Execute `supabase/migrations/001_initial_schema.sql` to generate all 10 core tables, foreign keys, and indexes.
4. Execute `supabase/migrations/002_rls_policies.sql` to enable Row Level Security, policies, and the user initialization trigger.
5. Go to **Storage** and create a public bucket named `vyral-media`.
6. Retrieve your **Project URL**, **Anon Key**, **Service Role Key**, and **PostgreSQL Connection String** from **Project Settings > API & Database**.

---

## 2. Backend Deployment on Render

1. Log in to [render.com](https://render.com) and click **New > Web Service**.
2. Connect your Git repository.
3. Configure the service:
   - **Environment:** `Docker` (Render will build the provided `backend/Dockerfile`, which includes PHP 8.2 and FFmpeg).
   - **Region:** Closest to your users / Supabase database.
4. Under **Environment Variables**, add:
   ```env
   APP_ENV=production
   DB_HOST=aws-0-your-supabase-region.pooler.supabase.com
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.your-supabase-project
   DB_PASSWORD=your-database-password
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_SERVICE_ROLE_KEY=your-supabase-service-role-key
   GEMINI_API_KEY=your-google-gemini-key
   FFMPEG_PATH=ffmpeg
   ```
5. Deploy the service and copy your backend URL (e.g. `https://vyral-studio-backend.onrender.com`).

---

## 3. Frontend Deployment on Netlify

1. Log in to [netlify.com](https://netlify.com) and click **Add new site > Import an existing project**.
2. Select your repository.
3. Netlify will automatically detect `frontend/netlify.toml`.
4. Ensure the build settings are:
   - **Base directory:** `frontend`
   - **Publish directory:** `frontend`
5. In `frontend/netlify.toml`, replace `https://vyral-studio-backend.onrender.com/api/:splat` with your actual Render backend URL.
6. Trigger deployment. The frontend will be live with full SSL, CDN routing, and CORS proxying.

---

## 4. Background Rendering Worker

The background worker daemon (`worker/worker.php`) can run:
- As a **Background Worker** service on Render using the same Docker container (`php worker/worker.php`).
- Alternatively, synchronous generation is handled automatically by `POST /api/generation/start`.

