-- ============================================================================
-- VYRAL STUDIO - DATABASE SCHEMA MIGRATION 001
-- Target: Supabase PostgreSQL (15+)
-- Description: Core schema, tables, foreign keys, constraints, and indexes
-- ============================================================================

-- Enable UUID extension if not already enabled
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- 1. PROFILES (Extends Supabase auth.users)
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email TEXT NOT NULL UNIQUE,
    display_name TEXT,
    avatar_url TEXT,
    role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    credits_balance INTEGER NOT NULL DEFAULT 50 CHECK (credits_balance >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 2. BRAND PROFILES (Feature A: Content DNA)
CREATE TABLE IF NOT EXISTS public.brand_profiles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    brand_name TEXT NOT NULL DEFAULT 'My Brand',
    brand_colors JSONB NOT NULL DEFAULT '{"primary": "#10b981", "secondary": "#8b5cf6", "background": "#070c0a"}'::jsonb,
    preferred_font TEXT NOT NULL DEFAULT 'Inter, sans-serif',
    narration_tone TEXT NOT NULL DEFAULT 'energetic' CHECK (narration_tone IN ('energetic', 'calm', 'dramatic', 'educational', 'mysterious', 'professional')),
    preferred_language TEXT NOT NULL DEFAULT 'en-US',
    visual_aesthetic TEXT NOT NULL DEFAULT 'Cinematic' CHECK (visual_aesthetic IN ('Cinematic', 'Minimalist', 'Documentary', 'Anime-inspired', 'Corporate', 'Fantasy', 'Educational')),
    content_style TEXT NOT NULL DEFAULT 'Storytelling',
    subtitle_styling JSONB NOT NULL DEFAULT '{"font_size": 24, "font_color": "#ffffff", "highlight_color": "#10b981", "position": "bottom", "animation": "bounce"}'::jsonb,
    is_default BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 3. PROJECTS
CREATE TABLE IF NOT EXISTS public.projects (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    input_type TEXT NOT NULL CHECK (input_type IN ('prompt', 'script', 'url')),
    source_content TEXT NOT NULL,
    target_platform TEXT NOT NULL DEFAULT 'tiktok' CHECK (target_platform IN ('tiktok', 'instagram_reels', 'youtube_shorts')),
    aspect_ratio TEXT NOT NULL DEFAULT '9:16' CHECK (aspect_ratio IN ('9:16', '16:9', '1:1')),
    target_duration_sec INTEGER NOT NULL DEFAULT 30 CHECK (target_duration_sec BETWEEN 10 AND 180),
    visual_style TEXT NOT NULL DEFAULT 'Cinematic',
    voice_id TEXT NOT NULL DEFAULT 'rachel',
    background_music TEXT DEFAULT 'mystic_ambient',
    selected_hook TEXT,
    status TEXT NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'generating', 'ready', 'failed')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 4. SCENES (Feature D: Smart Scene Editor)
CREATE TABLE IF NOT EXISTS public.scenes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
    scene_order INTEGER NOT NULL,
    narration_text TEXT NOT NULL,
    visual_prompt TEXT NOT NULL,
    subtitle_text TEXT NOT NULL,
    estimated_duration NUMERIC(5, 2) NOT NULL DEFAULT 4.00,
    media_asset_url TEXT,
    audio_asset_url TEXT,
    transition_type TEXT NOT NULL DEFAULT 'fade' CHECK (transition_type IN ('fade', 'wipeleft', 'wiperight', 'slideup', 'crossfade', 'none')),
    custom_styling JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    CONSTRAINT unique_project_scene_order UNIQUE (project_id, scene_order)
);

