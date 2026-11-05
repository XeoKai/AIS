<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['pos_csrf'])) {
    $_SESSION['pos_csrf'] = bin2hex(random_bytes(32));
}

$products = [];
$pageError = '';
try {
    $stmt = $pdo->query('SELECT product_id, name, category, sell, stock, barcode FROM products ORDER BY name');
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('POS product lookup failed: ' . $e->getMessage());
    $pageError = 'Products could not be loaded. Check that the AIS database schema has been installed.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f6f3">
    <title>Point of Sale · AIS Sari-Sari Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/pos.css">
</head>
<body>
<div class="app-shell pos-shell">
    <header class="topbar">
        <a class="brand" href="../index.php" aria-label="AIS home"><span class="brand-mark">AIS</span><span class="brand-copy"><strong>Sari-Sari Store</strong><small>Inventory &amp; Sales Management</small></span></a>
        <div class="top-actions"><span class="status-pill"><span class="status-dot"></span> Checkout ready</span><a class="status-pill back-link" href="../index.php">← Inventory</a></div>
    </header>
    <main class="pos-main">
        <div class="pos-heading"><div><p class="eyebrow">SALES</p><h1>Point of Sale</h1><p class="intro">Select products to build an order. Stock is only updated after checkout succeeds.</p></div><div class="sale-date" id="sale-date"></div></div>
        <?php if ($pageError): ?><div class="pos-alert" role="alert"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?> <a href="../database/ais.sql">View database schema</a>.</div><?php endif; ?>
        <div class="pos-layout" data-csrf="<?= htmlspecialchars($_SESSION['pos_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <section class="catalog-panel" aria-labelledby="catalog-title">
                <div class="section-heading"><div><h2 id="catalog-title">Select products</h2><p><?= count($products) ?> products in inventory</p></div><span class="catalog-count" id="catalog-count"><?= count($products) ?></span></div>
                <label class="pos-search"><span aria-hidden="true">⌕</span><input id="product-search" type="search" placeholder="Search products or scan barcode…" autocomplete="off" aria-label="Search products"></label>
                <div class="product-grid" id="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php $stock = (int)$product['stock']; ?>
                        <button class="product-card" type="button" data-id="<?= (int)$product['product_id'] ?>" data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" data-category="<?= htmlspecialchars($product['category'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars((string)$product['sell'], ENT_QUOTES, 'UTF-8') ?>" data-stock="<?= $stock ?>" data-barcode="<?= htmlspecialchars($product['barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= $stock < 1 ? 'disabled' : '' ?>>
                            <span class="product-card-top"><span class="product-category"><?= htmlspecialchars($product['category'] ?: 'Product', ENT_QUOTES, 'UTF-8') ?></span><span class="product-add" aria-hidden="true">＋</span></span>
                            <strong><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="product-card-bottom"><b>₱<?= number_format((float)$product['sell'], 2) ?></b><span class="<?= $stock < 1 ? 'out-stock' : ($stock <= 2 ? 'low-stock' : '') ?>"><?= $stock < 1 ? 'Out of stock' : $stock . ' in stock' ?></span></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="catalog-empty" id="catalog-empty" <?= $products ? 'hidden' : '' ?>>No matching products found.</div>
            </section>
            <aside class="order-panel" aria-labelledby="order-title">
                <div class="order-heading"><div><h2 id="order-title">Current order</h2><p><span id="item-count">0</span> items</p></div><button class="text-button" id="clear-order" type="button" disabled>Clear</button></div>
                <div class="order-items" id="order-items" aria-live="polite"><div class="order-empty"><span class="empty-basket" aria-hidden="true">＋</span><strong>Your order is empty</strong><p>Choose products to add them to this sale.</p></div></div>
                <div class="order-totals"><div class="total-row"><span>Subtotal</span><strong id="subtotal">₱0.00</strong></div><div class="grand-total"><span>Grand total</span><strong id="grand-total">₱0.00</strong></div>
                    <label class="received-field" for="amount-received"><span>Amount received</span><span class="currency-input"><span>₱</span><input id="amount-received" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" disabled></span></label>
                    <div class="change-row"><span>Change</span><strong id="change-due">₱0.00</strong></div>
                    <p class="payment-hint" id="payment-hint">Enter the amount received to calculate change.</p>
                    <button class="checkout-button" id="checkout" type="button" disabled>Complete sale <span aria-hidden="true">→</span></button>
                    <p class="stock-note"><span aria-hidden="true">✓</span> Inventory changes only after a successful sale.</p>
                </div>
            </aside>
        </div>
    </main>
</div>
<div class="pos-toast" id="pos-toast" role="status" aria-live="polite"></div>
<script src="../assets/js/pos.js"></script>
</body>
</html>
