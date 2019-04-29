<?php

// Setting the path
set_include_path(get_include_path().PATH_SEPARATOR.'./');

// Only try to autoload files with this extension(s)
spl_autoload_extensions('.php');

// Register our function as the spl_autoload implementation to use
spl_autoload_register(function ($namespace_class) {
    $files = dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $namespace_class);
    $autoload_extensions = explode(',', spl_autoload_extensions());

    foreach ($autoload_extensions as $extension) {
        require $files.$extension;
    }
});
