<?php

namespace Phillip;

use Molovo\Prompt\ANSI;
use Molovo\Prompt\Prompt;

class Output
{
    /**
     * The indicator used to represent a test in the output.
     */
    const INDICATOR = '◼';

    /**
     * ASCII escape codes for keypresses which are used for
     * navigating the output.
     */
    const LEFT_KEYPRESS  = "\x1b[D";
    const RIGHT_KEYPRESS = "\x1b[C";
    const UP_KEYPRESS    = "\x1b[A";
    const DOWN_KEYPRESS  = "\x1b[B";

    /**
     * The runner this output belongs to.
     *
     * @var Runner
     */
    private $runner;

    /**
     * The current cursor X coordinate.
     *
     * @var int
     */
    private $x = 0;

    /**
     * The current cursor Y coordinate.
     *
     * @var int
     */
    private $y = 0;

    /**
     * The highest X coordinate the cursor has reached.
     *
     * @var int
     */
    private $xMax = 0;

    /**
     * The highest Y coordinate the cursor has reached.
     *
     * @var int
     */
    private $yMax = 0;

    /**
     * Create the output object.
     *
     * @param Runner $runner The runner this output belongs to
     */
    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Print test results to the command line.
     */
    public function results()
    {
        $out = [''];

        $pre      = '  '.self::INDICATOR.' ';
        $total    = $this->runner->totalCount;
        $passed   = count($this->runner->passed);
        $failures = count($this->runner->failures);
        $errors   = count($this->runner->errors);
        $skipped  = count($this->runner->skipped);

        $out[] = ANSI::fg($pre, ANSI::GREEN).'Passes    '.$passed;
        $out[] = ANSI::fg($pre, ANSI::RED).'Failures  '.$failures;
        $out[] = ANSI::fg($pre, ANSI::YELLOW).'Errors    '.$errors;
        $out[] = ANSI::fg($pre, ANSI::MAGENTA).'Skipped   '.$skipped;

        foreach ($this->runner->failures as $test) {
            $out[] = '';
            $out[] = ANSI::fg('  FAILED - '.$test->name, ANSI::RED);
            $out[] = '  '.$test->getFailureMessage();
        }

        foreach ($this->runner->errors as $test) {
            $out[] = '';
            $out[] = ANSI::fg('  ERROR - '.$test->name, ANSI::YELLOW);
            $out[] = '  '.$test->getErrorMessage();
        }

        $color = ANSI::GREEN;
        if ($passed !== $total) {
            $color = ANSI::RED;
        }
        $count = ANSI::fg("$passed / $total", $color);
        $out[] = "\n  $count tests passed successfully.";

        Prompt::output(implode("\n", $out));

        if ($this->runner->options->coverage->enabled === true) {
            $this->coverage();
        }

        exit((int) ($passed !== $total));
    }

    /**
     * Print coverage information.
     */
    public function coverage()
    {
        $coverage = $this->options->coverage->enable !== false;
        if ($coverage) {
            Prompt::output(ANSI::fg("\n".'  Calculating Coverage...'."\n", ANSI::GRAY));

            $coverageData = xdebug_get_code_coverage();

            $include = [];
            $exclude = [];

            $includedFiles = $this->options->coverage->include;
            $excludedFiles = $this->options->coverage->exclude;

            if ($includedFiles) {
                if (!is_array($includedFiles)) {
                    $includedFiles = [$includedFiles];
                }

                foreach ($includedFiles as $glob) {
                    $glob    = $this->pwd.'/'.$glob;
                    $include = array_merge($include, $this->glob_recursive($glob));
                }
            }

            if ($excludedFiles) {
                if (!is_array($excludedFiles)) {
                    $excludedFiles = [$excludedFiles];
                }

                foreach ($excludedFiles as $glob) {
                    $glob    = $this->pwd.'/'.$glob;
                    $exclude = array_merge($exclude, $this->glob_recursive($glob));
                }
            }

            $processedCoverage = [
                'classes' => [],
                'files'   => [],
            ];
            foreach ($coverageData as $file => $lines) {
                if (in_array($file, $include) && !in_array($file, $exclude)) {
                    $fp    = fopen($file, 'r');
                    $class = $namespace = $buffer = '';
                    $i     = 0;
                    while (!$class) {
                        if (feof($fp)) {
                            break;
                        }

                        $buffer .= fread($fp, 512);
                        $tokens = @token_get_all($buffer);

                        if (strpos($buffer, '{') === false) {
                            continue;
                        }

                        for (;$i < count($tokens);$i++) {
                            if ($tokens[$i][0] === T_NAMESPACE) {
                                for ($j = $i + 1;$j < count($tokens); $j++) {
                                    if ($tokens[$j][0] === T_STRING) {
                                        $namespace .= '\\'.$tokens[$j][1];
                                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                                        break;
                                    }
                                }
                            }

                            if ($tokens[$i][0] === T_CLASS) {
                                for ($j = $i + 1;$j < count($tokens);$j++) {
                                    if ($tokens[$j] === '{') {
                                        $class = $tokens[$i + 2][1];
                                    }
                                }
                            }
                        }
                    }

                    $covered = array_filter($lines, function ($value, $line) {
                        return $value === 1;
                    }, ARRAY_FILTER_USE_BOTH);

                    if ($class) {
                        $name                                = Str::namespaced("$namespace\\$class");
                        $processedCoverage['classes'][$name] = [
                            'covered' => count($covered),
                            'total'   => count($lines),
                        ];
                    } else {
                        $name                              = str_replace($this->pwd, '', $file);
                        $processedCoverage['files'][$name] = [
                            'covered' => count($covered),
                            'total'   => count($lines),
                        ];
                    }
                }
            }

            $maxLen = 0;
            foreach ($processedCoverage['classes'] as $name => $values) {
                if (strlen($name) > $maxLen) {
                    $maxLen = strlen($name);
                }
            }
            foreach ($processedCoverage['files'] as $name => $values) {
                if (strlen($name) > $maxLen) {
                    $maxLen = strlen($name);
                }
            }

            $maxLen += 3;

            foreach ($processedCoverage as $type => $data) {
                if (count($data) !== 0) {
                    Prompt::output('  '.Str::title($type));
                }
                foreach ($data as $name => $values) {
                    $name    = str_pad($name, $maxLen, ' ');
                    $covered = $values['covered'];
                    $total   = $values['total'];
                    $value   = $covered.' / '.$total;

                    if ((($covered / $total) * 100) < 25) {
                        $value = ANSI::fg($value, ANSI::RED);
                    }

                    if ((($covered / $total) * 100) < 75) {
                        $value = ANSI::fg($value, ANSI::YELLOW);
                    }

                    if ((($covered / $total) * 100) >= 75) {
                        $value = ANSI::fg($value, ANSI::GREEN);
                    }

                    Prompt::output('  '.$name.' '.$value);
                }
            }
        }
    }

