let clinicCsrfToken = '';

async function clinicRequest(resource, action = '', payload = null) {
  const options = { credentials: 'same-origin', headers: {} };
  let url = `api.php?resource=${encodeURIComponent(resource)}`;

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
  const dashboardView = document.getElementById('dashboard-view');
  if (!document.querySelector('.sidebar-brand') || (dashboardView && !dashboardView.classList.contains('active'))) return;
  try {
    const { data: settings } = await clinicRequest('settings');
    if (/^#[0-9a-fA-F]{6}$/.test(settings.theme_color || '')) {
      document.documentElement.style.setProperty('--primary-color', settings.theme_color);
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
