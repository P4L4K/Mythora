/**
 * VYRAL STUDIO - Centralized API HTTP Client
 * Enforces JWT Bearer Authorization, JSON parsing, and unified error handling
 */
class ApiClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl || (window.MYTHORA_CONFIG ? window.MYTHORA_CONFIG.API_BASE_URL : '/api');
  }

  getAuthHeader() {
    const token = localStorage.getItem('mythora_access_token') || localStorage.getItem('vyral_access_token');
    return token ? { 'Authorization': `Bearer ${token}` } : {};
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    const defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...this.getAuthHeader()
    };

    const config = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers
      }
    };

    try {
      const response = await fetch(url, config);
      
      // Auto-handle 401 Unauthorized
      if (response.status === 401 && !endpoint.includes('/auth/login')) {
        localStorage.removeItem('mythora_access_token');
        localStorage.removeItem('mythora_user');
        if (!window.location.pathname.includes('login.html') && !window.location.pathname.endsWith('index.html')) {
          window.location.href = '/login.html?expired=true';
        }
      }

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(data.error || data.message || `Request failed with status ${response.status}`);
      }

      return data;
    } catch (err) {
      console.error(`[API Error] ${options.method || 'GET'} ${endpoint}:`, err);
      throw err;
    }
  }

  get(endpoint, headers = {}) {
    return this.request(endpoint, { method: 'GET', headers });
  }

  post(endpoint, body = {}, headers = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(body),
      headers
    });
  }

  put(endpoint, body = {}, headers = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(body),
      headers
    });
  }

  delete(endpoint, headers = {}) {
    return this.request(endpoint, { method: 'DELETE', headers });
  }
}

window.apiClient = new ApiClient();

