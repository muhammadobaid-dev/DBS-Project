<?php
require_once 'config.php';
require_once 'backend/auth.php';
requireLogin();

$customerId = (int)$_SESSION['customer_id'];

// ── Helper: Get or Create a Cart row for this customer ─────────────────────────
function getOrCreateCart($conn, $customerId) {
    $res = mysqli_query($conn, "SELECT cart_id FROM Cart WHERE customer_id=$customerId LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    if ($row) return (int)$row['cart_id'];

    // Create new cart
    $nextId = (int)mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(MAX(cart_id),0)+1 AS nid FROM Cart"))['nid'];
    mysqli_query($conn,
        "INSERT INTO Cart (cart_id, customer_id, created_at)
         VALUES ($nextId, $customerId, NOW())");
    return $nextId;
}

// ── Helper: Sync DB cart → SESSION ────────────────────────────────────────────
function loadCartFromDB($conn, $customerId) {
    $res = mysqli_query($conn,
        "SELECT ci.product_id, ci.quantity, p.product_name, p.price
         FROM Cart c
         JOIN Cart_Items ci ON ci.cart_id = c.cart_id
         JOIN Product p ON p.product_id = ci.product_id
         WHERE c.customer_id = $customerId");
    $cart = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $pid = (int)$row['product_id'];
        $cart[$pid] = [
            'product_id' => $pid,
            'name'       => $row['product_name'],
            'price'      => (float)$row['price'],
            'quantity'   => (int)$row['quantity'],
        ];
    }
    return $cart;
}

// ── Handle Actions ─────────────────────────────────────────────────────────────
$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? url('cart.php');

// ── ADD ────────────────────────────────────────────────────────────────────────
if ($action === 'add' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    // Validate stock
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT stock_quantity, product_name, price FROM Product WHERE product_id=$pid"));

    if ($row && $row['stock_quantity'] >= $qty) {
        $cartId = getOrCreateCart($conn, $customerId);

        // Check if this product already exists in Cart_Items
        $existing = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT cart_item_id, quantity FROM Cart_Items
             WHERE cart_id=$cartId AND product_id=$pid LIMIT 1"));

        if ($existing) {
            // UPDATE quantity
            $newQty = (int)$existing['quantity'] + $qty;
            mysqli_query($conn,
                "UPDATE Cart_Items SET quantity=$newQty
                 WHERE cart_item_id={$existing['cart_item_id']}");
        } else {
            // INSERT new item
            $nextItemId = (int)mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COALESCE(MAX(cart_item_id),0)+1 AS nid FROM Cart_Items"))['nid'];
            mysqli_query($conn,
                "INSERT INTO Cart_Items (cart_item_id, cart_id, product_id, quantity)
                 VALUES ($nextItemId, $cartId, $pid, $qty)");
        }

        // Also sync to session
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] += $qty;
        } else {
            $_SESSION['cart'][$pid] = [
                'product_id' => $pid,
                'name'       => $row['product_name'],
                'price'      => (float)$row['price'],
                'quantity'   => $qty,
            ];
        }

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "'{$row['product_name']}' added to cart!"];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sorry, not enough stock available.'];
    }
    header('Location: ' . $redirect); exit;
}

// ── UPDATE ─────────────────────────────────────────────────────────────────────
if ($action === 'update' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];

    $cartId = getOrCreateCart($conn, $customerId);

    if ($qty <= 0) {
        // Remove from DB
        mysqli_query($conn,
            "DELETE FROM Cart_Items WHERE cart_id=$cartId AND product_id=$pid");
        unset($_SESSION['cart'][$pid]);
    } else {
        // Update in DB
        $existing = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT cart_item_id FROM Cart_Items
             WHERE cart_id=$cartId AND product_id=$pid LIMIT 1"));
        if ($existing) {
            mysqli_query($conn,
                "UPDATE Cart_Items SET quantity=$qty
                 WHERE cart_item_id={$existing['cart_item_id']}");
        }
        // Update session
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] = $qty;
        }
    }
    header('Location: cart.php'); exit;
}

