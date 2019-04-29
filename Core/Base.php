<?php

namespace Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Base
{
    private $routes;
    private $env;

    public function __construct()
    {
        $this->_init();
    }

    public function run()
    {
        $this->routes = require_once _CONFIG.'Routes.php';

        $this->routes->run();
    }

    private function _init()
    {
        //load helper
        $directory = new RecursiveDirectoryIterator('../Helper');
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/\/*.php$/i');

        foreach ($regex as $item) {
            require_once $item->getPathname();
        }

        //load config
        $GLOBALS['config'] = get_config();
        $this->env = $GLOBALS['config']->env;
        //set environment
        switch ($this->env) {
            case 'development':
                error_reporting(-1);
                ini_set('display_errors', 1);

            break;
            case 'production':
                ini_set('display_errors', 0);
                error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);

            break;
            default:
                header('HTTP/1.1 503 Service Unavailable.', true, 503);
                echo 'The application environment is not set correctly.';
                exit(1);
        }

        session_start();
    }
}
