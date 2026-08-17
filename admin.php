<?php
require_once 'config.php';
require_once 'backend/auth.php';

if (!isAdmin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
        $result = loginAdmin($conn, $_POST['admin_user'] ?? '', $_POST['admin_pass'] ?? '');
        if (!$result['success']) $loginError = $result['error'];
    }
    if (!isAdmin()) {
        // Show login form
        ?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Admin Login — ShopVerse</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;}</style></head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm">
    <div class="text-center mb-6">
        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-800 rounded-2xl flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Admin Login</h1>
        <p class="text-gray-500 text-sm">ShopVerse Dashboard — <?= DEVELOPER ?></p>
    </div>
    <?php if (!empty($loginError)): ?>
        <p class="bg-red-50 text-red-600 text-sm rounded-xl px-4 py-3 mb-4"><?= htmlspecialchars($loginError) ?></p>
    <?php endif; ?>
    <form method="POST">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="admin_user" placeholder="Enter username" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="admin_pass" placeholder="Enter password" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit" name="admin_login" class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white py-3 rounded-xl font-bold hover:opacity-90 transition">
                Enter Dashboard
            </button>
        </div>

    </form>
</div></body></html><?php
        exit;
    }
}

$tab = $_GET['tab'] ?? 'overview';
$msg = '';

// ── Auto-seed Categories & Suppliers if empty ─────────────────────────────────
// Allow NULL for category_id and supplier_id (removes FK restriction)
@mysqli_query($conn, "ALTER TABLE Product MODIFY category_id INT NULL");
@mysqli_query($conn, "ALTER TABLE Product MODIFY supplier_id INT NULL");

$catCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Category"))['n'];
if ($catCount == 0) {
    mysqli_query($conn, "INSERT INTO Category (category_id, category_name, description) VALUES
        (1, 'Electronics',  'Phones, laptops, gadgets and accessories'),
        (2, 'Clothing',     'Men and women fashion and apparel'),
        (3, 'Books',        'Academic, fiction and non-fiction books'),
        (4, 'Home & Garden','Furniture, decor and garden supplies'),
        (5, 'Sports',       'Fitness, outdoor and sports equipment')");
}
$supCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Supplier"))['n'];
if ($supCount == 0) {
    mysqli_query($conn, "INSERT INTO Supplier (supplier_id, supplier_name, contact_person, phone_number, email, address) VALUES
        (1, 'TechPro Supplies', 'Ali Khan',    '0300-1234567', 'ali@techpro.pk',    'Karachi, Pakistan'),
        (2, 'FashionHub',       'Sara Ahmed',  '0321-9876543', 'sara@fashionhub.pk','Lahore, Pakistan'),
        (3, 'BookWorld',        'Omar Sheikh', '0333-5556666', 'omar@bookworld.pk', 'Islamabad, Pakistan')");
}

// ── Handle POST Actions ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Product
    if (isset($_POST['add_product'])) {
        $nextId  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(product_id),0)+1 AS n FROM Product"))['n'];
        $name    = sanitize($conn, $_POST['product_name']);
        $desc    = sanitize($conn, $_POST['description']);
        $price   = (float)$_POST['price'];
        $stock   = (int)$_POST['stock_quantity'];
        $reorder = (int)$_POST['reorder_level'];
        $weight  = (float)$_POST['weight'];
        $catId   = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : 'NULL';
        $supId   = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 'NULL';
        $imgUrl  = sanitize($conn, $_POST['image_url']);
        $result = mysqli_query($conn, "INSERT INTO Product (product_id,category_id,supplier_id,product_name,description,price,stock_quantity,reorder_level,weight,image_url)
            VALUES ($nextId,$catId,$supId,'$name','$desc',$price,$stock,$reorder,$weight,'$imgUrl')");
        if ($result) {
            $msg = "✅ Product '$name' added successfully!";
        } else {
            $msg = "❌ Error: " . mysqli_error($conn);
        }
        $tab = 'products';
    }
    // Update Stock
    if (isset($_POST['update_stock'])) {
        $pid   = (int)$_POST['product_id'];
        $stock = (int)$_POST['new_stock'];
        mysqli_query($conn, "UPDATE Product SET stock_quantity=$stock WHERE product_id=$pid");
        $msg = "✅ Stock updated.";
        $tab = 'products';
    }
    // Delete Product
    if (isset($_POST['delete_product'])) {
        $pid = (int)$_POST['product_id'];
        // Remove from carts and order items first (FK safety)
        mysqli_query($conn, "DELETE FROM Cart_Items WHERE product_id=$pid");
        mysqli_query($conn, "DELETE FROM Supplier_Order_Items WHERE product_id=$pid");
        // Get product name for message
        $prow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_name FROM Product WHERE product_id=$pid"));
        $pname = $prow['product_name'] ?? "#$pid";
        if (mysqli_query($conn, "DELETE FROM Product WHERE product_id=$pid")) {
            $msg = "🗑️ Product '$pname' deleted successfully.";
        } else {
            $msg = "❌ Could not delete: " . mysqli_error($conn);
        }
        $tab = 'products';
    }
    // Update Order Status
    if (isset($_POST['update_order'])) {
        $oid    = (int)$_POST['order_id'];
        $status = sanitize($conn, $_POST['status']);
        mysqli_query($conn, "UPDATE Orders SET status='$status' WHERE order_id=$oid");
        $msg = "✅ Order #$oid status updated.";
        $tab = 'orders';
    }
    // Admin logout
    if (isset($_POST['admin_logout'])) {
        logoutAdmin();
        redirect('admin.php');
    }
}

// ── Stats ──────────────────────────────────────────────────────────────────────
$stats = [
    'total_orders'    => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Orders"))['n'],
    'total_revenue'   => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) AS n FROM Orders WHERE status!='Cancelled'"))['n'],
    'total_products'  => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Product"))['n'],
    'total_customers' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Customer"))['n'],
    'low_stock'       => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM Product WHERE stock_quantity <= reorder_level"))['n'],
];

