#!/usr/bin/env php
<?php
/**
 * @file Controller file for compiling a resume.
 */

namespace AKlump\Resume;

use AKlump\LoftLib\Component\Bash\Bash;
use AKlump\LoftLib\Component\Bash\Color;
use AKlump\LoftLib\Component\Storage\FilePath;

define('ROOT', dirname(__FILE__));
define('DEFAULT_THEME', 'aklump');

require_once ROOT . '/vendor/autoload.php';

$readDataFrom = [];
$baseData = ROOT . "/data/base";
$readDataFrom[] = $baseData;

//
// Convert CLI options:
//
// w = website mode; the content will be displayed online, publicly
// t = which theme to use other than DEFAULT_THEME
// f = focus data, this is the name of a directory in the same directory as base.  If present these files will override the base data files.
// o = output path, path to a directory where the output should be rendered relative to the root of the project
//
$opts = getopt('wf:t:o:');
$media = array_key_exists('w', $opts) ? 'website' : 'print';
$theme = $opts['t'] ?? DEFAULT_THEME;
$outputDir = ROOT . '/' . ($opts['o'] ?? '/dist/default');

if ($opts['f'] ?? null) {
    $readDataFrom[] = dirname($baseData) . '/' . trim($opts['f'], '/');
}

try {
    $builder = new Builder($readDataFrom, ROOT . "/themes/$theme");

    // Output the index.html
    $html = $builder->getHtml($media);
    $obj = new FilePath($outputDir . '/index.html');
    $obj->put($html)->save();

    // Copy the theme's css.
    $buildDir = $obj = new FilePath($outputDir);
    $buildDir->to('resume.css')->copy(ROOT . "/themes/$theme/resume.css");

    // Copy fonts if they exist.
    $fontsDir = ROOT . "/themes/$theme/fonts/";
    if (is_dir($fontsDir) && ($to = $buildDir->getPath())) {
        Bash::exec([
            'test -e ',
            $fontsDir,
            '&& rsync -a --delete',
            $fontsDir,
            "$to/fonts/",
        ]);
    }
} catch (\Exception $exception) {
    print Color::wrap('red', $exception->getMessage()) . PHP_EOL;
    exit(1);
}
print Color::wrap('green', "Your resume is available at :" . $buildDir->getPath() . '/index.html') . PHP_EOL;
exit(0);
