<?php
// ============================================================
// app.php
// Main application shell — shown after login
// All content sections are in this single file;
// JavaScript shows/hides them without page reloads.
// ============================================================
require_once 'includes/config.php';
requireLogin();

$userName = h($_SESSION['user_name']);
$userRole = h($_SESSION['user_role']);
$avatarLetter = strtoupper(substr($_SESSION['user_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>InvenTrack — Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Chart.js from CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ============================================================
     TOAST CONTAINER
     ============================================================ -->
<div id="toast-container"></div>

<!-- ============================================================
     APP LAYOUT
     ============================================================ -->
<div class="app-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-text">InvenTrack<span>v1.0 — Inventory System</span></div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <button class="nav-item active" data-page="dashboard" onclick="showPage('dashboard')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </button>
      <button class="nav-item" data-page="products" onclick="showPage('products')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        Products
      </button>
      <button class="nav-item" data-page="sales" onclick="showPage('sales')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        Sales
      </button>
      <button class="nav-item" data-page="inventory" onclick="showPage('inventory')">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Inventory
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= $avatarLetter ?></div>
        <div>
          <div class="user-name"><?= $userName ?></div>
          <div class="user-role"><?= $userRole ?></div>
        </div>
      </div>
      <button class="btn btn-ghost btn-full" onclick="logout()">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </button>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <!-- Top bar -->
    <div class="topbar">
      <div class="flex items-center gap-3">
        <button class="menu-btn" onclick="toggleSidebar()">
          <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span id="topbar-title" class="topbar-title">Dashboard</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-muted text-sm"><?= date('D, d M Y') ?></span>
      </div>
    </div>

    <!-- PAGE: DASHBOARD -->
    <div id="page-dashboard" class="page-section page-content">
      <!-- Low stock banner -->
      <div id="low-stock-banner" class="alert-banner hidden">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <span></span>
      </div>

      <!-- Stat cards -->
      <div class="stats-grid">
        <div class="stat-card amber">
          <div class="stat-icon amber">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7"/></svg>
          </div>
          <div class="stat-value" id="stat-products">—</div>
          <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card green">
          <div class="stat-icon green">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
          </div>
          <div class="stat-value" id="stat-revenue">—</div>
          <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card blue">
          <div class="stat-icon blue">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01"/></svg>
          </div>
          <div class="stat-value" id="stat-sales">—</div>
          <div class="stat-label">Total Sales</div>
        </div>
        <div class="stat-card red">
          <div class="stat-icon red">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
          </div>
          <div class="stat-value" id="stat-lowstock">—</div>
          <div class="stat-label">Low Stock Items</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-icon amber">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="stat-value" id="stat-today">—</div>
          <div class="stat-label">Today's Revenue</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-grid">
        <div class="card">
          <div class="card-header">
            <span class="card-title">Revenue — Last 7 Days</span>
          </div>
          <div class="chart-container"><canvas id="revenue-chart"></canvas></div>
        </div>
        <div class="card">
          <div class="card-header">
            <span class="card-title">Top Products by Revenue</span>
          </div>
          <div class="chart-container"><canvas id="top-products-chart"></canvas></div>
        </div>
      </div>

      <!-- Recent transactions -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Transactions</span>
          <button class="btn btn-ghost btn-sm" onclick="showPage('sales')">View All →</button>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Product</th><th>Qty</th><th>Total</th><th>Date</th>
              </tr>
            </thead>
            <tbody id="recent-transactions">
              <tr><td colspan="5" style="text-align:center;padding:24px"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PAGE: PRODUCTS -->
    <div id="page-products" class="page-section page-content hidden">
      <div class="filter-bar">
        <input type="text" id="product-search" class="form-control" placeholder="Search by name or category…"
               oninput="loadProducts()">
        <select id="product-category-filter" class="form-control" style="max-width:200px" onchange="loadProducts()">
          <option value="">All Categories</option>
        </select>
        <button class="btn btn-primary" onclick="openAddProductModal()">+ Add Product</button>
      </div>
      <div class="card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th><th>Name</th><th>Category</th><th>Price</th>
                <th>Stock</th><th>Alert At</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="products-table">
              <tr><td colspan="7" style="text-align:center;padding:24px"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PAGE: SALES -->
    <div id="page-sales" class="page-section page-content hidden">
      <div class="filter-bar">
        <input type="text" id="sales-search" class="form-control" placeholder="Search product or customer…"
               oninput="loadSales()" style="max-width:220px">
        <input type="date" id="sales-from" class="form-control" style="max-width:160px" onchange="loadSales()">
        <input type="date" id="sales-to"   class="form-control" style="max-width:160px" onchange="loadSales()">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sales-from').value='';document.getElementById('sales-to').value='';loadSales()">Clear</button>
        <button class="btn btn-success" onclick="openSaleModal()">+ Record Sale</button>
        <button class="btn btn-info"    onclick="exportSales()">⬇ Export CSV</button>
      </div>
      <div class="card">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Product</th><th>Qty</th><th>Unit Price</th>
                <th>Total</th><th>Customer</th><th>Date</th>
              </tr>
            </thead>
            <tbody id="sales-table">
              <tr><td colspan="7" style="text-align:center;padding:24px"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PAGE: INVENTORY -->
    <div id="page-inventory" class="page-section page-content hidden">
      <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
        <div class="stat-card blue">
          <div class="stat-value" id="inv-total">—</div>
          <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card green">
          <div class="stat-value" id="inv-good">—</div>
          <div class="stat-label">Well Stocked</div>
        </div>
        <div class="stat-card amber">
          <div class="stat-value" id="inv-low">—</div>
          <div class="stat-label">Low Stock</div>
        </div>
        <div class="stat-card red">
          <div class="stat-value" id="inv-out">—</div>
          <div class="stat-label">Out of Stock</div>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Stock Levels</span>
          <button class="btn btn-ghost btn-sm" onclick="loadInventory()">↻ Refresh</button>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Product</th><th>Category</th><th>Stock</th>
                <th>Alert Threshold</th><th>Level</th><th>Status</th><th>History</th>
              </tr>
            </thead>
            <tbody id="inventory-table">
              <tr><td colspan="7" style="text-align:center;padding:24px"><span class="spinner"></span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /main-content -->
</div><!-- /app-layout -->

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Add / Edit Product Modal -->
<div class="modal-overlay" id="product-modal" onclick="if(event.target===this)closeModal('product-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="product-modal-title">Add Product</span>
      <button class="modal-close" onclick="closeModal('product-modal')">✕</button>
    </div>
    <input type="hidden" id="product-id">
    <div class="form-group">
      <label>Product Name *</label>
      <input type="text" id="product-name" class="form-control" placeholder="e.g. Wireless Keyboard">
    </div>
    <div class="form-group">
      <label>Category *</label>
      <input type="text" id="product-category" class="form-control" placeholder="e.g. Electronics"
             list="category-suggestions">
      <datalist id="category-suggestions">
        <option value="Electronics">
        <option value="Clothing">
        <option value="Food & Beverages">
        <option value="Furniture">
        <option value="Stationery">
      </datalist>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div class="form-group">
        <label>Price (₹) *</label>
        <input type="number" id="product-price" class="form-control" placeholder="0.00" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label>Quantity *</label>
        <input type="number" id="product-quantity" class="form-control" placeholder="0" min="0">
      </div>
    </div>
    <div class="form-group">
      <label>Low Stock Alert Threshold</label>
      <input type="number" id="product-threshold" class="form-control" placeholder="10" min="1" value="10">
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('product-modal')">Cancel</button>
      <button id="save-product-btn" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
    </div>
  </div>
</div>

<!-- Record Sale Modal -->
<div class="modal-overlay" id="sale-modal" onclick="if(event.target===this)closeModal('sale-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Record New Sale</span>
      <button class="modal-close" onclick="closeModal('sale-modal')">✕</button>
    </div>
    <div id="sale-form">
      <div class="form-group">
        <label>Product *</label>
        <select id="sale-product-select" class="form-control" onchange="onProductSelect()">
          <option value="">-- Select Product --</option>
        </select>
        <div id="sale-stock-info" class="text-sm mt-1"></div>
      </div>
      <div class="form-group">
        <label>Quantity *</label>
        <input type="number" id="sale-quantity" class="form-control" placeholder="1" min="1"
               oninput="calculateSaleTotal()">
      </div>
      <div class="form-group">
        <label>Customer Name</label>
        <input type="text" id="sale-customer" class="form-control" placeholder="Walk-in Customer">
      </div>
      <div class="form-group">
        <label>Sale Date</label>
        <input type="date" id="sale-date" class="form-control">
      </div>
      <!-- Price summary -->
      <div style="background:var(--bg-3);border-radius:8px;padding:14px;margin-top:8px">
        <div class="receipt-row">
          <span class="text-muted text-sm">Unit Price</span>
          <span class="font-mono" id="sale-unit-price">₹0.00</span>
        </div>
        <div class="receipt-row" style="margin-top:6px;padding-top:6px;border-top:1px dashed var(--border)">
          <span class="font-bold">Total</span>
          <span class="font-mono font-bold text-accent" id="sale-total-price">₹0.00</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('sale-modal')">Cancel</button>
      <button id="record-sale-btn" class="btn btn-success" onclick="recordSale()">Record Sale</button>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receipt-modal" onclick="if(event.target===this)closeModal('receipt-modal')">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Sale Receipt</span>
      <button class="modal-close" onclick="closeModal('receipt-modal')">✕</button>
    </div>
    <div id="receipt-content"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('receipt-modal')">Close</button>
      <button class="btn btn-primary" onclick="printReceipt()">🖨 Print</button>
    </div>
  </div>
</div>

<!-- Stock History Modal -->
<div class="modal-overlay" id="history-modal" onclick="if(event.target===this)closeModal('history-modal')">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <span class="modal-title" id="history-modal-title">Stock History</span>
      <button class="modal-close" onclick="closeModal('history-modal')">✕</button>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Date</th><th>Type</th><th>Change</th><th>After</th><th>Note</th></tr>
        </thead>
        <tbody id="history-table"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('history-modal')">Close</button>
    </div>
  </div>
</div>

<script>window.APP_BASE_URL = <?= json_encode(APP_BASE_URL, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="assets/js/app.js"></script>
<script>
  // Load dashboard on page start
  showPage('dashboard');
</script>
</body>
</html>
