/**
 * VYRAL STUDIO - Authentication & Session Service
 * Interfaces with Supabase Auth & Backend Profile Verification
 */
class AuthService {
  constructor() {
    this.tokenKey = 'vyral_access_token';
    this.userKey = 'vyral_user';
  }

  isAuthenticated() {
    return !!localStorage.getItem(this.tokenKey);
  }

  getToken() {
    return localStorage.getItem(this.tokenKey);
  }

  getUser() {
    const data = localStorage.getItem(this.userKey);
    try {
      return data ? JSON.parse(data) : null;
    } catch {
      return null;
    }
  }

  setSession(token, user) {
    localStorage.setItem(this.tokenKey, token);
    localStorage.setItem(this.userKey, JSON.stringify(user));
  }

  async login(email, password) {
    // Calls PHP auth endpoint which verifies Supabase credentials or local auth token
    const res = await window.apiClient.post('/auth/login', { email, password });
    if (res.token && res.user) {
      this.setSession(res.token, res.user);
    }
    return res;
  }

  async register(email, password, displayName) {
    const res = await window.apiClient.post('/auth/register', {
      email,
      password,
      display_name: displayName
    });
    if (res.token && res.user) {
      this.setSession(res.token, res.user);
    }
    return res;
  }

  async getProfile() {
    const profile = await window.apiClient.get('/auth/profile');
    if (profile.user) {
      localStorage.setItem(this.userKey, JSON.stringify(profile.user));
    }
    return profile;
  }

  logout() {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    window.location.href = '/login.html';
  }

  requireAuth() {
    if (!this.isAuthenticated()) {
      window.location.href = `/login.html?redirect=${encodeURIComponent(window.location.pathname)}`;
    }
  }
}

window.authService = new AuthService();

