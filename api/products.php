<?php
// ============================================================
// api/products.php
// CRUD operations for products
// ============================================================

require_once '../includes/config.php';
requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$db = getDB();

switch ($action) {

    // ----------------------------------------------------------
    // LIST all products (with optional search/filter)
    // GET api/products.php?action=list&search=laptop&category=Electronics
    // ----------------------------------------------------------
    case 'list':
        $search   = '%' . trim($_GET['search']   ?? '') . '%';
        $category = trim($_GET['category'] ?? '');

        if ($category) {
            $stmt = $db->prepare(
                'SELECT * FROM products
                  WHERE (name LIKE ? OR category LIKE ?)
                    AND category = ?
                  ORDER BY name ASC'
            );
            $stmt->execute([$search, $search, $category]);
        } else {
            $stmt = $db->prepare(
                'SELECT * FROM products
                  WHERE name LIKE ? OR category LIKE ?
                  ORDER BY name ASC'
            );
            $stmt->execute([$search, $search]);
        }

        $products = $stmt->fetchAll();
        jsonResponse(['success' => true, 'data' => $products]);
        break;

    // ----------------------------------------------------------
    // GET single product
    // GET api/products.php?action=get&id=5
    // ----------------------------------------------------------
    case 'get':
        $id   = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }
        jsonResponse(['success' => true, 'data' => $product]);
        break;

    // ----------------------------------------------------------
    // ADD new product
    // POST api/products.php?action=add
    // ----------------------------------------------------------
    case 'add':
        $name      = trim($_POST['name']      ?? '');
        $category  = trim($_POST['category']  ?? '');
        $price     = (float)($_POST['price']    ?? 0);
        $quantity  = (int)($_POST['quantity']  ?? 0);
        $threshold = (int)($_POST['threshold'] ?? LOW_STOCK_DEFAULT);

        // Validation
        $errors = [];
        if (strlen($name) < 2)    $errors[] = 'Product name is required.';
        if (!$category)           $errors[] = 'Category is required.';
        if ($price <= 0)          $errors[] = 'Price must be greater than 0.';
        if ($quantity < 0)        $errors[] = 'Quantity cannot be negative.';

        if ($errors) {
            jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }

        $stmt = $db->prepare(
            'INSERT INTO products (name, category, price, quantity, low_stock_threshold)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $category, $price, $quantity, $threshold]);
        $newId = $db->lastInsertId();

        // Log initial stock
        if ($quantity > 0) {
            $log = $db->prepare(
                'INSERT INTO stock_history (product_id, change_type, quantity_change, quantity_after, note, changed_by)
                 VALUES (?, "add", ?, ?, "Initial stock", ?)'
            );
            $log->execute([$newId, $quantity, $quantity, $_SESSION['user_id']]);
        }

        jsonResponse(['success' => true, 'message' => 'Product added successfully!', 'id' => $newId]);
        break;

    // ----------------------------------------------------------
    // EDIT product
    // POST api/products.php?action=edit
    // ----------------------------------------------------------
    case 'edit':
        $id        = (int)($_POST['id']        ?? 0);
        $name      = trim($_POST['name']        ?? '');
        $category  = trim($_POST['category']    ?? '');
        $price     = (float)($_POST['price']    ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);
        $threshold = (int)($_POST['threshold']  ?? LOW_STOCK_DEFAULT);

        $errors = [];
        if (!$id)               $errors[] = 'Invalid product ID.';
        if (strlen($name) < 2) $errors[] = 'Product name is required.';
        if (!$category)        $errors[] = 'Category is required.';
        if ($price <= 0)       $errors[] = 'Price must be greater than 0.';
        if ($quantity < 0)     $errors[] = 'Quantity cannot be negative.';

        if ($errors) {
            jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
        }

        // Fetch old quantity to log stock change
        $old = $db->prepare('SELECT quantity FROM products WHERE id = ?');
        $old->execute([$id]);
        $oldProduct = $old->fetch();

        if (!$oldProduct) {
            jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $stmt = $db->prepare(
            'UPDATE products
             SET name=?, category=?, price=?, quantity=?, low_stock_threshold=?
             WHERE id=?'
        );
        $stmt->execute([$name, $category, $price, $quantity, $threshold, $id]);

        // Log quantity adjustment if it changed
        $diff = $quantity - $oldProduct['quantity'];
        if ($diff !== 0) {
            $log = $db->prepare(
                'INSERT INTO stock_history (product_id, change_type, quantity_change, quantity_after, note, changed_by)
                 VALUES (?, "adjust", ?, ?, "Manual adjustment", ?)'
            );
            $log->execute([$id, $diff, $quantity, $_SESSION['user_id']]);
        }

        jsonResponse(['success' => true, 'message' => 'Product updated successfully!']);
        break;

    // ----------------------------------------------------------
    // DELETE product
    // POST api/products.php?action=delete
    // ----------------------------------------------------------
    case 'delete':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Invalid product ID.']);
        }

        $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'message' => 'Product not found.'], 404);
        }

        jsonResponse(['success' => true, 'message' => 'Product deleted successfully!']);
        break;

    // ----------------------------------------------------------
    // GET all unique categories
    // ----------------------------------------------------------
    case 'categories':
        $stmt = $db->query(
            'SELECT DISTINCT category FROM products ORDER BY category ASC'
        );
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        jsonResponse(['success' => true, 'data' => $categories]);
        break;

    // ----------------------------------------------------------
    // STOCK HISTORY for a product
    // ----------------------------------------------------------
    case 'stock_history':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare(
            'SELECT sh.*, u.name AS changed_by_name
             FROM stock_history sh
             LEFT JOIN users u ON sh.changed_by = u.id
             WHERE sh.product_id = ?
             ORDER BY sh.changed_at DESC
             LIMIT 50'
        );
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action'], 400);
}
