<?php

namespace Core;

class Router
{
    private $routes = [];
    private $action = [];
    private $method = [];
    private $path = '';

    // Magic Function __get , set the main folder of the app
    public function __get($key)
    {
        $this->path = !empty($this->path) ? $this->path.'\\'.ucfirst($key) : ucfirst($key);

        return $this;
    }

    public function add($method, $pattern, $controller, $action = '')
    {
        if ('/' == $pattern) {
            $pattern = '/^\/'.basename(strtolower(ROOT)).'\/$/';
        } else {
            $pattern = '/^\/'.str_replace('/', '\/', basename(strtolower(ROOT)).$pattern).'$/';
        }

        $controller = !empty($this->path) ? $this->path.'\\'.$controller : $controller;

        //debug($action);
        $this->routes[$pattern] = $controller;
        $this->action[$pattern] = $action;
        $this->method[$pattern] = $method;
    }

    public function run()
    {
        $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        //parse url path
        $url = parse_url($url, PHP_URL_PATH);

        //skip this url config if using it in local machine / IP Address
        if (1 != (bool) ip2long($_SERVER['HTTP_HOST']) && 'localhost' != $_SERVER['HTTP_HOST']) {
            $url = DS.basename(strtolower(ROOT)).$url;
        }

        foreach ($this->routes as $pattern => $controller) {
            if (preg_match($pattern, $url, $matches)) {
                if (false === stripos($this->method[$pattern], $method)) {
                    show_error(404);
                }

                array_shift($matches);

                if (is_string($controller) && is_string($this->action[$pattern]) && !empty($this->action[$pattern])) {
                    $controller = str_replace('/', '\\', _CONTROLLER.$controller);
                    $controller = new $controller();
                    $handler = [$controller, $this->action[$pattern]];

                    if (is_callable($handler)) {
                        call_user_func_array($handler, $matches);
                    } else {
                        show_error(404);
                    }
                } else {
                    call_user_func_array($controller, array_values($matches));
                }

                break;
            }
        }
    }
}
