</main>

<!-- Footer -->
<footer class="bg-slate-900 text-slate-300 mt-20">
    <!-- Feature strip -->
    <div class="feature-strip py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center text-sm">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-2xl">🚚</span>
                    <span class="font-semibold">Free Shipping $50+</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-2xl">🔒</span>
                    <span class="font-semibold">Secure Checkout</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-2xl">🔄</span>
                    <span class="font-semibold">Easy Returns</span>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <span class="text-2xl">💬</span>
                    <span class="font-semibold">24/7 Support</span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1 md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-brand-800 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-lg"><?= APP_NAME ?></span>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed">Your premium online shopping destination. Quality products, fast delivery, best prices.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Quick Links</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= url('index.php') ?>" class="hover:text-white transition">Home</a></li>
                    <li><a href="<?= url('cart.php') ?>" class="hover:text-white transition">Cart</a></li>
                    <li><a href="<?= url('checkout.php') ?>" class="hover:text-white transition">Checkout</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Account</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= url('login.php') ?>" class="hover:text-white transition">Login</a></li>
                    <li><a href="<?= url('register.php') ?>" class="hover:text-white transition">Register</a></li>
                    <li><a href="<?= url('orders.php') ?>" class="hover:text-white transition">My Orders</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-4 text-sm uppercase tracking-wider">Admin</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="<?= url('admin.php') ?>" class="hover:text-white transition">Dashboard</a></li>
                    <li><a href="<?= url('admin.php?tab=products') ?>" class="hover:text-white transition">Products</a></li>
                    <li><a href="<?= url('admin.php?tab=orders') ?>" class="hover:text-white transition">Orders</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 mt-10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-sm text-slate-500 text-center md:text-left">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> — DBMS 6th Semester Project</p>
                <p class="mt-1 text-xs">Built with PHP, MySQL & Tailwind CSS</p>
            </div>
            <a href="#" class="dev-badge" title="Project Developer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Developed by <strong class="text-indigo-300"><?= DEVELOPER ?></strong>
            </a>
        </div>
    </div>
</footer>

<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
