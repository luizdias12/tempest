<div class="login-container">
    <div class="login-card">
        <h1>Intranet</h1>
        <p>Faça login com suas credenciais de rede</p>

        <form method="POST" action="/login" class="login-form">
            <label for="username">Usuário</label>
            <input type="text" id="username" name="username" placeholder="Seu usuário" required autofocus>

            <label for="password">Senha</label>
            <div class="password-field">
                <input type="password" id="password" name="password" placeholder="Sua senha" required>
                <button type="button" id="toggle-password" class="password-toggle" aria-label="Mostrar senha">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <button type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar</button>
        </form>

        <div class="login-links">
            <a href="/login/cadastro"><i class="fa-solid fa-user-plus"></i> Não possui acesso? Cadastre-se</a>
            <a href="/login/redefinir"><i class="fa-solid fa-key"></i> Esqueceu a senha?</a>
        </div>
    </div>
</div>

<script>
    const toggle = document.getElementById('toggle-password');
    const senha = document.getElementById('password');

    toggle.addEventListener('click', function() {
        const mostrar = senha.type === 'password';
        senha.type = mostrar ? 'text' : 'password';
        this.querySelector('i').className = mostrar ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        this.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });

    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;
        if (!form.classList.contains('login-form')) return;

        var action = form.getAttribute('action') || '';

        if (action.indexOf('/login') !== -1) {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span> Conectando...';
            }
            return;
        }

        e.preventDefault();
    });
</script>