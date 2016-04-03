<?php

use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;
use Phillip\Test;

test('Test equivalent with two equal integers', function (Test $test) {
    $test->is(1)->equivalentTo(1);
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test equivalent with equivalent integer and string', function (Test $test) {
    $test->is(1)->equivalentTo('1');
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test equivalent with equivalent integer and float', function (Test $test) {
    $test->is(1)->equivalentTo(1.00);
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test equivalent with two equal strings', function (Test $test) {
    $test->is('test')->equivalentTo('test');
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test equivalent with two equal arrays', function (Test $test) {
    $test->is([1, 2, 3])->equivalentTo([1, 2, 3]);
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test equivalent with two equivalent arrays with mixed types', function (Test $test) {
    $test->is([1, '2', 3.00])->equivalentTo([1.00, 2, '3']);
})
    ->covers(Assertion::class, 'equivalentTo');

test('Test unsuccessful assertion of two non-equivalent integers', function (Test $test) {
    $test->is(1)->equivalentTo(2);
})
    ->expect(AssertionFailureException::class, '1 is not equivalent to 2')
    ->covers(Assertion::class, 'equivalentTo');

test('Test unsuccessful assertion of two non-equivalent strings', function (Test $test) {
    $test->is('this')->equivalentTo('that');
})
    ->expect(AssertionFailureException::class, '"this" is not equivalent to "that"')
    ->covers(Assertion::class, 'equivalentTo');

test('Test unsuccessful assertion of two non-equivalent arrays', function (Test $test) {
    $test->is([1, 2, 3])->equivalentTo([3, 2, 1]);
})
    ->expect(AssertionFailureException::class, 'Array is not equivalent to Array')
    ->covers(Assertion::class, 'equivalentTo');

test('Test successful negative assertion of two non-equivalent integers', function (Test $test) {
    $test->is(1)->not()->equivalentTo(2);
})
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');

test('Test successful negative assertion of two non-equivalent strings', function (Test $test) {
    $test->is('this')->not()->equivalentTo('that');
})
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');

test('Test successful negative assertion of two non-equivalent arrays', function (Test $test) {
    $test->is([1, 2, 3])->not()->equivalentTo([3, 2, 1]);
})
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');

test('Test unsuccessful negative assertion of two non-equivalent integers', function (Test $test) {
    $test->is(1)->not()->equivalentTo(1);
})
    ->expect(AssertionFailureException::class, '1 is equivalent to 1')
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');

test('Test unsuccessful negative assertion of two non-equivalent strings', function (Test $test) {
    $test->is('this')->not()->equivalentTo('this');
})
    ->expect(AssertionFailureException::class, '"this" is equivalent to "this"')
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');

test('Test unsuccessful negative assertion of two non-equivalent arrays', function (Test $test) {
    $test->is([1, 2, 3])->not()->equivalentTo([1, 2, 3]);
})
    ->expect(AssertionFailureException::class, 'Array is equivalent to Array')
    ->covers(Assertion::class, 'equivalentTo')
    ->covers(Assertion::class, 'not');
