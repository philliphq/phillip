<?php

/**
 * This file contains the Yaml class.
 *
 * @package philliphq/phillip
 *
 * @author James Dinsdale <hi@molovo.co>
 * @copyright Copyright 2016, James Dinsdale
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Phillip;

use Phillip\Exceptions\FileNotFoundException;

/**
 * Uses the PHP-YAML extension to parse YAML data, or mustangostang/spyc
 * as a fallback if the extension is not installed.
 *
 * @since v0.1.0
 */
class Yaml
{
    /**
     * Parse YAML data, and return an array.
     *
     * @param string $yaml The YAML data
     *
     * @return array
     */
    public static function parse($yaml)
    {
        $options = null;
        if (function_exists('yaml_parse')) {
            $options = yaml_parse($yaml);
        } else {
            $options = spyc_load($yaml);
        }

        if ($options === null) {
            throw new YamlParseException('There was an error parsing your YAML front matter');
        }

        return $options;
    }

    /**
     * Parse a YAML file, and return an array.
     *
     * @param string $filename The filename
     *
     * @return array
     */
    public static function parseFile($filename)
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException('The YAML file '.$filename.' could not be found.');
        }

        return static::parse(file_get_contents($filename));
    }

    /**
     * Convert an array into a YAML string.
     *
     * @param array $array The data to convert
     *
     * @return string The converted YAML
     */
    public static function convert(array $array)
    {
        if (function_exists('yaml_emit')) {
            $yaml = yaml_emit($array);

            // PHP-Yaml adds strange separators to the outputted YAML, we do
            // some manipulation of the outputted string to remove these.
            $yaml = explode("\n", $yaml);
            array_shift($yaml);
            array_pop($yaml);
            array_pop($yaml);

            return implode("\n", $yaml);
        }

        return spyc_dump($array);
    }

    /**
     * Convert an array to YAML and store it in a file.
     *
     * @param array  $array    The data to convert
     * @param string $filename The filename to store it in
     *
     * @return bool
     */
    public static function dump(array $array, $filename)
    {
        return file_put_contents($filename, static::convert($array));
    }
}
