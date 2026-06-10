<?php
// ====================================================
// ARQUIVO: backend/config/conexao.php
// Descrição: Gerencia conexão com banco de dados MySQL
// Padrão: Singleton - apenas uma instância de conexão
// ====================================================

require_once __DIR__ . '/config.php';

class Conexao {
    private static $instancia = null;
    private $conexao;

    // Construtor privado - impede instanciação direta
    private function __construct() {
        // Criar conexão usando mysqli
        $this->conexao = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        // Verificar erros de conexão
        if ($this->conexao->connect_error) {
            die('Erro ao conectar ao banco: ' . $this->conexao->connect_error);
        }

        // Configurar charset como UTF-8
        $this->conexao->set_charset('utf8mb4');
    }

    // Método estático para obter instância (Singleton)
    public static function getInstance() {
        if (self::$instancia === null) {
            self::$instancia = new Conexao();
        }
        return self::$instancia;
    }

    // Obter objeto de conexão
    public function getConexao() {
        return $this->conexao;
    }

    // Impedir clonagem
    private function __clone() {}

    // Impedir desserialização
    public function __wakeup() {}

    // Preparar statement (segurança contra SQL Injection)
    public function prepare($sql) {
        return $this->conexao->prepare($sql);
    }

    // Executar query simples (para SELECTs sem parâmetros)
    public function query($sql) {
        return $this->conexao->query($sql);
    }

    // Obter último ID inserido
    public function getLastId() {
        return $this->conexao->insert_id;
    }

    // Obter número de linhas afetadas
    public function getAffectedRows() {
        return $this->conexao->affected_rows;
    }

    // Fechar conexão
    public function close() {
        if ($this->conexao) {
            $this->conexao->close();
        }
    }

    // Obter erro da conexão
    public function getError() {
        return $this->conexao->error;
    }

    // Escapar string (proteção contra XSS em dados armazenados)
    public function escape($string) {
        return $this->conexao->real_escape_string($string);
    }
}

// Criar instância global de conexão
$db = Conexao::getInstance()->getConexao();

?>
