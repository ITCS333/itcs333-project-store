// PROVIDED — shared frontend shell with graceful-degradation helper.
// Each module's page lives under public/<module>/; this is the shared shell.

'use strict';

// Graceful degradation: show an inline banner when a dependency endpoint errors
// (e.g., a module returns MODULE_UNAVAILABLE / 503). Never let the page crash.
function showBanner(message) {
  const banner = document.getElementById('error-banner');
  if (!banner) return;
  banner.textContent = message;
  banner.style.display = 'block';
}

function hideBanner() {
  const banner = document.getElementById('error-banner');
  if (banner) banner.style.display = 'none';
}

// Fetch wrapper that degrades gracefully on 503/network errors.
async function apiFetch(path, options) {
  try {
    const res = await fetch(path, options);
    if (res.status === 503) {
      showBanner('A service is temporarily unavailable (503). Some features may not work.');
      return null;
    }
    return res;
  } catch (e) {
    showBanner('A service is unreachable. Some features may not work.');
    return null;
  }
}

async function loadProducts() {
  const res = await apiFetch('/api/catalog/products');
  if (!res) return;
  const body = await res.json();
  const list = document.getElementById('product-list');
  if (!list || !body.data) return;
  list.innerHTML = body.data.map((p) =>
    `<div class="card"><h3>${escapeHtml(p.name)}</h3><p>$${Number(p.price).toFixed(2)}</p></div>`
  ).join('');
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
}

document.addEventListener('DOMContentLoaded', loadProducts);
