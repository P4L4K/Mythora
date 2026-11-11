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

## 2. Render Web Service (PHP 8.2 Backend API + Embedded Video Rendering)

> **Free Tier Architecture Note**: On Render, standalone "Background Workers" require a paid plan ($7/mo minimum). To run **100% FREE on Render**, you do **NOT** need to create a Background Worker! 
> 
> The MYTHORA backend Docker container includes both PHP and FFmpeg inside the **Free Web Service**. When the frontend triggers `/api/generation/start`, the Web Service executes `VideoRendererService` and renders the video directly inside the container, uploading it to Supabase Storage.

1. Sign in to [render.com](https://render.com) and click **New + > Web Service**.
2. Connect your GitHub repository.
3. Configure the service:
   - **Name**: `mythora-api`
   - **Runtime**: `Docker`
   - **Dockerfile Path**: `backend/Dockerfile`
   - **Plan**: **Free** ($0/month)
4. Add the following **Environment Variables**:
   ```env
   APP_ENV=production
   DB_HOST=<supabase-pooler-host>
   DB_PORT=6543
   DB_NAME=postgres
   DB_USER=postgres.<your-project-ref>
   DB_PASSWORD=<your-db-password>
   SUPABASE_URL=https://<your-project-ref>.supabase.co
   SUPABASE_SERVICE_ROLE_KEY=<your-service-role-key>
   SUPABASE_STORAGE_BUCKET=mythora-media
   GEMINI_API_KEY=<your-gemini-api-key>
   FFMPEG_PATH=ffmpeg
   FFPROBE_PATH=ffprobe
   ```
5. Deploy the Web Service. Copy the assigned URL (e.g., `https://mythora-api.onrender.com`).

---

## 3. Render Static Site (Frontend - 100% Free)

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

---

## Summary of Free 2-Service Topology

| Service | Type | Cost | Purpose |
|---|---|---|---|
| **`mythora`** | Static Site | **$0 / Free** | Vanilla JS/HTML5 Frontend UI, 3D Canvas, Studio & Generator |
| **`mythora-api`** | Web Service (Docker) | **$0 / Free** | PHP 8.2 API, Supabase Adapter, Gemini AI & In-container FFmpeg Rendering |
| **`Supabase`** | Database / Storage | **$0 / Free** | PostgreSQL Database, Auth, and `mythora-media` Video Storage Bucket |

*(No paid Background Worker is needed)*
