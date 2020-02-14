<?php

/*
 * This file is part of PHAR Utils.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Seld\PharUtils;

class Linter
{
    /**
     * Lints all php files inside a given phar with the current PHP version
     *
     * @param string $path Phar file path
     */
    public static function lint($path)
    {
        foreach (new \RecursiveIteratorIterator(new \Phar($path)) as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (substr($file, -4) === '.php') {
                $descriptorspec = array(
                   0 => array("pipe", "r"),
                   1 => array("pipe", "w"),
                   2 => array("pipe", "w")
                );

                $process = proc_open((defined('PHP_BINARY') ? PHP_BINARY : 'php').' -l', $descriptorspec, $pipes);
                if (is_resource($process)) {
                    fwrite($pipes[0], file_get_contents((string) $file));
                    fclose($pipes[0]);

                    $stdout = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    $stderr = stream_get_contents($pipes[2]);
                    fclose($pipes[2]);

                    $exitCode = proc_close($process);

                    if ($exitCode !== 0) {
                        throw new \UnexpectedValueException('Failed linting '.$file.': '.$stderr);
                    }
                } else {
                    throw new \RuntimeException('Could not start linter process');
                }
            }
        }
    }
}
