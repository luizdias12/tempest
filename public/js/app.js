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
                    var field = modal.querySelector('[data-field="' + attr.name.slice(5) + '"]');
                    if (field) field.textContent = attr.value;
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
            return;
        }

        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(m) {
                if (m.style.display === 'flex') m.style.display = 'none';
            });
        }
    });
});