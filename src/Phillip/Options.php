<?php

/**
 * This file contains the Options class.
 *
 * @package philliphq/phillip
 *
 * @author James Dinsdale <hi@molovo.co>
 * @copyright Copyright 2016, James Dinsdale
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Phillip;

use Molovo\Object\Object;

/**
 * Parses options from the .phillip.yml config file and passed via
 * the command line, and stores them for use internally.
 *
 * @since v0.1.0
 */
class Options extends Object
{
    /**
     * An array of default options.
     *
     * @var array
     */
    private $defaults = [
        'shuffle' => false,
        'suites'  => [
            'tests' => 'tests',
        ],
        'coverage' => [
            'enable'  => false,
            'include' => [
                'src/*.php',
            ],
        ],
    ];

    /**
     * The test runner.
     *
     * @var Runner
     */
    private static $runner;

    /**
     * Bootstrap the options.
     *
     * @param array  $options The option values
     * @param Runner $runner  The test runner
     *
     * @return self The options object
     */
    public function __construct(array $options = [], Runner $runner = null)
    {
        if ($runner !== null) {
            $this->runner = $runner;
            $options      = $this->parseOptions();
        }

        parent::__construct($options);
    }

    /**
     * Retrieve an option value statically.
     *
     * @param string $key The key to get
     *
     * @return mixed
     */
    public static function get($key)
    {
        return Runner::instance()->options->valueForPath($key);
    }

    /**
     * Set an option value statically.
     *
     * @param string $key   The key to set
     * @param mixed  $value The value to set
     *
     * @return mixed
     */
    public static function set($key, $value)
    {
        return Runner::instance()->options->setValueForPath($key, $value);
    }

    /**
     * Parse options.
     *
     * @return array
     */
    private function parseOptions()
    {
        $opts = $this->defaults;
        $opts = $this->parseYamlOptions($opts);
        $opts = $this->parseCliOptions($opts);

        return $opts;
    }

    /**
     * Load options from the YAML config file, and merge them
     * with the existing options.
     *
     * @param array $opts The options to merge with
     *
     * @return array
     */
    private function parseYamlOptions(array $opts = [])
    {
        if (is_file($this->runner->pwd.'/.phillip.yml')) {
            $options = Yaml::parseFile($this->runner->pwd.'/.phillip.yml');

            return array_merge($opts, $options);
        }

        return $opts;
    }

    /**
     * Parse options given on the command line, and merge them
     * with the existing options.
     *
     * @param array $opts The options to merge with
     *
     * @return array
     */
    private function parseCliOptions(array $opts = [])
    {
        $cliOpts = getopt('chvrs:', [
            'help',
            'version',
            'random',
            'suite:',
            'coverage',
        ]);

        if (isset($cliOpts['help']) || isset($cliOpts['h'])) {
            $this->runner->output->help();
            exit;
        }

        if (isset($cliOpts['version']) || isset($cliOpts['v'])) {
            $this->runner->output->version();
            exit;
        }

        if (isset($cliOpts['random']) || isset($cliOpts['r'])) {
            $opts['random'] = true;
        }

        if (isset($cliOpts['suite']) || isset($cliOpts['s'])) {
            $opts['suite'] = $cliOpts['suite'] ?: $cliOpts['s'];
        }

        if (isset($cliOpts['coverage']) || isset($cliOpts['c'])) {
            $opts['coverage']['enable'] = true;
        }

        return $opts;
    }
}
