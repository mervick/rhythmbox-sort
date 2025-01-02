#!/usr/bin/env php
<?php

$defaultDataDir = '~/.local/share/rhythmbox';
//$defaultDataDir = '/home/izman/projects/php/rhythmbox-sort/test-data';

$argv = $GLOBALS['argv'];
array_shift($argv);

if (count($argv) != 1 || $argv[0] == '-h' || $argv[0] == '--help') {
    echo <<< TXT
Rename media files depends on ID tags
Usage:
    rename-files [-h|--help] <PATH>
PARAMS:
    <PATH>          - path to files
    -h | --help     - show this help

TXT;
    exit;
}

