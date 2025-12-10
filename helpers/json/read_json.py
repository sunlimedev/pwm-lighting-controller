import json

with open("scenes.json") as file:
    data = json.load(file)

for s in data["scenes"]:
    num = s["num"]
    brightness = s["brightness"]
    speed = s["speed"]
    start_time = s["start_time"]
    stop_time = s["stop_time"]
    colors = s["colors"]

    print(f"Scene {num} starts running at {start_time} and stops at {stop_time}. \nIt runs with a brightness of {brightness}/10, and a speed of {speed}/10. \nIt shows the following colors: {colors}.\n")
