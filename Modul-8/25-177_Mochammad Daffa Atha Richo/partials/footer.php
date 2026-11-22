</main>

<footer class="site-footer no-print">
    <div class="footer-inner">
        <span>Sistem Penjualan &mdash; Modul 8 PAW (Session &amp; Security)</span>
    </div>
</footer>

<script>
(function () {
    'use strict';

    var dropdowns = document.querySelectorAll('[data-dropdown]');

    function closeAll(except) {
        dropdowns.forEach(function (dd) {
            if (dd !== except) {
                dd.classList.remove('is-open');
                var btn = dd.querySelector('.dropdown-toggle');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }

    dropdowns.forEach(function (dd) {
        var btn = dd.querySelector('.dropdown-toggle');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var isOpen = dd.classList.contains('is-open');
            closeAll(dd);
            if (isOpen) {
                dd.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            } else {
                dd.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });

        btn.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                closeAll(dd);
                dd.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                var firstItem = dd.querySelector('.dropdown-item');
                if (firstItem) {
                    firstItem.focus();
                }
            }
        });
    });

    document.addEventListener('click', function (ev) {
        if (!ev.target.closest('[data-dropdown]')) {
            closeAll(null);
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') {
            var openDd = document.querySelector('[data-dropdown].is-open');
            closeAll(null);
            if (openDd) {
                var btn = openDd.querySelector('.dropdown-toggle');
                if (btn) {
                    btn.focus();
                }
            }
        }
    });
})();
</script>
</body>
</html>
