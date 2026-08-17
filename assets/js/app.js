/* ShopVerse — App JavaScript
   Developed by MUHAMMAD OBAID */

document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu toggle
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            menuBtn.setAttribute('aria-expanded', mobileMenu.classList.contains('open'));
        });
    }

    // Close mobile menu on link click
    mobileMenu?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => mobileMenu.classList.remove('open'));
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
        });
    });

    // Auto-hide flash messages
    const flash = document.getElementById('flashMsg');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transition = 'opacity .5s, transform .5s';
            flash.style.transform = 'translateX(100%)';
            setTimeout(() => flash.remove(), 500);
        }, 3500);
    }

    // Add loading state to form submits
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.dataset.original = btn.innerHTML; btn.innerHTML = 'Processing…'; }
        });
    });
});

function adjustQty(btn, delta) {
    const inp = btn.parentElement.querySelector('input[type=number]');
    if (!inp) return;
    const newVal = parseInt(inp.value) + delta;
    if (newVal >= 0) { inp.value = newVal; inp.form.submit(); }
}

function changeQty(delta) {
    const inp = document.getElementById('qtyInput');
    if (!inp) return;
    const max = parseInt(inp.max);
    inp.value = Math.max(1, Math.min(parseInt(inp.value) + delta, max));
}
