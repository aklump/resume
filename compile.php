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

$read_data_from = [];
$baseData = ROOT . "/data/base";
$read_data_from[] = $baseData;

//
// Convert CLI options:
//
// w = website mode; the content will be displayed online, publicly; emails are removed.
// t = which theme to use other than DEFAULT_THEME
// f = focus data, this is the name of a directory in the same directory as base.  If present these files will override the base data files.
// o = output path, path to a directory where the output should be rendered relative to the root of the project
// r = the filename (no extension) of the resume file
//
$opts = getopt('wf:t:o:r:');
$media = array_key_exists('w', $opts) ? 'website' : 'print';
$theme = $opts['t'] ?? DEFAULT_THEME;

$resume_filename = 'resume.html';
if ($media === 'website') {
    $resume_filename = 'index.html';
}
if (!empty($opts['r'])) {
    $resume_filename = $opts['r'] . '.html';
}

$output_dir = new FilePath(ROOT . '/' . ($opts['o'] ?? '/dist/default'));

if ($opts['f'] ?? null) {
    $read_data_from[] = dirname($baseData) . '/' . trim($opts['f'], '/');
}

try {
    $builder = new Builder($read_data_from, ROOT . "/themes/$theme");
    $html = $builder->getHtml('resume', $media);
    $resume = $output_dir->put($html)->to($resume_filename)->save();

    $html = $builder->getHtml('letter', $media);
    $letter = $output_dir->put($html)->to('letter.html')->save();

    // Copy the theme's css.
    $output_dir->to('resume.css')->copy(ROOT . "/themes/$theme/resume.css");

    // Copy fonts if they exist.
    $fontsDir = ROOT . "/themes/$theme/fonts/";
    if (is_dir($fontsDir) && ($to = $output_dir->getPath())) {
        Bash::exec([
            'test -e ',
            $fontsDir,
            '&& rsync -a --delete',
            $fontsDir,
            "$to/fonts/",
        ]);
    }
} catch (\Exception $exception) {
    print Color::wrap('red', $exception) . PHP_EOL;
    exit(1);
}
print Color::wrap('green', "Your cover letter is available at: " . $letter->getPath()) . PHP_EOL;
print Color::wrap('green', "Your resume is available at: " . $resume->getPath()) . PHP_EOL;
exit(0);