// Products list
$products   = mysqli_fetch_all(mysqli_query($conn, "SELECT p.*,c.category_name FROM Product p LEFT JOIN Category c ON p.category_id=c.category_id ORDER BY p.product_id DESC LIMIT 50"), MYSQLI_ASSOC);
// Orders list
$orders     = mysqli_fetch_all(mysqli_query($conn, "SELECT o.*,CONCAT(cu.first_name,' ',cu.last_name) AS cname FROM Orders o LEFT JOIN Customer cu ON o.customer_id=cu.customer_id ORDER BY o.order_date DESC LIMIT 50"), MYSQLI_ASSOC);
// Categories & Suppliers for form
$cats       = mysqli_fetch_all(mysqli_query($conn, "SELECT category_id,category_name FROM Category"), MYSQLI_ASSOC);
$suppliers  = mysqli_fetch_all(mysqli_query($conn, "SELECT supplier_id,supplier_name FROM Supplier"), MYSQLI_ASSOC);

$statusColors = ['Pending'=>'bg-yellow-100 text-yellow-800','Processing'=>'bg-blue-100 text-blue-800','Shipped'=>'bg-purple-100 text-purple-800','Delivered'=>'bg-green-100 text-green-800','Cancelled'=>'bg-red-100 text-red-800'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — ShopVerse</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{brand:{500:'#5c7cfa',600:'#4c6ef5',700:'#4263eb',800:'#3b5bdb'}}}}}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>body{font-family:'Inter',sans-serif;} .tab-active{background:#4c6ef5;color:#fff;}</style>
</head>
<body class="bg-gray-50">

<!-- Topbar -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-brand-800 rounded-xl flex items-center justify-center">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <span class="font-bold text-gray-900 text-lg">ShopVerse <span class="text-brand-600">Admin</span></span>
    </div>
    <div class="flex items-center gap-4">
        <a href="<?= url('index.php') ?>" class="text-sm text-gray-500 hover:text-brand-600 transition">← View Store</a>
        <form method="POST" class="inline">
            <button name="admin_logout" class="text-sm bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">Logout</button>
        </form>
    </div>
</div>
</header>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<?php if ($msg): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 mb-6 text-sm font-medium"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Tabs -->
<div class="flex gap-2 mb-8 bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm w-fit">
    <?php foreach (['overview'=>'📊 Overview','products'=>'📦 Products','orders'=>'🛒 Orders','add_product'=>'➕ Add Product'] as $t=>$label): ?>
        <a href="?tab=<?= $t ?>" class="px-4 py-2 rounded-xl text-sm font-medium transition <?= $tab===$t ? 'tab-active' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- ── OVERVIEW ── -->
<?php if ($tab === 'overview'): ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['💰','Total Revenue','$'.number_format($stats['total_revenue'],2),'bg-blue-50 text-blue-600'],
        ['📦','Total Orders',$stats['total_orders'],'bg-purple-50 text-purple-600'],
        ['🛍️','Products',$stats['total_products'],'bg-green-50 text-green-600'],
        ['👥','Customers',$stats['total_customers'],'bg-orange-50 text-orange-600'],
    ];
    foreach ($cards as [$icon,$label,$val,$color]): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl <?= $color ?> mb-3"><?= $icon ?></div>
            <p class="text-sm text-gray-500"><?= $label ?></p>
            <p class="text-2xl font-extrabold text-gray-900"><?= $val ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($stats['low_stock'] > 0): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6">
    <p class="font-semibold text-amber-800">⚠️ <?= $stats['low_stock'] ?> product(s) are at or below reorder level!</p>
    <a href="?tab=products" class="text-sm text-amber-700 underline mt-1 inline-block">View Products →</a>
