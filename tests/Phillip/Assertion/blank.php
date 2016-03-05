<?php

use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test blank with an empty string', function (Test $test) {
    $test->is('')->blank();
});

test('Test blank with an integer', function (Test $test) {
    $test->is(0)->blank();
});

test('Test blank with an empty array', function (Test $test) {
    $test->is([])->blank();
});

test('Test blank with an undefined variable', function (Test $test) {
    @$test->is($var)->blank();
});

test('Test blank with a non-empty string', function (Test $test) {
    $test->is('test')->blank();
})->expect(AssertionFailureException::class, '"test" is not empty');

test('Test blank with a non-zero integer', function (Test $test) {
    $test->is(1)->blank();
})->expect(AssertionFailureException::class, '"1" is not empty');

test('Test blank with a non-empty array', function (Test $test) {
    $test->is([1])->blank();
})->expect(AssertionFailureException::class, 'Array is not empty');

test('Test blank with a defined variable', function (Test $test) {
    $var = 'value';
    $test->is($var)->blank();
})->expect(AssertionFailureException::class, '"value" is not empty');

test('Test not blank with a non-empty string', function (Test $test) {
    $test->is('test')->not()->blank();
});

test('Test not blank with a non-zero integer', function (Test $test) {
    $test->is(1)->not()->blank();
});

test('Test not blank with a non-empty array', function (Test $test) {
    $test->is([1])->not()->blank();
});

test('Test not blank with a defined variable', function (Test $test) {
    $var = 'value';
    $test->is($var)->not()->blank();
});

test('Test not blank with an empty string', function (Test $test) {
    $test->is('')->not()->blank();
})->expect(AssertionFailureException::class, '"" is empty');

test('Test not blank with a zero integer', function (Test $test) {
    $test->is(0)->not()->blank();
})->expect(AssertionFailureException::class, '"0" is empty');

test('Test not blank with an empty array', function (Test $test) {
    $test->is([])->not()->blank();
})->expect(AssertionFailureException::class, 'Array is empty');

test('Test not blank with an undefined variable', function (Test $test) {
    @$test->is($var)->not()->blank();
})->expect(AssertionFailureException::class, 'NULL is empty');
