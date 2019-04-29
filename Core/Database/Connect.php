<?php

namespace Core\Database;

use PDO;

class Connect
{
    private $handler;
    private $error;
    private $statement;

    public function __construct($host, $dbname, $user, $password, $options = [])
    {
        if (empty($options)) {
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
        }

        try {
            $this->handler = new PDO("mysql:host=${host};dbname=${dbname}", $user, $password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function __destruct()
    {
        //disconnect db conn
        $this->handler = null;
    }

    public function query($query)
    {
        $this->statement = $this->handler->prepare($query);
    }

    public function bind($param, $value, $type = null)
    {
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
        $this->statement->bindValue($param, $value, $type);
    }

    public function arrayBind($data = [])
    {
        foreach ($data as $k => $v) {
            $this->bind(":${k}", $v);
        }
    }

    public function execute($bind = [])
    {
        if ($bind) {
            return $this->statement->execute($bind);
        }

        return $this->statement->execute();
    }

    public function result($type = '')
    {
        switch (true) {
            case 'object' == $type:
                $type = PDO::FETCH_OBJ;

                break;
            case 'column' == $type:
                $type = PDO::FETCH_COLUMN;

                break;
            default:
                $type = PDO::FETCH_ASSOC;
        }

        $this->execute();

        return $this->statement->fetchAll($type);
    }

    public function single()
    {
        $this->execute();

        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function rowCount()
    {
        return $this->statement->rowCount();
    }

    public function totalRows()
    {
        $this->execute();

        return $this->statement->rowCount();
    }

    public function lastInsertId()
    {
        return $this->handler->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->handler->beginTransaction();
    }

    public function endTransaction()
    {
        return $this->handler->commit();
    }

    public function cancelTransaction()
    {
        return $this->handler->rollBack();
    }

    public function debug()
    {
        return $this->statement->debugDumpParams();
    }
}
