<?php

use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test size with an array', function (Test $test) {
    $test->is([1, 2, 3])->size(3);
})
    ->covers(Assertion::class, 'size');

test('Test size with a string', function (Test $test) {
    $test->is('Unicorns!')->size(9);
})
    ->covers(Assertion::class, 'size');

test('Test size with an integer', function (Test $test) {
    $test->is(1)->size(1);
})
    ->covers(Assertion::class, 'size');

test('Test size with an incorrectly-sized array', function (Test $test) {
    $test->is([1, 2, 3])->size(4);
})
    ->expect(AssertionFailureException::class, 'Array does not have a size of "4"')
    ->covers(Assertion::class, 'size');

test('Test size with an incorrectly-sized string', function (Test $test) {
    $test->is('Unicorns!')->size(5);
})
    ->expect(AssertionFailureException::class, '"Unicorns!" does not have a string length of "5"')
    ->covers(Assertion::class, 'size');

test('Test size with an incorrectly-sized integer', function (Test $test) {
    $test->is(1)->size(3);
})
    ->expect(AssertionFailureException::class, 'Integer "1" does not have a size of "3"')
    ->covers(Assertion::class, 'size');

test('Test not size with an incorrectly-sized array', function (Test $test) {
    $test->is([1, 2, 3])->not()->size(4);
})
    ->covers(Assertion::class, 'size');

test('Test not size with an incorrectly-sized string', function (Test $test) {
    $test->is('Unicorns!')->not()->size(5);
})
    ->covers(Assertion::class, 'size');

test('Test not size with an incorrectly-sized integer', function (Test $test) {
    $test->is(1)->not()->size(3);
})
    ->covers(Assertion::class, 'size');

test('Test not size with an array', function (Test $test) {
    $test->is([1, 2, 3])->not()->size(3);
})
    ->expect(AssertionFailureException::class, 'Array has a size of "3"')
    ->covers(Assertion::class, 'size');

test('Test not size with a string', function (Test $test) {
    $test->is('Unicorns!')->not()->size(9);
})
    ->expect(AssertionFailureException::class, '"Unicorns!" has a string length of "9"')
    ->covers(Assertion::class, 'size');

test('Test not size with an integer', function (Test $test) {
    $test->is(1)->not()->size(1);
})
    ->expect(AssertionFailureException::class, 'Integer "1" has a size of "1"')
    ->covers(Assertion::class, 'size');
