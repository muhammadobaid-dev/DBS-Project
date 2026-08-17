<?php
require_once 'config.php';
require_once 'backend/auth.php';
requireLogin();

$customerId = (int)$_SESSION['customer_id'];
$orders = mysqli_fetch_all(mysqli_query($conn,
    "SELECT o.*, p.payment_status
     FROM Orders o
     LEFT JOIN Payment p ON o.order_id = p.order_id
     WHERE o.customer_id = $customerId
     ORDER BY o.order_date DESC"), MYSQLI_ASSOC);

$statusColors = ['Pending'=>'bg-yellow-100 text-yellow-800','Processing'=>'bg-blue-100 text-blue-800','Shipped'=>'bg-purple-100 text-purple-800','Delivered'=>'bg-green-100 text-green-800','Cancelled'=>'bg-red-100 text-red-800'];

$pageTitle = 'My Orders';
require_once 'includes/header.php';
?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-3xl font-extrabold text-gray-900 mb-8">My Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="text-center py-20 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-6xl mb-4">📦</div>
            <h2 class="text-xl font-bold text-gray-700 mb-2">No orders yet</h2>
            <p class="text-gray-500 mb-6">Start shopping to see your orders here.</p>
            <a href="index.php" class="btn-primary text-white px-8 py-3 rounded-xl font-bold inline-block shadow-lg">Shop Now</a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $o): ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <span class="text-xs text-gray-400">Order</span>
                            <h2 class="text-xl font-extrabold text-gray-900">#<?= str_pad($o['order_id'],6,'0',STR_PAD_LEFT) ?></h2>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="px-3 py-1 rounded-xl text-sm font-semibold <?= $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= htmlspecialchars($o['status']) ?>
                            </span>
                            <span class="text-xl font-extrabold text-brand-600">$<?= number_format($o['total_amount'],2) ?></span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm text-gray-500">
                        <div>
                            <span class="block text-xs text-gray-400 mb-0.5">Date</span>
                            <span class="font-medium text-gray-700"><?= date('d M Y', strtotime($o['order_date'])) ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-0.5">Payment</span>
                            <span class="font-medium text-gray-700"><?= htmlspecialchars($o['payment_method'] ?? '—') ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-0.5">Payment Status</span>
                            <span class="font-medium <?= $o['payment_status']==='Paid' ? 'text-green-600' : 'text-amber-600' ?>"><?= htmlspecialchars($o['payment_status'] ?? 'Pending') ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 mb-0.5">Discount</span>
                            <span class="font-medium text-gray-700">$<?= number_format($o['discount_amount'] ?? 0, 2) ?></span>
                        </div>
                    </div>
                    <?php
                    // Fetch order items
                    $items = mysqli_fetch_all(mysqli_query($conn,
                        "SELECT oi.*, p.product_name, p.image_url FROM Order_Items oi
                         LEFT JOIN Product p ON oi.product_id = p.product_id
                         WHERE oi.order_id = {$o['order_id']}"), MYSQLI_ASSOC);
                    if ($items): ?>
                    <div class="mt-4 border-t border-gray-100 pt-4 space-y-2">
                        <?php foreach ($items as $item): ?>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" class="w-full h-full object-cover" alt="">
                                    <?php else: ?><span class="w-full h-full flex items-center justify-center text-lg">🛒</span><?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 line-clamp-1"><?= htmlspecialchars($item['product_name']) ?></p>
                                    <p class="text-xs text-gray-400"><?= $item['quantity'] ?> × $<?= number_format($item['unit_price'],2) ?></p>
                                </div>
                                <p class="text-sm font-semibold text-gray-900">$<?= number_format($item['subtotal'],2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
