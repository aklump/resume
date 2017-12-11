# Aaron Klump's Resume

> My aim right now is to show you why the resume you hold in your hand should not be forgotten.  In this tiny code repo I will back the skills and claims I've just made to you.  I ask you to take a minute to review these pages so that you might have a better glimpse of my ability to excel in this position.

## Goals

This code base demonstrates my understanding of the following:

- Separation of data from presentation.
- Manipulation of data using PHP to make it API-ready.
- Use of a (Twig) template engine to generate markup from the API-ready data.
- Style markup using SMACSS and SCSS for both print and screen
- How to document an app for better user experience.

> The following section is written with the end user in mind.

## How to Build Your Resume

### Requirements

1. PHP >= 7.0

### Installation

1. Download the package
1. Run `composer update` from the root.
1. Copy the contents of _install/data/base_ to _data/base_ and use as a starting point, e.g. `rsync -av install/data data/`


### First, enter your data

1. You may delete all but _contact.yml_ and still generate a resume.
1. Any file with _.yml_ added to _data_ will be treated as a resume section
1. The order of sections is determined by the `sort` value in the yaml file; lower values come first.

### Then, compile your resume

1. From the CLI type `php compile.php` then view _dist/index.html_ in a web browser.
1. Print this to PDF using the browser's print option and you're done.

### Or... compile it for web

Compiling for the web uses different CSS and also handles links and email addresses differently.  Simply add the `-w` flag to the compile script.

1. From the CLI type `php compile.php -w` then view _dist/index.html_ in a web browser.

## How to Style Your Resume

1. You can build a new theme by copying _themes/2017_ and altering any of the files.  To use your new theme pass the directory name as an argument `php compile.php -tmy_theme`, where your theme is located in _themes/my_theme_.

