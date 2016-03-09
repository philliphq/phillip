<?php

namespace Phillip;

use Molovo\Graphite\Graphite;
use Molovo\Graphite\Table;
use Molovo\Str\Str;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Coverage
{
    /**
     * Line statii as returned from Xdebug.
     */
    const USED     = 1;
    const NOT_USED = -1;
    const DEAD     = -2;
    /**
     * An array containing details of lines marked as covered
     * by the tests being run.
     *
     * @var array
     */
    private $covered = [];

    /**
     * The current test runner.
     *
     * @var Runner
     */
    private $runner;

    /**
     * The coverage data, organised by class and file name.
     *
     * @var [type]
     */
    private $processed;

    /**
     * Create the coverage object.
     *
     * @param Runner $runner The current test runner
     */
    public function __construct(Runner $runner)
    {
        $this->runner = $runner;
        $this->output = new Graphite;
        $this->output->setGlobalIndent(2);
    }

    /**
     * Mark a function as covered.
     *
     * @param string $name The function name
     */
    public function addCoveredFunction($name)
    {
        return $this->addRef(new ReflectionFunction($name));
    }

    /**
     * Mark a class method as covered.
     *
     * @param string $object The class name
     * @param string $method The method name
     */
    public function addCoveredMethod($class, $method)
    {
        return $this->addRef(new ReflectionMethod($class, $method));
    }

    /**
     * Calculate the coverage.
     */
    public function calculate()
    {
        $this->getIncludedFiles();
        $this->processRawData();
    }

    /**
     * Output the coverage tables.
     *
     * @return string The rendered tables
     */
    public function output()
    {
        $data = [
            'classes' => [],
            'files'   => [],
        ];
        foreach ($this->processed as $type => $classes) {
            foreach ($classes as $name => $values) {
                $covered = $values['covered'];
                $total   = $values['total'];
                $pct     = (((int) $covered / (int) $total) * 100);

                $covered    = str_pad((string) $covered, 7, ' ', STR_PAD_LEFT);
                $total      = str_pad((string) $total, 5, ' ', STR_PAD_LEFT);
                $percentage = number_format($pct, 2).'%';
                $percentage = str_pad((string) $percentage, 10, ' ', STR_PAD_LEFT);

                if ($pct < 25) {
                    $covered    = $this->output->red($covered);
                    $total      = $this->output->red($total);
                    $percentage = $this->output->red($percentage);
                }

                if ($pct < 75) {
                    $covered    = $this->output->black->yellow($covered);
                    $total      = $this->output->black->yellow($total);
                    $percentage = $this->output->black->yellow($percentage);
                }

                if ($pct >= 75) {
                    $covered    = $this->output->green($covered);
                    $total      = $this->output->green($total);
                    $percentage = $this->output->green($percentage);
                }

                $data[$type][] = [$name, $covered, $total, $percentage];
            }
        }

        $out = [];
        if (count($data['classes']) > 0) {
            array_unshift($data['classes'], ['Class', 'Covered', 'Lines', 'Percentage']);

            $out[] = new Table($data['classes'], [
                'headerColor'     => Graphite::YELLOW,
                'columnSeparator' => '',
                'headerSeparator' => '',
                'cellPadding'     => 1,
            ]);
        }

        if (count($data['files']) > 0) {
            array_unshift($data['files'], ['File', 'Covered', 'Lines', 'Percentage']);

            $out[] = new Table($data['files'], [
                'headerColor'     => Graphite::YELLOW,
                'columnSeparator' => '',
                'headerSeparator' => '',
                'cellPadding'     => 1,
            ]);
        }

        return $this->output->render(implode("\n\n", $out));
    }

    /**
     * Get the filename and lines covered by a reflected function/method.
     *
     * @param ReflectionFunctionAbstract $ref The reflect function/method
     */
    public function addRef(ReflectionFunctionAbstract $ref)
    {
        // Get the method metadata
        $file  = $ref->getFileName();
        $start = $ref->getStartLine();
        $end   = $ref->getEndLine();

        // If a key for the file does not yet exist, then create it
        if (!isset($this->covered[$file])) {
            $this->covered[$file] = [];
        }

        // Add each of the lines within the function/method to the array
        $i = $start;
        while ($i++ <= $end) {
            if (!in_array($i, $this->covered[$file])) {
                $this->covered[$file][] = $i;
            }
        }
    }

    /**
     * Get the list of files to be included/excluded in coverage calculations.
     *
     * @return array
     */
    private function getIncludedFiles()
    {
        // Retrieve the include and exclude lists
        // from the options
        $include = Options::get('coverage.include');
        $exclude = Options::get('coverage.exclude');

        // Create empty arrays to store our filenames in
        $includedFiles = [];
        $excludedFiles = [];

        // If we have marked files as included
        if ($include !== null) {
            // Convert a string to an array so that
            // we only have to loop once
            if (!is_array($include)) {
                $include = [$include];
            }

            // Loop through each of the include glob strings
            foreach ($include as $glob) {
                // Prepend the current working directory to the glob
                $glob = $this->runner->pwd.'/'.$glob;

                // Add the glob results to the included files array
                $includedFiles = array_merge($includedFiles, glob_recursive($glob));
            }
        }

        // If we have marked files as excluded
        if ($exclude !== null) {
            // Convert a string to an array so that
            // we only have to loop once
            if (!is_array($exclude)) {
                $exclude = [$exclude];
            }

            // Loop through each of the include glob strings
            foreach ($exclude as $glob) {
                // Prepend the current working directory to the glob
                $glob = $this->runner->pwd.'/'.$glob;

                // Add the glob results to the excluded files array
                $excludedFiles = array_merge($excludedFiles, glob_recursive($glob));
            }
        }

        // Store the results
        $this->includedFiles = $includedFiles ?: [];
        $this->excludedFiles = $excludedFiles ?: [];
    }

    /**
     * Check if a file should be included in coverage calculations.
     *
     * @param string $file The filename
     *
     * @return bool
     */
    private function isCovered($file)
    {
        return in_array($file, $this->includedFiles)
            && !in_array($file, $this->excludedFiles)
            && isset($this->covered[$file]);
    }

    /**
     * Parse a file to retrieve the class (and namespace) within it.
     *
     * @param string $file The filename
     *
     * @return string|null
     */
    private function getClassForFile($file)
    {
        // Open a file pointer to the file
        $fp = fopen($file, 'r');

        // Initialise some variables
        $class = $namespace = $buffer = '';
        $i     = 0;

        // Loop through each line of the file until a class is found
        while (!$class) {
            // If we reach the end of the file then exit the loop
            if (feof($fp)) {
                break;
            }

            // Read a portion from the file and append it to the buffer
            $buffer .= fread($fp, 512);

            // Scan the file for tokens
            //
            // We suppress errors here, as we expect errors as we are
            // only parsing a portion of the file at a time
            $tokens = @token_get_all($buffer);

            // Don't bother trying to parse the output until we
            // can see an opening curly brace
            if (strpos($buffer, '{') === false) {
                continue;
            }

            // Loop through each of the found tokens
            for (;$i < count($tokens);$i++) {
                // Check if the namespace keyword has been found yet
                if ($tokens[$i][0] === T_NAMESPACE) {
                    // Continue looping through the found tokens
                    for ($j = $i + 1;$j < count($tokens); $j++) {
                        // A string immediately after the namespace keyword
                        // is the name of the namespace
                        if ($tokens[$j][0] === T_STRING) {
                            // Add each section of the namespace to
                            // our predefined variable
                            $namespace .= '\\'.$tokens[$j][1];
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            // If we reach a curly brace or a semicolon
                            // we've got the full namespace
                            break;
                        }
                    }
                }

                // Check if the class keyword has been found yet
                if ($tokens[$i][0] === T_CLASS) {
                    // Continue looping through the found tokens
                    for ($j = $i + 1;$j < count($tokens);$j++) {
                        // When we reach the curly brace, store the
                        // class name in the predefined variable
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        // If no class is found, return null
        if (!$class) {
            return;
        }

        // Return the fully-qualified class name
        return Str::namespaced("$namespace\\$class");
    }

    /**
     * Process the raw coverage data.
     *
     * @return array
     */
    private function processRawData()
    {
        // Get the raw data from Xdebug
        $data = xdebug_get_code_coverage();

        // Create an array for storing processed coverage data
        $processed = [
            'classes' => [],
            'files'   => [],
        ];

        // Loop through each of the files in the coverage data
        foreach ($data as $file => $lines) {
            // Check if this file is covered
            if ($this->isCovered($file)) {
                // Filter out dead code from the results
                $filter = function ($value, $line = null) {
                    return $value !== self::DEAD;
                };
                $executable = array_filter($lines, $filter);

                // Filter out unused code to get the lines covered
                $object = $this;
                $filter = function ($value, $line) use ($object, $file) {
                    if (in_array($line, $object->covered[$file])) {
                        return $value === 1;
                    }
                };
                $covered = array_filter($lines, $filter, ARRAY_FILTER_USE_BOTH);

                // Store the counts for this file
                $coverage = [
                    'covered' => count($covered),
                    'total'   => count($executable),
                ];

                // If a classname exists in the file,
                // store the counts against it
                if ($class = $this->getClassForFile($file)) {
                    $processed['classes'][$class] = $coverage;
                    continue;
                }

                // If no class has been found, store the counts
                // directly against the file name
                $name = str_replace($this->runner->pwd.'/', '', $file);

                $processed['files'][$name] = $coverage;
            }
        }

        // Store the processed data
        return $this->processed = $processed;
    }
}
