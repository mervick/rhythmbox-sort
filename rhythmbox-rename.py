import os
from lxml import etree
import chardet
from gi.repository import GLib
import difflib

tmpfile = '/dev/shm/rhythmbox.tmp'


def detect_encoding(file_path):
    with open(file_path, 'rb') as f:
        raw_data = f.read()
    result = chardet.detect(raw_data)
    return result['encoding']

def write_locations(tree, file_path):
    encoding = detect_encoding(file_path)
    tree.write(tmpfile, encoding=encoding, xml_declaration=True, pretty_print=True)

    with (open(file_path, 'r') as f1,
          open(tmpfile, 'r') as f2):
        org_lines = enumerate(f1.readlines())
        mod_lines = dict(enumerate(f2.readlines()))

    lines = []

    for idx, line in org_lines:
        if line != mod_lines[idx]:
            if mod_lines[idx].strip().startswith('<location>'):
                lines.append(mod_lines[idx])
                continue
        lines.append(line)

    with open(file_path, 'w') as merged_file:
        merged_file.writelines(lines)

def update_rhythmdb(file_path, from_dir, to_dir):
    parser = etree.XMLParser(remove_blank_text=True, resolve_entities=False)
    tree = etree.parse(file_path, parser)
    root = tree.getroot()

    for entry in root.findall('entry'):
        if entry.get('type') == 'song':
            location = entry.find('location')
            if location is not None and location.text.startswith(from_dir):
                location.text = location.text.replace(from_dir, to_dir, 1)

    write_locations(tree, file_path)

def update_playlists(file_path, from_dir, to_dir):
    parser = etree.XMLParser(remove_blank_text=True)
    tree = etree.parse(file_path, parser)
    root = tree.getroot()

    for playlist in root.findall('playlist'):
        for location in playlist.findall('location'):
            if location.text.startswith(from_dir):
                location.text = location.text.replace(from_dir, to_dir, 1)

    write_locations(tree, file_path)

def file_uri(path):
    return GLib.filename_to_uri(path, None)

def main():
    import argparse

    default_data_dir = os.path.expanduser("~/.local/share/rhythmbox")

    parser = argparse.ArgumentParser(description='Update database files after moving music files.')
    parser.add_argument('--data_dir', type=str, default=default_data_dir, help='Directory containing the database files')
    parser.add_argument('from_dir', type=str, help='Source directory from which files were moved')
    parser.add_argument('to_dir', type=str, help='Destination directory to which files were moved')

    args = parser.parse_args()

    data_dir = args.data_dir
    from_dir = args.from_dir
    to_dir = args.to_dir

    from_dir = file_uri(from_dir)
    to_dir = file_uri(to_dir)

    rhythmdb_path = os.path.join(data_dir, 'rhythmdb.xml')
    playlists_path = os.path.join(data_dir, 'playlists.xml')

    if os.path.exists(rhythmdb_path):
        update_rhythmdb(rhythmdb_path, from_dir, to_dir)
    else:
        print(f"File {rhythmdb_path} does not exist.")

    if os.path.exists(playlists_path):
        update_playlists(playlists_path, from_dir, to_dir)
    else:
        print(f"File {playlists_path} does not exist.")

if __name__ == '__main__':
    main()
