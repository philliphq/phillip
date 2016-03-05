<?php

use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test equal with two equal integers', function (Test $test) {
    $test->does(1)->equal(1);
});

test('Test equal with two equal strings', function (Test $test) {
    $test->does('test')->equal('test');
});

test('Test equal with two equal arrays', function (Test $test) {
    $test->does([1, 2, 3])->equal([1, 2, 3]);
});

test('Test equal with two non-equal integers', function (Test $test) {
    $test->does(1)->equal(2);
})->expect(AssertionFailureException::class, '"1" is not equal to "2"');

test('Test equal with two non-equal strings', function (Test $test) {
    $test->does('this')->equal('that');
})->expect(AssertionFailureException::class, '"this" is not equal to "that"');

test('Test equal with two non-equal arrays', function (Test $test) {
    $test->does([1, 2, 3])->equal([3, 2, 1]);
})->expect(AssertionFailureException::class, 'Array is not equal to Array');

test('Test not equal two non-equal integers', function (Test $test) {
    $test->does(1)->not()->equal(2);
});

test('Test not equal with two non-equal strings', function (Test $test) {
    $test->does('this')->not()->equal('that');
});

test('Test not equal with two non-equal arrays', function (Test $test) {
    $test->does([1, 2, 3])->not()->equal([3, 2, 1]);
});

test('Test not equal with two non-equal integers', function (Test $test) {
    $test->does(1)->not()->equal(1);
})->expect(AssertionFailureException::class, '"1" is equal to "1"');

test('Test not equal with two non-equal strings', function (Test $test) {
    $test->does('this')->not()->equal('this');
})->expect(AssertionFailureException::class, '"this" is equal to "this"');

test('Test not equal with two non-equal arrays', function (Test $test) {
    $test->does([1, 2, 3])->not()->equal([1, 2, 3]);
})->expect(AssertionFailureException::class, 'Array is equal to Array');
