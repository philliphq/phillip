<?php

use Molovo\Object\Object;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test anInstanceOf with class constant comparison', function (Test $test) {
    $test->is(new stdClass)->anInstanceOf(stdClass::class);
});

test('Test anInstanceOf with string comparison', function (Test $test) {
    $test->is(new stdClass)->anInstanceOf('stdClass');
});

test('Test anInstanceOf with an incorrect object', function (Test $test) {
    $test->is(new stdClass)->anInstanceOf(Test::class);
})->expect(AssertionFailureException::class, 'Object of type "stdClass" is not an instance of "Phillip\Test"');

test('Test anInstanceOf with an integer', function (Test $test) {
    $test->is(1)->anInstanceOf(Test::class);
})->expect(AssertionFailureException::class, '"1" is not an instance of "Phillip\Test"');

test('Test anInstanceOf with a string', function (Test $test) {
    $test->is('that')->anInstanceOf(Test::class);
})->expect(AssertionFailureException::class, '"that" is not an instance of "Phillip\Test"');

test('Test anInstanceOf with an empty array', function (Test $test) {
    $test->is([])->anInstanceOf(Test::class);
})->expect(AssertionFailureException::class, 'Array is not an instance of "Phillip\Test"');

test('Test anInstanceOf with an array with values', function (Test $test) {
    $test->is([1, 2, 3])->anInstanceOf(Test::class);
})->expect(AssertionFailureException::class, 'Array is not an instance of "Phillip\Test"');

test('Test not anInstanceOf with an incorrect object', function (Test $test) {
    $test->is(new stdClass)->not()->anInstanceOf(Test::class);
});

test('Test not anInstanceOf with an integer', function (Test $test) {
    $test->is(1)->not()->anInstanceOf(Test::class);
});

test('Test not anInstanceOf with a string', function (Test $test) {
    $test->is('that')->not()->anInstanceOf(Test::class);
});

test('Test not anInstanceOf with an empty array', function (Test $test) {
    $test->is([])->not()->anInstanceOf(Test::class);
});

test('Test not anInstanceOf with an array with values', function (Test $test) {
    $test->is([1, 2, 3])->not()->anInstanceOf(Test::class);
});

test('Test not anInstanceOf with class constant comparison', function (Test $test) {
    $test->is(new stdClass)->not()->anInstanceOf(stdClass::class);
})->expect(AssertionFailureException::class, 'Object of type "stdClass" is an instance of "stdClass"');

test('Test not anInstanceOf with string comparison', function (Test $test) {
    $test->is(new stdClass)->not()->anInstanceOf('stdClass');
})->expect(AssertionFailureException::class, 'Object of type "stdClass" is an instance of "stdClass"');