    /**
     * Update the table with a color to show test state.
     *
     * @param int $x     The X coordinate
     * @param int $y     The Y coordinate
     * @param int $color The color to output
     */
    public function updateTable($x, $y, $color)
    {
        $this->goToXY($x, $y);
        echo ANSI::fg(self::INDICATOR, $color);
        $this->x++;
        $this->goToXY($this->xMax, $this->yMax);
    }

    /**
     * Go to defined X,Y coordinates in the terminal output.
     *
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     */
    public function goToXY($x, $y)
    {
        if ($this->x < $x) {
            while ($this->x < $x) {
                echo self::RIGHT_KEYPRESS;
                $this->x++;
            }
        }

        if ($this->x > $x) {
            while ($this->x > $x) {
                echo self::LEFT_KEYPRESS;
                $this->x--;
            }
        }

        if ($this->y > $y) {
            while ($this->y > $y) {
                echo self::UP_KEYPRESS;
                $this->y--;
            }
        }

        if ($this->y < $y) {
            while ($this->y < $y) {
                echo self::DOWN_KEYPRESS;
                $this->y++;
            }
        }
    }

    /**
     * Get the line width of the current terminal.
     *
     * @return int
     */
    private function getLineWidth()
    {
        $line_width = isset($_ENV['COLUMNS']) && $_ENV['COLUMNS']
                    ? $_ENV['COLUMNS']
                    : 0;

        if ($line_width === 0) {
            $line_width = exec('tput cols');
        }

        return $line_width;
    }

    /**
     * Ouput the table of tests to the command line.
     */
    public function table()
    {
        $testsPerCol = 25;
        $count       = count($this->runner->tests);

        $line_width = $this->getLineWidth();

        switch (true) {
            case $line_width <= 50:
                $testsPerCol = 10;
                break;
            case $line_width <= 70:
                $testsPerCol = 15;
                break;
            case $line_width <= 80:
                $testsPerCol = 20;
                break;
            case $line_width > 100:
                $testsPerCol = 50;
                break;
            case $line_width > 50 && $line_width <= 100:
            default:
                $testsPerCol = 25;
                break;
        }

        echo '  ';
        $this->x = 2;
        $this->y = 0;
        foreach ($this->runner->tests as $i => $test) {
            $test->setPosition($this->x, $this->y);
            echo ANSI::fg('◼ ', ANSI::GRAY);
            $this->x += 2;

            $j = $i + 1;
            if (($j % ($testsPerCol * 4)) === 0) {
                echo "  $j";
            }
            if (($j % $testsPerCol) === 0 & $j !== $count) {
                echo "\n  ";
                $this->y += 1;
                $this->x = 2;
            }
        }

        echo "\n";
        $this->y++;
        $this->x = 0;

        $this->xMax = $this->x;
        $this->yMax = $this->y;
        $this->goToXY($this->xMax, $this->yMax);
    }
}
