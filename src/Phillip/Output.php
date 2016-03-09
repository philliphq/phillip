<?php

namespace Phillip;

use Molovo\Graphite\Box;
use Molovo\Graphite\Graphite;
use Molovo\Graphite\Table;

class Output extends Graphite
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
        $this->setGlobalIndent(2);
    }

    /**
     * Print test results to the command line.
     */
    public function results()
    {
        $out = [''];

        $total    = $this->runner->totalCount;
        $passed   = count($this->runner->passed);
        $failures = count($this->runner->failures);
        $errors   = count($this->runner->errors);
        $skipped  = count($this->runner->skipped);

        $results   = [];
        $results[] = $this->green(self::INDICATOR).' Passes    '.$passed;
        $results[] = $this->red(self::INDICATOR).' Failures  '.$failures;
        $results[] = $this->yellow(self::INDICATOR).' Errors    '.$errors;
        $results[] = $this->magenta(self::INDICATOR).' Skipped   '.$skipped;

        $box = new Box($results, [
            'borderColor' => self::YELLOW,
            'paddingX'    => 1,
        ]);
        $box->setTitle('Results');
        $out[] = $box;

        foreach ($this->runner->failures as $test) {
            $out[] = '';
            $out[] = $this->red('FAILED - '.$test->name);
            $out[] = $test->getFailureMessage();
        }

        foreach ($this->runner->errors as $test) {
            $out[] = '';
            $out[] = $this->yellow('ERROR - '.$test->name);
            $out[] = $test->getErrorMessage();
        }

        $count = $this->green("$passed / $total");
        if ($passed !== $total) {
            $count = $this->red("$passed / $total");
        }
        $out[] = '';
        $out[] = "$count tests passed successfully.";

        echo $this->render(implode("\n", $out));

        if ($this->runner->options->coverage->enable === true) {
            echo "\n";
            echo $this->gray->render('Calculating Coverage...');
            echo "\n";

            $this->runner->coverage->calculate();
            echo $this->runner->coverage->output();
        }

        exit((int) ($passed !== $total));
    }

    /**
     * Print help text to the command line.
     */
    public function help()
    {
        $this->setGlobalIndent(0);
        echo $this->yellow->render('Usage:');
        echo $this('  phillip [options] [file|directory]');

        echo $this('');
        echo $this->yellow->render('Options:');
        echo $this('  -h, --help           Show help text and exit.');
        echo $this('  -v, --version        Show version information and exit.');
        echo $this('  -c, --coverage       Show coverage data.');
        echo $this('  -s, --suite <suite>  Run a predefined test suite.');
        echo $this('  -r, --random         Run tests within each suite in random order.');
    }

    /**
     * Print version information to the command line.
     */
    public function version()
    {
        $this->setGlobalIndent(0);
        $version = file_get_contents(__DIR__.'/../../.version');
        echo $this->yellow->render('Phillip');
        echo $this('Version '.trim($version));
    }

    /**
     * Print coverage information.
     */
    public function coverage()
    {
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
        echo $this->setColor($color)->encode(self::INDICATOR);
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
            echo $this->gray('◼ ');
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
