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
