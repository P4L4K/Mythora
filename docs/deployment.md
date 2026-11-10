# Deployment Guide

How to deploy VYRAL STUDIO across Supabase, Render, and Netlify.

---

## 1. Supabase (Database & Auth)

1. Create a project at [supabase.com](https://supabase.com).
2. Open the **SQL Editor** in your Supabase dashboard.
3. Run `supabase/migrations/001_initial_schema.sql` (creates the 10 tables, indexes, and constraints).
4. Run `supabase/migrations/002_rls_policies.sql` (enables Row Level Security and the new user trigger).
5. Open **Storage** and create a public bucket named `vyral-media`.
6. From **Project Settings > API & Database**, copy:
   - Project URL (`SUPABASE_URL`)
   - Anon public key (`SUPABASE_ANON_KEY`)
   - Service role key (`SUPABASE_SERVICE_ROLE_KEY`)
   - Database password and connection pooler string (host, port, user).

---

## 2. Render (Backend API + FFmpeg)

1. Create an account or sign in to [render.com](https://render.com).
2. Click **New + > Web Service** and connect this repository.
3. Configure the service:
   - **Name**: `vyral-studio-backend`
   - **Language / Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile`
   - **Plan**: Starter (or higher for faster FFmpeg rendering)
4. Add the following **Environment Variables**:
   ```
   APP_ENV=production
   DB_HOST=<your-supabase-db-host>
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.<your-project-ref>
   DB_PASSWORD=<your-db-password>
   SUPABASE_URL=https://<your-project-ref>.supabase.co
   SUPABASE_SERVICE_ROLE_KEY=<your-service-role-key>
   GEMINI_API_KEY=<your-gemini-api-key>
   FFMPEG_PATH=ffmpeg
   ```
5. Click **Create Web Service**. Once deployed, Render will give you a public URL (e.g., `https://vyral-studio-backend.onrender.com`).

---

## 3. Netlify (Frontend)

1. Sign in to [netlify.com](https://netlify.com) and click **Add new site > Import an existing project**.
2. Select your repository.
3. Configure build settings:
   - **Base directory**: `frontend`
   - **Publish directory**: `frontend`
   - **Build command**: *(leave blank)*
4. Update `frontend/netlify.toml`:
   Point the API redirect rule to your Render URL:
   ```toml
   [[redirects]]
     from = "/api/*"
     to = "https://your-backend-name.onrender.com/api/:splat"
     status = 200
     force = true
   ```
5. Click **Deploy Site**.

---

## 4. Background Rendering Worker (Optional)

For high-volume rendering separate from API web workers:
1. On Render, click **New + > Background Worker**.
2. Point to the same repository and Dockerfile.
3. Set the start command to:
   ```bash
   php worker/worker.php
   ```
4. Attach the same environment variables as the backend Web Service.
