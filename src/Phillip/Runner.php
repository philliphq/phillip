<?php

/**
 * This file contains the Runner class.
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

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Exception;
use Molovo\Graphite\Graphite;
use Molovo\Str\Str;
use Phillip\Exceptions\TestNotFoundException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The test runner.
 *
 * @since v0.1.0
 */
class Runner implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * The current runner instance.
     *
     * @var null
     */
    public static $instance = null;

    /**
     * The tests to be run.
     *
     * @var Test[]
     */
    public $tests = [];

    /**
     * An array of tests which have passed.
     *
     * @var Test[]
     */
    public $passed = [];

    /**
     * An array of tests which have failed.
     *
     * @var Test[]
     */
    public $failures = [];

    /**
     * An array of tests which have errored.
     *
     * @var Test[]
     */
    public $errors = [];

    /**
     * An array of tests which have been skipped.
     *
     * @var Test[]
     */
    public $skipped = [];

    /**
     * Get the current runner instance.
     *
     * @return self
     */
    public static function instance()
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        static::$instance = new static;

        return static::$instance->init();
    }

    /**
     * Create the runner instance.
     */
    public function __construct()
    {
        // Get the current directory
        $this->pwd = $_SERVER['PWD'];

        \Accidents\Handler::register();
    }

    /**
     * Initialize the test runner.
     *
     * @return self
     */
    public function init()
    {
        // Create an object for outputting information to the CLI
        $this->output = new Output($this);

        // Parse the options
        $this->options = new Options([], $this);

        // Create an object for collating code coverage information
        $this->coverage = new Coverage($this);

        // Parse the arguments passed to the script
        $this->parseArguments();

        // Run the main bootstrap script if it exists
        $this->bootstrap($this->pwd.'/tests');

        return $this;
    }

    /**
     * Load tests based on passed in arguments and options.
     *
     * @param string|array $args A path, or array of paths
     */
    public function loadTests($args = null)
    {
        // Create an empty array to store test files in
        $files = [];

        // Convert strings to an array to allow looping
        if (!is_array($args)) {
            $args = [$args];
        }

        // Loop through each of the paths
        foreach ($args as $arg) {
            // Prepend the current directory
            $arg = $this->pwd.'/'.$arg;

            // If the path matches a file exactly, add it directly to the array
            if (is_file($arg)) {
                $files[] = $arg;
                continue;
            }

            // If the path matches a directory, add it to a separate array
            // so we can do more processing on it later
            if (is_dir($arg)) {
                $dirs[] = $arg;
            }
        }

        // If none of the paths exist, throw an exception
        if (empty($files) && empty($dirs)) {
            throw new TestNotFoundException('No tests could be found.');
        }

        // Loop through any directories
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                // Create an iterator on the directory
                $iterator = new RecursiveDirectoryIterator($dir);
                $items    = new RecursiveIteratorIterator($iterator);

                // Loop through each of the items found by the iterator
                foreach ($items as $file) {
                    // If file is not hidden, add it to the files array
                    if (strpos($file->getFilename(), '.') !== 0) {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }

        // If there are still no files, add an exception
        if (empty($files)) {
            throw new TestNotFoundException('No tests could be found.');
        }

        // Bootstrap each of the included directories
        if (!empty($dirs)) {
            foreach ($dirs as $dir) {
                $this->bootstrap($dir);
            }
        }

        // Include each of the test files. We do this within a closure
        // to prevent variable leakage between files.
        foreach ($files as $file) {
            $inclusion = function () use ($file) {
                include $file;
            };
            $inclusion();
        }
    }

    /**
     * Include a bootstrap file from within a given directory.
     *
     * @param string $dir The directory to bootstrap
     */
    private function bootstrap($dir)
    {
        if (is_file($dir.'/.bootstrap.php')) {
            include $dir.'/.bootstrap.php';
        }
    }

    /**
     * Parse arguments passed to the script.
     *
     * @return array The arguments
     */
    private function parseArguments()
    {
        $args = $_SERVER['argv'];

        // The first argument is the script name, so drop it
        array_shift($args);

        // Loop through each of the arguments
        foreach ($args as $index => $task) {
            if (strpos($task, '-') === 0) {
                // Argument is an option, so we remove it
                unset($args[$index]);

                // If the next argument is the option's value,
                // remove that as well.
                $option = preg_replace('/^[-]{1,2}/', '', $task);
                if (isset($args[$index + 1]) && $args[$index + 1] === $this->options->{$option}) {
                    unset($args[$index + 1]);
                }
                continue;
            }
        }

        return $this->args = $args;
    }

    /**
     * Add a test to the runner.
     *
     * @param Test $test
     */
    public function addTest(Test $test)
    {
        $this->tests[] = $test;
    }

    /**
     * Run all tests assigned to the runner.
     */
    public function run()
    {
        // Set the total count to zero
        $this->totalCount = 0;

        // Load the suites defined in the options
        $suites = $this->options->suites;

        // Check if a suite name has been passed via the command line
        if ($suite = $this->options->suite) {
            // If the suite hasn't been defined, throw an exception
            if (!$this->options->suites->{$suite}) {
                throw new TestNotFoundException('The suite '.$suite.' is not defined');
            }

            // Overwrite the array of suites, so that only the passed
            // suite is run
            $suites = [
                $suite => $this->options->suites->{$suite},
            ];
        }

        // If arguments exist, then we create a new suite from the
        // files/directories passed as arguments
        if ($this->args) {
            $suites = [
                'tests' => $this->args,
            ];
        }

        // Loop through each of the suites
        foreach ($suites as $suite => $dirs) {
            // Reset the runner's tests
            $this->tests = [];

            // Load the tests for the suite
            $this->loadTests($dirs);

            // Get the number of tests and increment the total count
            $total = count($this->tests);
            $this->totalCount += $total;

            // Get the suite name
            $name = Str::title($suite);

            // Output a message to the user
            echo "\n";
            echo $this->output->gray->render("Running $total tests in suite $name...");

            // Render the table of tests
            $this->output->table();

            // If the random option is set, shuffle the array of tests
            if ($this->options->random === true) {
                shuffle($this->tests);
            }

            // Loop through each of the tests
            foreach ($this->tests as $test) {
                // Run the test inside a try/catch block so that we can
                // include errors in results without stopping testing
                try {
                    $test->run();
                } catch (Exception $e) {
                    $bits = explode('\\', get_class($e));
                    // Mark the test as errored
                    $test->error(array_pop($bits).': '.$e->getMessage());
                }
            }
        }

        // Output the results to the CLI
        $this->output->results();
    }

    /**
     * Mark a test as passed, and update the table of tests.
     *
     * @param Test $test
     */
    public function addPass(Test $test)
    {
        $this->output->updateTable($test->pos->x, $test->pos->y, Graphite::GREEN);
        $this->passed[] = $test;
    }

    /**
     * Mark a test as failed, and update the table of tests.
     *
     * @param Test $test
     */
    public function addFailure(Test $test)
    {
        $this->output->updateTable($test->pos->x, $test->pos->y, Graphite::RED);
        $this->failures[] = $test;
    }

    /**
     * Mark a test as errored, and update the table of tests.
     *
     * @param Test $test
     */
    public function addError(Test $test)
    {
        $this->output->updateTable($test->pos->x, $test->pos->y, Graphite::YELLOW);
        $this->errors[] = $test;
    }

    /**
     * Mark a test as skipped, and update the table of tests.
     *
     * @param Test $test
     */
    public function addSkipped(Test $test)
    {
        $this->output->updateTable($test->pos->x, $test->pos->y, Graphite::MAGENTA);
        $this->skipped[] = $test;
    }
}
