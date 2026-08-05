<div class="login-container">
    <div class="login-card">
        <h1>Tempest</h1>
        <p>Faça login com suas credenciais de rede</p>

        <form method="POST" action="/login" class="login-form">
            <label for="username">Usuário Dominio</label>
            <input type="text" id="username" name="username" placeholder="Seu usuário" required autofocus>

            <label for="password">Senha</label>
            <input type="password" id="password" name="password" placeholder="Sua senha" required>

            <button type="submit"><i data-lucide="log-in"></i> Entrar</button>
        </form>
    </div>
</div>