<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

// Fetch product
$stmt = mysqli_prepare($conn,
    "SELECT p.*, c.category_name, c.category_id, s.supplier_name
     FROM Product p
     LEFT JOIN Category c ON p.category_id = c.category_id
     LEFT JOIN Supplier s ON p.supplier_id = s.supplier_id
     WHERE p.product_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) { header('Location: index.php'); exit; }

// Related products (same category)
$relStmt = mysqli_prepare($conn,
    "SELECT product_id, product_name, price, image_url, stock_quantity FROM Product
     WHERE category_id = ? AND product_id != ? LIMIT 4");
mysqli_stmt_bind_param($relStmt, 'ii', $product['category_id'], $id);
mysqli_stmt_execute($relStmt);
$related = mysqli_fetch_all(mysqli_stmt_get_result($relStmt), MYSQLI_ASSOC);

$pageTitle = $product['product_name'];
$pageDesc  = substr($product['description'] ?? '', 0, 150);
require_once 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-8">
        <a href="index.php" class="hover:text-brand-600 transition">Home</a>
        <span>›</span>
        <a href="index.php?category=<?= $product['category_id'] ?>" class="hover:text-brand-600 transition">
            <?= htmlspecialchars($product['category_name'] ?? 'Products') ?>
        </a>
        <span>›</span>
        <span class="text-gray-800 font-medium"><?= htmlspecialchars($product['product_name']) ?></span>
    </nav>

    <!-- Product Layout -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0">

            <!-- Image Side -->
            <div class="relative h-96 md:h-full bg-gradient-to-br from-gray-100 to-gray-200 min-h-80">
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>"
                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-8xl">🛒</div>
                <?php endif; ?>
                <span class="absolute top-4 left-4 bg-brand-600 text-white text-sm px-3 py-1.5 rounded-xl font-semibold">
                    <?= htmlspecialchars($product['category_name'] ?? 'General') ?>
                </span>
                <?php if ($product['stock_quantity'] <= 0): ?>
                    <span class="absolute top-4 right-4 bg-red-500 text-white text-sm px-3 py-1.5 rounded-xl font-semibold">Out of Stock</span>
                <?php elseif ($product['stock_quantity'] <= $product['reorder_level']): ?>
                    <span class="absolute top-4 right-4 bg-amber-500 text-white text-sm px-3 py-1.5 rounded-xl font-semibold">⚡ Only <?= $product['stock_quantity'] ?> left!</span>
                <?php endif; ?>
            </div>

            <!-- Info Side -->
            <div class="p-8 flex flex-col justify-between">
                <div>
                    <p class="text-brand-600 text-sm font-semibold uppercase tracking-wider mb-2">
                        <?= htmlspecialchars($product['supplier_name'] ?? 'ShopVerse') ?>
                    </p>
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-3"><?= htmlspecialchars($product['product_name']) ?></h1>

                    <!-- Price -->
                    <div class="flex items-baseline gap-3 mb-6">
                        <span class="text-4xl font-extrabold text-brand-600"><?= formatPrice($product['price']) ?></span>
                        <span class="text-gray-400 text-sm line-through"><?= formatPrice($product['price'] * 1.2) ?></span>
                        <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-lg">17% OFF</span>
                    </div>

                    <!-- Description -->
                    <p class="text-gray-600 leading-relaxed mb-6"><?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?></p>

                    <!-- Specs Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-400 block">Stock</span>
                            <span class="font-semibold text-gray-800"><?= $product['stock_quantity'] ?> units</span>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-400 block">Weight</span>
                            <span class="font-semibold text-gray-800"><?= $product['weight'] ? $product['weight'] . ' kg' : 'N/A' ?></span>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-400 block">Category</span>
                            <span class="font-semibold text-gray-800"><?= htmlspecialchars($product['category_name'] ?? '—') ?></span>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <span class="text-xs text-gray-400 block">Product ID</span>
                            <span class="font-semibold text-gray-800">#<?= $product['product_id'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Add to Cart Form -->
                <form method="POST" action="cart.php" class="space-y-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <input type="hidden" name="redirect" value="product.php?id=<?= $product['product_id'] ?>">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-medium text-gray-700">Qty:</label>
                        <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button" onclick="changeQty(-1)" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-lg font-bold text-gray-600 transition">−</button>
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?= $product['stock_quantity'] ?>"
                                   class="w-14 text-center py-2 outline-none text-sm font-semibold border-x border-gray-200">
                            <button type="button" onclick="changeQty(1)" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-lg font-bold text-gray-600 transition">+</button>
                        </div>
                        <span class="text-xs text-gray-400">(<?= $product['stock_quantity'] ?> available)</span>
                    </div>
                    <button type="submit" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                        class="w-full btn-primary text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3 <?= $product['stock_quantity'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?> shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <?= $product['stock_quantity'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                    </button>
                    <a href="checkout.php" class="block w-full text-center border-2 border-brand-500 text-brand-600 py-3.5 rounded-xl font-bold text-lg hover:bg-brand-50 transition">
                        Buy Now
                    </a>
                </form>

                <!-- Trust Badges -->
                <div class="flex items-center gap-4 mt-6 text-xs text-gray-400">
                    <span class="flex items-center gap-1">🚚 Free Shipping</span>
                    <span class="flex items-center gap-1">🔄 Easy Returns</span>
                    <span class="flex items-center gap-1">🔒 Secure Checkout</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($related): ?>
    <div class="mt-14">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">You May Also Like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <?php foreach ($related as $r): ?>
                <a href="product.php?id=<?= $r['product_id'] ?>" class="card-hover bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden group block">
                    <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($r['image_url'])): ?>
                            <img src="<?= htmlspecialchars($r['image_url']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition" alt="">
                        <?php else: ?>
                            <span class="text-4xl">🛒</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <p class="font-semibold text-gray-800 text-sm line-clamp-1 group-hover:text-brand-600 transition"><?= htmlspecialchars($r['product_name']) ?></p>
                        <p class="text-brand-600 font-bold mt-1"><?= formatPrice($r['price']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(delta) {
    const inp = document.getElementById('qtyInput');
    const max = parseInt(inp.max);
    let val = parseInt(inp.value) + delta;
    inp.value = Math.max(1, Math.min(val, max));
}
</script>

<?php require_once 'includes/footer.php'; ?>
