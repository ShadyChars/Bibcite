<?php declare(strict_types=1);

define('BIBCITE_CACHE_DIRECTORY', 'cache');

function get_temp_dir()
{
    // CslRenderer relies on WP's get_temp_dir() function to discover where
    // to create a cache directory. Force it to create one here.
    return realpath(dirname(__FILE__));
}

function plugin_dir_path($dirname) 
{
    // CslRenderer relies on WP's plugin_dir_path() function to discover its 
    // autoload file. Override to return the top-level autoload file.
    $filename = basename($_SERVER["SCRIPT_FILENAME"], '.php');
    return realpath(dirname($filename));
}