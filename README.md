# rhythmbox-sort 
### Script for sorting Rhythmbox playlists

## Installation
```
cd ~/.local/share
git clone https://github.com/mervick/sort-rhythmbox
chmod +x rhythmbox-sort/rhythmbox-sort
ln -s $(pwd)/rhythmbox-sort/rhythmbox-sort ~/.local/bin/rhythmbox-sort
```

### Usage:
```
rhythmbox-sort [-h|--help] [-p=PLAYLIST|--playlist=PLAYLIST] [-o=ORDER|--order=ORDER]
               [-s=COLUMN|--sort=COLUMN] [-d=PATH|--data-dir=PATH]

PARAMS:
-p=<PLAYLIST> | --playlist=<PLAYLIST>   - playlist name, case-sensitive
-s=<COLUMN> | --sort=<COLUMN>           - sort by column, one of title, artist, album, play-count, count, year, date, rating
-o=<ORDER>  | --order=<ORDER>           - order type, one of asc, desc, default
-d=<PATH>   | --data-dir=<PATH>         - path to rhythmbox data dir where are located playlists.xml and rhythmdb.xml files
                                          by default it's located at ~/.local/share/rhythmbox
-h | --help                             - show this help
```

### Requirements:

php >= 5.6
