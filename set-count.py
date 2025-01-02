#!/usr/bin/env python3

import subprocess

files = {
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/01. Deine Liebe.mp3': 9,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/02. Neid.mp3': 7,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/03. Du bist es wert.mp3': 14,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/04. Vergiftet (feat. Vanessa Katakalos).mp3': 15,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/05. Warum weinst du?.mp3': 8,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/06. Ich bin.mp3': 9,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/10. Das Beste.mp3': 3,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/12. Käfig aus Glas (feat. Neill Freiwald).mp3': 7,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/13. Lauf Weiter.mp3': 4,
'/home/izman/Music/GrooVenoM/GrooVenoM - Mitten ins Herz (2020)/15. Vergiftet (Bonus Version).mp3': 15

}


for f in files:
    subprocess.call(["php", "/home/izman/projects/php/rhythmbox-sort/rhythmbox-set-count.php", "-f", f, "-c", str(files[f]) ])



# 1814   17 GB
# 1254   12.1 Gb
