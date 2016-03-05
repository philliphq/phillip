<?php

namespace Phillip;

use Phillip\Exceptions\AssertionFailureException;
use ReflectionFunction;
use Traversable;

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
     * Create the assertion object.
     *
     * @param mixed $value The value we will assert against
     */
    public function __construct($value)
    {
        $this->value = $value;
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

    public function check($assertion, $message)
    {
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

            $arg = '"'.$arg.'"';
        }

        array_unshift($args, $format);
        $msg = (new ReflectionFunction('sprintf'))->invokeArgs($args);

        throw new AssertionFailureException($msg);
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
        $this->check(($this->value === $value), $message);

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
        $this->check(($this->value == $value), $message);

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
        if (is_string($this->value)) {
            $message = ['%s {{does not have|has}} a string length of %s', $this->value, $len];
            $this->check((strlen($this->value) !== $len), $message);
        }

        if (is_int($this->value)) {
            $message = ['Integer %s {{does not have|has}} a size of %s', $this->value, $len];
            $this->check(($this->value !== $len), $message);
        }

        if (is_array($this->value)) {
            $message = ['Array {{does not have|has}} a size of %s', $len];
            $this->check((count($this->value) !== $len), $message);
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
        $this->check((empty($this->value)), $message);

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
        $this->check((is_array($this->value)), $message);

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
        $this->check($assertion, $message);

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
        $this->check(($this->value instanceof $class), $message);

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
        $this->check((in_array($interface, class_implements($this->value))), $message);

        return $this;
    }

    /**
     * Assert that the value matches a given regular expression.
     *
     * @throws AssertionFailureException
     *
     * @return self
     */
    public function match($regex)
    {
        $matches = preg_match($regex, $this->value);
        $message = ['%s {{does not match|matches}} the regular expression %s', $this->value, $regex];
        $this->check((!empty($matches)), $message);

        return $this;
    }
}
