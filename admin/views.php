(() => {
  const rows = [...document.querySelectorAll('#product-rows tr')];
  const search = document.getElementById('search');
  const category = document.getElementById('category');
  const lowButton = document.getElementById('low-stock-filter');
  const count = document.getElementById('product-count');
  const showing = document.getElementById('showing-count');
  const empty = document.getElementById('empty-state');
  const modalBackdrop = document.getElementById('product-modal');
  const form = document.getElementById('product-form');
  const toast = document.getElementById('toast');
  let lowOnly = false;
  let toastTimer;

  function notify(message) {
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
  }

  function updateRows() {
    const query = search.value.trim().toLowerCase();
    const selectedCategory = category.value;
    let visible = 0;
    rows.forEach(row => {
      const matches = (!query || row.dataset.name.includes(query) || row.dataset.barcode.includes(query)) &&
        (!selectedCategory || row.dataset.category === selectedCategory) &&
        (!lowOnly || Number(row.dataset.stock) <= 2);
      row.hidden = !matches;
      if (matches) visible++;
    });
    count.textContent = `${visible} ${visible === 1 ? 'product' : 'products'}`;
    showing.textContent = visible;
    empty.hidden = visible !== 0;
    document.getElementById('clear-filters').style.display = query || selectedCategory || lowOnly ? 'inline' : 'none';
  }

  search.addEventListener('input', updateRows);
  category.addEventListener('change', updateRows);
  lowButton.addEventListener('click', () => {
    lowOnly = !lowOnly;
    lowButton.classList.toggle('selected', lowOnly);
    updateRows();
  });
  document.getElementById('stock-metric').addEventListener('click', () => {
    lowOnly = true;
    lowButton.classList.add('selected');
    updateRows();
    document.querySelector('.main-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  document.getElementById('clear-filters').addEventListener('click', () => {
    search.value = '';
    category.value = '';
    lowOnly = false;
    lowButton.classList.remove('selected');
    updateRows();
  });

  const openModal = () => { modalBackdrop.hidden = false; form.elements.name.focus(); };
  const closeModal = () => { modalBackdrop.hidden = true; form.reset(); };
  document.getElementById('add-product').addEventListener('click', openModal);
  document.getElementById('close-modal').addEventListener('click', closeModal);
  document.getElementById('cancel-modal').addEventListener('click', closeModal);
  modalBackdrop.addEventListener('click', event => { if (event.target === modalBackdrop) closeModal(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modalBackdrop.hidden) closeModal(); });

  form.addEventListener('submit', event => {
    event.preventDefault();
    const data = new FormData(form);
    const name = String(data.get('name')).trim();
    const sell = Number(data.get('sell')) || 0;
    const cost = Number(data.get('cost')) || 0;
    const stock = Math.max(0, Number(data.get('stock')) || 0);
    const barcode = String(data.get('barcode')).trim();
    const supplier = String(data.get('supplier')).trim() || '—';
    const group = String(data.get('category'));
    const tr = document.createElement('tr');
    tr.dataset.name = name.toLowerCase();
    tr.dataset.category = group;
    tr.dataset.barcode = barcode.toLowerCase();
    tr.dataset.stock = String(stock);
    const markup = cost > 0 ? Math.round(((sell - cost) / cost) * 100) : 0;
    const money = value => `₱${value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const cells = [
      `<span class="product-name">${escapeHtml(name)}</span><span class="product-category">${escapeHtml(group)}</span>`,
      money(sell), money(cost), `<span class="markup-chip">${markup}%</span>`,
      `<span class="stock-badge ${stock <= 2 ? 'stock-low' : ''}">${stock}${stock <= 2 ? ' · Low' : ''}</span>`,
      escapeHtml(barcode || '—'), escapeHtml(supplier), '<button class="row-menu" type="button" aria-label="More actions">···</button>'
    ];
    cells.forEach(value => { const td = document.createElement('td'); td.innerHTML = value; tr.appendChild(td); });
    document.getElementById('product-rows').prepend(tr);
    rows.unshift(tr);
    closeModal();
    updateRows();
    notify('Product added to inventory.');
  });

  function escapeHtml(value) {
    return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
  }

  document.getElementById('sync-now').addEventListener('click', () => {
    const now = new Date();
    document.getElementById('sync-time').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    notify('Everything is up to date.');
  });
  document.getElementById('bg-sync').addEventListener('click', () => notify('Background sync is enabled.'));
  document.getElementById('sign-out').addEventListener('click', () => notify('You are signed in as morris.'));
  document.getElementById('review-merge').addEventListener('click', () => notify('Duplicate products are ready for review.'));
  document.getElementById('restock-link').addEventListener('click', event => { event.preventDefault(); notify('Restock report is ready to print or export.'); });

  async function showTab(view, { updateHistory = true } = {}) {
    if (view === 'Inventory') {
      if (updateHistory) history.pushState({ view }, '', window.location.pathname);
      window.location.href = window.location.pathname;
      return;
    }

    const allowed = ['Sales', 'Receivables', 'Payables', 'Reports', 'Summary', 'Users', 'Settings'];
    if (!allowed.includes(view)) return;

    const panel = document.querySelector('.main-panel');
    if (!panel) return;

    panel.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(`admin/views.php?view=${encodeURIComponent(view)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const content = await response.text();

      if (!response.ok) {
        panel.innerHTML = content || '<section class="tab-panel"><h2>Unable to load this tab</h2><p>The server responded with an error.</p></section>';
        return;
      }

      panel.innerHTML = content;
      document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.toggle('active', item.dataset.view === view);
      });
      document.getElementById('page-title').textContent = view;

      if (updateHistory) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        history.pushState({ view }, '', url);
      }
    } catch (error) {
      panel.innerHTML = '<section class="tab-panel"><h2>Could not load this tab</h2><p>Check the server connection and try again.</p></section>';
    } finally {
      panel.removeAttribute('aria-busy');
    }
  }

  document.addEventListener('click', event => {
    const tab = event.target.closest('.nav-item, .metric-card[data-view]');
    if (tab && tab.dataset.view) {
      event.preventDefault();
      showTab(tab.dataset.view);
      return;
    }

    const exportButton = event.target.closest('[data-export-table]');
    if (exportButton) {
      const table = document.getElementById(exportButton.dataset.exportTable);
      if (!table) return;
      const lines = [...table.querySelectorAll('tr')].filter(row => !row.hidden).map(row => [...row.querySelectorAll('th, td')].map(cell => `"${cell.innerText.trim().replace(/"/g, '""')}"`).join(','));
      const csv = lines.join('\n');
      const link = document.createElement('a');
      const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
      link.href = url; link.download = exportButton.dataset.filename || 'ais-export.csv'; link.click(); URL.revokeObjectURL(url);
      notify('CSV exported.');
    }
  });

  window.addEventListener('popstate', event => {
    const view = event.state?.view || new URLSearchParams(window.location.search).get('view') || 'Inventory';
    showTab(view, { updateHistory: false });
  });

  document.addEventListener('input', event => {
    const input = event.target.closest('[data-table-filter]');
    if (!input) return;
    const table = document.getElementById(input.dataset.tableFilter);
    if (!table) return;
    const query = input.value.trim().toLowerCase();
    [...table.querySelectorAll('tbody tr')].forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(query); });
  });

  document.addEventListener('submit', event => {
    if (event.target.id !== 'store-settings') return;
    event.preventDefault();
    const data = new FormData(event.target);
    localStorage.setItem('ais-store-settings', JSON.stringify({ store_name: data.get('store_name'), stock_threshold: data.get('stock_threshold') }));
    notify('Preferences saved in this browser.');
  });

  const initialView = new URLSearchParams(window.location.search).get('view');
  if (initialView && initialView !== 'Inventory') {
    showTab(initialView, { updateHistory: false });
  }
})();
