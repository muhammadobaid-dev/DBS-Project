<?php
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/../backend/auth.php';

$cartCount = getCartCount();
$catResult = mysqli_query($conn, "SELECT category_id, category_name FROM Category ORDER BY category_name");
$categories = mysqli_fetch_all($catResult, MYSQLI_ASSOC);
$currentPage = basename($_SERVER['PHP_SELF']);
$base = basePath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' . APP_NAME : APP_NAME . ' — Premium Online Store' ?></title>
    <meta name="description" content="<?= isset($pageDesc) ? htmlspecialchars($pageDesc) : 'Shop the latest products at ShopVerse — your premium online shopping destination.' ?>">
    <meta name="author" content="<?= DEVELOPER ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#eef2ff', 100: '#dbe4ff', 200: '#bac8ff',
                            300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1',
                            600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81',
                        }
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Navbar -->
<nav class="sticky top-0 z-50 nav-blur border-b border-slate-200/80 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <a href="<?= url('index.php') ?>" class="flex items-center gap-2.5 group">
                <div class="w-9 h-9 bg-gradient-to-br from-brand-500 to-brand-800 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-extrabold bg-gradient-to-r from-brand-600 to-brand-900 bg-clip-text text-transparent"><?= APP_NAME ?></span>
                    <span class="hidden sm:block text-[10px] text-slate-400 -mt-0.5 tracking-wider uppercase">Premium Store</span>
                </div>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-1">
                <a href="<?= url('index.php') ?>" class="nav-link px-3 py-2 rounded-lg text-sm font-medium <?= $currentPage === 'index.php' ? 'active' : 'text-slate-600 hover:text-brand-600' ?>">Home</a>

                <div class="relative" id="catDropdownWrap">
                    <button id="catDropdownBtn" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-brand-600 transition flex items-center gap-1">
                        Categories
                        <svg class="w-4 h-4 transition-transform duration-200" id="catArrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="catDropdownMenu" class="dropdown-menu absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                        <a href="<?= url('index.php') ?>" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition font-medium">All Products</a>
                        <hr class="my-1 border-slate-100">
                        <?php foreach ($categories as $cat): ?>
                            <a href="<?= url('index.php?category=' . $cat['category_id']) ?>"
                               class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition
                               <?= (!empty($_GET['category']) && (int)$_GET['category'] === (int)$cat['category_id']) ? 'bg-brand-50 text-brand-700 font-semibold' : '' ?>">
                                <?= htmlspecialchars($cat['category_name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-2 sm:gap-3">
                <form method="GET" action="<?= url('index.php') ?>" class="hidden lg:flex items-center bg-slate-100 rounded-xl px-3 py-2 gap-2 border border-transparent focus-within:border-brand-300 focus-within:bg-white transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        class="bg-transparent text-sm text-slate-700 outline-none w-44 placeholder-slate-400">
                </form>

                <a href="<?= url('cart.php') ?>" class="relative flex items-center gap-2 btn-primary text-white px-3 sm:px-4 py-2 rounded-xl text-sm font-semibold shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="hidden sm:inline">Cart</span>
                    <?php if ($cartCount > 0): ?>
                        <span id="cartBadge" class="badge-cart absolute -top-2 -right-2 bg-rose-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                    <div class="relative hidden md:block" id="userDropdownWrap">
                        <button class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-brand-600 transition px-2 py-1 rounded-lg hover:bg-slate-100">
                            <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-brand-700 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                <?= strtoupper(substr($_SESSION['customer_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="max-w-[100px] truncate"><?= htmlspecialchars(explode(' ', $_SESSION['customer_name'] ?? 'Account')[0]) ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="userDropdownMenu" class="dropdown-menu absolute right-0 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-2 z-50" style="top:100%;">
                            <div class="px-4 py-2 border-b border-slate-50">
                                <p class="text-xs text-slate-400">Signed in as</p>
                                <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?></p>
                            </div>
                            <a href="<?= url('orders.php') ?>" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700">My Orders</a>
                            <a href="<?= url('logout.php') ?>" class="block px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-brand-600 transition px-3 py-2 rounded-xl hover:bg-slate-100 border border-slate-200">
                        Login
                    </a>
                    <a href="<?= url('register.php') ?>" class="hidden md:inline-flex items-center text-sm font-semibold text-white btn-primary px-4 py-2 rounded-xl">
                        Sign Up
                    </a>
                <?php endif; ?>

                <!-- Mobile menu button -->
                <button id="mobileMenuBtn" aria-expanded="false" class="md:hidden p-2 rounded-xl hover:bg-slate-100 transition text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="md:hidden border-t border-slate-100 pb-4">
            <form method="GET" action="<?= url('index.php') ?>" class="flex items-center bg-slate-100 rounded-xl px-3 py-2 gap-2 mt-3">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" placeholder="Search..." class="bg-transparent text-sm outline-none flex-1">
            </form>
            <div class="mt-3 space-y-1">
                <a href="<?= url('index.php') ?>" class="block px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 hover:bg-brand-50">Home</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= url('index.php?category=' . $cat['category_id']) ?>" class="block px-3 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-brand-50"><?= htmlspecialchars($cat['category_name']) ?></a>
                <?php endforeach; ?>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= url('orders.php') ?>" class="block px-3 py-2.5 rounded-xl text-sm text-slate-600 hover:bg-brand-50">My Orders</a>
                    <a href="<?= url('logout.php') ?>" class="block px-3 py-2.5 rounded-xl text-sm text-rose-600 hover:bg-rose-50">Logout</a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-brand-600">Login</a>
                    <a href="<?= url('register.php') ?>" class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-brand-600">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
(function(){
    var wrap = document.getElementById('catDropdownWrap');
    var menu = document.getElementById('catDropdownMenu');
    var arrow = document.getElementById('catArrow');
    var timer;
    if (wrap && menu) {
        wrap.addEventListener('mouseenter', function(){ clearTimeout(timer); menu.classList.add('open'); if(arrow) arrow.style.transform='rotate(180deg)'; });
        wrap.addEventListener('mouseleave', function(){ timer=setTimeout(function(){ menu.classList.remove('open'); if(arrow) arrow.style.transform='rotate(0deg)'; },150); });
        wrap.querySelector('button').addEventListener('click', function(){ menu.classList.toggle('open'); if(arrow) arrow.style.transform=menu.classList.contains('open')?'rotate(180deg)':'rotate(0deg)'; });
    }
    var uWrap = document.getElementById('userDropdownWrap');
    var uMenu = document.getElementById('userDropdownMenu');
    if (uWrap && uMenu) {
        var t2;
        uWrap.addEventListener('mouseenter', function(){ clearTimeout(t2); uMenu.classList.add('open'); });
        uWrap.addEventListener('mouseleave', function(){ t2=setTimeout(function(){ uMenu.classList.remove('open'); },150); });
    }
})();
</script>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash'])): ?>
    <div id="flashMsg" class="fixed top-20 right-4 z-50 max-w-sm bg-white border <?= $_SESSION['flash']['type'] === 'success' ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-rose-300 bg-rose-50 text-rose-800' ?> rounded-2xl shadow-xl px-5 py-4 flex items-center gap-3">
        <span class="text-xl"><?= $_SESSION['flash']['type'] === 'success' ? '✓' : '✕' ?></span>
        <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></p>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<main class="min-h-screen">
