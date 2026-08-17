<?php
require_once 'config.php';
require_once 'backend/auth.php';

if (isLoggedIn()) redirect('index.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginCustomer($conn, $_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        setFlash('success', 'Welcome back, ' . $result['name'] . '!');
        redirect('index.php');
    }
    $errors[] = $result['error'];
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-16 gradient-hero relative">
    <div class="absolute inset-0 hero-glow pointer-events-none"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 relative z-10 border border-white/20">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-gradient-to-br from-brand-500 to-brand-800 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Welcome Back</h1>
            <p class="text-slate-500 text-sm mt-1">Sign in to your <?= APP_NAME ?> account</p>
        </div>
        <?php if ($errors): ?>
            <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 mb-5 text-sm text-rose-700">
                <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4" data-loading>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                <input type="password" name="password" required class="form-input">
            </div>
            <button type="submit" class="w-full btn-primary text-white py-3.5 rounded-xl font-bold text-base shadow-lg mt-2">
                Sign In
            </button>
        </form>
        <p class="text-center text-sm text-slate-500 mt-6">
            Don't have an account? <a href="<?= url('register.php') ?>" class="text-brand-600 font-semibold hover:underline">Register</a>
        </p>
        <p class="text-center text-xs text-slate-400 mt-4">Developed by <?= DEVELOPER ?></p>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
