<?php

use Phillip\Assertion;
use Phillip\Exceptions\AssertionFailureException;

/*
 * Test `boolean()` method.
 */
test('Test boolean with true', function ($t) {
    $t->is(true)->boolean();
})
    ->covers(Assertion::class, 'boolean');

test('Test boolean with false', function ($t) {
    $t->is(false)->boolean();
})
    ->covers(Assertion::class, 'boolean');

test('Test boolean with int', function ($t) {
    $t->is(1)->boolean();
})
    ->expect(AssertionFailureException::class, '1 is not a boolean')
    ->covers(Assertion::class, 'boolean');

test('Test boolean with string', function ($t) {
    $t->is('true')->boolean();
})
    ->expect(AssertionFailureException::class, '"true" is not a boolean')
    ->covers(Assertion::class, 'boolean');

/*
 * Test `true()` method
 */

test('Test true with boolean', function ($t) {
    $t->is(true)->true();
})
    ->covers(Assertion::class, 'boolean');

test('Test true with string', function ($t) {
    $t->is('true')->true();
})
    ->expect(AssertionFailureException::class, '"true" is not true')
    ->covers(Assertion::class, 'true');

test('Test true with false boolean', function ($t) {
    $t->is(false)->true();
})
    ->expect(AssertionFailureException::class, 'FALSE is not true')
    ->covers(Assertion::class, 'true');

/*
 * Test `false()` method
 */

test('Test false with boolean', function ($t) {
    $t->is(false)->false();
})
    ->covers(Assertion::class, 'boolean');

test('Test false with string', function ($t) {
    $t->is('false')->false();
})
    ->expect(AssertionFailureException::class, '"false" is not false')
    ->covers(Assertion::class, 'true');

test('Test false with true boolean', function ($t) {
    $t->is(true)->false();
})
    ->expect(AssertionFailureException::class, 'TRUE is not false')
    ->covers(Assertion::class, 'false');
