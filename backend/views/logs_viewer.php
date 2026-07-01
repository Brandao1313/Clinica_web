<?php
defined('PAINEL_ADMIN_LOADED') or die(header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/backend/views/painel_admin.php'));
// ====================================================
// ARQUIVO: backend/views/logs_viewer.php
// Descrição: Visualizador dos logs de atividade e
//            segurança — incluído por painel_admin.php
// ====================================================

$aba       = in_array($_GET['aba'] ?? '', ['seguranca']) ? 'seguranca' : 'atividades';
$arquivo   = $aba === 'seguranca'
    ? BASE_PATH . '/logs/security.log'
    : BASE_PATH . '/logs/atividades.log';

// Últimas 200 linhas do arquivo selecionado
$linhas = [];
if (is_file($arquivo)) {
    $todas  = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linhas = array_slice(array_reverse($todas), 0, 200);
}

// Parser: "[2024-01-15 10:32:00] ACAO: LOGIN | USER_ID: 1 | DETALHES: ..."
function parsear_linha_log($linha) {
    preg_match('/^\[(.+?)\]\s+(.+)$/', $linha, $m);
    return [
        'data'    => $m[1] ?? '',
        'corpo'   => $m[2] ?? $linha,
        'bruto'   => $linha,
    ];
}
?>

<h3><i class="fa-solid fa-file-lines"></i> Logs do Sistema</h3>

<!-- Abas -->
<div style="display:flex;gap:4px;margin-bottom:16px;">
    <a href="?acao=logs&aba=atividades"
       class="btn-action <?php echo $aba === 'atividades' ? '' : 'secondary'; ?>"
       style="border-radius:6px 6px 0 0;">
        <i class="fa-solid fa-list-check"></i> Atividades
    </a>
    <a href="?acao=logs&aba=seguranca"
       class="btn-action <?php echo $aba === 'seguranca' ? '' : 'secondary'; ?>"
       style="border-radius:6px 6px 0 0;">
        <i class="fa-solid fa-shield-halved"></i> Segurança
    </a>
    <span style="margin-left:auto;font-size:0.85rem;color:var(--cor-texto-claro);align-self:center;">
        Últimas <?php echo count($linhas); ?> entradas
        <?php if (is_file($arquivo)): ?>
            · <?php echo number_format(filesize($arquivo) / 1024, 1); ?> KB
        <?php endif; ?>
    </span>
</div>

<?php if (empty($linhas)): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info"></i>
        Nenhum registro encontrado em
        <code><?php echo htmlspecialchars(basename($arquivo)); ?></code>.
    </div>
<?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table" style="font-size:0.82rem;font-family:monospace;">
            <thead>
                <tr>
                    <th style="width:165px;">Data/Hora</th>
                    <th>Evento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($linhas as $linha): ?>
                    <?php $p = parsear_linha_log($linha); ?>
                    <tr>
                        <td style="white-space:nowrap;color:var(--cor-texto-claro);">
                            <?php echo htmlspecialchars($p['data']); ?>
                        </td>
                        <td style="word-break:break-all;">
                            <?php
                                // Destaque visual: ERRO/FAIL = vermelho, SUCESSO/LOGIN = verde
                                $corpo = htmlspecialchars($p['corpo']);
                                if (preg_match('/falha|fail|erro|block|FALHA|FAIL|ERRO|BLOCK/i', $corpo)) {
                                    echo '<span style="color:var(--cor-perigo);">' . $corpo . '</span>';
                                } elseif (preg_match('/LOGIN\b|CADASTRO|CRIADO|ATUALIZ|SUCESSO/i', $corpo)) {
                                    echo '<span style="color:var(--cor-destaque-escura);">' . $corpo . '</span>';
                                } else {
                                    echo $corpo;
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
