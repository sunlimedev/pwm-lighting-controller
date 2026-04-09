import time
import board
import busio
import numpy
import sqlite3
import gpiozero
import datetime
import threading
import adafruit_pca9685

# thread global flag
stop_flag = threading.Event()


# ---------------------- init functions ------------------------

def initialize_pwm():
    # create the I2C bus interface
    i2c = busio.I2C(board.SCL, board.SDA)

    # create a PCA9685 object and set the frequency for LED control
    pwm = adafruit_pca9685.PCA9685(i2c)
    pwm.frequency = 1600

    return pwm


def pwm_good(pwm):
    # blink red 3 times on startup
    for i in range(3):
        # set red color
        pwm.channels[0].duty_cycle = 0xffff
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold red for 0.5 second
        time.sleep(0.5)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.25 second
        time.sleep(0.25)

    return


def initialize_database():
    # connect to SQLite database and get cursor
    conn = sqlite3.connect('/home/user/project/database/lighting.db')
    cursor = conn.cursor()

    # set WAL mode to avoid blocking between python and PHP
    cursor.execute("PRAGMA journal_mode = WAL;")

    # set sync to normal for fast commits that are still safe from crashes
    cursor.execute("PRAGMA synchronous = NORMAL;")

    # set timeout retry length to 5s in case of lock
    cursor.execute("PRAGMA busy_timeout = 5000;")

    # set max WAL size to 4MB
    cursor.execute("PRAGMA wal_autocheckpoint = 1000;")

    return conn, cursor


def database_good(pwm):
    # blink yellow 3 times on startup
    for i in range(3):
        # set yellow color
        pwm.channels[0].duty_cycle = 0xffff
        pwm.channels[1].duty_cycle = 0xffff
        pwm.channels[2].duty_cycle = 0x0000
        # hold yellow for 0.5 second
        time.sleep(0.5)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.25 second
        time.sleep(0.25)

    return


def initialize_input_bus():
    # provide GPIO input pins to use
    input_pins = [22, 10, 9, 11, 5, 6, 13, 26]

    # initialize input_pins as gpiozero DigitalInputDevice class objects
    inputs = [gpiozero.DigitalInputDevice(pin=pin, pull_up=True) for pin in input_pins]

    return inputs


def input_bus_good(pwm):
    # blink green 3 times on startup
    for i in range(3):
        # set green color
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0xffff
        pwm.channels[2].duty_cycle = 0x0000
        # hold green for 0.5 second
        time.sleep(0.5)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.5 second
        time.sleep(0.25)

    return


def initialize_run_LED():
    # RUN on board using GPIO18 (board pin 12)
    run_LED = gpiozero.LED(18)
    run_LED.off()

    return run_LED


# ---------------------- data functions ------------------------

def read_scene_info(cursor, scene_id):
    # read scene info from lighting.db
    cursor.execute(
        "SELECT behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 FROM scenes WHERE scene_id = ?",
        (scene_id,))

    # get full default scene row as tuple (tuples aren't real, they can't hurt you)
    row = cursor.fetchone()

    # store scene table data in variables
    behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 = row

    # turn behavior string into callable lighting function
    function = globals().get(behavior)

    # put all color_id keys into a list
    color_ids = [color0, color1, color2, color3, color4, color5, color6, color7, color8, color9]

    # remove unused colors starting from last
    for i in range(9, -1, -1):
        if color_ids[i] is None:
            del color_ids[i]

    # if all colors were null, add black to the list (keep things from breaking)
    if all(color_id is None for color_id in color_ids):
        color_ids = [64]

    color_list = color_ids_to_list(cursor, color_ids)

    # derive cycle time from speed
    cycle_time = 6 - speed

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x3333 * (5 - brightness))

    return function, color_list, cycle_time, dimmer


def read_connection_scene(cursor, connection_id):
    # increment connection_id for sqlite 1-indexing
    connection_id += 1

    # navigate to scene used in connection in lighting.db
    cursor.execute("SELECT scene FROM connections WHERE connection_id = ?", (connection_id,))

    # get connection scene row (more tuple nonsense)
    row = cursor.fetchone()

    # get scene_id from pulled row
    scene_id = row[0]

    # if scene_id is null, set to 1 (so nothing breaks)
    if scene_id is None:
        scene_id = 1

    return scene_id


