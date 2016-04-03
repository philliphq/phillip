<?php

use Molovo\Object\Object;
use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test anArray with an empty array', function (Test $test) {
    $test->is([])->anArray();
})
    ->covers(Assertion::class, 'anArray');

test('Test anArray with an array with values', function (Test $test) {
    $test->is([1, 2, 3])->anArray();
})
    ->covers(Assertion::class, 'anArray');

test('Test anArray with an integer', function (Test $test) {
    $test->is(0)->anArray();
})
    ->expect(AssertionFailureException::class, '0 is not an array')
    ->covers(Assertion::class, 'anArray');

test('Test anArray with a string', function (Test $test) {
    $test->is('that')->anArray();
})
    ->expect(AssertionFailureException::class, '"that" is not an array')
    ->covers(Assertion::class, 'anArray');

test('Test anArray with an object', function (Test $test) {
    $test->is(new stdClass)->anArray();
})
    ->expect(AssertionFailureException::class, 'Object of type "stdClass" is not an array')
    ->covers(Assertion::class, 'anArray');

test('Test not anArray with an integer', function (Test $test) {
    $test->is(0)->not()->anArray();
})
    ->covers(Assertion::class, 'anArray');

test('Test not anArray with a string', function (Test $test) {
    $test->is('that')->not()->anArray();
})
    ->covers(Assertion::class, 'anArray');

test('Test not anArray with an object', function (Test $test) {
    $test->is(new stdClass)->not()->anArray();
})
    ->covers(Assertion::class, 'anArray');

test('Test not anArray with an empty array', function (Test $test) {
    $test->is([])->not()->anArray();
})
    ->expect(AssertionFailureException::class, 'Array is an array')
    ->covers(Assertion::class, 'anArray');

test('Test not anArray with an array with values', function (Test $test) {
    $test->is([1, 2, 3])->not()->anArray();
})
    ->expect(AssertionFailureException::class, 'Array is an array')
    ->covers(Assertion::class, 'anArray');
