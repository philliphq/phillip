<?php

/**
 * This file contains the Test class.
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

use Closure;
use Exception;
use Molovo\Object\Object;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Exceptions\MissingCallbackException;

/**
 * A test. Contains methods for directly passing/failing tests,
 * as well as making assertions against it.
 */
class Test
{
    /**
     * The name of this test.
     *
     * @var string
     */
    public $name = null;

    /**
     * A unique hash used to indentify the test.
     *
     * @var string
     */
    public $hash = null;

    /**
     * The callback to execute when this test is run.
     *
     * @var Closure
     */
    private $callback = null;

    /**
     * Another test which this test depends on.
     *
     * @var Test
     */
    private $dependency = null;

    /**
     * An exception which we expect to see thrown when the test runs.
     *
     * @var Exception
     */
    private $expectedException = null;

    /**
     * An exception message which we expect to see thrown when the test runs.
     *
     * @var string
     */
    private $expectedExceptionMessage = null;

    /**
     * The current state of the test.
     *
     * @var string|null
     */
    private $state = null;

    /**
     * The number of assertions which have been made for this test.
     *
     * @var int
     */
    private $assertions = 0;

    /**
     * An array of a classes/functions which this test covers.
     *
     * @var string[]
     */
    private $covers = [];

    /**
     * Contains coordinates of the indicator in the table which
     * represents this test.
     *
     * @var object
     */
    public $pos;

    /**
     * The message shown when this test fails.
     *
     * @var string
     */
    private $failureMessage = 'The test failed.';

    /**
     * The message shown when this test errors.
     *
     * @var string
     */
    private $errorMessage = 'An error occurred while running the test.';

    /**
     * Create the test.
     *
     * @param string       $name     The name of the test
     * @param Runner       $runner   The runner which will run this test
     * @param Closure|null $callback The callback containing the body of the test
     */
    public function __construct($name, Runner $runner, Closure $callback = null)
    {
        // Store the test name and runner
        $this->name   = $name;
        $this->runner = $runner;

        // Create a unique hash and store it
        $this->hash = spl_object_hash($this);

        // If the callback is passed into the constructor, register it
        if ($callback !== null) {
            $this->execute($callback);
        }
    }

