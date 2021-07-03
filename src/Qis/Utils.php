<?php
/**
 * Utils class
 *
 * @package Qis
 */

namespace Qis;

/**
 * Recursive glob
 *
 * @package Qis
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Utils
{
    /**
     * Recursive Glob
     *
     * @param string $pattern Pattern
     * @param int $flags Flags to pass to glob
     * @param string $path Path to glob in
     * @return array
     */
    public static function rglob($pattern, $flags = 0, $path = '')
    {
        if ($path == '\\' || $path == '/') {
            // We don't want to try to find all the paths from root
            // It takes too long
            return [];
        }

        if (!$path && ($dir = dirname($pattern)) != '.') {
            if ($dir == '\\' || $dir == '/') {
                // This means the pattern starts with root
                // This takes too long
                return [];
            }
            return self::rglob(
                basename($pattern),
                $flags, $dir . DIRECTORY_SEPARATOR
            );
        }

        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);

        foreach ($paths as $p) {
            $files = array_merge(
                $files, self::rglob($pattern, $flags, $p . DIRECTORY_SEPARATOR)
            );
        }

        return $files;
    }

    /**
     * Find common root from a list of file paths
     *
     * @param array $list A list of file paths
     * @return string
     */
    public static function findCommonRoot($list)
    {
        $longest = 0;

        if (!is_array($list)) {
            $list = [$list];
        }

        if (empty($list)) {
            return '';
        }

        // Find the longest item
        foreach ($list as $item) {
            if (is_object($item)) {
                continue;
            }
            if (strlen($item) > $longest) {
                $longest = strlen($item);
            }
        }

        // Inspect each item by character until we find a difference, then
        // return the previous
        for ($i = 1; $i <= $longest; $i++) {
            $common = [];
            foreach ($list as $item) {
                $common[] = substr($item, 0, $i);
            }

            if (count(array_unique($common)) > 1) {
                return substr($common[0], 0, -1);
            }
        }

        return $list[0];
    }
}
