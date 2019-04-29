<?php

namespace Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Cache
{
    public function set($cache_name, $cache, $time = 600)
    {
        $cache_name = md5($cache_name);
        $file = _CACHE.$cache_name.'_'.$time.'.cache';
        $pointer = fopen($file, 'w');
        fwrite($pointer, $cache);
        fclose($pointer);

        if (file_exists($file)) {
            chmod($file, 0777);
        }
    }

    public function get($cache_name)
    {
        $cache_name = md5($cache_name);
        $file = _CACHE.$cache_name;
        $directory = new RecursiveDirectoryIterator(_CACHE);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/\/*.cache$/i');
        $cache = '';

        foreach ($regex as $item) {
            if (false !== strpos($item->getPathname(), $file)) {
                $cache = $item->getPathname();

                break;
            }
        }

        if ($cache) {
            $part = explode('_', $cache);
            $time = str_replace('.cache', '', end($part));

            if (time() - $time < filemtime($cache)) {
                return file_get_contents($cache);
            }

            return '';
        }

        return '';
    }
}
