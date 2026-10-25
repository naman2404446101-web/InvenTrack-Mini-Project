<?php
// ============================================================
// api/sales.php
// Record and retrieve sales transactions
// ============================================================

require_once '../includes/config.php';
requireLogin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db     = getDB();

switch ($action) {

    // ----------------------------------------------------------
    // LIST sales (with optional date filter)
    // GET api/sales.php?action=list&from=2024-01-01&to=2024-12-31
    // ----------------------------------------------------------
    case 'list':
        $from   = $_GET['from']   ?? '';
        $to     = $_GET['to']     ?? '';
        $search = '%' . trim($_GET['search'] ?? '') . '%';

        $params = [$search, $search];
        $where  = 'WHERE (p.name LIKE ? OR s.customer_name LIKE ?)';

        if ($from && $to) {
            $where    .= ' AND s.sale_date BETWEEN ? AND ?';
            $params[]  = $from;
            $params[]  = $to;
        } elseif ($from) {
            $where    .= ' AND s.sale_date >= ?';
            $params[]  = $from;
        } elseif ($to) {
            $where    .= ' AND s.sale_date <= ?';
            $params[]  = $to;
        }

        $stmt = $db->prepare(
            "SELECT s.id, p.name AS product_name, p.category,
                    s.quantity, s.unit_price, s.total_price,
                    s.customer_name, u.name AS sold_by_name,
                    s.sale_date, s.created_at
             FROM sales s
             JOIN products p ON s.product_id = p.id
             LEFT JOIN users u ON s.sold_by = u.id
             $where
             ORDER BY s.created_at DESC"
        );
        $stmt->execute($params);

        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ----------------------------------------------------------
    // RECORD a new sale
    // POST api/sales.php?action=record
    // ----------------------------------------------------------
    case 'record':
        $productId    = (int)($_POST['product_id']    ?? 0);
        $qty          = (int)($_POST['quantity']       ?? 0);
        $customerName = trim($_POST['customer_name']   ?? 'Walk-in Customer');
        $saleDate     = trim($_POST['sale_date']       ?? date('Y-m-d'));

        // Validation
        if (!$productId) jsonResponse(['success' => false, 'message' => 'Select a product.']);
        if ($qty <= 0)   jsonResponse(['success' => false, 'message' => 'Quantity must be at least 1.']);

        // Lock the product row so concurrent requests can't oversell
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $db->rollBack();
                jsonResponse(['success' => false, 'message' => 'Product not found.']);
            }

            if ($product['quantity'] < $qty) {
                $db->rollBack();
                jsonResponse([
                    'success'  => false,
                    'message'  => "Insufficient stock! Only {$product['quantity']} units available."
                ]);
            }

            $unitPrice  = (float)$product['price'];
            $totalPrice = $unitPrice * $qty;
            $newQty     = $product['quantity'] - $qty;

            // Insert sale record
            $ins = $db->prepare(
                'INSERT INTO sales (product_id, quantity, unit_price, total_price, customer_name, sold_by, sale_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $productId, $qty, $unitPrice, $totalPrice,
                $customerName ?: 'Walk-in Customer',
                $_SESSION['user_id'], $saleDate
            ]);
            $saleId = $db->lastInsertId();

            // Deduct stock
            $upd = $db->prepare('UPDATE products SET quantity = ? WHERE id = ?');
            $upd->execute([$newQty, $productId]);

            // Log stock change
            $log = $db->prepare(
                'INSERT INTO stock_history (product_id, change_type, quantity_change, quantity_after, note, changed_by)
                 VALUES (?, "sale", ?, ?, ?, ?)'
            );
            $log->execute([
                $productId, -$qty, $newQty,
                "Sale #$saleId to $customerName",
                $_SESSION['user_id']
            ]);

            $db->commit();

            // Return full sale data for receipt generation
            jsonResponse([
                'success'      => true,
                'message'      => 'Sale recorded successfully!',
                'sale'         => [
                    'id'            => $saleId,
                    'product_name'  => $product['name'],
                    'category'      => $product['category'],
                    'quantity'      => $qty,
                    'unit_price'    => $unitPrice,
                    'total_price'   => $totalPrice,
                    'customer_name' => $customerName ?: 'Walk-in Customer',
                    'sale_date'     => $saleDate,
                    'sold_by'       => $_SESSION['user_name'],
                    'remaining_qty' => $newQty,
                ]
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Transaction failed. Please try again.'], 500);
        }
        break;

    // ----------------------------------------------------------
    // DASHBOARD summary numbers
    // ----------------------------------------------------------
    case 'dashboard':
        // Total products
        $totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        // Total sales revenue (all time)
        $totalRevenue = $db->query('SELECT COALESCE(SUM(total_price),0) FROM sales')->fetchColumn();

        // Total sales count
        $totalSales = $db->query('SELECT COUNT(*) FROM sales')->fetchColumn();

        // Today's revenue
        $todayRevenue = $db->query(
            "SELECT COALESCE(SUM(total_price),0) FROM sales WHERE sale_date = CURDATE()"
        )->fetchColumn();

        // Low stock products
        $lowStock = $db->query(
            'SELECT COUNT(*) FROM products WHERE quantity <= low_stock_threshold'
        )->fetchColumn();

        // Recent 8 transactions
        $recent = $db->query(
            'SELECT s.id, p.name AS product_name, s.quantity, s.total_price, s.customer_name, s.sale_date
             FROM sales s
             JOIN products p ON s.product_id = p.id
             ORDER BY s.created_at DESC
             LIMIT 8'
        )->fetchAll();

        // Sales per day for last 7 days (for chart)
        $chartData = $db->query(
            "SELECT sale_date, SUM(total_price) AS revenue, COUNT(*) AS num_sales
             FROM sales
             WHERE sale_date >= CURDATE() - INTERVAL 6 DAY
             GROUP BY sale_date
             ORDER BY sale_date ASC"
        )->fetchAll();

        // Top 5 selling products (by revenue)
        $topProducts = $db->query(
            'SELECT p.name, SUM(s.total_price) AS revenue, SUM(s.quantity) AS units_sold
             FROM sales s
             JOIN products p ON s.product_id = p.id
             GROUP BY s.product_id, p.name
             ORDER BY revenue DESC
             LIMIT 5'
        )->fetchAll();

        jsonResponse([
            'success'        => true,
            'total_products' => (int)$totalProducts,
            'total_revenue'  => (float)$totalRevenue,
            'total_sales'    => (int)$totalSales,
            'today_revenue'  => (float)$todayRevenue,
            'low_stock'      => (int)$lowStock,
            'recent'         => $recent,
            'chart_data'     => $chartData,
            'top_products'   => $topProducts,
        ]);
        break;

    // ----------------------------------------------------------
    // Export sales as CSV
    // ----------------------------------------------------------
    case 'export':
        $from = $_GET['from'] ?? '';
        $to   = $_GET['to']   ?? '';

        $params = [];
        $where  = '';
        if ($from && $to) {
            $where    = 'WHERE s.sale_date BETWEEN ? AND ?';
            $params[] = $from;
            $params[] = $to;
        }

        $stmt = $db->prepare(
            "SELECT s.id, p.name AS product, p.category, s.quantity,
                    s.unit_price, s.total_price, s.customer_name, s.sale_date
             FROM sales s
             JOIN products p ON s.product_id = p.id
             $where
             ORDER BY s.sale_date ASC"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_export_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['#', 'Product', 'Category', 'Qty', 'Unit Price', 'Total', 'Customer', 'Date']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['product'], $r['category'],
                $r['quantity'], $r['unit_price'], $r['total_price'],
                $r['customer_name'], $r['sale_date']
            ]);
        }
        fclose($out);
        exit;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
