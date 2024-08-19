#!/usr/bin/env bash

##
 # Compile Aaron's resume for aklump.github.io/resume/
 #
 # @param string
 #
 # @echo
 # @return 0
 ##

php compile.php -w -o docs/
rm docs/letter.html
