import os
import xml.etree.ElementTree as ET
from urllib.parse import quote

def get_track_info(file_path):
    db_path = os.path.expanduser('~/.local/share/rhythmbox/rhythmdb.xml')
    file_uri = 'file://' + quote(file_path)
    tree = ET.parse(db_path)
    root = tree.getroot()
    for entry in root.findall('entry'):
        location = entry.find('location')
        if location is not None and location.text == file_uri:
            rating = entry.find('rating')
            play_count = entry.find('play-count')
            return (int(rating.text) if rating is not None else None,
                    int(play_count.text) if play_count is not None else None)
    return None, None

