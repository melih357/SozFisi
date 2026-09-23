(() => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    navToggle?.addEventListener('click', () => nav?.classList.toggle('open'));

    document.querySelectorAll('[data-alert-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('.alert')?.remove());
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy') || '';
            try {
                await navigator.clipboard.writeText(value);
                const original = button.textContent;
                button.textContent = 'Kopyalandı ✓';
                setTimeout(() => button.textContent = original, 1800);
            } catch {
                window.prompt('Bağlantıyı kopyalayın:', value);
            }
        });
    });

    document.querySelectorAll('[data-toggle-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-toggle-target'));
            target?.classList.toggle('open');
            target?.querySelector('textarea, input, select')?.focus();
        });
    });

    const filterButtons = document.querySelectorAll('[data-filter]');
    const rows = document.querySelectorAll('[data-agreement-list] .agreement-row');
    const search = document.querySelector('[data-agreement-search]');
    let activeFilter = 'all';

    const applyAgreementFilters = () => {
        const term = (search?.value || '').trim().toLocaleLowerCase('tr-TR');
        rows.forEach((row) => {
            const status = row.getAttribute('data-status') || '';
            const haystack = row.getAttribute('data-search') || '';
            const filterMatches = activeFilter === 'all' ||
                (activeFilter === 'active' && ['approved', 'active', 'completion_pending'].includes(status)) ||
                (activeFilter === 'completed' && status === 'completed') ||
                status === activeFilter;
            row.hidden = !(filterMatches && haystack.includes(term));
        });
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.getAttribute('data-filter') || 'all';
            filterButtons.forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            applyAgreementFilters();
        });
    });
    search?.addEventListener('input', applyAgreementFilters);

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('./frontend/service-worker.js').catch(() => {});
        });
    }
})();
