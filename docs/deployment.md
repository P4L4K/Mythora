# MYTHORA - Deployment Guide

This guide details how to deploy MYTHORA across **Render** (Frontend Static Site, PHP Web Service, and Background Worker) and **Supabase** (Database, Auth, and Permanent Storage).

---

## 1. Supabase Setup (Database, Auth & Storage)

1. Create a project at [supabase.com](https://supabase.com).
2. Go to the **SQL Editor** in your Supabase dashboard.
3. Run `supabase/migrations/001_initial_schema.sql` to create the schema, tables, indexes, and constraints.
4. Run `supabase/migrations/002_rls_policies.sql` to enable Row Level Security and the new user trigger.
5. In **Storage**, create a new public bucket named `mythora-media`.
6. From **Project Settings > API & Database**, copy:
   - Project URL (`SUPABASE_URL`)
   - Anon public key (`SUPABASE_ANON_KEY`)
   - Service role key (`SUPABASE_SERVICE_ROLE_KEY`)
   - Connection pooler details (Host, Port, User, Database name, Password).

---

## 2. Render Web Service (PHP 8.2 Backend API)

1. Sign in to [render.com](https://render.com) and click **New + > Web Service**.
2. Connect your GitHub repository.
3. Configure the service:
   - **Name**: `mythora-api`
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile`
   - **Plan**: Starter
4. Add the following **Environment Variables**:
   ```env
   APP_ENV=production
   DB_HOST=<supabase-db-host>
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.<your-project-ref>
   DB_PASSWORD=<your-db-password>
   SUPABASE_URL=https://<your-project-ref>.supabase.co
   SUPABASE_SERVICE_ROLE_KEY=<your-service-role-key>
   SUPABASE_STORAGE_BUCKET=mythora-media
   GEMINI_API_KEY=<your-gemini-api-key>
   FFMPEG_PATH=ffmpeg
   ```
5. Deploy the Web Service. Copy the assigned URL (e.g., `https://mythora-api.onrender.com`).

---

## 3. Render Background Worker (PHP CLI + FFmpeg)

Video rendering runs asynchronously outside the web request cycle via a dedicated worker:

1. On Render, click **New + > Background Worker**.
2. Connect the same repository.
3. Configure the worker:
   - **Name**: `mythora-worker`
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile`
   - **Start Command**: `php worker/worker.php`
   - **Plan**: Starter (or Standard for faster FFmpeg encoding)
4. Add the exact same **Environment Variables** used in the Web Service above.
5. Deploy the worker. It will immediately begin polling the PostgreSQL `generation_jobs` table for pending tasks.

---

## 4. Render Static Site (Frontend)

1. On Render, click **New + > Static Site**.
2. Connect your repository.
3. Configure the static site:
   - **Name**: `mythora`
   - **Branch**: `master` (or `main`)
   - **Root Directory**: `frontend`
   - **Publish Directory**: `.`
   - **Build Command**: *(leave empty)*
4. Under **Redirects/Rewrites**, add an API proxy rule:
   - **Source**: `/api/*`
   - **Destination**: `https://mythora-api.onrender.com/api/*`
   - **Action**: `Rewrite`
5. Deploy the Static Site. Your application is live at `https://mythora.onrender.com`.
