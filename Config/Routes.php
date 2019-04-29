<?php

use Core\Router;

$router = new Router();

/*
 *  default route, add($method,$pattern,$controller,$action)
 * advanced route, __get($main_folder)->add($method,$pattern,$controller,$action)
 */

$router->frontend->add('GET', '/', 'Welcome', 'index');
$router->frontend->add('GET', '/a/a', 'Welcome', 'index');

return $router;
