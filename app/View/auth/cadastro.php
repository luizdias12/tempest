<?php
$nomeEncontrado = $nomeEncontrado ?? '';
$filtroCpf = $filtroCpf ?? '';
$etapa = $nomeEncontrado !== '' ? 2 : 1;
?>
<div class="login-container">
    <div class="login-card">
        <h1>Cadastro</h1>
        <?php if ($etapa === 1): ?>
            <p>Informe seu CPF para verificar seu acesso</p>

            <form method="POST" action="/login/cadastro" class="login-form">
                <label for="cpf">CPF</label>
                <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($filtroCpf) ?>" placeholder="Apenas números" required autofocus>

                <button type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Verificar</button>
                <a href="/login" class="btn-clear">Voltar ao login</a>
            </form>
        <?php else: ?>
            <p>Olá, <strong><?= htmlspecialchars(initcap($nomeEncontrado)) ?></strong>! Defina seus dados de acesso:</p>

            <form method="POST" action="/login/cadastro" class="login-form">
                <input type="hidden" name="cpf" value="<?= htmlspecialchars($filtroCpf) ?>">

                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" placeholder="Nome de usuário" required autofocus>

                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="E-mail corporativo" required>

                <label for="ramal">Ramal <span class="login-opcional">(opcional)</span></label>
                <input type="text" id="ramal" name="ramal" placeholder="Ex.: 7300" maxlength="5">

                <label for="corporativo">Corporativo <span class="login-opcional">(opcional)</span></label>
                <input type="text" id="corporativo" name="corporativo" placeholder="Somente números">

                <label for="id_setor">Setor</label>
                <select name="id_setor" id="id_setor">
                    <option value="">Selecione o setor</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= htmlspecialchars((string) $setor['id']) ?>"><?= htmlspecialchars($setor['setor']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>

                <label for="confirmar">Confirmar senha</label>
                <input type="password" id="confirmar" name="confirmar" placeholder="Repita a senha" required>

                <button type="submit"><i class="fa-solid fa-user-plus"></i> Criar conta</button>
                <a href="/login" class="btn-clear">Voltar ao login</a>
            </form>
        <?php endif; ?>
    </div>
</div>