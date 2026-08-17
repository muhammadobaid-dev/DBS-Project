<?php
require_once 'config.php';
require_once 'backend/auth.php';
requireLogin();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect('cart.php');


$errors   = [];
$success  = false;
$orderId  = null;

// ── Process Order on POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($conn, $_POST['first_name']  ?? '');
    $lastName  = sanitize($conn, $_POST['last_name']   ?? '');
    $email     = sanitize($conn, $_POST['email']       ?? '');
    $phone     = sanitize($conn, $_POST['phone']       ?? '');
    $city      = sanitize($conn, $_POST['city']        ?? '');
    $state     = sanitize($conn, $_POST['state']       ?? '');
    $zip       = sanitize($conn, $_POST['zip']         ?? '');
    $payment   = sanitize($conn, $_POST['payment']     ?? '');

    if (!$firstName) $errors[] = 'First name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
    if (!$city)  $errors[] = 'City is required.';
    if (!$payment) $errors[] = 'Select a payment method.';

    if (empty($errors)) {
        mysqli_begin_transaction($conn);
        try {
            // 1. Upsert Customer
            $customerId = $_SESSION['customer_id'] ?? null;
            if (!$customerId) {
                $passHash = password_hash('guest_' . $email . time(), PASSWORD_DEFAULT);
                $res = mysqli_query($conn,
                    "SELECT customer_id FROM Customer WHERE email='$email' LIMIT 1");
                $existing = mysqli_fetch_assoc($res);
                if ($existing) {
                    $customerId = $existing['customer_id'];
                } else {
                    $nextId = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(customer_id),0)+1 AS nid FROM Customer"))['nid'];
                    mysqli_query($conn, "INSERT INTO Customer
                        (customer_id,first_name,last_name,email,password_hash,mobile_number,total_spent)
                        VALUES ($nextId,'$firstName','$lastName','$email','$passHash','$phone',0)");
                    $customerId = $nextId;
                }
                $_SESSION['customer_id']   = $customerId;
                $_SESSION['customer_name'] = "$firstName $lastName";
            }

            // 2. Save Address
            $nextAddrId = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(address_id),0)+1 AS nid FROM Address"))['nid'];
            mysqli_query($conn, "INSERT INTO Address (address_id,customer_id,city,state,zip_code,is_default)
                VALUES ($nextAddrId,$customerId,'$city','$state','$zip',1)");

            // 3. Calculate totals
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $shipping  = $subtotal >= 50 ? 0 : 5.99;
            $total     = $subtotal + $shipping;
            $discount  = 0;

            // 4. Create Order
            $nextOrderId = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(order_id),0)+1 AS nid FROM Orders"))['nid'];
            $orderDate   = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO Orders
                (order_id,customer_id,order_date,total_amount,discount_amount,payment_method,status)
                VALUES ($nextOrderId,$customerId,'$orderDate',$total,$discount,'$payment','Pending')");

            // 5. Insert Order Items + Decrement Stock
            $nextItemId = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(order_item_id),0)+1 AS nid FROM Order_Items"))['nid'];
            foreach ($cart as $item) {
                $pid      = (int)$item['product_id'];
                $qty      = (int)$item['quantity'];
                $price    = (float)$item['price'];
                $subtotalItem = $price * $qty;

                // Verify stock again (race-condition safe)
                $stockRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock_quantity FROM Product WHERE product_id=$pid FOR UPDATE"));
                if (!$stockRow || $stockRow['stock_quantity'] < $qty) {
                    throw new Exception("Insufficient stock for product #{$pid}.");
                }

                mysqli_query($conn, "INSERT INTO Order_Items
                    (order_item_id,order_id,product_id,quantity,unit_price,subtotal)
                    VALUES ($nextItemId,$nextOrderId,$pid,$qty,$price,$subtotalItem)");
                $nextItemId++;

                // Decrement stock
                mysqli_query($conn, "UPDATE Product SET stock_quantity = stock_quantity - $qty WHERE product_id=$pid");
            }

            // 6. Create Payment Record
            $nextPayId = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(payment_id),0)+1 AS nid FROM Payment"))['nid'];
            $txRef     = strtoupper(uniqid('TXN'));
            $payStatus = in_array($payment, ['Credit Card','Debit Card']) ? 'Paid' : 'Pending';
            mysqli_query($conn, "INSERT INTO Payment
                (payment_id,order_id,amount_paid,payment_status,transaction_ref,payment_date)
                VALUES ($nextPayId,$nextOrderId,$total,'$payStatus','$txRef','$orderDate')");

            // 7. Update customer total_spent
            mysqli_query($conn, "UPDATE Customer SET total_spent = total_spent + $total WHERE customer_id=$customerId");

            mysqli_commit($conn);

            // 8. Clear cart & store order id
            $_SESSION['cart']   = [];
            $orderId            = $nextOrderId;
            $success            = true;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = 'Order failed: ' . $e->getMessage();
        }
    }
}

