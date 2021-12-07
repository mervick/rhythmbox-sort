<?php

$defaultDataDir = '~/.local/share/rhythmbox';
//$defaultDataDir = '/home/izman/projects/php/rhythmbox-sort/test-data';

$options = getopt('hf:c:d::', ['help', 'file:', 'count:', 'data-dir::']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<< TXT
Set the play-count in the rhythmbox database
Usage:
    rhythmbox-set-count [-h|--help] [-d=PATH|--data-dir=PATH]
PARAMS:
    -d=<PATH>   | --data-dir=<PATH>         - path to rhythmbox data dir where are located playlists.xml and rhythmdb.xml files
                                              by default it's located at $defaultDataDir
    -h | --help                             - show this help

TXT;
    exit;
}

// Get console params and set default values
$dataDir = $options['d'] ?? $options['data-dir'] ?? $defaultDataDir;

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

$removeTags = [
    "COMM", // "Comments",
    "COMR", // "Commercial frame",
    "GRID", // "Group identification registration",
    "LINK", // "Linked information",
    "OWNE", // "Ownership frame",
    "PRIV", // "Private frame",
    "PCNT", // "Play counter",
    "POPM", // "Popularimeter",
    "TXXX", // "User defined text information frame",
    "USER", // "Terms of use",
    "WCOM", // "Commercial information",
    "WCOP", // "Copyright/Legal information",
    "WOAF", // "Official audio file webpage",
    "WOAR", // "Official artist/performer webpage",
    "WOAS", // "Official audio source webpage",
    "WORS", // "Official Internet radio station homepage",
    "WPAY", // "Payment",
    "WPUB", // "Publishers official webpage",
    "WXXX", // "User defined URL link frame"
    "GEOB", // "General encapsulated object",
];

$removeTagsStr = [];
foreach ($removeTags as $tag) {
    $removeTagsStr[] = "--remove-frame=$tag";
}
$removeTagsStr = implode(' ', $removeTagsStr);
$update = false;

// Get attributes of songs for sorting
$stack =$rhythmdbXml->xpath('entry[@type="song"]');
$all = count($stack);
$fs_protoccol = 'file://';

function fileExtension($s) {
    $n = strrpos($s,".");
    return ($n===false) ? "" : substr($s,$n+1);
}

foreach ($stack as $index => $songXml) {
    $artist = $songXml->{'artist'} . '';
    $albumArtist = $songXml->{'album-artist'} . '';
    $comment = $songXml->{'comment'} . '';
    $bitrate = $songXml->{'bitrate'} . '';
    $location = urldecode($songXml->{'location'});

    $filename = substr($location, strlen($fs_protoccol));
    if (strpos($location, $fs_protoccol) !== 0) continue;
    $escapedFilename = str_replace("'", "'\\''", $filename);

    $custom = [];

    if (empty($bitrate) && strtolower(fileExtension($location)) !== 'flac') {
        $update = true;
        $data = `ffprobe -i '$escapedFilename' -v quiet -show_entries stream=bit_rate -hide_banner -print_format json`;
        $data = json_decode($data, true);
        $bitrate = $data['streams'][0]['bit_rate'] ?? null;
        if (!empty($bitrate)) {
            $bitrate = round(intval($bitrate) / 1000);
            $songXml->{'bitrate'} = $bitrate;
        }
    }

    if (!empty($albumArtist) && $albumArtist !== $artist) {
        $custom[] = "--text-frame=TPE2:'$artist'";
        $songXml->{'album-artist'} = $artist;
    }

    if (!empty($comment) || !empty($custom)) {
        $update = true;
        unset($songXml->{'comment'});

        $percent = sprintf("%0.2f", $index / $all * 100);
        echo "$percent%  $filename\n";
        $custom = implode(' ', $custom);
        `eyeD3 --remove-all-comments $removeTagsStr $custom '$escapedFilename' >/dev/null 2>/dev/null`;
//        echo "eyeD3 --remove-all-comments $removeTagsStr $custom '$filename' >/dev/null 2>/dev/null\n";
    }
}

if ($update) {
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($rhythmdbXml->asXML());

    // Create backup
    copy($rhythmdbFile, $rhythmdbFile . '.' . date('Ymd\THis'));
    // Save to rhythmdb playlists file
    file_put_contents($rhythmdbFile, $dom->saveXML());
    echo "Update $rhythmdbFile\n";
}

