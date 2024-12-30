<?php

namespace pck\pdo_db_as_pear;

define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);
define('DB_FETCHMODE_DEFAULT', 0);
define('DB_FETCHMODE_ORDERED', 1);
define('DB_FETCHMODE_ASSOC', 2);
define('DB_FETCHMODE_OBJECT', 3);
define('DB_OK', 1);

class pdo_db_as_pear extends \PDO
{
    public function __construct(string $dsn, string $username = '', string $password = '', array $options = array())
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('pdo_statement_as_pear', array($this)));
        return $this;
    }

    public function getOne($query, $params = array())
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        if (!$stmt) {
            return $stmt;
        }
        $result = $stmt->fetch(\PDO::FETCH_BOTH);
        if (is_array($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function getAssoc($query, $force_array = false, $params = array(), $fetch_mode = \PDO::FETCH_BOTH)
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        if (!$stmt) {
            return $stmt;
        }
        $results = $stmt->fetchAll(\PDO::FETCH_BOTH);
        $column_count = $stmt->columnCount();

        $assoc_results = array();
        foreach ($results as $result) {
            if ($column_count == 2) {
                if ($force_array === true) {
                    $assoc_results[$result[0]] = array_slice($result, 1);
                } else {
                    $assoc_results[$result[0]] = $result[1];
                }
            } else {
                $assoc_results[$result[0]] = array_slice($result, 1);
            }
        }
        return $assoc_results;
    }

    public function getCol($query, $column_number = 0, $params = array())
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        if (!$stmt) {
            return $stmt;
        }
        return $stmt->fetchAll(\PDO::FETCH_COLUMN, $column_number);
    }

    public function getAll($query, $params = array())
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        if (!$stmt) {
            return $stmt;
        }
        return $stmt->fetchAll();
    }

    public function getRow($query, $params = array())
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        if (!$stmt) {
            return $stmt;
        }
        return $stmt->fetch();
    }

    public function run_query($query, $params = array())
    {
        return $this->runQuery($query, $params);
    }

    public function runQuery($query, $params = array())
    {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function isError($result)
    {
        if (is_a($result, "pdo_statement_as_pear") == true) {
            if (isset($result->errorInfo()[0])) {
                if ($result->errorInfo()[0] != '00000') {
                    return true;
                }
            }
        }
        return false;
    }

    public function getMessage()
    {
        if (isset($this->errorInfo()[2])) {
            return $this->errorInfo()[2];
        } else {
            return '';
        }
    }

    public function autoExecute($table_name, $table_values, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $table_fields = array_keys($table_values);
        $first = true;
        switch ($mode) {
            case DB_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                $sql = "INSERT INTO $table_name ($names) VALUES ($values)";
                break;
            case DB_AUTOQUERY_UPDATE:
                $set = '';
                foreach ($table_fields as $value) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table_name SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                break;
            default:
                throw new \Exception("Invalid query mode passed to autoExecute");
                break;
        }
        $field_values = array_values($table_values);
        $stmt = $this->prepare($sql);
        $stmt->execute($field_values);
        return true;
    }

    public function autoCommit($commit = true)
    {
        if ($commit === false) {
            $this->beginTransaction();
        }
    }

    public function execute($stmt, $data = array())
    {
        return $stmt->execute($data);
    }

    public function freePrepared($stmt)
    {
        // do nothing (simply a wrapper for PEAR DB)
    }
}

class pdo_statement_as_pear extends \PDOStatement
{
    protected function __construct() {}

    public function getCode()
    {
        if (isset($this->errorInfo()[0])) {
            return $this->errorInfo()[0];
        } else {
            return '';
        }
    }

    public function getMessage()
    {
        if (isset($this->errorInfo()[2])) {
            return $this->errorInfo()[2];
        } else {
            return '';
        }
    }

    public function isError()
    {
        if (isset($this->errorInfo()[0])) {
            if ($this->errorInfo()[0] != '00000') {
                return true;
            }
        }
        return false;
    }

    public function numRows()
    {
        return $this->rowCount();
    }

    public function fetchRow($fetch_mode = \PDO::FETCH_BOTH, $offset = null)
    {
        $row = $this->fetch($fetch_mode, \PDO::FETCH_ORI_NEXT, $offset);
        return $row;
    }
}
