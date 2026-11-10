-- ============================================================================
-- VYRAL STUDIO - DATABASE SCHEMA MIGRATION 002
-- Target: Supabase PostgreSQL (15+)
-- Description: Row Level Security (RLS) Policies, Triggers, & Functions
-- ============================================================================

-- 1. ENABLE ROW LEVEL SECURITY ACROSS ALL TABLES
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.brand_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.projects ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.scenes ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.videos ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.media_assets ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.generation_jobs ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.credit_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.api_usage_logs ENABLE ROW LEVEL SECURITY;

-- 2. HELPER FUNCTION TO CHECK ADMIN STATUS
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS BOOLEAN AS $$
BEGIN
  RETURN EXISTS (
    SELECT 1 FROM public.profiles
    WHERE id = auth.uid() AND role = 'admin'
  );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 3. PROFILES POLICIES
CREATE POLICY "Users can read their own profile"
    ON public.profiles FOR SELECT
    USING (auth.uid() = id OR public.is_admin());

CREATE POLICY "Users can update their own profile display name and avatar"
    ON public.profiles FOR UPDATE
    USING (auth.uid() = id)
    WITH CHECK (auth.uid() = id);

-- 4. BRAND PROFILES POLICIES
CREATE POLICY "Users can manage their brand profiles"
    ON public.brand_profiles FOR ALL
    USING (auth.uid() = user_id OR public.is_admin())
    WITH CHECK (auth.uid() = user_id);

-- 5. PROJECTS POLICIES
CREATE POLICY "Users can view and manage their projects"
    ON public.projects FOR ALL
    USING (auth.uid() = user_id OR public.is_admin())
    WITH CHECK (auth.uid() = user_id);

-- 6. SCENES POLICIES
CREATE POLICY "Users can view and manage scenes belonging to their projects"
    ON public.scenes FOR ALL
    USING (
        EXISTS (
            SELECT 1 FROM public.projects
            WHERE projects.id = scenes.project_id
            AND (projects.user_id = auth.uid() OR public.is_admin())
        )
    )
    WITH CHECK (
        EXISTS (
            SELECT 1 FROM public.projects
            WHERE projects.id = scenes.project_id
            AND projects.user_id = auth.uid()
        )
    );

-- 7. VIDEOS POLICIES
CREATE POLICY "Users can view their own videos"
    ON public.videos FOR SELECT
    USING (auth.uid() = user_id OR public.is_admin());

CREATE POLICY "Users can delete their own videos"
    ON public.videos FOR DELETE
    USING (auth.uid() = user_id OR public.is_admin());

-- 8. MEDIA ASSETS POLICIES
CREATE POLICY "Users can view and manage their media assets"
    ON public.media_assets FOR ALL
    USING (auth.uid() = user_id OR public.is_admin())
    WITH CHECK (auth.uid() = user_id);

-- 9. GENERATION JOBS POLICIES
CREATE POLICY "Users can view their generation jobs"
    ON public.generation_jobs FOR SELECT
    USING (auth.uid() = user_id OR public.is_admin());

CREATE POLICY "Users can cancel their pending jobs"
    ON public.generation_jobs FOR UPDATE
    USING (auth.uid() = user_id)
    WITH CHECK (auth.uid() = user_id AND status = 'cancelled');

-- 10. CREDIT TRANSACTIONS POLICIES
CREATE POLICY "Users can read their own credit history"
    ON public.credit_transactions FOR SELECT
    USING (auth.uid() = user_id OR public.is_admin());

-- 11. SUBSCRIPTIONS POLICIES
CREATE POLICY "Users can view their own subscription"
    ON public.subscriptions FOR SELECT
    USING (auth.uid() = user_id OR public.is_admin());

-- 12. API USAGE LOGS POLICIES
CREATE POLICY "Admins can view API usage logs"
    ON public.api_usage_logs FOR SELECT
    USING (public.is_admin());

-- 13. AUTOMATIC PROFILE INITIALIZATION TRIGGER
-- When a user registers through Supabase Auth, automatically create profile & default brand profile
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
DECLARE
    new_profile_id UUID;
BEGIN
    INSERT INTO public.profiles (id, email, display_name, credits_balance, role)
    VALUES (
        NEW.id,
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'full_name', split_part(NEW.email, '@', 1)),
        50,
        'user'
    )
    RETURNING id INTO new_profile_id;

    -- Give welcome credits transaction
    INSERT INTO public.credit_transactions (user_id, amount, transaction_type, description)
    VALUES (new_profile_id, 50, 'initial_grant', 'Welcome bonus credits for sign up');

    -- Initialize default Content DNA brand profile
    INSERT INTO public.brand_profiles (user_id, brand_name, visual_aesthetic, narration_tone)
    VALUES (new_profile_id, 'My Studio', 'Cinematic', 'energetic');

    -- Initialize default subscription
    INSERT INTO public.subscriptions (user_id, plan_tier, monthly_credits)
    VALUES (new_profile_id, 'free', 50);

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Bind trigger to auth.users table
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

