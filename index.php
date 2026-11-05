<?php
$products = [
    ['name' => 'Arabica Coffee Beans 250g', 'category' => 'Beverages', 'sell' => 285.00, 'cost' => 198.00, 'stock' => 18, 'barcode' => '4800012345678', 'supplier' => 'Brew & Co.'],
    ['name' => 'Oat Milk Original 1L', 'category' => 'Dairy & Alternatives', 'sell' => 139.00, 'cost' => 103.00, 'stock' => 6, 'barcode' => '4800098765432', 'supplier' => 'Daily Harvest'],
    ['name' => 'Sea Salt Potato Chips', 'category' => 'Snacks', 'sell' => 52.00, 'cost' => 34.00, 'stock' => 2, 'barcode' => '4800044455566', 'supplier' => 'Good Snacks'],
    ['name' => 'Sea Salt Potato Chips', 'category' => 'Snacks', 'sell' => 52.00, 'cost' => 34.00, 'stock' => 0, 'barcode' => '4800044455573', 'supplier' => 'Good Snacks'],
];
$lowStock = count(array_filter($products, fn($product) => $product['stock'] <= 2));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f6f3">
    <title>Inventory · RetailFlow POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <a class="brand" href="#" aria-label="RetailFlow POS home">
            <span class="brand-mark">RF</span>
            <span class="brand-copy"><strong>RetailFlow POS</strong><small>Fast. Reliable. Built for retail.</small></span>
        </a>
        <div class="top-actions">
            <span class="status-pill sync-time"><span class="status-dot"></span><span>Last sync: <b id="sync-time">07:57:29 PM</b></span></span>
            <button class="status-pill store-pill" type="button" title="Current store"><span>🏬</span> <b>store-001</b><span class="pill-divider">·</span><b>owner</b><span class="edit-icon">✎</span></button>
            <button class="status-pill user-pill" type="button"><span class="user-avatar">♟</span> morris</button>
            <button class="status-pill bg-sync" type="button" id="bg-sync"><span>⟳</span> BG Sync</button>
            <button class="primary-button sync-now" type="button" id="sync-now"><span>↕</span> Sync Now</button>
            <button class="signout-button" type="button" id="sign-out"><span>→</span> Sign Out</button>
        </div>
    </header>

    <section class="metrics" aria-label="Today's overview">
        <button class="metric-card sales-card" data-view="Sales" type="button"><span class="metric-icon">₱</span><span class="metric-copy"><small>TODAY'S SALES</small><strong>₱51.00</strong></span><span class="metric-arrow">›</span></button>
        <button class="metric-card expenses-card" data-view="Payables" type="button"><span class="metric-icon">↟</span><span class="metric-copy"><small>TODAY'S EXPENSES</small><strong>₱2000.00</strong></span><span class="metric-arrow">›</span></button>
        <button class="metric-card receivables-card" data-view="Receivables" type="button"><span class="metric-icon">↶</span><span class="metric-copy"><small>RECEIVABLES</small><strong>₱67.00</strong><em>Total outstanding ›</em></span><span class="metric-arrow">›</span></button>
        <button class="metric-card stock-card" id="stock-metric" type="button"><span class="metric-icon">▦</span><span class="metric-copy"><small>STOCK ALERTS</small><strong><?= $lowStock ?></strong><em><?= $lowStock ?> item out of stock ›</em></span><span class="metric-arrow">›</span></button>
    </section>

    <div class="workspace">
        <aside class="sidebar" aria-label="Main navigation">
            <nav class="nav-list">
                <?php foreach (['Sales', 'Inventory', 'Receivables', 'Payables', 'Reports', 'Summary', 'Users', 'Settings'] as $item): ?>
                    <button class="nav-item <?= $item === 'Inventory' ? 'active' : '' ?>" type="button" data-view="<?= htmlspecialchars($item) ?>"><?= htmlspecialchars($item) ?></button>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-foot"><span class="online-dot"></span><span>All systems operational</span></div>
        </aside>

        <main class="main-panel">
            <div class="page-heading">
                <div><p class="eyebrow">PRODUCT MANAGEMENT</p><h1 id="page-title">Inventory</h1>
                    <p class="intro">Add products with selling price, cost price, stock level, and optional barcode. Cost price is<br class="desktop-break"> used to calculate your real margin and ROI accuracy.</p>
                </div>
                <button class="add-button" id="add-product" type="button"><span>＋</span> Add New Product</button>
            </div>

            <div class="notice low-notice" id="reorder-notice"><span class="notice-symbol">⚠</span><div><strong><span id="low-count"><?= $lowStock ?></span> item<?= $lowStock === 1 ? '' : 's' ?> at or below reorder point.</strong><p><a href="#restock" id="restock-link">Reports → Restock Report</a> to print or export the full list.</p></div></div>

            <div class="toolbar">
                <label class="search-box"><span>⌕</span><input id="search" type="search" placeholder="Search by name or barcode" aria-label="Search products"></label>
                <label class="select-wrap"><select id="category" aria-label="Filter by category"><option value="">All categories</option><option>Beverages</option><option>Dairy &amp; Alternatives</option><option>Snacks</option></select><span>⌄</span></label>
                <button class="low-stock-button" id="low-stock-filter" type="button"><span>⚠</span> Low stock (<?= $lowStock ?>)</button>
            </div>
            <div class="results-count"><span id="product-count"><?= count($products) ?> products</span><button id="clear-filters" type="button">Clear filters</button></div>

            <div class="duplicate-banner" id="duplicate-banner"><span>⚠</span><strong>1 duplicate product name detected.</strong><button id="review-merge" type="button">Review &amp; Merge</button></div>

            <div class="table-scroll">
                <table class="product-table">
                    <thead><tr><th>PRODUCT</th><th>SELL PRICE</th><th>COST PRICE</th><th>MARKUP %</th><th>STOCK QTY</th><th>BARCODE</th><th>SUPPLIER</th><th></th></tr></thead>
                    <tbody id="product-rows">
                    <?php foreach ($products as $index => $product): $markup = $product['cost'] > 0 ? round((($product['sell'] - $product['cost']) / $product['cost']) * 100) : 0; ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($product['name'])) ?>" data-category="<?= htmlspecialchars($product['category']) ?>" data-barcode="<?= htmlspecialchars($product['barcode']) ?>" data-stock="<?= (int)$product['stock'] ?>">
                            <td><span class="product-name"><?= htmlspecialchars($product['name']) ?></span><span class="product-category"><?= htmlspecialchars($product['category']) ?></span></td>
                            <td>₱<?= number_format($product['sell'], 2) ?></td><td>₱<?= number_format($product['cost'], 2) ?></td><td><span class="markup-chip"><?= $markup ?>%</span></td>
                            <td><span class="stock-badge <?= $product['stock'] <= 2 ? 'stock-low' : '' ?>"><?= (int)$product['stock'] ?><?= $product['stock'] <= 2 ? ' · Low' : '' ?></span></td><td class="barcode"><?= htmlspecialchars($product['barcode']) ?></td><td><?= htmlspecialchars($product['supplier']) ?></td>
                            <td><button class="row-menu" type="button" aria-label="More actions for <?= htmlspecialchars($product['name']) ?>">···</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="empty-state" id="empty-state" hidden><span>⌕</span><strong>No products found</strong><p>Try a different search or category.</p></div>
            </div>
            <div class="table-footer"><span>Showing <b id="showing-count"><?= count($products) ?></b> of <?= count($products) ?> products</span><button type="button" class="export-button" id="export-csv">↓ Export CSV</button></div>
        </main>
    </div>
    <footer class="app-footer"><span>RetailFlow POS <span class="footer-dot">·</span> Store 001</span><span>Inventory stays in sync across your team.</span></footer>
