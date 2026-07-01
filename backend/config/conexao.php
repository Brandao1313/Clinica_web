<?php
// ====================================================
// ARQUIVO: backend/config/conexao.php
// Descrição: Gerencia conexão com banco de dados SQLite (via PDO)
// Padrão: Singleton - apenas uma instância de conexão
//
// Mantém uma API compatível com mysqli (prepare/bind_param/
// get_result/fetch_assoc/num_rows/error/close) para que o
// restante da aplicação não precise ser reescrito.
// ====================================================

require_once __DIR__ . '/config.php';

/**
 * Resultado de uma consulta - compatível com mysqli_result
 */
class ResultCompat {
    private $linhas;
    private $posicao = 0;
    public $num_rows;

    public function __construct(array $linhas) {
        $this->linhas = $linhas;
        $this->num_rows = count($linhas);
    }

    public function fetch_assoc() {
        if ($this->posicao >= $this->num_rows) {
            return null;
        }
        return $this->linhas[$this->posicao++];
    }

    public function fetch_all() {
        return $this->linhas;
    }
}

/**
 * Statement preparado - compatível com mysqli_stmt
 */
class StmtCompat {
    private $pdo;
    private $stmt;
    private $parametros = [];

    public function __construct(PDO $pdo, PDOStatement $stmt) {
        $this->pdo = $pdo;
        $this->stmt = $stmt;
    }

    /**
     * Compatível com mysqli_stmt::bind_param($tipos, ...$vars)
     * O tipo é ignorado (SQLite é dinamicamente tipado)
     */
    public function bind_param($tipos, &...$vars) {
        $this->parametros = &$vars;
        return true;
    }

    public function execute() {
        $valores = [];
        foreach ($this->parametros as $valor) {
            $valores[] = $valor;
        }
        return $this->stmt->execute($valores);
    }

    public function get_result() {
        return new ResultCompat($this->stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function close() {
        $this->stmt = null;
        return true;
    }

    public function __get($nome) {
        if ($nome === 'affected_rows') {
            return $this->stmt->rowCount();
        }
        if ($nome === 'error') {
            $info = $this->stmt->errorInfo();
            return $info[2] ?? '';
        }
        return null;
    }
}

class Conexao {
    private static $instancia = null;
    private $pdo;

    // Construtor privado - impede instanciação direta
    private function __construct() {
        $caminho_bd = DB_PATH;
        $diretorio = dirname($caminho_bd);

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }

        $banco_novo = !file_exists($caminho_bd);

        $this->pdo = new PDO('sqlite:' . $caminho_bd);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        if ($banco_novo) {
            $this->executarScript(__DIR__ . '/../../sql/schema.sqlite.sql');
        } else {
            $this->aplicarMigracoes();
        }
    }

    /**
     * Aplica migrações incrementais em bancos já existentes
     * (ex.: módulo de médicos/horários/financeiro)
     */
    private function aplicarMigracoes() {
        $tabela_existe = $this->pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='medicos'"
        )->fetch();

        if (!$tabela_existe) {
            $this->executarScript(__DIR__ . '/../../sql/migrate_medicos.sql');
        }
    }

    /**
     * Executa um arquivo .sql inteiro (criação de tabelas + seed)
     */
    private function executarScript($arquivo) {
        $sql = file_get_contents($arquivo);
        if ($sql === false) {
            die('Erro ao ler script de criação do banco: ' . $arquivo);
        }
        $this->pdo->exec($sql);
    }

    // Método estático para obter instância (Singleton)
    public static function getInstance() {
        if (self::$instancia === null) {
            self::$instancia = new Conexao();
        }
        return self::$instancia;
    }

    // Mantido por compatibilidade: retorna a própria instância,
    // que implementa prepare()/query()/error/close() como o mysqli fazia
    public function getConexao() {
        return $this;
    }

    // Impedir clonagem
    private function __clone() {}

    // Impedir desserialização
    public function __wakeup() {}

    /**
     * Preparar statement (compatível com mysqli)
     */
    public function prepare($sql) {
        $stmt = $this->pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        return new StmtCompat($this->pdo, $stmt);
    }

    /**
     * Executar query simples (para SELECTs sem parâmetros)
     */
    public function query($sql) {
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            return false;
        }
        return new ResultCompat($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Obter último ID inserido
    public function getLastId() {
        return $this->pdo->lastInsertId();
    }

    public function __get($nome) {
        if ($nome === 'insert_id') {
            return $this->pdo->lastInsertId();
        }
        if ($nome === 'error') {
            $info = $this->pdo->errorInfo();
            return $info[2] ?? '';
        }
        return null;
    }

    // Fechar conexão (no-op, mantido por compatibilidade)
    public function close() {
        return true;
    }

    // Escapar string - não utilizado com prepared statements,
    // mantido apenas por compatibilidade
    public function escape($string) {
        return addslashes($string);
    }
}

// Criar instância global de conexão
$db = Conexao::getInstance()->getConexao();

?>
