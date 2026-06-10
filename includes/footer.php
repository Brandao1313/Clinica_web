<?php
// ====================================================
// ARQUIVO: includes/footer.php
// Descrição: Rodapé compartilhado
// ====================================================

if (!isset($base_url)) {
    $base_url = '';
}
?>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Clínica Saúde &amp; Bem-Estar. Todos os direitos reservados.</p>
        <p><a href="<?php echo $base_url; ?>sobre.php">Sobre nós</a> · <a href="<?php echo $base_url; ?>especialidades.php">Especialidades</a> · <a href="<?php echo $base_url; ?>exames.php">Exames</a></p>
    </footer>
</body>
</html>
