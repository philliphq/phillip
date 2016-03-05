<?php

namespace Phillip;

use Molovo\Object\Object;
use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;

class Options extends Object
{
    /**
     * An array of default options.
     *
     * @var array
     */
    private static $defaults = [
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
     * @param Runner $runner The test runner
     *
     * @return self The options object
     */
    public static function bootstrap(Runner $runner)
    {
        static::$runner = $runner;

        return new static(static::parseOptions());
    }

    /**
     * Parse options.
     *
     * @return array
     */
    private static function parseOptions()
    {
        $opts = static::$defaults;
        $opts = static::parseYamlOptions($opts);
        $opts = static::parseCliOptions($opts);

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
    private static function parseYamlOptions(array $opts = [])
    {
        if (is_file(static::$runner->pwd.'/.phillip.yml')) {
            $options = Yaml::parseFile(static::$runner->pwd.'/.phillip.yml');

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
    private static function parseCliOptions(array $opts = [])
    {
        $cliOpts = getopt('chvrs:', [
            'help',
            'version',
            'random',
            'suite:',
            'coverage',
        ]);

        if (isset($cliOpts['help']) || isset($cliOpts['h'])) {
            static::printHelp();
            exit;
        }

        if (isset($cliOpts['version']) || isset($cliOpts['v'])) {
            static::printVersion();
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

    /**
     * Print help text to the command line.
     */
    private static function printHelp()
    {
        Prompt::output(ANSI::fg('Usage:', ANSI::YELLOW));
        Prompt::output('  phillip [options] [file|directory]');

        Prompt::output('');
        Prompt::output(ANSI::fg('Options:', ANSI::YELLOW));
        Prompt::output('  -h, --help               Show help text and exit.');
        Prompt::output('  -v, --version            Show version information and exit.');
        Prompt::output('  -c, --coverage           Show coverage data.');
        Prompt::output('  -s, --suite <suite>      Run a predefined test suite.');
        Prompt::output('  -r, --random             Run tests within each suite in random order.');
    }

    /**
     * Print version information to the command line.
     */
    private static function printVersion()
    {
        $version = file_get_contents(__DIR__.'/../../.version');
        Prompt::output(ANSI::fg('Phillip', ANSI::YELLOW));
        Prompt::output('Version '.trim($version));
    }
}
