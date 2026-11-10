/**
 * VYRAL STUDIO - Core Application Initializer & UI Helpers
 * Modular shared functions across Dashboard, Generator, Editor & Library
 */

// Mobile Navigation Drawer Controller
function initMobileNav() {
  const toggleBtn = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  if (!toggleBtn || !sidebar) return;

  // Create backdrop if not already created
  let backdrop = document.querySelector('.sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'sidebar-backdrop';
    document.body.appendChild(backdrop);
  }

  function toggleSidebar() {
    const isOpen = sidebar.classList.toggle('mobile-open');
    backdrop.classList.toggle('active', isOpen);
  }

  function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('active');
  }

  toggleBtn.addEventListener('click', toggleSidebar);
  backdrop.addEventListener('click', closeSidebar);
}

// Global XSS Escape Utility
function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[m]);
}

// Auto-run on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
});