</div>
<?php endif; ?>

<!-- Recent Orders -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Recent Orders</h2>
        <a href="?tab=orders" class="text-sm text-brand-600 hover:underline">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr><th class="px-6 py-3 text-left">Order</th><th class="px-6 py-3 text-left">Customer</th><th class="px-6 py-3 text-left">Amount</th><th class="px-6 py-3 text-left">Status</th><th class="px-6 py-3 text-left">Date</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach (array_slice($orders, 0, 5) as $o): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-semibold text-gray-900">#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($o['cname'] ?? 'Guest') ?></td>
                    <td class="px-6 py-4 font-semibold text-brand-600">$<?= number_format($o['total_amount'],2) ?></td>
                    <td class="px-6 py-4"><span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700' ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                    <td class="px-6 py-4 text-gray-500"><?= date('d M Y',strtotime($o['order_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No orders yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── PRODUCTS ── -->
<?php elseif ($tab === 'products'): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
        <h2 class="font-bold text-gray-900">Products (<?= count($products) ?>)</h2>
        <a href="?tab=add_product" class="bg-gradient-to-r from-brand-600 to-brand-800 text-white px-4 py-2 rounded-xl text-sm font-semibold">+ Add Product</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr><th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Price</th><th class="px-4 py-3 text-left">Stock</th><th class="px-4 py-3 text-left">Update Stock</th><th class="px-4 py-3 text-left">Delete</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($products as $p): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-400 font-mono">#<?= $p['product_id'] ?></td>
                    <td class="px-4 py-3 font-medium text-gray-900"><a href="<?= url('product.php?id=' . $p['product_id']) ?>" class="hover:text-brand-600" target="_blank"><?= htmlspecialchars($p['product_name']) ?></a></td>
                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td class="px-4 py-3 font-semibold text-brand-600">$<?= number_format($p['price'],2) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-lg text-xs font-semibold <?= $p['stock_quantity'] <= 0 ? 'bg-red-100 text-red-700' : ($p['stock_quantity'] <= $p['reorder_level'] ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') ?>">
                            <?= $p['stock_quantity'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                            <input type="number" name="new_stock" value="<?= $p['stock_quantity'] ?>" min="0" class="w-20 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:border-brand-400">
                            <button type="submit" name="update_stock" class="bg-brand-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-brand-700 transition">Save</button>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" onsubmit="return confirm('⚠️ Delete \'<?= htmlspecialchars(addslashes($p['product_name'])) ?>\' permanently? This cannot be undone!')">
                            <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                            <button type="submit" name="delete_product"
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?><tr><td colspan="7" class="px-6 py-8 text-center text-gray-400">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── ORDERS ── -->
<?php elseif ($tab === 'orders'): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-bold text-gray-900">All Orders (<?= count($orders) ?>)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr><th class="px-4 py-3 text-left">Order</th><th class="px-4 py-3 text-left">Customer</th><th class="px-4 py-3 text-left">Total</th><th class="px-4 py-3 text-left">Payment</th><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-semibold text-gray-900">#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($o['cname'] ?? 'Guest') ?></td>
                    <td class="px-4 py-3 font-semibold text-brand-600">$<?= number_format($o['total_amount'],2) ?></td>
                    <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($o['payment_method']) ?></td>
                    <td class="px-4 py-3 text-gray-500"><?= date('d M Y, h:i A',strtotime($o['order_date'])) ?></td>
                    <td class="px-4 py-3">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                            <select name="status" class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:border-brand-400">
                                <?php foreach (['Pending','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                                    <option <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_order" class="bg-brand-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-brand-700 transition">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No orders yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── ADD PRODUCT ── -->
<?php elseif ($tab === 'add_product'): ?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Add New Product</h2>
        <form method="POST" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" name="product_name" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price ($) *</label>
                    <input type="number" name="price" step="0.01" min="0" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" min="0" required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level</label>
                    <input type="number" name="reorder_level" min="0" value="10" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                    <input type="number" name="weight" step="0.1" min="0" value="0" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-gray-400 font-normal">(optional)</span></label>
                    <select name="category_id" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                        <option value="">— No Category —</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                        <option value="0">— None —</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['supplier_id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                    <input type="url" name="image_url" placeholder="https://..." class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100">
                </div>
            </div>
            <button type="submit" name="add_product" class="w-full bg-gradient-to-r from-brand-600 to-brand-800 text-white py-4 rounded-xl font-bold text-base shadow-lg hover:opacity-90 transition">
                ➕ Add Product
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

</div><!-- /max-w-7xl -->
</body>
</html>
