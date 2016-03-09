<?php

use Molovo\Object\Object;
use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test traversable with object instance', function (Test $test) {
    $test->is(new Object)->traversable();
})
    ->covers(Assertion::class, 'traversable');

test('Test traversable with array', function (Test $test) {
    $test->is([1, 2, 3])->traversable();
})
    ->covers(Assertion::class, 'traversable');

test('Test traversable with integer', function (Test $test) {
    $test->is(1)->traversable(Test::class);
})
    ->expect(AssertionFailureException::class, '"1" is not traversable')
    ->covers(Assertion::class, 'traversable');

test('Test traversable with string', function (Test $test) {
    $test->is('that')->traversable(Test::class);
})
    ->expect(AssertionFailureException::class, '"that" is not traversable')
    ->covers(Assertion::class, 'traversable');

test('Test not traversable with integer', function (Test $test) {
    $test->is(1)->not()->traversable(Test::class);
})
    ->covers(Assertion::class, 'traversable')
    ->covers(Assertion::class, 'not');

test('Test not traversable with string', function (Test $test) {
    $test->is('that')->not()->traversable(Test::class);
})
    ->covers(Assertion::class, 'traversable')
    ->covers(Assertion::class, 'not');

test('Test not traversable with object instance', function (Test $test) {
    $test->is(new Object)->not()->traversable();
})
    ->expect(AssertionFailureException::class, 'Object of type "Molovo\Object\Object" is traversable')
    ->covers(Assertion::class, 'traversable')
    ->covers(Assertion::class, 'not');

test('Test not traversable with array', function (Test $test) {
    $test->is([1, 2, 3])->not()->traversable();
})
    ->expect(AssertionFailureException::class, 'Array is traversable')
    ->covers(Assertion::class, 'traversable')
    ->covers(Assertion::class, 'not');
