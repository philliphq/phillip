<?php

use Phillip\Runner;
use Phillip\Test;

if (!function_exists('test')) {
    /**
     * Define a test.
     *
     * @param string  $name     The name of the test
     * @param Closure $callback The callback to execute
     *
     * @return Test
     */
    function test($name, Closure $callback = null)
    {
        $runner = Runner::instance();
        $test   = new Test($name, $runner, $callback);
        $runner->addTest($test);

        return $test;
    }
}

if (!function_exists('glob_recursive')) {
    /**
     * Recursively match glob strings against directory contents.
     *
     * @param string $pattern The glob pattern
     * @param int    $flags   Flags to pass to glob()
     *
     * @return array Found files
     */
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}
