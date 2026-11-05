<?php
/** Read-only content for the administration navigation tabs. */
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(mixed $value): string
{
    return '₱' . number_format((float)$value, 2);
}

function panelHeader(string $eyebrow, string $title, string $description, string $action = ''): void
{
    echo '<div class="page-heading"><div><p class="eyebrow">' . e($eyebrow) . '</p><h1>' . e($title) . '</h1><p class="intro">' . e($description) . '</p></div>' . $action . '</div>';
}

$view = $_GET['view'] ?? '';
$allowed = ['Sales', 'Receivables', 'Payables', 'Reports', 'Summary', 'Users', 'Settings'];
if (!in_array($view, $allowed, true)) {
    http_response_code(404);
    exit('<section class="tab-panel"><h2>Page not found</h2><p>This tab is not available.</p></section>');
}

try {
    require_once __DIR__ . '/../config/database.php';

    if ($view === 'Sales') {
        $stmt = $pdo->query('SELECT s.sale_id, s.total, s.amount_received, s.change_due, s.created_at, COUNT(si.sale_item_id) AS line_count FROM sales s LEFT JOIN sale_items si ON si.sale_id = s.sale_id GROUP BY s.sale_id, s.total, s.amount_received, s.change_due, s.created_at ORDER BY s.created_at DESC');
        $sales = $stmt->fetchAll();
        $today = $pdo->query('SELECT COUNT(*) AS count, COALESCE(SUM(total), 0) AS total FROM sales WHERE created_at >= CURRENT_DATE()')->fetch();
        panelHeader('CHECKOUT', 'Sales', 'Review completed transactions or open the point of sale to start a new checkout.', '<a class="add-button" href="cashier/pos.php">＋ New sale</a>');
        echo '<div class="metrics"><div class="metric-card"><span class="metric-copy"><small>SALES TODAY</small><strong>' . (int)$today['count'] . '</strong></span></div><div class="metric-card"><span class="metric-copy"><small>REVENUE TODAY</small><strong>' . money($today['total']) . '</strong></span></div></div>';
        echo '<div class="toolbar"><label class="search-box"><span>⌕</span><input type="search" data-table-filter="sales-table" placeholder="Search sale number or date" aria-label="Search sales"></label><button class="export-button" type="button" data-export-table="sales-table" data-filename="ais-sales.csv">Export CSV</button></div>';
        echo '<div class="table-scroll"><table class="product-table" id="sales-table"><thead><tr><th>SALE</th><th>DATE &amp; TIME</th><th>ITEM LINES</th><th>TOTAL</th><th>RECEIVED</th><th>CHANGE</th></tr></thead><tbody>';
        foreach ($sales as $sale) {
            echo '<tr><td>#' . (int)$sale['sale_id'] . '</td><td>' . e(date('M j, Y · g:i A', strtotime($sale['created_at']))) . '</td><td>' . (int)$sale['line_count'] . '</td><td>' . money($sale['total']) . '</td><td>' . money($sale['amount_received']) . '</td><td>' . money($sale['change_due']) . '</td></tr>';
        }
        echo '</tbody></table>' . (!$sales ? '<div class="empty-state"><strong>No sales recorded yet</strong><p>Completed checkouts will appear here.</p><a href="cashier/pos.php">Open point of sale</a></div>' : '') . '</div>';
    } elseif ($view === 'Reports') {
        $daily = $pdo->query('SELECT DATE(created_at) AS sale_date, COUNT(*) AS sale_count, COALESCE(SUM(total), 0) AS revenue FROM sales WHERE created_at >= CURRENT_DATE() - INTERVAL 29 DAY GROUP BY DATE(created_at) ORDER BY sale_date DESC')->fetchAll();
        $top = $pdo->query('SELECT product_name, SUM(quantity) AS units, SUM(subtotal) AS revenue FROM sale_items si JOIN sales s ON s.sale_id = si.sale_id WHERE s.created_at >= CURRENT_DATE() - INTERVAL 29 DAY GROUP BY product_name ORDER BY revenue DESC LIMIT 5')->fetchAll();
        panelHeader('ANALYTICS', 'Reports', 'Sales activity for the last 30 days, calculated from completed checkout records.');
        echo '<div class="toolbar"><button class="export-button" type="button" data-export-table="daily-report" data-filename="ais-daily-sales.csv">Export daily sales</button><button class="export-button" type="button" data-export-table="top-products" data-filename="ais-top-products.csv">Export top products</button></div>';
        echo '<div class="metrics"><div class="metric-card"><span class="metric-copy"><small>LAST 30 DAYS</small><strong>' . count($daily) . ' DAYS</strong></span></div><div class="metric-card"><span class="metric-copy"><small>TOP ITEM</small><strong>' . (count($top) ? e($top[0]['product_name']) : 'No sales yet') . '</strong></span></div></div>';
        echo '<div class="table-scroll"><table class="product-table" id="daily-report"><thead><tr><th>DATE</th><th>TRANSACTIONS</th><th>REVENUE</th></tr></thead><tbody>';
        foreach ($daily as $row) {
            echo '<tr><td>' . e(date('M j, Y', strtotime($row['sale_date']))) . '</td><td>' . (int)$row['sale_count'] . '</td><td>' . money($row['revenue']) . '</td></tr>';
        }
        echo '</tbody></table>' . (!$daily ? '<div class="empty-state"><strong>No sales in this period</strong><p>Reports will populate after the first completed checkout.</p></div>' : '') . '</div>';
        echo '<div class="table-scroll"><table class="product-table" id="top-products"><thead><tr><th>PRODUCT</th><th>UNITS</th><th>REVENUE</th></tr></thead><tbody>';
        foreach ($top as $row) {
            echo '<tr><td>' . e($row['product_name']) . '</td><td>' . (int)$row['units'] . '</td><td>' . money($row['revenue']) . '</td></tr>';
        }
        echo '</tbody></table>' . (!$top ? '<div class="empty-state"><strong>No product sales yet</strong><p>Product performance will appear after checkout.</p></div>' : '') . '</div>';
    } elseif ($view === 'Summary') {
        $stats = $pdo->query('SELECT (SELECT COUNT(*) FROM products) AS products, (SELECT COALESCE(SUM(stock), 0) FROM products) AS units, (SELECT COUNT(*) FROM products WHERE stock <= 2) AS low_stock, (SELECT COALESCE(SUM(total), 0) FROM sales) AS revenue')->fetch();
        panelHeader('STORE OVERVIEW', 'Summary', 'A live snapshot of inventory and sales in the AIS database.');
        echo '<div class="metrics"><div class="metric-card"><span class="metric-copy"><small>PRODUCTS</small><strong>' . (int)$stats['products'] . '</strong></span></div><div class="metric-card"><span class="metric-copy"><small>UNITS IN STOCK</small><strong>' . (int)$stats['units'] . '</strong></span></div><div class="metric-card"><span class="metric-copy"><small>LOW STOCK</small><strong>' . (int)$stats['low_stock'] . '</strong></span></div><div class="metric-card"><span class="metric-copy"><small>REVENUE</small><strong>' . money($stats['revenue']) . '</strong></span></div></div>';
    } elseif ($view === 'Receivables' || $view === 'Payables') {
        $isReceivable = $view === 'Receivables';
        panelHeader($isReceivable ? 'CUSTOMER ACCOUNTS' : 'STORE EXPENSES', $view, $isReceivable ? 'Track customer balances and payments.' : 'Track supplier invoices, purchases, and store expenses.');
        echo '<section class="notice"><div><strong>' . ($isReceivable ? 'Credit sales are not enabled yet.' : 'Expense and supplier payment tracking is not enabled yet.') . '</strong><p>The current installation is ready for this section, but the related accounting tables have not been added to the schema.</p></div></section>';
    } elseif ($view === 'Users') {
        panelHeader('ACCESS CONTROL', 'Users', 'Manage who can access AIS and assign cashier or administrator roles.');
        echo '<section class="notice"><div><strong>User accounts are not configured in this installation.</strong><p>The repository does not yet include a users table or role-based sign-in. User management can be added once the authentication module is implemented.</p></div></section>';
    } else {
        panelHeader('PREFERENCES', 'Settings', 'Store preferences for this browser session.');
        echo '<form id="store-settings" class="settings-form"><label>Store name<input name="store_name" maxlength="100" placeholder="AIS Sari-Sari Store" required></label><label>Low-stock alert threshold<input name="stock_threshold" type="number" min="0" max="100000" step="1" value="2" required></label><p class="intro">These preferences are saved in this browser only. Database connection and security settings must be configured on the server.</p><button class="add-button" type="submit">Save preferences</button></form><section class="notice"><div><strong>Database status</strong><p>Connected. The dashboard reads inventory and sales from the configured AIS database.</p></div></section>';
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage();
    http_response_code(500);
    echo '<section class="tab-panel"><p class="eyebrow">SYSTEM MESSAGE</p><h1>Could not load ' . e($view) . '</h1><p class="intro">' . e($message) . '</p><p>Set up the AIS database and make sure the PHP server can connect to MySQL.</p></section>';
}