def color_ids_to_list(cursor, color_ids):
    ####################### from ChatGPT #######################
    # Build placeholders for the IN clause
    placeholders = ",".join("?" for _ in color_ids)

    # Query hex values for all needed colors at once
    query = f"""SELECT color_id, hexval FROM colors WHERE color_id IN ({placeholders})"""

    cursor.execute(query, color_ids)
    rows = cursor.fetchall()

    # Map ids to hex strings
    id_to_hex = {row[0]: row[1] for row in rows}

    # Final ordered list of hex values
    colors = [id_to_hex.get(color_id) for color_id in color_ids]
    ############################################################

    # convert hex string into floats
    color_list = []
    for color in colors:
        # create a list for the individual red, green, and blue values
        rgb = []

        # extract each color from the hex value string
        red = color[:2]
        green = color[2:4]
        blue = color[4:]

        # convert string to int (hex format)
        red = int(red, 16)
        green = int(green, 16)
        blue = int(blue, 16)

        # convert int to float
        red = red / 255.0
        green = green / 255.0
        blue = blue / 255.0

        # round float to nearest five hundredth so we have less complex real-time fp calculations
        red = round(red / 0.05) * 0.05
        green = round(green / 0.05) * 0.05
        blue = round(blue / 0.05) * 0.05

        # append rgb values to rgb list
        rgb.append(red)
        rgb.append(green)
        rgb.append(blue)

        # move rgb values to color_list list
        color_list.append(rgb)

    return color_list


def read_events(cursor):
    # read entire events table
    cursor.execute("SELECT * FROM events")

    # pull the entire table as a list of tuples
    events = cursor.fetchall()

    # extract events table columns into their own lists
    event_scenes = [events[1] for events in events]
    event_dates = [events[2] for events in events]

    return event_scenes, event_dates


def read_open_hours(cursor, weekday):
    # read entire time table
    cursor.execute("SELECT * FROM time")

    # pull the entire table as a list of tuples
    hours = cursor.fetchall()

    # extract the business hours depending on the weekday
    open_hour, open_minute, close_hour, close_minute = hours[weekday][1], hours[weekday][2], hours[weekday][3], hours[weekday][4]

    return open_hour, open_minute, close_hour, close_minute


def set_active_connections(conn, cursor, connection_id):
    # set the active connections to none in the table
    cursor.execute("UPDATE connections SET is_active = 0")

    # if a new connection is active
    if connection_id != 0:
        # set the is_active column to 1 for the active connection
        cursor.execute("UPDATE connections SET is_active = 1 WHERE connection_id = ?", (connection_id,))

    # commit changes to database
    conn.commit()

    return


def read_input_bus(inputs):
    # gather states from inputs
    states = [i.value for i in inputs]

    return states


def check_time():
    # get time struct from pi clock
    now = datetime.datetime.now()

    # get ISO 8601 string of today's date
    today = datetime.date.today()
    date_string = today.isoformat()

    return date_string, now.hour, now.minute


def check_test_flags_active(cursor):
    # check flag on testmode table
    cursor.execute("SELECT flag, reload FROM testmode")

    # pull column from table as tuple
    flag = cursor.fetchone()

    return flag


def read_test_scene_info(cursor):
    # read scene info from lighting.db
    cursor.execute("SELECT * FROM testmode")

    # get full default scene row as tuple (tuples aren't real, they can't hurt you)
    row = cursor.fetchone()

    # store event scene table data in variables
    _, _, behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 = row

    # turn behavior string into callable lighting function
    function = globals().get(behavior)

    # put all color_id keys into a list
    color_ids = [color0, color1, color2, color3, color4, color5, color6, color7, color8, color9]

    # remove unused colors starting from last
    for i in range(9, -1, -1):
        if color_ids[i] is None:
            del color_ids[i]

    # if all colors were null, add black to the list (keep things from breaking)
    if all(color_id is None for color_id in color_ids):
        color_ids = [64]

    color_list = color_ids_to_list(cursor, color_ids)

    # derive cycle time from speed
    cycle_time = 6 - speed

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x3333 * (5 - brightness))

    return function, color_list, cycle_time, dimmer


def read_default_scene_id(cursor):
    # read scene info from lighting.db
    cursor.execute("SELECT scene_id FROM scenes WHERE is_default = 1")

    # get row from table
    row = cursor.fetchone()

    # get scene_id
    scene_id = row[0]

    return scene_id


# -------------------- lighting functions ----------------------

def sequence_solid(pwm, color_list, cycle_time, dimmer):
    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            # assign lookup table value to color channels minus the scaled dimmer value
            pwm.channels[0].duty_cycle = int(color_list[color][0] * (0xffff - dimmer))
            pwm.channels[1].duty_cycle = int(color_list[color][1] * (0xffff - dimmer))
            pwm.channels[2].duty_cycle = int(color_list[color][2] * (0xffff - dimmer))

            # check for raised flag during the cycle_time timeout
            if stop_flag.wait(timeout=cycle_time):
                return None


