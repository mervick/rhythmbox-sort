<?php

$options = getopt('hp:o::s::d::', ['help', 'playlist:', 'sort::', 'order::', 'data-dir::']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<< TXT
Sort tracks in the rhythmbox playlist
Usage:
    sort-rhythmbox [-h|--help] [-p=PLAYLIST|--playlist=PLAYLIST] [-o=ORDER|--order=ORDER]
                   [-s=SORT|--sort=SORT] [-d=DIRECTORY|--data-dir=DIRECTORY]

TXT;
    exit;
}

$dataDir = $options['d'] ?? $options['data-dir'] ?? '~/.local/share/rhythmbox';
$sortAttr = strtolower($options['s'] ?? $options['sort'] ?? 'title');
$order = strtolower($options['o'] ?? $options['order'] ?? 'default');
$playlist = $options['p'] ?? $options['playlist'] ?? null;

if (!$playlist) {
    echo "Option 'playlist' is required\n";
    exit(1);
}

if ($playlist === 'rated') {
    echo "Option 'playlist' is invalid\n";
    exit(2);
}

$validOrders = ['asc', 'desc', 'default'];
if (!in_array($order, $validOrders)) {
    $validOrders = implode(', ', $validOrders);
    echo "Option 'order' is invalid, can be one of: $validOrders\n";
    exit(3);
}

$sortAttrs = [
    'title' => [
        'title', 'asc'
    ],
    'artist' => [
        ['artist', 'title'], 'asc'
    ],
    'album' => [
        ['album', 'title'], 'asc'
    ],
    'play-count' => [
        'play-count', 'desc'
    ],
    'count' => [
        'play-count', 'desc'
    ],
    'rating' => [
        ['rating', 'play-count'], 'desc'
    ],
];

if (!isset($sortAttrs[$sortAttr])) {
    $validAttrs = implode(', ', array_keys($sortAttrs));
    echo "Option 'sort' is invalid, can be one of: $validAttrs\n";
    exit(4);
}

$playlistsFile = $dataDir . '/playlists.xml';
$rhythmdbFile = $dataDir . '/rhythmdb.xml';

$data = file_get_contents($playlistsFile);
if (!$data) {
    echo "Unable yo open file $playlistsFile\n";
    exit(5);
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($myXMLData);

if ($xml === false) {
    echo "Failed loading XML in file $playlistsFile: ";
    foreach(libxml_get_errors() as $error) {
        echo "<br>", $error->message;
    }
} else {
    print_r($xml);
}



