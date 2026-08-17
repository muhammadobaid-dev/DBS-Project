<?php
require_once 'config.php';
require_once 'backend/auth.php';

if (isLoggedIn()) redirect('index.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerCustomer($conn, $_POST);
    if ($result['success']) {
        setFlash('success', "Account created! Welcome, {$result['name']}!");
        redirect('index.php');
    }
    $errors = $result['errors'];
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-16 gradient-hero relative">
    <div class="absolute inset-0 hero-glow pointer-events-none"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 relative z-10">
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-gradient-to-br from-brand-500 to-brand-800 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Create Account</h1>
            <p class="text-slate-500 text-sm mt-1">Join <?= APP_NAME ?> and start shopping</p>
        </div>
        <?php if ($errors): ?>
            <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 mb-5 text-sm text-rose-700">
                <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4" data-loading>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">First Name *</label>
                    <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" class="form-input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email Address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Mobile Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Password * (min 6 chars)</label>
                <input type="password" name="password" required class="form-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Confirm Password *</label>
                <input type="password" name="password2" required class="form-input">
            </div>
            <button type="submit" class="w-full btn-primary text-white py-3.5 rounded-xl font-bold text-base shadow-lg mt-2">
                Create Account
            </button>
        </form>
        <p class="text-center text-sm text-slate-500 mt-6">
            Already have an account? <a href="<?= url('login.php') ?>" class="text-brand-600 font-semibold hover:underline">Login</a>
        </p>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
