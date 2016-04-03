<?php

use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test length with an array', function (Test $test) {
    $test->is([1, 2, 3])->length(3);
})
    ->covers(Assertion::class, 'length');

test('Test length with a string', function (Test $test) {
    $test->is('Unicorns!')->length(9);
})
    ->covers(Assertion::class, 'length');

test('Test length with an integer', function (Test $test) {
    $test->is(1)->length(1);
})
    ->covers(Assertion::class, 'length');

test('Test length with an incorrectly-lengthd array', function (Test $test) {
    $test->is([1, 2, 3])->length(4);
})
    ->expect(AssertionFailureException::class, 'Array does not have a size of 4')
    ->covers(Assertion::class, 'length');

test('Test length with an incorrectly-sized string', function (Test $test) {
    $test->is('Unicorns!')->length(5);
})
    ->expect(AssertionFailureException::class, '"Unicorns!" does not have a string length of 5')
    ->covers(Assertion::class, 'length');

test('Test length with an incorrectly-sized integer', function (Test $test) {
    $test->is(1)->length(3);
})
    ->expect(AssertionFailureException::class, 'Integer 1 does not have a size of 3')
    ->covers(Assertion::class, 'length');

test('Test not length with an incorrectly-sized array', function (Test $test) {
    $test->is([1, 2, 3])->not()->length(4);
})
    ->covers(Assertion::class, 'length');

test('Test not length with an incorrectly-sized string', function (Test $test) {
    $test->is('Unicorns!')->not()->length(5);
})
    ->covers(Assertion::class, 'length');

test('Test not length with an incorrectly-sized integer', function (Test $test) {
    $test->is(1)->not()->length(3);
})
    ->covers(Assertion::class, 'length');

test('Test not length with an array', function (Test $test) {
    $test->is([1, 2, 3])->not()->length(3);
})
    ->expect(AssertionFailureException::class, 'Array has a size of 3')
    ->covers(Assertion::class, 'length');

test('Test not length with a string', function (Test $test) {
    $test->is('Unicorns!')->not()->length(9);
})
    ->expect(AssertionFailureException::class, '"Unicorns!" has a string length of 9')
    ->covers(Assertion::class, 'length');

test('Test not length with an integer', function (Test $test) {
    $test->is(1)->not()->length(1);
})
    ->expect(AssertionFailureException::class, 'Integer 1 has a size of 1')
    ->covers(Assertion::class, 'length');
