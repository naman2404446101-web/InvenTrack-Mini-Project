// ============================================================
// assets/js/app.js
// Main frontend application logic
// All data is loaded via Fetch API (no page reloads)
// ============================================================

// ============================================================
// UTILITIES
// ============================================================

const APP_BASE_URL = window.APP_BASE_URL || '';

/** Show a toast notification */
function toast(message, type = 'info') {
  const icons = {
    success: '✓',
    error:   '✕',
    info:    'ℹ'
  };
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = `<span style="font-weight:700">${icons[type] || icons.info}</span> ${message}`;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

/** Format a number as Indian Rupees */
function formatRupee(n) {
  return '₹' + parseFloat(n).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

/** Format a date string to readable DD-MMM-YYYY */
function formatDate(d) {
  if (!d) return '—';
  const date = new Date(d + 'T00:00:00');
  return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Show / hide a modal */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/** Simple API wrapper */
async function api(url, options = {}) {
  try {
    const res = await fetch(url, options);
    return await res.json();
  } catch (e) {
    return { success: false, message: 'Network error. Please try again.' };
  }
}

/** Get value from a form field */
function fv(id) { return document.getElementById(id)?.value ?? ''; }

// ============================================================
// NAVIGATION — show / hide sections without reload
// ============================================================
function showPage(page) {
  document.querySelectorAll('.page-section').forEach(el => el.classList.add('hidden'));
  const target = document.getElementById('page-' + page);
  if (target) target.classList.remove('hidden');

  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.page === page);
  });

  document.getElementById('topbar-title').textContent = {
    dashboard: 'Dashboard',
    products:  'Product Management',
    sales:     'Sales Management',
    inventory: 'Inventory Tracker',
  }[page] || page;

  // Load data for the selected page
  if (page === 'dashboard')  loadDashboard();
  if (page === 'products')   loadProducts();
  if (page === 'sales')      loadSales();
  if (page === 'inventory')  loadInventory();

  // Close sidebar on mobile
  document.querySelector('.sidebar').classList.remove('open');

  window._currentPage = page;
}

// ============================================================
// DASHBOARD
// ============================================================
async function loadDashboard() {
  const data = await api(`${APP_BASE_URL}/api/sales.php?action=dashboard`);
  if (!data.success) return;

  // Stat cards
  document.getElementById('stat-products').textContent = data.total_products;
  document.getElementById('stat-revenue').textContent  = formatRupee(data.total_revenue);
  document.getElementById('stat-sales').textContent    = data.total_sales;
  document.getElementById('stat-lowstock').textContent = data.low_stock;
  document.getElementById('stat-today').textContent    = formatRupee(data.today_revenue);

  // Low stock banner
  const banner = document.getElementById('low-stock-banner');
  if (data.low_stock > 0) {
    banner.classList.remove('hidden');
    banner.querySelector('span').textContent =
      `⚠ ${data.low_stock} product(s) are low on stock! Go to Inventory to view.`;
  } else {
    banner.classList.add('hidden');
  }

  // Recent transactions
  const tbody = document.getElementById('recent-transactions');
  if (data.recent.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted" style="text-align:center;padding:24px">No transactions yet</td></tr>';
  } else {
    tbody.innerHTML = data.recent.map(s => `
      <tr>
        <td class="font-mono text-accent">#${s.id}</td>
        <td>${escHtml(s.product_name)}</td>
        <td>${s.quantity}</td>
        <td class="font-mono">${formatRupee(s.total_price)}</td>
        <td>${formatDate(s.sale_date)}</td>
      </tr>
    `).join('');
  }

  // Charts
  buildRevenueChart(data.chart_data);
  buildTopProductsChart(data.top_products);
}

let revenueChart, topChart;

function buildRevenueChart(chartData) {
  const ctx = document.getElementById('revenue-chart').getContext('2d');

  // Fill missing days in last 7
  const days = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    days.push(d.toISOString().split('T')[0]);
  }
  const revenueMap = {};
  chartData.forEach(r => { revenueMap[r.sale_date] = parseFloat(r.revenue); });
  const labels   = days.map(d => new Date(d + 'T00:00:00').toLocaleDateString('en-IN', { day:'2-digit', month:'short' }));
  const revenues = days.map(d => revenueMap[d] || 0);

  if (revenueChart) revenueChart.destroy();
  revenueChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Revenue (₹)',
        data: revenues,
        backgroundColor: 'rgba(245,166,35,0.25)',
        borderColor: '#f5a623',
        borderWidth: 2,
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: '#2a2f45' }, ticks: { color: '#7880a0' } },
        y: { grid: { color: '#2a2f45' }, ticks: { color: '#7880a0',
          callback: v => '₹' + v.toLocaleString('en-IN') } }
      }
    }
  });
}

