<?php
// ====================================================
// ARQUIVO: backend/views/medico_form.php
// Descrição: Formulário de cadastro/edição de médico
//            - incluído por painel_admin.php
// ====================================================

$id_medico = intval($_GET['id'] ?? 0);
$medico = null;

if ($id_medico > 0) {
    $stmt = $conexao_db->prepare('SELECT * FROM medicos WHERE id = ?');
    $stmt->bind_param('i', $id_medico);
    $stmt->execute();
    $medico = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$medico) {
        echo '<div class="alert alert-error">Médico não encontrado.</div>';
        return;
    }
}

// Reaproveitar dados submetidos em caso de erro de validação
$dados = $_SESSION['dados_medico'] ?? null;
unset($_SESSION['dados_medico']);

$valores = [
    'nome' => $dados['nome'] ?? $medico['nome'] ?? '',
    'crm' => $dados['crm'] ?? $medico['crm'] ?? '',
    'id_especialidade' => $dados['id_especialidade'] ?? $medico['id_especialidade'] ?? '',
    'email' => $dados['email'] ?? $medico['email'] ?? '',
    'telefone' => $dados['telefone'] ?? $medico['telefone'] ?? '',
    'valor_consulta' => $dados['valor_consulta'] ?? $medico['valor_consulta'] ?? '',
    'percentual_medico' => $dados['percentual_medico'] ?? $medico['percentual_medico'] ?? '70',
    'percentual_exame' => $dados['percentual_exame'] ?? $medico['percentual_exame'] ?? '0',
    'bio' => $dados['bio'] ?? $medico['bio'] ?? '',
    'foto' => $dados['foto'] ?? $medico['foto'] ?? '',
    'ativo' => $dados['ativo'] ?? ($medico['ativo'] ?? 1),
];

$stmt = $conexao_db->prepare('SELECT id, nome FROM especialidades WHERE ativo = 1 ORDER BY nome');
$stmt->execute();
$especialidades = $stmt->get_result()->fetch_all();
$stmt->close();
?>

<h3><?php echo $id_medico > 0 ? 'Editar Médico' : 'Novo Médico'; ?></h3>

<?php if (!empty($_SESSION['erros_medico'])): ?>
    <div class="alert alert-error">
        <?php foreach ($_SESSION['erros_medico'] as $erro): ?>
            <p><i class="fa-solid fa-circle-xmark"></i> <?php echo htmlspecialchars($erro); ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['erros_medico']); ?>
<?php endif; ?>

<form method="POST" action="../controllers/medico_controller.php" style="max-width: 600px;">
    <input type="hidden" name="acao" value="salvar_medico">
    <input type="hidden" name="csrf_token" value="<?php echo gerar_token_csrf(); ?>">
    <?php if ($id_medico > 0): ?>
        <input type="hidden" name="id_medico" value="<?php echo $id_medico; ?>">
    <?php endif; ?>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-stethoscope"></i> Dados do médico</div>

        <div style="margin-bottom: 15px;">
            <label for="nome"><strong>Nome completo *</strong></label>
            <input type="text" id="nome" name="nome" required minlength="3" maxlength="150" value="<?php echo htmlspecialchars($valores['nome']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="flex: 1; min-width: 180px;">
                <label for="crm"><strong>CRM *</strong></label>
                <input type="text" id="crm" name="crm" required maxlength="20" value="<?php echo htmlspecialchars($valores['crm']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label for="id_especialidade"><strong>Especialidade *</strong></label>
                <select id="id_especialidade" name="id_especialidade" required style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
                    <option value="">-- Selecione --</option>
                    <?php foreach ($especialidades as $esp): ?>
                        <option value="<?php echo $esp['id']; ?>" <?php echo (string) $valores['id_especialidade'] === (string) $esp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($esp['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="flex: 1; min-width: 180px;">
                <label for="email"><strong>Email</strong></label>
                <input type="email" id="email" name="email" maxlength="100" value="<?php echo htmlspecialchars($valores['email']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label for="telefone"><strong>Telefone</strong></label>
                <input type="text" id="telefone" name="telefone" maxlength="15" value="<?php echo htmlspecialchars($valores['telefone']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
            <div style="flex: 1; min-width: 150px;">
                <label for="valor_consulta"><strong>Valor da consulta (R$) *</strong></label>
                <input type="number" id="valor_consulta" name="valor_consulta" required min="0.01" step="0.01" value="<?php echo htmlspecialchars($valores['valor_consulta']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="percentual_medico"><strong>% Repasse ao médico *</strong></label>
                <input type="number" id="percentual_medico" name="percentual_medico" required min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($valores['percentual_medico']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label for="percentual_exame"><strong>% Repasse sobre exames</strong></label>
                <input type="number" id="percentual_exame" name="percentual_exame" min="0" max="100" step="0.01" value="<?php echo htmlspecialchars($valores['percentual_exame']); ?>" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="foto"><strong>URL da foto</strong></label>
            <input type="text" id="foto" name="foto" maxlength="255" value="<?php echo htmlspecialchars($valores['foto']); ?>" placeholder="imagens/medicos/exemplo.jpg" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="bio"><strong>Biografia / Descrição</strong></label>
            <textarea id="bio" name="bio" style="width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 5px; min-height: 100px;"><?php echo htmlspecialchars($valores['bio']); ?></textarea>
        </div>

        <div style="margin-bottom: 0;">
            <label>
                <input type="checkbox" name="ativo" value="1" <?php echo $valores['ativo'] ? 'checked' : ''; ?>>
                <strong>Médico ativo</strong>
            </label>
        </div>
    </div>

    <div class="form-grupo">
        <div class="form-grupo-titulo"><i class="fa-solid fa-lock"></i> Acesso ao sistema</div>

        <?php if ($id_medico > 0): ?>
            <p style="font-size:0.85rem;color:#666;margin-bottom:10px;">Deixe em branco para manter a senha atual.</p>
        <?php else: ?>
            <p style="font-size:0.85rem;color:#666;margin-bottom:10px;">O médico usará o e-mail e essa senha para fazer login.</p>
        <?php endif; ?>

        <div style="display:flex;gap:15px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label for="senha"><strong>Senha <?php echo $id_medico === 0 ? '*' : '(nova)'; ?></strong></label>
                <input type="password" id="senha" name="senha" <?php echo $id_medico === 0 ? 'required' : ''; ?> minlength="6" autocomplete="new-password"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
            <div style="flex:1;min-width:200px;">
                <label for="confirmacao_senha"><strong>Confirmar senha <?php echo $id_medico === 0 ? '*' : ''; ?></strong></label>
                <input type="password" id="confirmacao_senha" name="confirmacao_senha" <?php echo $id_medico === 0 ? 'required' : ''; ?> minlength="6" autocomplete="new-password"
                       style="width:100%;padding:10px;border:1px solid #e0e0e0;border-radius:5px;">
            </div>
        </div>
    </div>

    <button type="submit" class="btn-action">Salvar</button>
    <a href="?acao=medicos" class="btn-action secondary">Voltar</a>
</form>
