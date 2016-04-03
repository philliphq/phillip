<?php

/**
 * This file contains the AssertionFailureException class.
 *
 * @package philliphq/phillip
 *
 * @author James Dinsdale <hi@molovo.co>
 * @copyright Copyright 2016, James Dinsdale
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Phillip\Exceptions;

/**
 * Thrown when an assertion fails.
 *
 * If this exception is thrown within the body of a test,
 * it will cause the test to fail.
 *
 * @since v0.1.0
 */
class AssertionFailureException extends \Exception
{
}
