<?php
$filtroCpf = $filtroCpf ?? '';
$etapa = $filtroCpf !== '' ? 2 : 1;
?>
<div class="login-container">
    <div class="login-card">
        <h1>Redefinir Senha</h1>
        <?php if ($etapa === 1): ?>
            <p>Informe seu CPF para redefinir a senha</p>

            <form method="POST" action="/login/redefinir" class="login-form">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($filtroCpf) ?>" placeholder="Apenas números" required autofocus>

                <button type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Verificar</button>
                <a href="/login" class="btn-clear">Voltar ao login</a>
            </form>
        <?php else: ?>
            <p>Defina sua nova senha:</p>

            <form method="POST" action="/login/redefinir" class="login-form">
                <input type="hidden" name="cpf" value="<?= htmlspecialchars($filtroCpf) ?>">

                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required autofocus>

                <label for="confirmar">Confirmar nova senha</label>
                <input type="password" id="confirmar" name="confirmar" placeholder="Repita a senha" required>

                <button type="submit"><i class="fa-solid fa-key"></i> Redefinir senha</button>
                <a href="/login" class="btn-clear">Voltar ao login</a>
            </form>
        <?php endif; ?>
    </div>
</div>