</div>

<div class="modal-backdrop" id="product-modal" hidden>
    <section class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <button class="modal-close" id="close-modal" type="button" aria-label="Close">×</button>
        <p class="eyebrow">INVENTORY</p><h2 id="modal-title">Add new product</h2><p class="modal-intro">Add product details to keep your stock and margins up to date.</p>
        <form id="product-form">
            <label>Product name<input name="name" required placeholder="e.g. House blend coffee"></label>
            <div class="form-grid"><label>Category<select name="category"><option>Beverages</option><option>Dairy &amp; Alternatives</option><option>Snacks</option><option>Other</option></select></label><label>Barcode <span class="optional">(optional)</span><input name="barcode" placeholder="Scan or enter barcode"></label></div>
            <div class="form-grid"><label>Selling price<input name="sell" type="number" min="0" step="0.01" required placeholder="0.00"></label><label>Cost price<input name="cost" type="number" min="0" step="0.01" required placeholder="0.00"></label></div>
            <div class="form-grid"><label>Stock quantity<input name="stock" type="number" min="0" step="1" required placeholder="0"></label><label>Supplier<input name="supplier" placeholder="Supplier name"></label></div>
            <div class="modal-actions"><button class="cancel-button" id="cancel-modal" type="button">Cancel</button><button class="primary-button" type="submit">Save product</button></div>
        </form>
    </section>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="assets/js/script.js"></script>
</body>
</html>
