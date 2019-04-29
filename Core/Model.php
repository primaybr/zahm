<?php

namespace Core;

use Core\Database as Database;

class Model
{
    public $db;
    protected $table;
    protected $dbconfig;
    protected $fields = '*';
    protected $primary_key;
    protected $return_type = 'array';

    public function __construct($table, $database)
    {
        $this->dbconfig = $this->setDatabase($database);
        $this->db = new Database\Connect(
            $this->dbconfig->host,
            $this->dbconfig->dbname,
            $this->dbconfig->user,
            $this->dbconfig->password
        );

        $this->table = $this->dbconfig->prefix.$table;
        $this->builder = new Database\Builder($this->table);
        $this->primary_key = $this->_setPrimaryKey();
    }
	
	/**
     * The "setDatabase" function.
     *
     * select database configuration
     *
     * @param string $database Database name
     *
     * @return config
     */
    public function setDatabase($database)
    {
        $config = $GLOBALS['config']->database;

        return $config->{$database};
    }

    /**
     * The "setFields" function.
     *
     * Allows fields to be set before executing get() or find().
     *
     * @param array|string $fields Field name, or an array of field/value pairs
     *
     * @return Model
     */
    public function setFields($fields)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as $v) {
            if ('*' == $this->fields) {
                $this->fields = "`${v}`,";
            } else {
                $this->fields .= "`${v}`,";
            }
        }

        $this->fields = rtrim($this->fields, ',');

        return $this;
    }
	
	public function where($key = '', $value = '', $type = '=')
	{
		$this->builder->where($key,$value,$type);
		
		return $this;
	}

    public function get($limit = 20, $offset = 0)
    {
        $query = $this->builder->select($this->fields)
            ->offset($offset)
            ->limit($limit)
            ->compile()
        ;
		debug($query);
        $this->db->query($query);
        $this->db->arrayBind($this->builder->binds);

        return $this->db->result($this->return_type);
    }

    public function asArray()
    {
        $this->return_type = 'array';

        return $this;
    }

    public function asObject()
    {
        $this->return_type = 'object';

        return $this;
    }

    public function save($data, $update = [])
    {
        if ($update) {
            $query = $this->builder->update($data, $update)->compile();
        } else {
            $query = $this->builder->insert($data)->compile();
        }

        $this->db->query($query);
        $this->db->arrayBind($this->builder->binds);

        if ($this->db->execute()) {
            if ($update) {
                return $this->db->rowCount();
            }

            return $this->db->lastInsertId();
        }

        return false;
    }

    public function delete($data = [])
    {
        if ($data) {
            $query = $this->builder->delete($data)->compile();
            $this->db->query($query);
            $this->db->arrayBind($this->builder->binds);

            if ($this->db->execute()) {
                return $this->db->rowCount();
            }

            return false;
        }
    }

    public function totalRows()
    {
        $query = $this->builder->select('')->count()->compile();
        $this->db->query($query);

        if ($this->db->execute()) {
            return $this->db->rowCount();
        }

        return false;
    }

    public function getLastId($id = 'id')
    {
        $query = $this->builder->select('')->max($id)->compile();
        $this->db->query($query);

        $result = $this->db->single();

        if ($result) {
            return $result['id'];
        }

        return false;
    }

    public function find($id = null)
    {
        if (is_array($id)) {
            $query = $this->builder->select($this->fields)
                ->where_in([$this->primary_key => $id])
                ->compile()
            ;
        } elseif (is_numeric($id) || is_string($id)) {
            $query = $this->builder->select($this->fields)
                ->where($this->primary_key, $id)
                ->compile()
            ;
        } else {
            return $this->get($id);
        }

        $this->db->query($query);
        $this->db->arrayBind($this->builder->binds);

        return $this->db->result($this->return_type);
    }

    public function getFields()
    {
        if (!empty($this->table)) {
            $this->db->query("DESCRIBE {$this->table}");
            $result = $this->db->result($this->return_type);

            if ($result) {
                return $result;
            }

            return false;
        }

        return false;
    }

    /**
     * The "builder" function.
     *
     * get builder instance.
     *
     * @return Database\Builder
     */
    public function builder()
    {
        if ($this->builder instanceof Database\Builder) {
            return $this->builder;
        }

        return Database\Builder($this->table);
    }

    private function _setPrimaryKey()
    {
        $fields = $this->getFields();

        if ($fields) {
            foreach ($fields as $v) {
                if ('PRI' == $v['Key']) {
                    return $v['Field'];
                }
            }
        }
    }
}
