<div class="login-container">
    <div class="login-card">
        <h1>Tempest</h1>
        <p>Faça login com suas credenciais de rede</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">Usuário ou senha inválidos</div>
        <?php endif; ?>

        <form method="POST" action="/login" class="login-form">
            <label for="username">Usuário Dominio</label>
            <input type="text" id="username" name="username" placeholder="Seu usuário" required autofocus>

            <label for="password">Senha</label>
            <input type="password" id="password" name="password" placeholder="Sua senha" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</div>