function buildTopProductsChart(topProducts) {
  const ctx = document.getElementById('top-products-chart').getContext('2d');
  if (topChart) topChart.destroy();
  topChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: topProducts.map(p => p.name),
      datasets: [{
        data: topProducts.map(p => parseFloat(p.revenue)),
        backgroundColor: ['#f5a623','#ff6b35','#3b82f6','#22c55e','#8b5cf6'],
        borderWidth: 0,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#7880a0', font: { size: 11 }, padding: 12 } }
      },
      cutout: '65%'
    }
  });
}

// ============================================================
// PRODUCTS
// ============================================================
async function loadProducts() {
  const search   = fv('product-search');
  const category = fv('product-category-filter');
  const params   = new URLSearchParams({ action: 'list', search, category });
  const data     = await api(`${APP_BASE_URL}/api/products.php?${params}`);
  if (!data.success) return;

  const tbody = document.getElementById('products-table');
  if (data.data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px" class="text-muted">No products found.</td></tr>`;
    return;
  }

  tbody.innerHTML = data.data.map(p => {
    const isLow = p.quantity <= p.low_stock_threshold;
    const badge = isLow
      ? `<span class="badge badge-danger">Low</span>`
      : `<span class="badge badge-success">OK</span>`;
    return `
      <tr>
        <td class="font-mono text-muted">${p.id}</td>
        <td><strong>${escHtml(p.name)}</strong></td>
        <td><span class="badge badge-info">${escHtml(p.category)}</span></td>
        <td class="font-mono">${formatRupee(p.price)}</td>
        <td class="font-mono">${p.quantity} ${badge}</td>
        <td class="text-muted text-sm">${p.low_stock_threshold}</td>
        <td>
          <div class="flex gap-2">
            <button class="btn btn-info btn-sm" onclick="editProduct(${p.id})">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id},'${escHtml(p.name).replace(/'/g,"\\'")}')">Delete</button>
          </div>
        </td>
      </tr>`;
  }).join('');

  // Also load categories for filter dropdown
  loadCategoryFilter();
}

async function loadCategoryFilter() {
  const data = await api(`${APP_BASE_URL}/api/products.php?action=categories`);
  if (!data.success) return;
  const sel = document.getElementById('product-category-filter');
  const cur = sel.value;
  sel.innerHTML = '<option value="">All Categories</option>' +
    data.data.map(c => `<option value="${escHtml(c)}" ${c===cur?'selected':''}>${escHtml(c)}</option>`).join('');

  // Also populate sale & add-product category dropdowns
  const saleProduct = document.getElementById('sale-product-select');
  // These are loaded separately in their own functions
}

function openAddProductModal() {
  document.getElementById('product-form').reset();
  document.getElementById('product-id').value = '';
  document.getElementById('product-modal-title').textContent = 'Add Product';
  openModal('product-modal');
}

async function editProduct(id) {
  const data = await api(`${APP_BASE_URL}/api/products.php?action=get&id=${id}`);
  if (!data.success) { toast(data.message, 'error'); return; }
  const p = data.data;
  document.getElementById('product-id').value        = p.id;
  document.getElementById('product-name').value      = p.name;
  document.getElementById('product-category').value  = p.category;
  document.getElementById('product-price').value     = p.price;
  document.getElementById('product-quantity').value  = p.quantity;
  document.getElementById('product-threshold').value = p.low_stock_threshold;
  document.getElementById('product-modal-title').textContent = 'Edit Product';
  openModal('product-modal');
}