// Pre-fill from session
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));
$shipping = $subtotal >= 50 ? 0 : 5.99;
$total    = $subtotal + $shipping;

$pageTitle = 'Checkout';
require_once 'includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <?php if ($success): ?>
        <!-- ── Success State ── -->
        <div class="max-w-xl mx-auto text-center py-16">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center text-5xl mx-auto mb-6 shadow-lg">✅</div>
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Order Placed!</h1>
            <p class="text-gray-500 mb-2">Thank you for your purchase.</p>
            <div class="bg-brand-50 border border-brand-100 rounded-2xl px-8 py-5 inline-block my-4">
                <p class="text-sm text-gray-500">Order Reference</p>
                <p class="text-3xl font-extrabold text-brand-600">#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></p>
            </div>
            <p class="text-gray-500 text-sm mb-8">You'll receive a confirmation email shortly. Your items are being prepared.</p>
            <div class="flex gap-3 justify-center">
                <a href="orders.php" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold shadow-lg">View My Orders</a>
                <a href="index.php" class="border-2 border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Continue Shopping</a>
            </div>
        </div>

    <?php else: ?>
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8">Checkout</h1>

        <!-- Errors -->
        <?php if ($errors): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 mb-6">
                <p class="font-semibold text-red-700 mb-1">Please fix the following:</p>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm">
            <div class="flex flex-col lg:flex-row gap-8">

                <!-- Left: Forms -->
                <div class="flex-1 space-y-6">

                    <!-- Personal Info -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <span class="w-7 h-7 bg-brand-600 text-white rounded-full text-sm flex items-center justify-center font-bold">1</span>
                            Contact Information
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input type="text" name="first_name" required value="<?= htmlspecialchars($_SESSION['customer_fname'] ?? '') ?>"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($_SESSION['customer_lname'] ?? '') ?>"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($_SESSION['customer_email'] ?? '') ?>"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <span class="w-7 h-7 bg-brand-600 text-white rounded-full text-sm flex items-center justify-center font-bold">2</span>
                            Shipping Address
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                <input type="text" name="city" required
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                                <input type="text" name="state"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ZIP / Postal Code</label>
                                <input type="text" name="zip"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                        <h2 class="text-lg font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <span class="w-7 h-7 bg-brand-600 text-white rounded-full text-sm flex items-center justify-center font-bold">3</span>
                            Payment Method
                        </h2>
                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $methods = [
                                'Credit Card'  => '💳',
                                'Debit Card'   => '🏦',
                                'Cash on Delivery' => '💵',
                                'Bank Transfer'    => '🏧',
                            ];
                            foreach ($methods as $method => $icon):
                            ?>
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="payment" value="<?= $method ?>" class="peer sr-only" required>
                                    <div class="border-2 border-gray-200 rounded-xl p-4 flex items-center gap-3 peer-checked:border-brand-500 peer-checked:bg-brand-50 transition">
                                        <span class="text-2xl"><?= $icon ?></span>
                                        <span class="text-sm font-medium text-gray-700 peer-checked:text-brand-700"><?= $method ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Order Summary -->
                <div class="lg:w-80">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                        <h2 class="text-lg font-bold text-gray-900 mb-5">Your Order</h2>

                        <div class="space-y-3 mb-5">
                            <?php foreach ($cart as $item): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="bg-brand-100 text-brand-800 text-xs rounded-lg px-1.5 py-0.5 font-semibold flex-shrink-0"><?= $item['quantity'] ?>×</span>
                                        <span class="text-gray-700 line-clamp-1"><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                    <span class="font-semibold text-gray-900 flex-shrink-0 ml-2"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="border-gray-100 my-4">

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span><span><?= formatPrice($subtotal) ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Shipping</span>
                                <span class="<?= $shipping === 0 ? 'text-green-600 font-medium' : '' ?>">
                                    <?= $shipping === 0 ? '🎉 Free' : formatPrice($shipping) ?>
                                </span>
                            </div>
                            <hr class="border-gray-100">
                            <div class="flex justify-between text-base font-bold text-gray-900">
                                <span>Total</span>
                                <span class="text-brand-600 text-lg"><?= formatPrice($total) ?></span>
                            </div>
                        </div>

                        <button type="submit" class="mt-6 w-full btn-primary text-white py-4 rounded-xl font-bold text-lg shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Place Order
                        </button>
                        <p class="text-center text-xs text-gray-400 mt-3">🔒 Your information is 100% secure</p>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
