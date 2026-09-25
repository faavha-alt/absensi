// Navigasi responsif: drawer sidebar untuk layar kecil + pintasan keyboard.
(function () {
    const close = () => document.body.classList.remove('nav-open');

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-nav-toggle]')) {
            document.body.classList.toggle('nav-open');
            return;
        }

        if (event.target.closest('[data-nav-close]')) {
            close();
            return;
        }

        // Tutup drawer saat memilih menu (layar kecil)
        if (window.innerWidth < 1024 && event.target.closest('.app-sidebar a')) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }

        // Pintasan: '/' fokus pencarian (bila ada), 'g' lalu 'd' -> dashboard
        if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            const search = document.querySelector('[data-global-search]');
            if (search) {
                event.preventDefault();
                search.focus();
            }
        }
    });
})();
