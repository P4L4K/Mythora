/**
 * VYRAL STUDIO - Environment Runtime Configuration
 */
window.VYRAL_CONFIG = {
  APP_NAME: 'VYRAL STUDIO',
  API_BASE_URL: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost:8000/api'
    : '/api',
  SUPABASE_URL: window.__ENV_SUPABASE_URL || 'https://mock-supabase.local',
  SUPABASE_ANON_KEY: window.__ENV_SUPABASE_ANON_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.mock',
  POLL_INTERVAL_MS: 2500
};

