<?php
// Teste de conexão rápido
require_once 'backend/config/conexao.php';
require_once 'backend/config/config.php';

try {
    $conexao_db = Conexao::getInstance()->getConexao();

    // Teste 1: Conexão
    echo "<i class="fa-solid fa-circle-check"></i> Conexão com BD: OK\n";

    // Teste 2: Contar clientes
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM clientes');
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    echo "<i class="fa-solid fa-circle-check"></i> Clientes cadastrados: " . $resultado['total'] . "\n";

    // Teste 3: Contar especialidades
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM especialidades');
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    echo "<i class="fa-solid fa-circle-check"></i> Especialidades: " . $resultado['total'] . "\n";

    // Teste 4: Contar exames
    $stmt = $conexao_db->prepare('SELECT COUNT(*) as total FROM exames');
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    echo "<i class="fa-solid fa-circle-check"></i> Exames: " . $resultado['total'] . "\n";

    echo "\n<i class="fa-solid fa-circle-check"></i> TUDO FUNCIONANDO PERFEITAMENTE!\n";

} catch (Exception $e) {
    echo "<i class="fa-solid fa-circle-xmark"></i> ERRO: " . $e->getMessage() . "\n";
}
?>
