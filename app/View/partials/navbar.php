<nav class="site-nav">
    <div class="nav-links">
        <a href="/">Início</a>
        <a href="/funcionarios/index">Funcionários</a>
        <div class="dropdown">
            <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">TI</a>
            <div class="dropdown-menu">
                <a href="/ti/lista">Lista TI</a>
            </div>
        </div>
    </div>
    <div class="nav-user">
        <?php
        use App\Service\AuthService;
        $user = AuthService::getUser();
        if ($user): ?>
            <span class="nav-user-name"><?= htmlspecialchars($user['name']) ?></span>
            <a href="/logout" class="nav-logout">Sair</a>
        <?php else: ?>
            <a href="/login" class="nav-login">Entrar</a>
        <?php endif; ?>
    </div>
</nav>

<script>
function toggleDropdown(event) {
    event.preventDefault();
    var menu = event.currentTarget.nextElementSibling;
    var isVisible = menu.classList.contains('show');
    document.querySelectorAll('.dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
    if (!isVisible) {
        menu.classList.add('show');
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(m) { m.classList.remove('show'); });
    }
});
</script>