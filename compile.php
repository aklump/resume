<?php
/**
 * @file Controller file for compiling a resume.s
 */

namespace AKlump\Resume;

use AKlump\LoftLib\Component\Bash\Bash;
use AKlump\LoftLib\Component\Storage\FilePath;

define('ROOT', dirname(__FILE__));
define('DEFAULT_THEME', 'aklump');
define('DIST_DIR', ROOT . '/' . DIST_DIR;

require_once ROOT . '/vendor/autoload.php';

$readDataFrom = [];
$baseData = ROOT . "/data/base";
$readDataFrom[] = $baseData;

// Convert CLI options

// w = website mode; the content will be displayed online, publicly
$opts = getopt('wf:t:');
$media = array_key_exists('w', $opts) ? 'website' : 'print';
$theme = $opts['t'] ?? DEFAULT_THEME;

// f = focus data, this is the name of a directory in the same directory as base.
if ($opts['f'] ?? null) {
    $readDataFrom[] = dirname($baseData) . '/' . trim($opts['f'], '/');
}

try {
    $builder = new Builder($readDataFrom, ROOT . "/themes/$theme");

    // Output the index.html
    $html = $builder->getHtml($media);
    $obj = new FilePath(DIST_DIR . '/index.html');
    $obj->put($html)->save();

    // Copy the theme's css.
    $buildDir = $obj = new FilePath(DIST_DIR);
    $buildDir->to('resume.css')->copy(ROOT . "/themes/$theme/resume.css");

    // Copy fonts if they exist.
    $fontsDir = ROOT . "/themes/$theme/fonts/";
    if (is_dir($fontsDir) && ($to = $buildDir->getPath())) {
        Bash::exec([
            'test -e ',
            $fontsDir,
            '&& rsync -a',
            $fontsDir,
            "$to/fonts/",
        ]);
    }
} catch (\Exception $exception) {
    die($exception->getMessage() . PHP_EOL);
}
