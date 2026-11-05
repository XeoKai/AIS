(() => {
  const rows = [...document.querySelectorAll('#product-rows tr')];
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const search = $('#search');
  const category = $('#category');
  const lowButton = $('#low-stock-filter');
  const count = $('#product-count');
  const showing = $('#showing-count');
  const empty = $('#empty-state');
  const modalBackdrop = $('#product-modal');
  const form = $('#product-form');
  const toast = $('#toast');
  let lowOnly = false;
  let toastTimer;

  function notify(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
  }

  function updateRows() {
    if (!search || !category || !count || !showing || !empty) return;
    const query = search.value.trim().toLowerCase();
    const selectedCategory = category.value;
    let visible = 0;
    rows.forEach(row => {
      const matches = (!query || row.dataset.name.includes(query) || row.dataset.barcode.includes(query)) &&
        (!selectedCategory || row.dataset.category === selectedCategory) && (!lowOnly || Number(row.dataset.stock) <= 2);
      row.hidden = !matches;
      if (matches) visible++;
    });
    count.textContent = `${visible} ${visible === 1 ? 'product' : 'products'}`;
    showing.textContent = visible;
    empty.hidden = visible !== 0;
    const clear = $('#clear-filters');
    if (clear) clear.style.display = query || selectedCategory || lowOnly ? 'inline' : 'none';
  }

  search?.addEventListener('input', updateRows);
  category?.addEventListener('change', updateRows);
  lowButton?.addEventListener('click', () => {
    lowOnly = !lowOnly;
    lowButton.classList.toggle('selected', lowOnly);
    updateRows();
  });
  $('#stock-metric')?.addEventListener('click', () => {
    lowOnly = true;
    lowButton?.classList.add('selected');
    updateRows();
    $('.main-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  $('#clear-filters')?.addEventListener('click', () => {
    if (search) search.value = '';
    if (category) category.value = '';
    lowOnly = false;
    lowButton?.classList.remove('selected');
    updateRows();
  });

  const openModal = () => { if (modalBackdrop && form) { modalBackdrop.hidden = false; form.elements.name.focus(); } };
  const closeModal = () => { if (modalBackdrop && form) { modalBackdrop.hidden = true; form.reset(); } };
  $('#add-product')?.addEventListener('click', openModal);
  $('#close-modal')?.addEventListener('click', closeModal);
  $('#cancel-modal')?.addEventListener('click', closeModal);
  modalBackdrop?.addEventListener('click', event => { if (event.target === modalBackdrop) closeModal(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && modalBackdrop && !modalBackdrop.hidden) closeModal(); });

  form?.addEventListener('submit', event => {
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
    Object.assign(tr.dataset, { name: name.toLowerCase(), category: group, barcode: barcode.toLowerCase(), stock: String(stock) });
    const markup = cost > 0 ? Math.round(((sell - cost) / cost) * 100) : 0;
    const money = value => `₱${value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const cells = [`<span class="product-name">${escapeHtml(name)}</span><span class="product-category">${escapeHtml(group)}</span>`, money(sell), money(cost), `<span class="markup-chip">${markup}%</span>`, `<span class="stock-badge ${stock <= 2 ? 'stock-low' : ''}">${stock}${stock <= 2 ? ' · Low' : ''}</span>`, escapeHtml(barcode || '—'), escapeHtml(supplier), '<button class="row-menu" type="button" aria-label="More actions">···</button>'];
    cells.forEach(value => { const td = document.createElement('td'); td.innerHTML = value; tr.appendChild(td); });
    $('#product-rows')?.prepend(tr);
    rows.unshift(tr);
    closeModal(); updateRows(); notify('Product added to this page. Connect inventory saving to persist it in the database.');
  });

  function escapeHtml(value) {
    return value.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
  }

  $('#sync-now')?.addEventListener('click', () => {
    const time = $('#sync-time');
    if (time) time.textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    notify('Refresh the page to load the latest database records.');
  });
  $('#bg-sync')?.addEventListener('click', () => notify('Background sync is not configured in this installation.'));
  $('#sign-out')?.addEventListener('click', () => notify('Sign-out is unavailable until authentication is configured.'));
  $('#review-merge')?.addEventListener('click', () => notify('Review duplicate barcodes and product records before merging.'));
  $('#restock-link')?.addEventListener('click', event => { event.preventDefault(); notify('Use the inventory export to prepare a restock list.'); });

  async function showTab(view) {
    if (view === 'Inventory') {
      window.location.href = 'index.php';
      return;
    }
    const panel = $('.main-panel');
    if (!panel) return;
    panel.setAttribute('aria-busy', 'true');
    try {
      const response = await fetch(`admin/views.php?view=${encodeURIComponent(view)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!response.ok) throw new Error('Tab could not be loaded');
      panel.innerHTML = await response.text();
      $$('.nav-item').forEach(item => {
        const active = item.dataset.view === view;
        item.classList.toggle('active', active);
        if (active) item.setAttribute('aria-current', 'page'); else item.removeAttribute('aria-current');
      });
      const title = $('#page-title');
      if (title) title.textContent = view;
      panel.focus({ preventScroll: true });
    } catch (error) {
      notify('Could not load this tab. Check the server connection and try again.');
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
    if (exportButton) exportTable(exportButton.dataset.exportTable, exportButton.dataset.filename);
  });

  document.addEventListener('input', event => {
    const input = event.target.closest('[data-table-filter]');
    if (!input) return;
    const table = document.getElementById(input.dataset.tableFilter);
    if (!table) return;
    const query = input.value.trim().toLowerCase();
    $$('tbody tr', table).forEach(row => { row.hidden = !row.textContent.toLowerCase().includes(query); });
  });

  document.addEventListener('submit', event => {
    if (event.target.id !== 'store-settings') return;
    event.preventDefault();
    const data = new FormData(event.target);
    localStorage.setItem('ais-store-settings', JSON.stringify({ store_name: data.get('store_name'), stock_threshold: data.get('stock_threshold') }));
    notify('Preferences saved in this browser.');
  });

  function exportTable(id, filename = 'ais-export.csv') {
    const table = document.getElementById(id);
    if (!table) return;
    const lines = $$('tr', table).filter(row => !row.hidden).map(row => $$('th,td', row).map(cell => `"${cell.innerText.trim().replace(/"/g, '""')}"`).join(','));
    const link = document.createElement('a');
    const url = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' }));
    link.href = url; link.download = filename; link.click(); URL.revokeObjectURL(url);
    notify('CSV exported.');
  }

  $('#export-csv')?.addEventListener('click', () => {
    const headers = ['Product', 'Category', 'Sell price', 'Cost price', 'Stock', 'Barcode', 'Supplier'];
    const data = rows.filter(row => !row.hidden).map(row => [$('.product-name', row)?.textContent || '', row.dataset.category, ...[...row.cells].slice(1, 3).map(cell => cell.textContent.trim()), row.dataset.stock, row.dataset.barcode, row.cells[6]?.textContent.trim() || '']);
    const csv = [headers, ...data].map(line => line.map(value => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
    const link = document.createElement('a');
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    link.href = url; link.download = 'ais-inventory.csv'; link.click(); URL.revokeObjectURL(url);
    notify('Inventory CSV exported.');
  });
})();
