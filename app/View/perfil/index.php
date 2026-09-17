<?php

use App\Service\AuthService;

$perfil = $perfil ?? [];
$setores = $setores ?? [];
$nome = AuthService::getUser()['name'] ?? ($perfil['usuario'] ?? '');
$cpfMasc = maskCpf($perfil['cpf'] ?? '');
?>
<div class="header-bar">
    <h2 class="header_title">Meu Perfil</h2>
</div>

<div class="perfil-grid">
    <div class="perfil-card">
        <div class="perfil-card-header">
            <i class="fa-solid fa-user"></i>
            <span>Dados de acesso</span>
        </div>
        <dl class="perfil-list">
            <div class="perfil-list-item">
                <dt>Nome</dt>
                <dd><?= htmlspecialchars(initcap($nome)) ?></dd>
            </div>
            <div class="perfil-list-item">
                <dt>Usuário</dt>
                <dd><?= htmlspecialchars($perfil['usuario'] ?? '-') ?></dd>
            </div>
            <div class="perfil-list-item">
                <dt>E-mail</dt>
                <dd><?= htmlspecialchars($perfil['email'] ?? '-') ?></dd>
            </div>
            <div class="perfil-list-item">
                <dt>Ramal</dt>
                <dd><?= htmlspecialchars($perfil['ramal'] ?? '-') ?></dd>
            </div>
            <div class="perfil-list-item">
                <dt>Setor</dt>
                <dd><?= htmlspecialchars(initcap($perfil['setor'] ?? '')) ?: '-' ?></dd>
            </div>
            <?php if (isset($perfil['corporativo']) && $perfil['corporativo'] !== '' && $perfil['corporativo'] !== '00000000000'): ?>
                <div class="perfil-list-item">
                    <dt>Corporativo</dt>
                    <dd><?= htmlspecialchars($perfil['corporativo']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="perfil-card">
        <div class="perfil-card-header">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Editar dados</span>
        </div>
        <form method="POST" action="/perfil" class="perfil-form">
            <label for="perfil-email">E-mail</label>
            <input type="email" id="perfil-email" name="email" value="<?= htmlspecialchars($perfil['email'] ?? '') ?>" placeholder="E-mail corporativo">

            <label for="perfil-ramal">Ramal</label>
            <input type="text" id="perfil-ramal" name="ramal" value="<?= htmlspecialchars($perfil['ramal'] ?? '') ?>" maxlength="5" placeholder="Ex.: 7300">

            <label for="perfil-setor">Setor</label>
            <select name="id_setor" id="perfil-setor">
                <option value="">Sem setor</option>
                <?php foreach ($setores as $setor): ?>
                    <option value="<?= htmlspecialchars((string) $setor['id']) ?>" <?= (string) ($perfil['id_setor'] ?? '') === (string) $setor['id'] ? 'selected' : '' ?>><?= htmlspecialchars($setor['setor']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit"><i class="fa-solid fa-floppy-disk"></i> Salvar alterações</button>
        </form>
    </div>

    <div class="perfil-card">
        <div class="perfil-card-header">
            <i class="fa-solid fa-key"></i>
            <span>Alterar senha</span>
        </div>
        <form method="POST" action="/perfil/senha" class="perfil-form">
            <label for="senha-atual">Senha atual</label>
            <input type="password" id="senha-atual" name="senha_atual" required autocomplete="current-password">

            <label for="nova-senha">Nova senha</label>
            <input type="password" id="nova-senha" name="nova_senha" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">

            <label for="confirmar-senha">Confirmar nova senha</label>
            <input type="password" id="confirmar-senha" name="confirmar_senha" required autocomplete="new-password">

            <button type="submit"><i class="fa-solid fa-key"></i> Alterar senha</button>
        </form>
    </div>
</div>