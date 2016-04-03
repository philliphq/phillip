<?php

/**
 * This file contains the Assertion class.
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

use Phillip\Exceptions\AssertionFailureException;
use ReflectionFunction;
use Traversable;

/**
 * An object against which assertions can be made.
 *
 * @since v0.1.0
 */
class Assertion
{
    /**
     * The value to assert against.
     *
     * @var mixed
     */
    private $value = null;

    /**
     * Whether the assertion should be negative.
     *
     * @var bool
     */
    private $negative = false;

    /**
     * The test which called this assertion.
     *
     * @var Test
     */
    private $test = null;

    /**
     * Create the assertion object.
     *
     * @param mixed $value The value we will assert against
     * @param Test  $test  The test in which this assertion is being made
     */
    public function __construct($value, Test $test)
    {
        $this->value = $value;
        $this->test  = $test;
    }

    /**
     * Make the assertion negative.
     *
     * @return self
     */
    public function not()
    {
        $this->negative = true;

        return $this;
    }

    /**
     * Check the resolution of a function.
     *
     * @param bool  $assertion The result of the assertion
     * @param array $message   An array containing a failure message and
     *                         variables to substitute into it
     *
     * @return bool
     */
    public function assert($assertion, $message)
    {
        // Increment the assertion count
        $this->test->incrementAssertionCount();

        $assertion = $this->negative ? !((bool) $assertion) : (bool) $assertion;

        if ($assertion === false) {
            return call_user_func_array([$this, 'fail'], $message);
        }

        return true;
    }

    /**
     * Convert arguments into a string-safe exception message,
     * then throw an assertion failure.
     *
     * @throws AssertionFailureException
     */
    private function fail()
    {
        $args = func_get_args();

        $format = array_shift($args);

        $regex = '/\{\{(?P<positive>[^{}]*)\|(?<negative>[^{}]*)\}\}/';
        preg_match_all($regex, $format, $matches);

        $replacements = $this->negative ? $matches['negative'] : $matches['positive'];

        foreach ($matches[0] as $i => $match) {
            $format = str_replace($match, $replacements[$i], $format);
        }

        $format = preg_replace('/\s+/', ' ', $format);

        foreach ($args as &$arg) {
            if (is_array($arg)) {
                $arg = 'Array';
                continue;
            }

            if (is_object($arg)) {
                $arg = 'Object of type "'.get_class($arg).'"';
                continue;
            }

            if ($arg === true) {
                $arg = 'TRUE';
                continue;
            }

            if ($arg === false) {
                $arg = 'FALSE';
                continue;
            }

            if ($arg === null) {
                $arg = 'NULL';
                continue;
            }

            if (is_string($arg)) {
                $arg = '"'.$arg.'"';
                continue;
            }
        }

        array_unshift($args, $format);
        $msg = (new ReflectionFunction('sprintf'))->invokeArgs($args);

        throw new AssertionFailureException($msg);
    }

    /**
     * Assert that the value is a boolean type.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function boolean()
    {
        $message = ['%s is {{not|}} a boolean', $this->value];
        $this->assert(is_bool($this->value), $message);

        return $this;
    }

    /**
     * Assert that the value is true.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function true()
    {
        $message = ['%s is {{not|}} true', $this->value];
        $this->assert(($this->value === true), $message);

        return $this;
    }

    /**
     * Assert that the value is false.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function false()
    {
        $message = ['%s is {{not|}} false', $this->value];
        $this->assert(($this->value === false), $message);

        return $this;
    }

    /**
     * Assert that two values are equal.
     *
     * @param mixed $value The value to test
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function equal($value)
    {
        $message = ['%s is {{not|}} equal to %s', $this->value, $value];
        $this->assert(($this->value === $value), $message);

        return $this;
    }

    /**
     * Assert that two values are equivalent.
     *
     * @param mixed $value The value to test
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function equivalentTo($value)
    {
        $message = ['%s is {{not|}} equivalent to %s', $this->value, $value];
        $this->assert(($this->value == $value), $message);

        return $this;
    }

    /**
     * Check the size of the value.
     *
     * @param int $len The length to test
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function size($len)
    {
        $len = (int) $len;
        if (is_string($this->value)) {
            $message = ['%s {{does not have|has}} a string length of %s', $this->value, $len];
            $this->assert((strlen($this->value) === $len), $message);
        }

        if (is_int($this->value)) {
            $message = ['Integer %s {{does not have|has}} a size of %s', $this->value, $len];
            $this->assert(($this->value === $len), $message);
        }

        if (is_array($this->value)) {
            $message = ['Array {{does not have|has}} a size of %s', $len];
            $this->assert((count($this->value) === $len), $message);
        }

        return $this;
    }

    /**
     * An alias of Assertion::size.
     *
     * @param int $len The length to test
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function length($len)
    {
        return $this->size($len);
    }

    /**
     * Assert that the value is empty.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function blank()
    {
        $message = ['%s is {{not|}} empty', $this->value];
        $this->assert((empty($this->value)), $message);

        return $this;
    }

    /**
     * Assert that the value is an array.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function anArray()
    {
        $message = ['%s is {{not|}} an array', $this->value];
        $this->assert((is_array($this->value)), $message);

        return $this;
    }

    /**
     * Assert that the value is traversable.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function traversable()
    {
        $message   = ['%s is {{not|}} traversable', $this->value];
        $assertion = (is_array($this->value) || $this->value instanceof Traversable);
        $this->assert($assertion, $message);

        return $this;
    }

    /**
     * Assert that the value is an instance of $class.
     *
     * @param string $class The classname to assert against
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function anInstanceOf($class)
    {
        $message = ['%s is {{not|}} an instance of %s', $this->value, $class];
        $this->assert(($this->value instanceof $class), $message);

        return $this;
    }

    /**
     * Assert that the value implements the given interface.
     *
     * @param string $interface The interface to assert against
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function implement($interface)
    {
        $message = ['Class %s {{does not implement|implements}} interface %s', $this->value, $interface];
        $this->assert((in_array($interface, class_implements($this->value))), $message);

        return $this;
    }

    /**
     * Assert that the value matches a given regular expression.
     *
     * @param string $regex A regular expression to match against
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function match($regex)
    {
        $matches = preg_match($regex, $this->value);
        $message = ['%s {{does not match|matches}} the regular expression %s', $this->value, $regex];
        $this->assert((!empty($matches)), $message);

        return $this;
    }
}