// ── REMOVE ─────────────────────────────────────────────────────────────────────
if ($action === 'remove') {
    $pid    = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
    $cartId = getOrCreateCart($conn, $customerId);

    // Delete from DB
    mysqli_query($conn,
        "DELETE FROM Cart_Items WHERE cart_id=$cartId AND product_id=$pid");

    // Delete from session
    unset($_SESSION['cart'][$pid]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Item removed from cart.'];
    header('Location: cart.php'); exit;
}

// ── CLEAR ──────────────────────────────────────────────────────────────────────
if ($action === 'clear') {
    $res = mysqli_query($conn,
        "SELECT cart_id FROM Cart WHERE customer_id=$customerId LIMIT 1");
    $cartRow = mysqli_fetch_assoc($res);
    if ($cartRow) {
        mysqli_query($conn,
            "DELETE FROM Cart_Items WHERE cart_id={$cartRow['cart_id']}");
    }
    $_SESSION['cart'] = [];
    header('Location: cart.php'); exit;
}

// ── On Page Load: always load cart fresh from DB ───────────────────────────────
$_SESSION['cart'] = loadCartFromDB($conn, $customerId);

// ── Render Cart Page ───────────────────────────────────────────────────────────
$pageTitle = 'Shopping Cart';
require_once 'includes/header.php';

$cart     = $_SESSION['cart'] ?? [];
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
$shipping = $subtotal >= 50 ? 0 : 5.99;
$total    = $subtotal + $shipping;
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8 flex items-center gap-3">
        <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Shopping Cart
        <span class="text-sm font-normal text-gray-400">(<?= count($cart) ?> item<?= count($cart) !== 1 ? 's' : '' ?>)</span>
    </h1>

    <?php if (empty($cart)): ?>
        <!-- Empty Cart -->
        <div class="text-center py-24 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-7xl mb-4">🛒</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Your cart is empty</h2>
            <p class="text-gray-500 mb-8">Looks like you haven't added anything yet.</p>
            <a href="index.php" class="btn-primary text-white px-8 py-4 rounded-xl font-bold text-lg inline-block shadow-lg">
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Cart Items -->
            <div class="flex-1 space-y-4">
                <!-- Clear All -->
                <div class="flex justify-end">
                    <a href="cart.php?action=clear" onclick="return confirm('Clear all items?')"
                       class="text-sm text-red-500 hover:text-red-700 flex items-center gap-1 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Clear Cart
                    </a>
                </div>

                <?php foreach ($cart as $item): ?>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-5">
                        <!-- Image -->
                        <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            <?php
                                $dbRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_url FROM Product WHERE product_id=" . (int)$item['product_id']));
                            ?>
                            <?php if (!empty($dbRow['image_url'])): ?>
                                <img src="<?= htmlspecialchars($dbRow['image_url']) ?>" class="w-full h-full object-cover" alt="">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-3xl">🛒</div>
                            <?php endif; ?>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <a href="product.php?id=<?= $item['product_id'] ?>"
                               class="font-semibold text-gray-900 hover:text-brand-600 transition line-clamp-1">
                                <?= htmlspecialchars($item['name']) ?>
                            </a>
                            <p class="text-brand-600 font-bold mt-0.5"><?= formatPrice($item['price']) ?> each</p>
                        </div>

                        <!-- Qty Update -->
                        <form method="POST" action="cart.php" class="flex items-center gap-2">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                                <button type="button" onclick="adjustQty(this,-1)" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold transition">−</button>
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" max="99"
                                       onchange="this.form.submit()" class="w-10 text-center text-sm font-semibold border-x border-gray-200 py-1.5 outline-none">
                                <button type="button" onclick="adjustQty(this,1)" class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 font-bold transition">+</button>
                            </div>
                        </form>

                        <!-- Subtotal -->
                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-gray-900"><?= formatPrice($item['price'] * $item['quantity']) ?></p>
                        </div>

                        <!-- Remove -->
                        <a href="cart.php?action=remove&product_id=<?= $item['product_id'] ?>"
                           class="text-red-400 hover:text-red-600 transition flex-shrink-0" title="Remove">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="lg:w-80">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                    <h2 class="text-lg font-bold text-gray-900 mb-5">Order Summary</h2>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal (<?= array_sum(array_column($cart, 'quantity')) ?> items)</span>
                            <span class="font-medium"><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span class="font-medium <?= $shipping === 0 ? 'text-green-600' : '' ?>">
                                <?= $shipping === 0 ? '🎉 Free' : formatPrice($shipping) ?>
                            </span>
                        </div>
                        <?php if ($shipping > 0): ?>
                            <p class="text-xs text-gray-400">Add <?= formatPrice(50 - $subtotal) ?> more for free shipping!</p>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-brand-500 h-1.5 rounded-full transition-all" style="width:<?= min(100, ($subtotal/50)*100) ?>%"></div>
                            </div>
                        <?php endif; ?>
                        <hr class="border-gray-100">
                        <div class="flex justify-between text-base font-bold text-gray-900">
                            <span>Total</span>
                            <span class="text-brand-600 text-lg"><?= formatPrice($total) ?></span>
                        </div>
                    </div>
                    <a href="checkout.php" class="mt-6 w-full btn-primary text-white py-4 rounded-xl font-bold text-center block shadow-lg text-lg">
                        Proceed to Checkout →
                    </a>
                    <a href="index.php" class="mt-3 block text-center text-sm text-gray-500 hover:text-brand-600 transition">
                        ← Continue Shopping
                    </a>
                    <!-- Badges -->
                    <div class="mt-6 flex justify-center gap-4 text-xs text-gray-400">
                        <span>🔒 Secure</span>
                        <span>🚚 Fast Delivery</span>
                        <span>🔄 Easy Returns</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function adjustQty(btn, delta) {
    const inp = btn.parentElement.querySelector('input[type=number]');
    const newVal = parseInt(inp.value) + delta;
    if (newVal >= 0) { inp.value = newVal; inp.form.submit(); }
}
</script>

<?php require_once 'includes/footer.php'; ?>