async function saveProduct() {
  const id       = fv('product-id');
  const action   = id ? 'edit' : 'add';
  const formData = new FormData();
  formData.append('action',    action);
  formData.append('id',        id);
  formData.append('name',      fv('product-name'));
  formData.append('category',  fv('product-category'));
  formData.append('price',     fv('product-price'));
  formData.append('quantity',  fv('product-quantity'));
  formData.append('threshold', fv('product-threshold'));

  const btn  = document.getElementById('save-product-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';

  const data = await api(`${APP_BASE_URL}/api/products.php?action=${action}`, {
    method: 'POST', body: formData
  });

  btn.disabled = false; btn.textContent = 'Save Product';

  if (data.success) {
    toast(data.message, 'success');
    closeModal('product-modal');
    loadProducts();
  } else {
    toast(data.message, 'error');
  }
}

async function deleteProduct(id, name) {
  if (!confirm(`Delete "${name}"? This will also remove all sales records.`)) return;
  const formData = new FormData();
  formData.append('id', id);
  const data = await api(`${APP_BASE_URL}/api/products.php?action=delete`, {
    method: 'POST', body: formData
  });
  if (data.success) {
    toast(data.message, 'success');
    loadProducts();
  } else {
    toast(data.message, 'error');
  }
}

// ============================================================
// SALES
// ============================================================
async function loadSales() {
  const from   = fv('sales-from');
  const to     = fv('sales-to');
  const search = fv('sales-search');
  const params = new URLSearchParams({ action: 'list', from, to, search });
  const data   = await api(`${APP_BASE_URL}/api/sales.php?${params}`);
  if (!data.success) return;

  const tbody = document.getElementById('sales-table');
  if (data.data.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px" class="text-muted">No sales records found.</td></tr>`;
    return;
  }

  tbody.innerHTML = data.data.map(s => `
    <tr>
      <td class="font-mono text-accent">#${s.id}</td>
      <td><strong>${escHtml(s.product_name)}</strong><br><span class="text-muted text-sm">${escHtml(s.category)}</span></td>
      <td class="font-mono">${s.quantity}</td>
      <td class="font-mono">${formatRupee(s.unit_price)}</td>
      <td class="font-mono text-success">${formatRupee(s.total_price)}</td>
      <td>${escHtml(s.customer_name)}</td>
      <td>${formatDate(s.sale_date)}</td>
    </tr>`).join('');
}

async function loadProductsForSale() {
  const data = await api(`${APP_BASE_URL}/api/products.php?action=list`);
  if (!data.success) return;
  const sel = document.getElementById('sale-product-select');
  sel.innerHTML = '<option value="">-- Select Product --</option>' +
    data.data.map(p =>
      `<option value="${p.id}" data-price="${p.price}" data-qty="${p.quantity}">${escHtml(p.name)} (Stock: ${p.quantity})</option>`
    ).join('');
}

function openSaleModal() {
  document.getElementById('sale-form').reset();
  document.getElementById('sale-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('sale-unit-price').textContent = '₹0.00';
  document.getElementById('sale-total-price').textContent = '₹0.00';
  document.getElementById('sale-stock-info').textContent = '';
  loadProductsForSale();
  openModal('sale-modal');
}

function onProductSelect() {
  const sel  = document.getElementById('sale-product-select');
  const opt  = sel.selectedOptions[0];
  const price = parseFloat(opt?.dataset?.price || 0);
  const qty   = parseInt(opt?.dataset?.qty   || 0);
  document.getElementById('sale-unit-price').textContent  = formatRupee(price);
  document.getElementById('sale-stock-info').textContent  = qty > 0 ? `Available: ${qty} units` : '⚠ Out of stock';
  document.getElementById('sale-stock-info').className    = qty > 0 ? 'text-sm text-success' : 'text-sm text-danger';
  calculateSaleTotal();
}

function calculateSaleTotal() {
  const sel   = document.getElementById('sale-product-select');
  const opt   = sel.selectedOptions[0];
  const price = parseFloat(opt?.dataset?.price || 0);
  const qty   = parseInt(fv('sale-quantity') || 0);
  document.getElementById('sale-total-price').textContent = formatRupee(price * qty);
}

async function recordSale() {
  const formData = new FormData();
  formData.append('product_id',    fv('sale-product-select'));
  formData.append('quantity',      fv('sale-quantity'));
  formData.append('customer_name', fv('sale-customer'));
  formData.append('sale_date',     fv('sale-date'));

  const btn = document.getElementById('record-sale-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Recording...';

  const data = await api(`${APP_BASE_URL}/api/sales.php?action=record`, {
    method: 'POST', body: formData
  });

  btn.disabled = false; btn.textContent = 'Record Sale';

  if (data.success) {
    closeModal('sale-modal');
    showReceipt(data.sale);
    loadSales();
    // Refresh dashboard stat if visible
    if (window._currentPage === 'dashboard') loadDashboard();
  } else {
    toast(data.message, 'error');
  }
}

function showReceipt(sale) {
  const now = new Date().toLocaleString('en-IN');
  document.getElementById('receipt-content').innerHTML = `
    <div class="receipt">
      <div class="receipt-header">
        <h3>InvenTrack</h3>
        <div class="text-muted text-sm">Sales Receipt</div>
        <div class="text-sm">${now}</div>
      </div>
      <hr class="receipt-divider">
      <div class="receipt-row"><span>Receipt #</span><span>${sale.id}</span></div>
      <div class="receipt-row"><span>Customer</span><span>${escHtml(sale.customer_name)}</span></div>
      <div class="receipt-row"><span>Date</span><span>${formatDate(sale.sale_date)}</span></div>
      <div class="receipt-row"><span>Served By</span><span>${escHtml(sale.sold_by)}</span></div>
      <hr class="receipt-divider">
      <div class="receipt-row"><span>Product</span><span>${escHtml(sale.product_name)}</span></div>
      <div class="receipt-row"><span>Category</span><span>${escHtml(sale.category)}</span></div>
      <div class="receipt-row"><span>Unit Price</span><span>${formatRupee(sale.unit_price)}</span></div>
      <div class="receipt-row"><span>Quantity</span><span>${sale.quantity}</span></div>
      <hr class="receipt-divider">
      <div class="receipt-row receipt-total"><span>TOTAL</span><span>${formatRupee(sale.total_price)}</span></div>
      <hr class="receipt-divider">
      <div class="text-muted text-sm" style="text-align:center;margin-top:8px">Remaining Stock: ${sale.remaining_qty} units</div>
    </div>`;
  openModal('receipt-modal');
  toast('Sale recorded successfully!', 'success');
}

function printReceipt() {
  const content = document.getElementById('receipt-content').innerHTML;
  const win = window.open('', '', 'width=400,height=600');
  win.document.write(`
    <html><head><title>Receipt</title>
    <style>
      body { font-family: monospace; font-size: 13px; padding: 20px; }
      .receipt-row { display: flex; justify-content: space-between; padding: 3px 0; }
      .receipt-divider { border: 1px dashed #ccc; margin: 8px 0; }
      .receipt-total { font-weight: bold; font-size: 1.1em; }
      .receipt-header { text-align: center; margin-bottom: 12px; }
    </style></head><body>${content}</body></html>`);
  win.document.close();
  win.print();
}

function exportSales() {
  const from = fv('sales-from');
  const to   = fv('sales-to');
  window.location = `${APP_BASE_URL}/api/sales.php?action=export&from=${from}&to=${to}`;
}

// ============================================================
// INVENTORY TRACKER
// ============================================================
async function loadInventory() {
  const data = await api(`${APP_BASE_URL}/api/products.php?action=list`);
  if (!data.success) return;

  const products  = data.data;
  const lowStock  = products.filter(p => p.quantity <= p.low_stock_threshold);
  const outStock  = products.filter(p => p.quantity === 0);
  const goodStock = products.filter(p => p.quantity > p.low_stock_threshold);

  document.getElementById('inv-total').textContent = products.length;
  document.getElementById('inv-low').textContent   = lowStock.length;
  document.getElementById('inv-out').textContent   = outStock.length;
  document.getElementById('inv-good').textContent  = goodStock.length;

  const tbody = document.getElementById('inventory-table');
  tbody.innerHTML = products.map(p => {
    const pct = Math.min(100, Math.round((p.quantity / (p.low_stock_threshold * 3 || 30)) * 100));
    const barColor = p.quantity === 0 ? '#ef4444' : p.quantity <= p.low_stock_threshold ? '#f59e0b' : '#22c55e';
    const statusBadge = p.quantity === 0
      ? `<span class="badge badge-danger">Out of Stock</span>`
      : p.quantity <= p.low_stock_threshold
      ? `<span class="badge badge-warning">Low Stock</span>`
      : `<span class="badge badge-success">In Stock</span>`;
    return `
      <tr>
        <td><strong>${escHtml(p.name)}</strong></td>
        <td><span class="badge badge-info">${escHtml(p.category)}</span></td>
        <td class="font-mono">${p.quantity}</td>
        <td class="font-mono">${p.low_stock_threshold}</td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;min-width:120px">
            <div style="flex:1;background:#2a2f45;border-radius:3px;height:6px;overflow:hidden">
              <div style="width:${pct}%;background:${barColor};height:100%;border-radius:3px;transition:width 0.4s"></div>
            </div>
            <span class="text-sm text-muted">${pct}%</span>
          </div>
        </td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn btn-ghost btn-sm" onclick="showStockHistory(${p.id},'${escHtml(p.name).replace(/'/g,"\\'")}')">History</button>
        </td>
      </tr>`;
  }).join('');
}

async function showStockHistory(id, name) {
  document.getElementById('history-modal-title').textContent = `Stock History: ${name}`;
  const tbody = document.getElementById('history-table');
  tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px"><span class="spinner"></span></td></tr>';
  openModal('history-modal');

  const data = await api(`${APP_BASE_URL}/api/products.php?action=stock_history&id=${id}`);
  if (!data.success || data.data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted" style="text-align:center;padding:20px">No history found.</td></tr>';
    return;
  }

  tbody.innerHTML = data.data.map(h => {
    const changeColor = h.quantity_change > 0 ? 'text-success' : 'text-danger';
    const changeSign  = h.quantity_change > 0 ? '+' : '';
    const typeBadge = {
      add:    '<span class="badge badge-success">Add</span>',
      sale:   '<span class="badge badge-info">Sale</span>',
      adjust: '<span class="badge badge-warning">Adjust</span>',
    }[h.change_type] || h.change_type;
    return `
      <tr>
        <td>${new Date(h.changed_at).toLocaleDateString('en-IN', {day:'2-digit',month:'short',year:'numeric'})}</td>
        <td>${typeBadge}</td>
        <td class="font-mono ${changeColor}">${changeSign}${h.quantity_change}</td>
        <td class="font-mono">${h.quantity_after}</td>
        <td class="text-muted text-sm">${escHtml(h.note || '—')}</td>
      </tr>`;
  }).join('');
}

// ============================================================
// AUTHENTICATION
// ============================================================
function switchAuthTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.getElementById('login-form-div').classList.toggle('hidden',    tab !== 'login');
  document.getElementById('register-form-div').classList.toggle('hidden', tab !== 'register');
}

async function login() {
  const fd = new FormData();
  fd.append('action',   'login');
  fd.append('email',    fv('login-email'));
  fd.append('password', fv('login-password'));

  const btn = document.getElementById('login-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Logging in...';

  const data = await api(`${APP_BASE_URL}/api/auth.php`, { method: 'POST', body: fd });

  btn.disabled = false; btn.textContent = 'Login';

  if (data.success) {
    window.location.reload();
  } else {
    document.getElementById('login-error').textContent = data.message;
  }
}

async function register() {
  const fd = new FormData();
  fd.append('action',   'register');
  fd.append('name',     fv('reg-name'));
  fd.append('email',    fv('reg-email'));
  fd.append('password', fv('reg-password'));
  fd.append('confirm',  fv('reg-confirm'));

  const btn = document.getElementById('register-btn');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Creating account...';

  const data = await api(`${APP_BASE_URL}/api/auth.php`, { method: 'POST', body: fd });

  btn.disabled = false; btn.textContent = 'Create Account';

  const msg = document.getElementById('reg-message');
  msg.textContent = data.message;
  msg.className   = data.success ? 'text-success mt-2' : 'text-danger mt-2';
  if (data.success) {
    setTimeout(() => switchAuthTab('login'), 1500);
  }
}

function logout() {
  window.location = `${APP_BASE_URL}/api/auth.php?action=logout`;
}

// ============================================================
// XSS PREVENTION: escape HTML for output into DOM
// ============================================================
function escHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ============================================================
// KEYBOARD SHORTCUTS
// ============================================================
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});

// ============================================================
// MOBILE SIDEBAR TOGGLE
// ============================================================
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', e => {
  const sidebar = document.querySelector('.sidebar');
  const menuBtn = document.querySelector('.menu-btn');
  if (sidebar && !sidebar.contains(e.target) && menuBtn && !menuBtn.contains(e.target)) {
    sidebar.classList.remove('open');
  }
});
