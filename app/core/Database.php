<?php
/**
 * Database Wrapper (PDO Singleton / Query Builder Helper)
 */
class Database {
    private static $instance = null;
    private $dbh;
    private $stmt;
    private $error;

    public function __construct() {
        // Data Source Name
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->dbh = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Coba fallback ke UNIX socket jika koneksi TCP belum aktif
            $possibleSockets = [
                '/tmp/mariadb_run/mysql.sock',
                '/opt/lampp/var/mysql/mysql.sock',
                '/var/run/mysqld/mysqld.sock',
                '/tmp/mysql.sock'
            ];

            foreach ($possibleSockets as $sock) {
                if (file_exists($sock)) {
                    try {
                        $dsnSocket = 'mysql:unix_socket=' . $sock . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                        $this->dbh = new PDO($dsnSocket, DB_USER, DB_PASS, $options);
                        return;
                    } catch (PDOException $ex) {}
                }
            }

            $this->error = $e->getMessage();
            throw new Exception("Database Connection Error: " . $this->error);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Prepare query statement
    public function query($sql) {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }

    // Bind parameters
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    // Execute query statement
    public function execute($params = []) {
        if (!empty($params)) {
            return $this->stmt->execute($params);
        }
        return $this->stmt->execute();
    }

    // Get multiple rows
    public function resultSet($params = []) {
        $this->execute($params);
        return $this->stmt->fetchAll();
    }

    // Get single row
    public function single($params = []) {
        $this->execute($params);
        $result = $this->stmt->fetch();
        return $result ? $result : null;
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    // Transaction helpers
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function commit() {
        return $this->dbh->commit();
    }

    public function rollBack() {
        return $this->dbh->rollBack();
    }

    // Direct PDO handle getter
    public function getPdo() {
        return $this->dbh;
    }
}
