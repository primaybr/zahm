<?php

namespace Core;

class Controller
{
    public $config;
    public $model;
    public $log;
    public $session;
    private $_template;

    public function __construct()
    {
        $this->config = $GLOBALS['config'];
        $this->_template = new Template();
        $this->log = new Log();

        $this->base_url = $this->config->site->base_url;
        $this->admin_url = $this->base_url.'/'.$this->config->site->admin_url;
        $this->assets_url = $this->base_url.'/'.$this->config->site->assets_url.'/';
        $this->assets_backend = $this->assets_url.'backend/';
        $this->assets_frontend = $this->assets_url.'frontend/';
        $this->session = check_session('sessid');
    }

    public function render($template, $data = [], $return = false)
    {
        $data_temp = [
            'base_url' => $this->base_url,
            'assets_url' => $this->assets_url,
            'assets_backend' => $this->assets_backend,
            'assets_frontend' => $this->assets_frontend,
            'admin_url' => $this->admin_url,
        ];

        if (!empty($data)) {
            $data = array_merge($data, $data_temp);
        } else {
            $data = $data_temp;
        }

        unset($data_temp);

        return $this->_template->render($template, $data, $return);
    }

    /**
     * The "model" function.
     *
     * @param array|string $table    table name, or an array of table
     * @param string       $database database name
     *
     * @return Model
     */
    public function model($table, $database = 'default')
    {
        if ('default' == $database) {
            if (is_array($table)) {
                foreach ($table as $val) {
                    $this->{$val} = new Model($val, $database);
                }
            } else {
                $this->{$table} = new Model($table, $database);
            }
        } else {
            $this->{$database} = new \stdClass();

            if (is_array($table)) {
                foreach ($table as $val) {
                    $this->{$database}->{$val} = new Model($val, $database);
                }
            } else {
                $this->{$database}->{$table} = new Model($table, $database);
            }
        }

        return $this;
    }
}