def sequence_fade(pwm, color_list, cycle_time, dimmer):
    # create lookup table for loop
    x = numpy.linspace(0, 2 * numpy.pi, 100 * cycle_time, endpoint=False, dtype=float)
    
    fade = []
    for i in x:
        fade.append(int(round(32767.5 * numpy.sin(i + ((3 * numpy.pi) / 2)) + 32767.5)))

    # all speeds use 10ms step time
    step_time = 0.01

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100 * cycle_time):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int(
                    (color_list[color][0] * int(fade[i] - ((fade[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int(
                    (color_list[color][1] * int(fade[i] - ((fade[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int(
                    (color_list[color][2] * int(fade[i] - ((fade[i] / 0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_decay(pwm, color_list, cycle_time, dimmer):
    # create lookup table for loop
    x1 = numpy.linspace(0, 65535, 20 * cycle_time, endpoint=True, dtype=int)
    x2 = numpy.linspace(65535, 0, 80 * cycle_time, endpoint=True, dtype=int)

    decay = []
    for i in range(cycle_time * 20):
	    decay.append(int(x1[i]))
    for i in range(cycle_time * 80):
	    decay.append(int(x2[i]))

    # all speeds use 10ms step time
    step_time = 0.01

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100 * cycle_time):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int(
                    (color_list[color][0] * int(decay[i] - ((decay[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int(
                    (color_list[color][1] * int(decay[i] - ((decay[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int(
                    (color_list[color][2] * int(decay[i] - ((decay[i] / 0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_wigwag(pwm, color_list, cycle_time, dimmer):
    # lookup table for function
    wigwag = [0x0, 0x0, 0xffff, 0xffff, 0x0, 0xffff, 0xffff, 0x0, 0xffff, 0xffff]

    # create smaller time increment for loop
    step_time = cycle_time / 10

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(10):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(wigwag[i] - dimmer)))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(wigwag[i] - dimmer)))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(wigwag[i] - dimmer)))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_sos(pwm, color_list, cycle_time, dimmer):
    # lookup table for function
    sos = [0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0,
           0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff,
           0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff,
           0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff,
           0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0,
           0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0,
           0x0, 0x0]

    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(sos[i] - ((sos[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(sos[i] - ((sos[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(sos[i] - ((sos[i] / 0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_breathe(pwm, color_list, cycle_time, dimmer):
    # create lookup table for loop
    x = numpy.linspace(0, 6 * numpy.pi, 100 * cycle_time, endpoint=False, dtype=float)

    breathe = []
    for i in x:
        breathe.append(int(round(20000 * numpy.sin(i + ((3 * numpy.pi) / 2)) + 45535)))

    # all speeds use 10ms step time
    step_time = 0.01

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100 * cycle_time):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int(
                    (color_list[color][0] * int(breathe[i] - ((breathe[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int(
                    (color_list[color][1] * int(breathe[i] - ((breathe[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int(
                    (color_list[color][2] * int(breathe[i] - ((breathe[i] / 0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def crossfade(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop and set increment count
    step_time = 0.025

    increment_dict = {
        1: 10,
        2: 20,
        3: 30,
        4: 40,
        5: 50
    }

    inc = increment_dict[cycle_time]

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(len(color_list)):
            # get current color and next color for transition
            if color == num_colors - 1:
                current_color = color_list[color]
                next_color = color_list[0]
                color_difference = [a - b for a, b in zip(next_color, current_color)]

                for i in range(1, inc + 1):
                    # assign current color values with progressive difference from next color
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int(
                        (color_difference[0] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / inc)

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None

            # get current color and next color for transition
            else:
                current_color = color_list[color]
                next_color = color_list[color + 1]
                color_difference = [a - b for a, b in zip(next_color, current_color)]

                for i in range(1, inc + 1):
                    # assign current color values with progressive difference from next color
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int(
                        (color_difference[0] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / inc)

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None


def crossfade_hold(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop and set increment count
    step_time = 0.015

    increment_dict = {
        1: 10,
        2: 20,
        3: 30,
        4: 40,
        5: 50
    }

    inc = increment_dict[cycle_time]

    hold_time = (2 * step_time * inc) / 5

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(len(color_list)):
            # get current color and next color for transition
            if color == num_colors - 1:
                current_color = color_list[color]
                next_color = color_list[0]
                color_difference = [a - b for a, b in zip(next_color, current_color)]

                # hold color for hold_time then crossfade
                pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer))
                pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer))
                pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer))

                # check for raised flag during the hold_time timeout
                if stop_flag.wait(timeout=hold_time):
                    return None

                for i in range(1, inc + 1):
                    # assign current color values with progressive difference from next color
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int(
                        (color_difference[0] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / inc)

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None

            # get current color and next color for transition
            else:
                current_color = color_list[color]
                next_color = color_list[color + 1]
                color_difference = [a - b for a, b in zip(next_color, current_color)]

                # hold color for hold_time then crossfade
                pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer))
                pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer))
                pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer))

                # check for raised flag during the hold_time timeout
                if stop_flag.wait(timeout=hold_time):
                    return None

                for i in range(1, inc + 1):
                    # assign current color values with progressive difference from next color
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int(
                        (color_difference[0] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / inc)
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / inc)

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None


# --------------------------- main -----------------------------

def main():
    # -------------- initialization and startup ----------------

    # create pwm object for light control
    pwm = initialize_pwm()

    # indicate pwm object has been initialized
    pwm_good(pwm)

    # load sqlite database for program
    conn, cursor = initialize_database()

    # indicate database has been initialized
    database_good(pwm)

    # initialize input bus for hardware inputs
    inputs = initialize_input_bus()

    # indicate input bus has been initialized
    input_bus_good(pwm)

    # initialize run LED
    run_LED = initialize_run_LED()
    
    # blink run LED forever
    run_LED.blink(on_time=0.5, off_time=0.5)

	# get default scene number
    default_id = read_default_scene_id(cursor)

    # read default scene info from scenes table
    function, color_list, cycle_time, dimmer = read_scene_info(cursor, scene_id=default_id)

    # start lighting thread with default scene info
    lighting_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
    lighting_thread.start()

    # create status variable to track if lighting is disabled
    lights_off = False

    # create status variable to see if a connection was just running
    connection_was_running = False
    
    # set starting value for default scene_id
    default_id = 0

    # ----------------- lighting thread loop -------------------

    # light tube control hierarchy
    # -------------------------------
    # 1 - test mode: if test mode is active, then it has control
    # 2 - connections: if a connection receives power AND test mode is off, then it will be given control
    # 3 - time: if the business is closed AND no connections are active AND test mode is off, then lighting will be disabled
    # 4 - event: if today is an event day AND the business is open AND no connections are active AND test mode is off, then event lighting will be displayed
    # 5 - default: if no other level has control, then default lighting will be displayed

    while True:
        # check current test mode flags state
        flag, reload = check_test_flags_active(cursor)
        
        # if test mode is active
        while(flag == 1):
            # run test scene for up to 30 seconds or until flag is reset
            for i in range(0,60):
                # check current test mode flags state
                flag, reload = check_test_flags_active(cursor)
                
                if flag == 1 and reload == 1:
                    # get test scene info
                    test_function, test_color_list, test_cycle_time, test_dimmer = read_test_scene_info(cursor)

                    # stop the lighting thread
                    stop_flag.set()
                    lighting_thread.join()
                    stop_flag.clear()

                    # and restart the lighting thread with the test scene info
                    lighting_thread = threading.Thread(target=test_function, args=(pwm, test_color_list, test_cycle_time, test_dimmer))
                    lighting_thread.start()

                    # update reload on table and reset 30s timer
                    cursor.execute("UPDATE testmode SET reload = 0")
                    conn.commit()
                    time.sleep(0.1)
                    break

                elif flag == 1:
                    if i == 59:
                        # turn off flag to end test mode
                        cursor.execute("UPDATE testmode SET flag = 0")
                        conn.commit()
                        time.sleep(0.1)
                    else:
                        time.sleep(0.5)

            if flag == 0:
                # stop the lighting thread
                stop_flag.set()
                lighting_thread.join()
                stop_flag.clear()

                # and restart lighting thread with default scene info
                lighting_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
                lighting_thread.start()

        # check current state of input bus
        states = read_input_bus(inputs)

        # if any connections are currently active
        if any(states):
            # set connection status to running
            connection_was_running = True

            # return the lowest index that is true
            connection = states.index(True)

            # get the scene_id for the connection
            scene_id = read_connection_scene(cursor, connection_id=connection)

            # and check the associated scene info
            temp_function, temp_color_list, temp_cycle_time, temp_dimmer = read_scene_info(cursor, scene_id)

            # if the scene info has not changed from the last connection's scene info
            if (temp_function, temp_color_list, temp_cycle_time, temp_dimmer) == (function, color_list, cycle_time,
                                                                                  dimmer):
                # wait for 100ms and loop again
                time.sleep(0.1)
                continue

            # if the scene info has changed
            else:
                # update the database to reflect the active connection (+1 for sqlite 1-indexing)
                set_active_connections(conn, cursor, connection + 1)

                # get the new scene info
                function, color_list, cycle_time, dimmer = temp_function, temp_color_list, temp_cycle_time, temp_dimmer

                # stop the lighting thread
                stop_flag.set()
                lighting_thread.join()
                stop_flag.clear()

                # and restart the lighting thread with the new scene info
                lighting_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
                lighting_thread.start()

                # wait for 100ms and loop again
                time.sleep(0.1)
                continue

        # if no connections are currently active
        else:
            # check if connection was just running
            if connection_was_running:
                # set running to false
                connection_was_running = False
                # set all connections as off in table
                set_active_connections(conn, cursor, connection_id=0)

            # get system weekday
            today = datetime.datetime.today()
            weekday = today.weekday()

            # check time table for up-to-date business hours
            open_hour, open_minute, close_hour, close_minute = read_open_hours(cursor, weekday)

            # check the current time
            curr_date, curr_hour, curr_minute = check_time()

            # manage all possible hour scenarios
            if open_hour <= close_hour:
                # normal hours like 9a-5p
                is_open = (open_hour, open_minute) <= (curr_hour, curr_minute) < (close_hour, close_minute)
            else:
                # overnight hours like 10:30a-1a (wingstop case)
                is_open = (curr_hour, curr_minute) >= (open_hour, open_minute) or (curr_hour, curr_minute) < (
                    close_hour, close_minute)

            # if the current time is within business hours
            if is_open:
                # set off as not running
                lights_off = False

                # check events table for up-to-date event dates
                event_scenes, event_dates = read_events(cursor)

                # check if current date is event date
                try:
                    event_index = event_dates.index(curr_date)
                except ValueError:
                    event_index = -1

                # if current day is present on event table
                if event_index != -1:
                    # check the associated scene info
                    temp_function, temp_color_list, temp_cycle_time, temp_dimmer = read_scene_info(cursor, scene_id=
                    event_scenes[event_index])

                    # if the scene info has not changed from the last scene's info
                    if (temp_function, temp_color_list, temp_cycle_time, temp_dimmer) == (function, color_list,
                                                                                          cycle_time, dimmer):
                        # wait for 200ms and loop again
                        time.sleep(0.2)
                        continue

                    # if the scene info has changed
                    else:
                        # get the new scene info
                        function, color_list, cycle_time, dimmer = temp_function, temp_color_list, temp_cycle_time, temp_dimmer

                        # stop the lighting thread
                        stop_flag.set()
                        lighting_thread.join()
                        stop_flag.clear()

                        # and restart the lighting thread with new scene info
                        lighting_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
                        lighting_thread.start()

                        # wait for 200ms and loop again
                        time.sleep(0.2)
                        continue

                # if current day is not event day
                else:
                    # get default scene_id
                    default_id = read_default_scene_id(cursor)

                    # check the default scene info
                    temp_function, temp_color_list, temp_cycle_time, temp_dimmer = read_scene_info(cursor, scene_id=default_id)

                    # if the scene info has not changed from the last scene's info
                    if (temp_function, temp_color_list, temp_cycle_time, temp_dimmer) == (function, color_list, cycle_time, dimmer) and default_id == read_default_scene_id(cursor):
                        # wait for 200ms and loop again
                        time.sleep(0.2)
                        continue

                    # if the scene info has changed
                    else:
                        # get the new scene info
                        function, color_list, cycle_time, dimmer = temp_function, temp_color_list, temp_cycle_time, temp_dimmer

                        # stop the lighting thread
                        stop_flag.set()
                        lighting_thread.join()
                        stop_flag.clear()

                        # and restart the lighting thread with new scene info
                        lighting_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
                        lighting_thread.start()

                        # wait for 200ms and loop again
                        time.sleep(0.2)
                        continue

            # if the current time is not within business hours
            else:
                # check if current time has just left business hours
                if not lights_off:
                    # set lights as off
                    lights_off = True

                    # stop the lighting thread
                    stop_flag.set()
                    lighting_thread.join()
                    stop_flag.clear()

                    # set scene info for lights off
                    function, color_list, cycle_time, dimmer = globals().get("sequence_solid"), [[0.0, 0.0, 0.0]], 1, 0

                    # and restart the lighting thread with new info
                    lighting_thread = threading.Thread(target=sequence_solid,
                                                       args=(pwm, color_list, cycle_time, dimmer))
                    lighting_thread.start()

                    # wait for 200ms and loop again
                    time.sleep(0.2)
                    continue

                # if off lighting has been running for at least one loop
                else:
                    # wait for 200ms and loop again
                    time.sleep(0.2)
                    continue


if __name__ == "__main__":
    main()
