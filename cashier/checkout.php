<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'Use POST to complete a sale.']);
}
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!$csrf || empty($_SESSION['pos_csrf']) || !hash_equals($_SESSION['pos_csrf'], $csrf)) {
    respond(403, ['error' => 'Your session expired. Refresh the page and try again.']);
}
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || !isset($payload['items'], $payload['amount_received']) || !is_array($payload['items'])) {
    respond(422, ['error' => 'Please provide valid order and payment details.']);
}

$quantities = [];
foreach ($payload['items'] as $item) {
    $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id || !$qty) respond(422, ['error' => 'Each product must have a valid quantity.']);
    $quantities[$id] = ($quantities[$id] ?? 0) + $qty;
}
if (!$quantities) respond(422, ['error' => 'Add at least one product to the order.']);
$received = filter_var($payload['amount_received'], FILTER_VALIDATE_FLOAT);
if ($received === false || !is_finite((float)$received) || $received < 0) respond(422, ['error' => 'Enter a valid amount received.']);

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo->beginTransaction();
    $placeholders = implode(',', array_fill(0, count($quantities), '?'));
    $stmt = $pdo->prepare("SELECT product_id, name, sell, stock FROM products WHERE product_id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_keys($quantities));
    $products = [];
    foreach ($stmt->fetchAll() as $row) $products[(int)$row['product_id']] = $row;

    $totalCents = 0;
    $lines = [];
    foreach ($quantities as $id => $qty) {
        if (!isset($products[$id])) throw new DomainException('A selected product is no longer available. Refresh the page and try again.');
        $product = $products[$id];
        if ($qty > (int)$product['stock']) throw new DomainException($product['name'] . ' only has ' . (int)$product['stock'] . ' left in stock.');
        $unitCents = (int)round((float)$product['sell'] * 100);
        $lineCents = $unitCents * $qty;
        $totalCents += $lineCents;
        $lines[] = ['id' => $id, 'name' => $product['name'], 'quantity' => $qty, 'unit_price' => $unitCents / 100, 'subtotal' => $lineCents / 100];
    }
    $receivedCents = (int)round((float)$received * 100);
    if ($receivedCents < $totalCents) throw new DomainException('Amount received must cover the grand total.');

    $sale = $pdo->prepare('INSERT INTO sales (total, amount_received, change_due, created_at) VALUES (?, ?, ?, NOW())');
    $sale->execute([$totalCents / 100, $receivedCents / 100, ($receivedCents - $totalCents) / 100]);
    $saleId = (int)$pdo->lastInsertId();
    $insertLine = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
    $deduct = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?');
    foreach ($lines as $line) {
        $insertLine->execute([$saleId, $line['id'], $line['name'], $line['quantity'], $line['unit_price'], $line['subtotal']]);
        $deduct->execute([$line['quantity'], $line['id'], $line['quantity']]);
        if ($deduct->rowCount() !== 1) throw new DomainException($line['name'] . ' stock changed. Please refresh and try again.');
    }
    $pdo->commit();
    respond(200, ['success' => true, 'sale_id' => $saleId, 'total' => $totalCents / 100, 'change' => ($receivedCents - $totalCents) / 100, 'items' => $lines]);
} catch (DomainException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    respond(409, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('POS checkout failed: ' . $e->getMessage());
    respond(500, ['error' => 'Sale could not be completed. No inventory was changed. Please try again.']);
}
