<?php
/** Dashboard entry point: use the shared inventory/admin application shell. */
$view = $_GET['view'] ?? 'Summary';
$allowed = ['Sales', 'Inventory', 'Receivables', 'Payables', 'Reports', 'Summary', 'Users', 'Settings'];
if (!in_array($view, $allowed, true)) {
    $view = 'Summary';
}
$query = http_build_query(['view' => $view]);
header('Location: ../index.php?' . $query, true, 302);
exit;
