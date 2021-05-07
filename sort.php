<?php

// Sorting attributes table
$sortAttrs = [
    'title' => [
        ['title'], 'asc', 'string'
    ],
    'artist' => [
        ['artist', 'album'], 'asc', 'string'
    ],
    'album' => [
        ['album', 'title'], 'asc', 'string'
    ],
    'play-count' => [
        ['play-count'], 'desc', 'number'
    ],
    'count' => [
        ['play-count'], 'desc', 'number'
    ],
    'year' => [
        ['date'], 'asc', 'number'
    ],
    'date' => [
        ['date'], 'asc', 'number'
    ],
    'rating' => [
        ['rating', 'play-count'], 'desc', 'number'
    ],
];
$validAttrs = implode(', ', array_keys($sortAttrs));

$validOrders = ['asc', 'desc', 'default'];
$validOrdersStr = implode(', ', $validOrders);

$defaultDataDir = '~/.local/share/rhythmbox';

$options = getopt('hp:o::s::d::', ['help', 'playlist:', 'sort::', 'order::', 'data-dir::']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<< TXT
Sort tracks in the rhythmbox playlist
Usage:
    sort-rhythmbox [-h|--help] [-p=PLAYLIST|--playlist=PLAYLIST] [-o=ORDER|--order=ORDER]
                   [-s=COLUMN|--sort=COLUMN] [-d=PATH|--data-dir=PATH]
PARAMS:
    -p=<PLAYLIST> | --playlist=<PLAYLIST>   - playlist name, case-sensitive
    -s=<COLUMN> | --sort=<COLUMN>           - sort by column, one of $validAttrs
    -o=<ORDER>  | --order=<ORDER>           - order type, one of $validOrdersStr
    -d=<PATH>   | --data-dir=<PATH>         - path to rhythmbox data dir where are located playlists.xml and rhythmdb.xml files
                                              by default it's located at $defaultDataDir
    -h | --help                             - show this help

TXT;
    exit;
}

// Get console params and set default values
$dataDir = $options['d'] ?? $options['data-dir'] ?? $defaultDataDir;
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
if (!in_array($order, $validOrders)) {
    echo "Option 'order' is invalid, can be one of: $validOrdersStr\n";
    exit(3);
}

if (!isset($sortAttrs[$sortAttr])) {
    echo "Option 'sort' is invalid, can be one of: $validAttrs\n";
    exit(4);
}

if ($order === 'default') {
    $order = $sortAttrs[$sortAttr][1];
}

$order = $order === 'asc' ? 1 : -1;
$sort = $sortAttrs[$sortAttr][0];
$type = $sortAttrs[$sortAttr][2];

// Fix ~ to home dir of current user
$homeDir = shell_exec("cd; pwd");
$dataDir = str_replace('~', trim($homeDir), $dataDir);

$playlistsFile = $dataDir . '/playlists.xml';
$rhythmdbFile = $dataDir . '/rhythmdb.xml';

// Read playlists file
$playlistsContents = file_get_contents($playlistsFile);
if (!$playlistsContents) {
    echo "Unable yo open file $playlistsFile\n";
    exit(5);
}

libxml_use_internal_errors(true);
$playlistsXml = simplexml_load_string($playlistsContents);

// Load XML of playlists file
if ($playlistsXml === false) {
    echo "Failed loading XML in file $playlistsFile:\n";
    foreach(libxml_get_errors() as $error) {
        echo $error->message, "\n";
    }
    exit(6);
}

// Validate right file format of playlists file
if ($playlistsXml->getName() !== 'rhythmdb-playlists') {
    echo "Invalid rhythmdb playlists file format in $playlistsFile\n";
    exit(7);
}

// Searching a playlist
foreach ($playlistsXml->xpath("playlist") as $playlistXml) {

    // Convert html entities
    $name = html_entity_decode($playlistXml->attributes()['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8');

    if ($name === $playlist) {
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

        $files = (array)$playlistXml->{'location'};
        $songs = [];

        // Get attributes of songs for sorting
        foreach ($rhythmdbXml->xpath('entry[@type="song"]') as $songXml) {
            $key = array_search($songXml->{'location'}, $files);

            if ($key !== false) {
                $songs[$key] = [];

                foreach ($sort as $attr) {
                    $value = $songXml->$attr->__toString();
                    if ($type === 'number') {
                        $value = intval($value);
                    }
                    elseif ($type === 'string') {
                        $value = html_entity_decode($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    }

                    $songs[$key][$attr] = $value;
                }
            }
        }

        $defaults = array_fill_keys($sort, $type === 'number' ? 0 : '');

        foreach ($files as $key => $location) {
            $files[$key] = [
                'sort' => $songs[$key] ?? $defaults,
                'location' => $location,
            ];
        }

        if ($type === 'number') {
            // Create sorting function by numeric values
            $sortFunc = function(array $a, array $b) use ($sort, $order) {
                $result = 0;
                foreach ($sort as $attr) {
                    if ($a['sort'][$attr] !== $b['sort'][$attr]) {
                        return ($a['sort'][$attr] > $b['sort'][$attr] ? 1 : -1) * $order;
                    }
                }
                return $result;
            };
        } else {
            // Create sorting function by string values
            $sortFunc = function(array $a, array $b) use ($sort, $order) {
                $result = 0;
                foreach ($sort as $attr) {
                    $result = strnatcmp($a['sort'][$attr], $b['sort'][$attr]) * $order;
                    if ($result !== 0) break;
                }
                return $result;
            };
        }

        usort($files, $sortFunc);

        unset($playlistXml->location);
        foreach ($files as $key => $file) {
            $playlistXml->addChild('location', $file['location']);
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($playlistsXml->asXML());

        // Create backup
        copy($playlistsFile, $playlistsFile . '.' . date('Ymd\THis'));
        // Save to rhythmdb playlists file
        file_put_contents($playlistsFile, $dom->saveXML());
        exit;
    }
}

echo "There is no playlist with name \"$playlist\"\n";
exit(11);
