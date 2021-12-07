<?php

$defaultDataDir = '~/.local/share/rhythmbox';
//$defaultDataDir = '/home/izman/projects/php/rhythmbox-sort/test-data';

$options = getopt('hf:c:d::', ['help', 'file:', 'count:', 'data-dir::']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<< TXT
Set the play-count in the rhythmbox database
Usage:
    rhythmbox-set-count [-h|--help] [-f=FILEPATH|--file=FILEPATH] [-c=COUNT|--count=COUNT] [-d=PATH|--data-dir=PATH]
PARAMS:
    -f=<FILEPATH> | --file=<FILEPATH>       - filepath of media file
    -c=<COUNT> | --count=<COUNT>            - set play-count value
    -d=<PATH>   | --data-dir=<PATH>         - path to rhythmbox data dir where are located playlists.xml and rhythmdb.xml files
                                              by default it's located at $defaultDataDir
    -h | --help                             - show this help

TXT;
    exit;
}

// Get console params and set default values
$dataDir = $options['d'] ?? $options['data-dir'] ?? $defaultDataDir;
$file = $options['f'] ?? $options['file'] ?? null;
$file = urldecode($file);
$setCount = $options['c'] ?? $options['count'] ?? null;

if (empty($file)) {
    echo "Option 'file' is required\n";
    exit(1);
}

if (!isset($setCount)) {
    echo "Option 'count' is required\n";
    exit(2);
}

if (!is_numeric($setCount) || intval($setCount) != $setCount) {
    echo "Option 'count' is invalid\n";
    exit(3);
}
$setCount = intval($setCount);

if (strpos($file, 'file://') !== 0) {
    $file = 'file://' . $file;
}


// Replace ~ to home dir of current user
$homeDir = shell_exec("cd; pwd");
$dataDir = str_replace('~', trim($homeDir), $dataDir);
$rhythmdbFile = $dataDir . '/rhythmdb.xml';

// Read rhythmdb file
$rhythmdbContents = file_get_contents($rhythmdbFile);
if (!$rhythmdbContents) {
    echo "Unable yo open file $rhythmdbFile\n";
    exit(8);
}

// Load XML of rhythmdb file
$rhythmdbXml = simplexml_load_string($rhythmdbContents);

if ($rhythmdbXml === false) {
    echo "Failed loading XML in file $rhythmdbFile:\n";
    foreach(libxml_get_errors() as $error) {
        echo $error->message, "\n";
    }
    exit(9);
}

// Validate right file format of rhythmdb file
if ($rhythmdbXml->getName() !== 'rhythmdb') {
    echo "Invalid rhythmdb file format in $rhythmdbFile\n";
    exit(10);
}

// Get attributes of songs for sorting
foreach ($rhythmdbXml->xpath('entry[@type="song"]') as $songXml) {
    $location = urldecode($songXml->{'location'});
    if ($location == $file) {
        echo "$location\n";
        $playCount = intval($songXml->{'play-count'});
        if ($playCount !== $setCount) {
            $songXml->{'play-count'} = $setCount;

            echo "set $file, count = $setCount\n";

            $dom = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($rhythmdbXml->asXML());

            // Create backup
            copy($rhythmdbFile, $rhythmdbFile . '.' . date('Ymd\THis'));
            // Save to rhythmdb playlists file
            file_put_contents($rhythmdbFile, $dom->saveXML());
        }
        exit;
    }
}

echo "There is no filename with name \"$file\" in database\n";
exit(11);
