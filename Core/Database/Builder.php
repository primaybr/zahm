<?php

namespace Core\Database;

class Builder
{
    public $binds;
    protected $q_select;
    protected $q_from;
    protected $q_where;
    protected $q_limit;
    protected $q_offset;
    protected $q_where_in;
    protected $q_join;
    protected $q_insert;
    protected $q_update;
    protected $q_delete;
    protected $q_order_by;
    protected $table;

    public function __construct($table)
    {
        $this->from($table);
        $this->table = $table;
    }

    public function select($fields = '*')
    {
        if (is_string($fields)) {
            $this->q_select = 'SELECT '.$fields;
        }

        return $this;
    }

    public function insert($data)
    {
        if ($data) {
            $field_data = '';
            $value_data = '';

            foreach ($data as $k => $v) {
                $field_data .= '`'.$k.'`'.',';
                $value_data .= ':'.$k.''.',';
                $this->binds[$k] = $v;
            }

            $field_data = rtrim($field_data, ',');
            $value_data = rtrim($value_data, ',');

            $this->q_insert = "INSERT INTO `{$this->table}` ({$field_data}) VALUES ({$value_data})";
        }

        return $this;
    }

    public function update($data)
    {
        if ($data) {
            $field_data = '';
            $bind = [];

            foreach ($data as $k => $v) {
                $field_data .= "`${k}`=:${k}".',';
                $this->binds[$k] = $v;
            }

            $field_data = rtrim($field_data, ',');

            $this->q_update = "UPDATE {$this->table} SET {$field_data}";
        }

        return $this;
    }

    public function delete($where = [])
    {
        if (is_array($where)) {
            foreach ($where as $key => $val) {
                $this->where($key, $val);
            }

            $this->q_delete = "DELETE FROM `{$this->table}`".$this->q_where;
        }

        return $this;
    }

    public function max($field, $alias = '')
    {
        if (!empty($alias)) {
            $alias = " AS ${alias}";
        } else {
            $alias = " AS ${field}";
        }

        $this->q_select .= " MAX(${field}) ${alias}";

        return $this;
    }

    public function min($field, $alias = '')
    {
        if (!empty($alias)) {
            $alias = " AS ${alias}";
        } else {
            $alias = " AS ${field}";
        }

        $this->q_select .= " MIN(${field}) ${alias}";

        return $this;
    }

    public function count($field = '*')
    {
        $this->q_select .= " COUNT(${field})";

        return $this;
    }

    public function from($table = '')
    {
        $from = ' FROM ';

        if (is_array($table)) {
            foreach ($table as $key => $value) {
                $table .= $value.',';
            }

            $result = rtrim($table, ',');
        } else {
            $result = $from.$table;
        }

        $this->q_from = $result;

        return $this;
    }

    public function where($key = '', $value = '', $type = '=')
    {
        $where = ' WHERE ';
        $type = strtoupper(trim($type));

        $query = "`${key}` ${type} :${key}";

        if ('LIKE' == $type || 'NOT LIKE' == $type) {
            $this->binds[$key] = "%${value}%";
        } else {
            $this->binds[$key] = $value;
        }

        $result = $where.$query;

        if (!empty($this->q_where)) {
            $result = $this->q_where.' AND '.$query;
        }

        $this->q_where = $result;

        return $this;
    }

    public function whereIn($data = [], $not = false)
    {
        $where_in = ' WHERE ';
        if ($data) {
            $in_string = $result = '';

            foreach ($data as $key => $value) {
                if (!is_array($value)) {
                    $value = explode(',', $value);
                }

                $in_value = '';
                foreach ($value as $val) {
                    $in_value .= ":${key}${val},";
                    $this->binds[$key.$val] = $val;
                }

                $in_value = rtrim($in_value, ',');

                if ($not) {
                    $in_string .= "`${key}` NOT IN (${in_value}) AND ";
                } else {
                    $in_string .= "`${key}` IN (${in_value}) AND ";
                }
            }

            $result = $in_string;

            if (!empty($this->q_where_in)) {
                $result = $in_string.$this->q_where_in;
            }

            $result = $where_in.$result;
            $this->q_where_in = rtrim($result, ' AND ');
        }

        return $this;
    }

    public function offset($offset = '')
    {
        $this->q_offset = ' OFFSET :offset';
        $this->binds['offset'] = $offset;

        return $this;
    }

    public function limit($limit = '')
    {
        $this->q_limit = ' LIMIT :limit';
        $this->binds['limit'] = $limit;

        return $this;
    }

    public function join($table, $cond, $type)
    {
        if ('' !== $type) {
            $type = strtoupper(trim($type));

            if (!in_array($type, ['LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'], true)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        $this->q_join = $type.'JOIN '.$table.$cond;
    }

    public function orderBy($order_by, $order)
    {
        $this->q_order_by = ' ORDER BY :order_by :order';
        $this->binds['order_by'] = $order_by;
        $this->binds['order'] = $order;

        return $this;
    }

    public function compile()
    {
        if (!empty($this->q_select)) {
            $sql = $this->q_select.$this->q_from.$this->q_join.$this->q_where.$this->q_where_in.$this->q_order_by.$this->q_limit.$this->q_offset;
        } elseif (!empty($this->q_insert)) {
            $sql = $this->q_insert;
        } elseif (!empty($this->q_update)) {
            $sql = $this->q_update.$this->q_where;
        } elseif (!empty($this->q_delete)) {
            $sql = $this->q_delete;
        }

        return str_replace("''", "'", $sql);
    }
}
