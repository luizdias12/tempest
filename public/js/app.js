document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const headerToggleBtn = document.getElementById('headerToggleBtn');

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (headerToggleBtn) {
        headerToggleBtn.addEventListener('click', toggleSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', toggleSidebar);
    }

    // Dropdown do sidebar
    document.querySelectorAll('.sidebar-dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var dropdown = this.closest('.sidebar-dropdown');
            dropdown.classList.toggle('open');
        });
    });

    // Link ativo do sidebar
    var currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav a').forEach(function(link) {
        var href = link.getAttribute('href');
        if (!href || href === '#') return;

        if (href === '/') {
            if (currentPath === '/') link.classList.add('active');
            return;
        }

        if (currentPath.indexOf(href) === 0) {
            link.classList.add('active');
            var dropdown = link.closest('.sidebar-dropdown');
            if (dropdown) dropdown.classList.add('open');
        }
    });

    // Toast
    document.querySelectorAll('.toast').forEach(function(toast) {
        var closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                dismissToast(toast);
            });
        }

        setTimeout(function() {
            dismissToast(toast);
        }, 4000);
    });

    function dismissToast(toast) {
        if (toast.classList.contains('toast-out')) return;
        toast.classList.add('toast-out');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    // Fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });

    // Modal (delegação de eventos)
    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('[data-modal-open]');
        if (trigger) {
            var modal = document.getElementById(trigger.getAttribute('data-modal-open'));
            if (!modal) return;
            var source = trigger.closest('[data-row]');
            if (source) {
                for (var i = 0; i < source.attributes.length; i++) {
                    var attr = source.attributes[i];
                    if (attr.name.indexOf('data-') !== 0 || attr.name === 'data-row') continue;
                    var fields = modal.querySelectorAll('[data-field="' + attr.name.slice(5) + '"]');
                    for (var j = 0; j < fields.length; j++) {
                        var field = fields[j];
                        if (field.tagName === 'SELECT' || field.tagName === 'INPUT') {
                            field.value = attr.value;
                            if (field.tagName === 'SELECT') field.dispatchEvent(new Event('change'));
                        } else {
                            field.textContent = attr.value;
                        }
                    }
                }
            }
            modal.style.display = 'flex';
            return;
        }

        var fill = e.target.closest('[data-modal-content]');
        if (fill) {
            var modal = document.getElementById(fill.getAttribute('data-modal-content'));
            if (modal) {
                var body = modal.querySelector('.modal-body');
                if (body && fill.getAttribute('data-full')) {
                    body.innerText = fill.getAttribute('data-full');
                }
                modal.style.display = 'flex';
            }
            return;
        }

        var closeBtn = e.target.closest('.modal .close');
        if (closeBtn) {
            var modal = closeBtn.closest('.modal');
            if (modal) modal.style.display = 'none';
            var url = new URL(window.location.href);
            if (url.searchParams.has('open')) {
                url.searchParams.delete('open');
                history.replaceState(null, '', url.toString());
            }
            return;
        }
    });

    // Carousel
    var carousel = document.getElementById('homeCarousel');
    if (carousel) {
        var slides = carousel.querySelectorAll('.carousel-slide');
        var indicators = carousel.querySelectorAll('.carousel-indicator');
        var current = 0;
        var intervalId = null;
        var INTERVAL = 150000;
        var PAUSED = false;

        function goToSlide(index) {
            if (!slides.length) return;
            current = (index + slides.length) % slides.length;
            slides.forEach(function(slide, i) {
                slide.classList.toggle('active', i === current);
            });
            indicators.forEach(function(ind, i) {
                ind.classList.toggle('active', i === current);
            });
        }

        function nextSlide() {
            goToSlide(current + 1);
        }

        function startAutoplay() {
            stopAutoplay();
            if (!PAUSED && slides.length > 1) {
                intervalId = setInterval(nextSlide, INTERVAL);
            }
        }

        function stopAutoplay() {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        }

        carousel.querySelector('.carousel-prev').addEventListener('click', function() {
            goToSlide(current - 1);
            startAutoplay();
        });

        carousel.querySelector('.carousel-next').addEventListener('click', function() {
            goToSlide(current + 1);
            startAutoplay();
        });

        indicators.forEach(function(ind) {
            ind.addEventListener('click', function() {
                goToSlide(parseInt(ind.getAttribute('data-slide')));
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', function() { PAUSED = true; startAutoplay(); });
        carousel.addEventListener('mouseleave', function() { PAUSED = false; startAutoplay(); });

        startAutoplay();
    }
});