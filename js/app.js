'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ── Mobile sidebar toggle ──
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
        if (overlay) overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // ── Mobile nav menu ──
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
        });
    }

    // ── Password toggle ──
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            if (!input) return;
            const isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            btn.innerHTML = isText ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
        });
    });

    // ── Role selector in register ──
    const roleOptions = document.querySelectorAll('.role-option');
    const roleInput = document.getElementById('roleInput');
    const roleFields = document.querySelectorAll('.role-fields');

    roleOptions.forEach(option => {
        option.addEventListener('click', () => {
            roleOptions.forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            const role = option.dataset.role;
            if (roleInput) roleInput.value = role;
            roleFields.forEach(f => {
                f.style.display = f.dataset.role === role ? 'block' : 'none';
            });
        });
    });

    // ── FAQ accordion ──
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const answer = q.nextElementSibling;
            const isOpen = answer && answer.classList.contains('open');
            document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
            document.querySelectorAll('.faq-question i').forEach(i => i.className = 'fa-solid fa-chevron-down');
            if (!isOpen && answer) {
                answer.classList.add('open');
                const icon = q.querySelector('i');
                if (icon) icon.className = 'fa-solid fa-chevron-up';
            }
        });
    });

    // ── Auto-dismiss alerts ──
    document.querySelectorAll('.alert[data-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .4s';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });

    // ── Stat counter animation ──
    const statNumbers = document.querySelectorAll('.stat-counter');
    const observerOptions = { threshold: 0.5 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.target || '0', 10);
                let current = 0;
                const step = Math.ceil(target / 60);
                const timer = setInterval(() => {
                    current = Math.min(current + step, target);
                    el.textContent = current.toLocaleString() + (el.dataset.suffix || '');
                    if (current >= target) clearInterval(timer);
                }, 30);
                observer.unobserve(el);
            }
        });
    }, observerOptions);
    statNumbers.forEach(el => observer.observe(el));

    // ── Toast notifications ──
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toastContainer') || (() => {
            const c = document.createElement('div');
            c.id = 'toastContainer';
            c.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
            document.body.appendChild(c);
            return c;
        })();
        const toast = document.createElement('div');
        const bg = type === 'success' ? '#15803d' : type === 'error' ? '#b22234' : '#1d4ed8';
        toast.style.cssText = `background:${bg};color:#fff;padding:.85rem 1.4rem;border-radius:14px;font-weight:600;box-shadow:0 8px 20px rgba(0,0,0,.2);animation:fadeInUp .3s ease;max-width:340px;font-family:Inter,sans-serif;font-size:.92rem`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 4000);
    };

    // ── Confirm delete / cancel ──
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', e => {
            if (!confirm(btn.dataset.confirm)) e.preventDefault();
        });
    });

    // ── Active nav link ──
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-links a, .sidebar-nav a').forEach(link => {
        const linkPath = link.getAttribute('href')?.split('/').pop();
        if (linkPath && linkPath === currentPath) link.classList.add('active');
    });

    // ── Inventory inline edit ──
    document.querySelectorAll('.inv-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.inv-card');
            const display = card?.querySelector('.inv-units-display');
            const form = card?.querySelector('.inv-edit-form');
            if (display && form) {
                display.style.display = 'none';
                form.style.display = 'block';
            }
        });
    });
    document.querySelectorAll('.inv-cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.inv-card');
            const display = card?.querySelector('.inv-units-display');
            const form = card?.querySelector('.inv-edit-form');
            if (display && form) {
                display.style.display = 'block';
                form.style.display = 'none';
            }
        });
    });
});