    /**
     * Defines the callback to be executed when the test is run.
     *
     * @param Closure $callback The callback containing the body of the test
     *
     * @return self
     */
    public function execute(Closure $callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Run the test.
     */
    public function run()
    {
        // If no callback, or an invalid callback is defined,
        // throw an exception
        if ($this->callback === null || !($this->callback instanceof Closure)) {
            throw new MissingCallbackException('You must provide a closure to each test.');
        }

        // Wrap the closure in another closure, so that we can apply our
        // own handler to each test
        $callback = $this->callback;
        $test     = $this;
        $handler  = function () use ($callback, $test) {
            // Start code coverage recording, if enabled
            $coverage = $test->runner->options->coverage->enable !== false;
            if ($coverage) {
                xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
            }

            // Execute the callback
            $callback($test);

            // End code coverage recording
            if ($coverage) {
                xdebug_stop_code_coverage(false);
            }
        };

        // If a dependency is defined, and is another test
        if ($this->dependency instanceof self) {
            $test = $this;
            $hash = $this->dependency->hash;

            // If the dependency has already passed, then just run our test
            if (in_array($this->dependency, $this->runner->passed)) {
                return $handler($this);
            }

            // If the dependency has already failed, errored or skipped,
            // then skip our test
            if (in_array($this->dependency, $this->runner->failures)
                || in_array($this->dependency, $this->runner->errors)
                || in_array($this->dependency, $this->runner->skipped)) {
                return $this->skip();
            }

            // If we get to here, the dependency has not yet been run.
            // We register listeners for each of the possible states,
            // which run the test when the dependency passes, or skip it
            // if the dependency fails, errors or skips.
            $this->runner->on("test.$hash.passed", function () use ($test, $handler) {
                $handler($test);
            });

            $this->runner->on("test.$hash.failed", function () use ($test) {
                $test->skip();
            });

            $this->runner->on("test.$hash.error", function () use ($test) {
                $test->skip();
            });

            $this->runner->on("test.$hash.skipped", function () use ($test) {
                $test->skip();
            });

            return;
        }

        // Execute the callback
        try {
            try {
                $handler($this);
            } catch (AssertionFailureException $e) {
                // If we're actively listening for an assertion failure,
                // rethrow the exception so that our failure handler can
                // deal with it
                if ($this->expectedException === get_class($e)) {
                    throw $e;
                }

                // If AssertionFailureException is thrown, then one of our
                // assertions proved to be incorrect, so we fail the test
                return $this->fail($e->getMessage());
            }
        } catch (Exception $e) {
            // If we're expecting an exception, run a few more assertions
            // against the thrown exception
            if ($this->expectedException === get_class($e)) {
                if ($this->expectedExceptionMessage !== null) {
                    if ($this->expectedExceptionMessage !== $e->getMessage()) {
                        return $this->fail('Expected Exception message "'.$this->expectedExceptionMessage.'" does not match "'.$e->getMessage().'".');
                    }
                }

                $this->pass();
            }

            if ($this->state === null && $this->assertions === 0) {
                // If the test is still stateless, we re-throw the exception,
                // so that it can be caught by the test runner
                throw $e;
            }
        }

        // If we get here, then the test has finished. If the test hasn't passed
        // or failed itself within the callback, then we check the number of
        // assertions. If any are found, it can be assumed that the assertions
        // were correct, as any incorrect assertion would have thrown the
        // AssertionFailureException which we caught above
        if ($this->state === null && $this->assertions > 0) {
            // If we're expecting an exception but none has thrown,
            // the test should fail.
            if ($this->expectedException !== null) {
                $this->fail('Expected Exception '.$this->expectedException.' was not thrown.');

                return;
            }

            $this->pass();

            return;
        }

        // If there is still no state, then throw an error as no assertions
        // have been made, making this a useless test
        if ($this->state === null) {
            $this->error('No assertions were made.');
        }
    }

    /**
     * Define a test dependency. The dependency must pass in order
     * for this test to run.
     *
     * @param self $dependency Another test which this one depends on
     *
     * @return self
     */
    public function depends(self $dependency = null)
    {
        $this->dependency = $dependency;

        return $this;
    }

    /**
     * Define an expected exception. The exception must be thrown
     * for this test to pass.
     *
     * @param string      $exception An exception classname
     * @param string|null $message   The expected message if exception is thrown
     *
     * @return self
     */
    public function expect($exception, $message = null)
    {
        $this->expectedException = $exception;

        if ($message !== null) {
            $this->expectedExceptionMessage = $message;
        }

        return $this;
    }

    /**
     * Select the method(s) which are covered by this test.
     *
     * @param string      $object An object or function name
     * @param string|null $method An optional method name
     */
    public function covers($object, $method = null)
    {
        $this->covers[] = "$object::$method";

        if (is_string($object) && $method === null) {
            if (!strstr($object, '::')) {
                $this->runner->coverage->addCoveredFunction($object);

                return $this;
            }

            list($object, $method) = explode('::', $object);
        }

        $this->runner->coverage->addCoveredMethod($object, $method);

        return $this;
    }

    /**
     * Define a data provider which will feed this test.
     *
     * @param array $data The data
     */
    public function data(array $data)
    {
        $callback = $this->callback;

        // Grab the first item from the dataset, and pass it to
        // the callback for this test
        $first          = array_shift($data);
        $this->callback = function ($test) use ($callback, $first) {
            // Add the test object to the array of arguments
            // and execute the callback
            array_unshift($first, $test);
            call_user_func_array($callback, $first);
        };

        // Loop through the remaining data, and register a new test
        // for each item within it, incrementing a count at the end
        // of the test name to allow them to be distinguished from
        // each other
        foreach ($data as $i => $values) {
            $name = "$this->name #$i";
            $test = test($name, function ($test) use ($callback, $values) {
                array_unshift($values, $test);
                call_user_func_array($callback, $values);
            });

            if ($this->expectedException !== null) {
                $test->expect($this->expectedException, $this->expectedExceptionMessage);
            }

            if (!empty($this->covers)) {
                foreach ($this->covers as $method) {
                    $test->covers($method);
                }
            }
        }

        return $this;
    }

    /**
     * Set the position of the test in the output table.
     *
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     */
    public function setPosition($x, $y)
    {
        $this->pos    = new Object;
        $this->pos->x = $x;
        $this->pos->y = $y;
    }

    /**
     * Pass the test.
     *
     * @param bool|null $condition An optional boolean expression or value.
     *                             If this evaluates to false, then the
     *                             test is failed rather than passed.
     */
    public function pass($condition = null)
    {
        // Fail the test if an expression is passed which evaluates to false
        if ($condition !== null && !(bool) $condition) {
            return $this->fail();
        }

        // Tell the runner we passed
        $this->runner->addPass($this);
        $this->event('passed');
    }

    /**
     * Fail the test.
     *
     * @param string|null $message An optional failure message
     */
    public function fail($message = null)
    {
        // Set the failure message
        if ($message !== null) {
            $this->failureMessage = $message;
        }

        // Tell the runner we failed
        $this->runner->addFailure($this, $message);
        $this->event('failed');
    }

    /**
     * Mark the test as errored.
     *
     * @param string|null $message An optional error message
     */
    public function error($message = null)
    {
        // Set the error message
        if ($message !== null) {
            $this->errorMessage = $message;
        }

        // Tell the runner we errored
        $this->runner->addError($this, $message);
        $this->event('error');
    }

    /**
     * Skip the test.
     */
    public function skip()
    {
        // Tell the runner we skipped
        $this->runner->addSkipped($this);
        $this->event('skipped');
    }

    /**
     * Emit an event broadcasting the current state.
     *
     * @param string $state The test state
     */
    private function event($state)
    {
        $this->state = $state;
        $this->runner->emit("test.$this->hash.$this->state");
    }

    /**
     * Get the test's failure message.
     *
     * @return string
     */
    public function getFailureMessage()
    {
        return $this->failureMessage;
    }

    /**
     * Get the test's error message.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Create an assertion.
     *
     * @param mixed $value The value
     *
     * @return Assertion An assertion object
     */
    public function is($value)
    {
        return new Assertion($value, $this);
    }

    /**
     * Create an assertion (Alias of self::is).
     *
     * @param mixed $value The value
     *
     * @return Assertion An assertion object
     */
    public function does($value)
    {
        return $this->is($value);
    }

    /**
     * Increment the assertion count.
     *
     * @return int
     */
    public function incrementAssertionCount()
    {
        return $this->assertions++;
    }
}