-- 5. VIDEOS (Rendered Outputs)
CREATE TABLE IF NOT EXISTS public.videos (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    storage_path TEXT NOT NULL,
    download_url TEXT NOT NULL,
    duration_sec NUMERIC(6, 2) NOT NULL,
    file_size_bytes BIGINT NOT NULL DEFAULT 0,
    resolution TEXT NOT NULL DEFAULT '1080x1920',
    aspect_ratio TEXT NOT NULL DEFAULT '9:16',
    format TEXT NOT NULL DEFAULT 'mp4',
    rendered_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 6. MEDIA ASSETS
CREATE TABLE IF NOT EXISTS public.media_assets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    project_id UUID REFERENCES public.projects(id) ON DELETE SET NULL,
    asset_type TEXT NOT NULL CHECK (asset_type IN ('image', 'audio', 'video', 'music')),
    storage_path TEXT NOT NULL,
    public_url TEXT NOT NULL,
    source TEXT NOT NULL DEFAULT 'ai_generated',
    metadata JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 7. GENERATION JOBS (Job Queue)
CREATE TABLE IF NOT EXISTS public.generation_jobs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    project_id UUID NOT NULL REFERENCES public.projects(id) ON DELETE CASCADE,
    job_type TEXT NOT NULL DEFAULT 'full_video' CHECK (job_type IN ('full_video', 'scene_regenerate', 'script_only', 'audio_only')),
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),
    current_stage TEXT NOT NULL DEFAULT 'queued' CHECK (current_stage IN ('queued', 'generating_script', 'generating_visuals', 'synthesizing_audio', 'building_subtitles', 'rendering_ffmpeg', 'uploading_media', 'completed', 'failed')),
    progress_percent INTEGER NOT NULL DEFAULT 0 CHECK (progress_percent BETWEEN 0 AND 100),
    stage_metadata JSONB DEFAULT '{}'::jsonb,
    error_message TEXT,
    retry_count INTEGER NOT NULL DEFAULT 0,
    target_scene_id UUID REFERENCES public.scenes(id) ON DELETE SET NULL,
    started_at TIMESTAMPTZ,
    completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 8. CREDIT TRANSACTIONS
CREATE TABLE IF NOT EXISTS public.credit_transactions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    amount INTEGER NOT NULL,
    transaction_type TEXT NOT NULL CHECK (transaction_type IN ('initial_grant', 'generation_debit', 'scene_regen_debit', 'admin_adjustment', 'purchase_credit', 'refund')),
    description TEXT NOT NULL,
    reference_id UUID,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 9. SUBSCRIPTIONS
CREATE TABLE IF NOT EXISTS public.subscriptions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    plan_tier TEXT NOT NULL DEFAULT 'free' CHECK (plan_tier IN ('free', 'creator_pro', 'studio_elite')),
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'canceled', 'past_due')),
    monthly_credits INTEGER NOT NULL DEFAULT 50,
    current_period_start TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    current_period_end TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now() + interval '30 days'),
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now()),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- 10. API USAGE LOGS
CREATE TABLE IF NOT EXISTS public.api_usage_logs (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    provider TEXT NOT NULL,
    service_type TEXT NOT NULL,
    tokens_consumed INTEGER DEFAULT 0,
    cost_estimated NUMERIC(8, 6) DEFAULT 0.000000,
    status_code INTEGER NOT NULL,
    response_time_ms INTEGER NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT timezone('utc'::text, now())
);

-- INDEXES FOR PERFORMANCE
CREATE INDEX IF NOT EXISTS idx_projects_user_id ON public.projects(user_id);
CREATE INDEX IF NOT EXISTS idx_projects_status ON public.projects(status);
CREATE INDEX IF NOT EXISTS idx_scenes_project_id ON public.scenes(project_id);
CREATE INDEX IF NOT EXISTS idx_generation_jobs_status ON public.generation_jobs(status);
CREATE INDEX IF NOT EXISTS idx_generation_jobs_user_id ON public.generation_jobs(user_id);
CREATE INDEX IF NOT EXISTS idx_videos_user_id ON public.videos(user_id);
CREATE INDEX IF NOT EXISTS idx_videos_project_id ON public.videos(project_id);
CREATE INDEX IF NOT EXISTS idx_credit_transactions_user_id ON public.credit_transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_brand_profiles_user_id ON public.brand_profiles(user_id);

