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

    // Fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    });

    // Modal
    var modal = document.getElementById('modal');
    if (modal) {
        var modalText = document.getElementById('modal-text');
        var closeBtn = document.querySelector('.close');

        document.querySelectorAll('.clickable').forEach(function(cell) {
            cell.addEventListener('click', function() {
                modalText.innerText = this.getAttribute('data-full');
                modal.style.display = 'flex';
            });
        });

        if (closeBtn) {
            closeBtn.onclick = function() { modal.style.display = 'none'; };
        }

        window.onclick = function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
});