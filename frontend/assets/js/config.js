/**
 * MYTHORA - Environment Runtime Configuration
 */
window.MYTHORA_CONFIG = {
  APP_NAME: 'MYTHORA',
  API_BASE_URL: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1'
    ? 'http://localhost:8000/api'
    : 'https://mythora-api.onrender.com/api',
  SUPABASE_URL: window.__ENV_SUPABASE_URL || 'https://your-project.supabase.co',
  SUPABASE_ANON_KEY: window.__ENV_SUPABASE_ANON_KEY || 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
  POLL_INTERVAL_MS: 2500
};
