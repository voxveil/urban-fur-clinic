let clinicCsrfToken = '';
const clinicCatalogCategoryChannel = 'BroadcastChannel' in window
  ? new BroadcastChannel('clinic-catalog-categories')
  : null;

if (clinicCatalogCategoryChannel) {
  clinicCatalogCategoryChannel.addEventListener('message', event => {
    if (event.data?.type === 'updated') {
      window.dispatchEvent(new Event('clinic:catalog-categories-updated'));
    }
  });
}

function clinicNotifyCatalogCategoriesChanged() {
  window.dispatchEvent(new Event('clinic:catalog-categories-updated'));
  clinicCatalogCategoryChannel?.postMessage({ type: 'updated' });
}

function clinicOnCatalogCategoriesChanged(callback) {
  window.addEventListener('clinic:catalog-categories-updated', callback);
}

const clinicAuditUpdateChannel = 'BroadcastChannel' in window
  ? new BroadcastChannel('clinic-audit-history')
  : null;

if (clinicAuditUpdateChannel) {
  clinicAuditUpdateChannel.addEventListener('message', event => {
    if (event.data?.type === 'updated') {
      window.dispatchEvent(new Event('clinic:audit-history-updated'));
    }
  });
}

function clinicNotifyAuditHistoryUpdated() {
  window.dispatchEvent(new Event('clinic:audit-history-updated'));
  clinicAuditUpdateChannel?.postMessage({ type: 'updated' });
}

async function clinicRequest(resource, action = '', payload = null, query = {}) {
  const options = { credentials: 'same-origin', headers: {} };
  let url = `api.php?resource=${encodeURIComponent(resource)}`;
  const queryString = new URLSearchParams(query).toString();
  if (queryString) url += `&${queryString}`;

  if (payload !== null) {
    if (!clinicCsrfToken) {
      const tokenResponse = await fetch('api.php?resource=token', { credentials: 'same-origin' });
      const tokenBody = await tokenResponse.json();
      if (!tokenResponse.ok) {
        throw new Error(tokenBody.error || 'Unable to verify your session.');
      }
      clinicCsrfToken = tokenBody.csrfToken;
    }
    options.method = 'POST';
    options.headers['Content-Type'] = 'application/json';
    options.headers['X-CSRF-Token'] = clinicCsrfToken;
    options.body = JSON.stringify({ ...payload, action });
  }

  const response = await fetch(url, options);
  let body;
  try {
    body = await response.json();
  } catch (error) {
    throw new Error(`The server returned an invalid response (HTTP ${response.status}).`);
  }
  if (!response.ok) {
    if (response.status === 419) {
      clinicCsrfToken = '';
    }
    throw new Error(body.error || `Request failed (HTTP ${response.status}).`);
  }

  if (payload !== null) clinicNotifyAuditHistoryUpdated();
  return body;
}

function clinicEscape(value) {
  return String(value ?? '').replace(/[&<>"']/g, character => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  })[character]);
}

function clinicShowError(error) {
  window.alert(error instanceof Error ? error.message : String(error));
}

document.addEventListener('DOMContentLoaded', async () => {
  const sidebarBrand = document.querySelector('.sidebar-brand');
  if (!sidebarBrand || sidebarBrand.closest('.view:not(.active)')) return;
  const responsiveSidebar = document.querySelector('.sidebar');
  if (responsiveSidebar && !responsiveSidebar.closest('#dashboard-view') && !document.body.classList.contains('appointments-page')) {
    document.body.classList.add('responsive-shell');
    const navToggle = document.createElement('button');
    navToggle.className = 'responsive-nav-toggle';
    navToggle.type = 'button';
    navToggle.setAttribute('aria-label', 'Open navigation');
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-controls', 'responsiveSidebar');
    navToggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    responsiveSidebar.id = 'responsiveSidebar';
    const backdrop = document.createElement('div');
    backdrop.className = 'responsive-nav-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.prepend(backdrop, navToggle);
    const closeNav = () => {
      document.body.classList.remove('responsive-nav-open');
      navToggle.setAttribute('aria-expanded', 'false');
      navToggle.setAttribute('aria-label', 'Open navigation');
    };
    navToggle.addEventListener('click', () => {
      const open = document.body.classList.toggle('responsive-nav-open');
      navToggle.setAttribute('aria-expanded', String(open));
      navToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    });
    backdrop.addEventListener('click', closeNav);
    responsiveSidebar.querySelectorAll('.nav-item a').forEach(link => link.addEventListener('click', closeNav));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeNav(); });
  }
  try {
    const { data: settings } = await clinicRequest('settings');
    const useSystemColors = settings.theme_mode === 'system';
    const themeColor = useSystemColors ? '#0ea5e9' : settings.theme_color;
    const sidebarColor = useSystemColors ? '#0284c7' : settings.sidebar_color;
    if (/^#[0-9a-fA-F]{6}$/.test(themeColor || '')) {
      document.documentElement.style.setProperty('--primary-color', themeColor);
      document.documentElement.style.setProperty('--primary-hover', useSystemColors ? '#0284c7' : themeColor);
    }
    if (/^#[0-9a-fA-F]{6}$/.test(sidebarColor || '')) {
      document.documentElement.style.setProperty('--sidebar-bg', sidebarColor);
    }
    if (settings.theme_mode === 'dark' || settings.theme_mode === 'light') {
      document.documentElement.dataset.theme = settings.theme_mode;
    } else {
      delete document.documentElement.dataset.theme;
    }
    if (settings.clinic_logo_url) {
      document.querySelectorAll('.sidebar-brand').forEach(brand => {
        const icon = brand.querySelector('i');
        if (!icon) return;
        const logo = document.createElement('img');
        logo.className = 'clinic-sidebar-logo';
        logo.src = settings.clinic_logo_url;
        logo.alt = '';
        icon.replaceWith(logo);
      });
    }
  } catch (error) {
    console.error('Unable to load clinic branding settings.', error);
  }
});
