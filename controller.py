import time
import board
import busio
import sqlite3
import gpiozero
import threading
from adafruit_ds3231 import DS3231
from adafruit_pca9685 import PCA9685

# thread global flag
stop_flag = threading.Event()


######################## init functions ########################

def initialize_i2c_devices():
    # create the I2C bus interface
    i2c = busio.I2C(board.SCL, board.SDA)

    # create a PCA9685 object and set the frequency for LED control
    pwm = PCA9685(i2c)
    pwm.frequency = 1000

    # create a DS3231 object
    rtc = DS3231(i2c)

    return pwm, rtc


def load_database():
    # connect to sqlite database and get cursor
    conn = sqlite3.connect('lighting.db')
    cursor = conn.cursor()

    return cursor


def initialize_input_bus():
    # provide input pins to use
    input_pins = [22, 10, 9, 11, 5, 6, 13, 26]

    # initialize input_pins as gpiozero DigitalInputDevice class instances
    inputs = [gpiozero.DigitalInputDevice(pin=input_pins[0], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[1], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[2], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[3], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[4], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[5], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[6], pull_up=True),
              gpiozero.DigitalInputDevice(pin=input_pins[7], pull_up=True)]

    return inputs


def read_input_bus(inputs):
    # gather states from inputs
    states = [i.value for i in inputs]

    return states


def startup_blink(pwm):
    # blink white 3 times on startup
    for i in range(3):
        # set all channels to max (white)
        pwm.channels[0].duty_cycle = 0xffff
        pwm.channels[1].duty_cycle = 0xffff
        pwm.channels[2].duty_cycle = 0xffff
        # hold white for 0.25 second
        time.sleep(0.25)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.25 second
        time.sleep(0.25)

    return


######################## data functions ########################

def read_default_scene(cursor):
    # navigate to default scene info in lighting.db
    cursor.execute(
        "SELECT behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 FROM scenes WHERE scene_id = 1")

    # get full default scene row as tuple (what the hell is a tuple)
    row = cursor.fetchone()

    # move row tuple to individual variables
    behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 = row

    # turn behavior string into callable lighting function
    function = globals().get(behavior)

    # put all color_id keys into a list
    color_ids = [color0, color1, color2, color3, color4, color5, color6, color7, color8, color9]

    # remove unused colors starting from last
    for i in range(9, -1, -1):
        if color_ids[i] is None:
            del color_ids[i]

    # if all colors were null, add white to the list so things don't break
    if all(color_id is None for color_id in color_ids):
        color_ids = [1]

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

    # derive cycle time from speed
    cycle_time = 6 - speed

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x3333 * (5 - brightness))

    return function, color_list, cycle_time, dimmer


def read_connection_scene(cursor, connection):
    # set passed connection integer as connection_id for table
    connection_id = connection

    # navigate to scene used in connection in lighting.db
    cursor.execute("SELECT scene FROM connections WHERE connection_id = ?", (connection_id,))

    # get connection scene row (more tuple nonsense)
    row = cursor.fetchone()

    # get scene_id from pulled row
    scene_id = row[0]

    # if scene_id is null, set to 1 (so nothing breaks)
    if scene_id is None:
        scene_id = 1

    # read connection scene info from lighting.db
    cursor.execute(
        "SELECT behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 FROM scenes WHERE scene_id = ?",
        (scene_id,))

    # get full default scene row as tuple (tuples aren't real, they can't hurt you)
    row = cursor.fetchone()

    # store connection scene table data in variables
    behavior, brightness, speed, color0, color1, color2, color3, color4, color5, color6, color7, color8, color9 = row

    # turn behavior string into callable lighting function
    function = globals().get(behavior)

    # put all color_id keys into a list
    color_ids = [color0, color1, color2, color3, color4, color5, color6, color7, color8, color9]

    # remove unused colors starting from last
    for i in range(9, -1, -1):
        if color_ids[i] is None:
            del color_ids[i]

    # if all colors were null, add white to the list (keep things from breaking)
    if all(color_id is None for color_id in color_ids):
        color_ids = [1]

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

    # derive cycle time from speed
    cycle_time = 6 - speed

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x3333 * (5 - brightness))

    return function, color_list, cycle_time, dimmer


def set_rtc(rtc, year, month, day, hour, minute, weekday):
    # placeholder second, weekday, yearday, DS3231 doesn't use DST
    temp = time.struct_time((year, month, day, hour, minute, 0, 0, 0, -1))

    # allow python to fill in yearday bc it's a pain in the ass
    epoch = time.mktime(temp)
    computed = time.localtime(epoch)

    # use seconds = 0 again, computed yearday, and dst = 0
    rtc.datetime = time.struct_time((year, month, day, hour, minute, 0, weekday, computed.tm_yday, -1))

    return


def check_rtc(rtc):
    # get time struct from rtc
    now = rtc.datetime

    return now.tm_month, now.tm_mday, now.tm_hour, now.tm_min


###################### lighting functions ######################

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
    # lookup table for function
    fade = [0x0, 0x41, 0x102, 0x244, 0x405, 0x644, 0x8fd, 0xc2f, 0xfd5, 0x13ed, 0x1872, 0x1d60, 0x22b1, 0x2861, 0x2e69,
            0x34c3, 0x3b6a, 0x4256, 0x4980, 0x50e1, 0x5872, 0x602b, 0x6803, 0x6ff5, 0x77f6, 0x7fff, 0x8809, 0x900a,
            0x97fc, 0x9fd4, 0xa78d, 0xaf1e, 0xb67f, 0xbda9, 0xc495, 0xcb3c, 0xd196, 0xd79e, 0xdd4e, 0xe29f, 0xe78d,
            0xec12, 0xf02a, 0xf3d0, 0xf702, 0xf9bb, 0xfbfa, 0xfdbb, 0xfefd, 0xffbe, 0xffff, 0xffbe, 0xfefd, 0xfdbb,
            0xfbfa, 0xf9bb, 0xf702, 0xf3d0, 0xf02a, 0xec12, 0xe78d, 0xe29f, 0xdd4e, 0xd79e, 0xd196, 0xcb3c, 0xc495,
            0xbda9, 0xb67f, 0xaf1e, 0xa78d, 0x9fd4, 0x97fc, 0x900a, 0x8809, 0x8000, 0x77f6, 0x6ff5, 0x6803, 0x602b,
            0x5872, 0x50e1, 0x4980, 0x4256, 0x3b6a, 0x34c3, 0x2e69, 0x2861, 0x22b1, 0x1d60, 0x1872, 0x13ed, 0xfd5,
            0xc2f, 0x8fd, 0x644, 0x405, 0x244, 0x102, 0x41]

    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
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
    # lookup table for function
    decay = [0x979, 0xc85, 0x15a8, 0x24dd, 0x3a30, 0x5594, 0x770a, 0x9e9d, 0xcc42, 0xffff, 0xffff, 0xf5f6, 0xec4f,
             0xe30b, 0xda22, 0xd196, 0xc95e, 0xc17b, 0xb9e1, 0xb29c, 0xab98, 0xa4dd, 0x9e69, 0x9831, 0x923a, 0x8c7e,
             0x86fd, 0x81b0,0x7c9e, 0x77bb, 0x7305, 0x6e83, 0x6a30, 0x6604, 0x6205, 0x5e2e, 0x5a7f, 0x56f0, 0x5388,
             0x5041, 0x4d1b, 0x4a16, 0x472b, 0x4460, 0x41b7, 0x3f21, 0x3ca5, 0x3a44, 0x37fd, 0x35c9, 0x33b0, 0x31aa,
             0x2fb8, 0x2dd9, 0x2c08, 0x2a51, 0x28a7, 0x2711, 0x2587, 0x2412, 0x22a3, 0x2148, 0x1ff9, 0x1eb8, 0x1d84,
             0x1c5d, 0x1b43, 0x1a30, 0x192a, 0x182b, 0x1738, 0x1653, 0x156d, 0x149c, 0x13ca, 0x1305, 0x1247, 0x1190,
             0x10df, 0x1034, 0xf91, 0xef3, 0xe5d, 0xdcc, 0xd43, 0xcc0, 0xc3d, 0xbc7, 0xb51, 0xadb, 0xa72, 0xa09, 0x9a0,
             0x944, 0x8e2, 0x88d, 0x838, 0x7e2, 0x794, 0x745]

    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
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


def sequence_morse(pwm, color_list, cycle_time, dimmer):
    # lookup table for function
    morse = [0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c,
             0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c,
             0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644,
             0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d,
             0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d,
             0xf9bb, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff,
             0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d,
             0x8000, 0x5872, 0x34c3, 0x1872, 0x644]

    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int(
                    (color_list[color][0] * int(morse[i] - ((morse[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int(
                    (color_list[color][1] * int(morse[i] - ((morse[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int(
                    (color_list[color][2] * int(morse[i] - ((morse[i] / 0xffff) * dimmer))))

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
    # lookup table for function
    breathe = [0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0, 0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee, 0xdfff, 0xe9e8,
               0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffb5, 0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee, 0xc91b, 0xbcf4,
               0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28, 0x8495, 0x8128, 0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0,
               0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee, 0xdfff, 0xe9e8, 0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffff, 0xffb5,
               0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee, 0xc91b, 0xbcf4, 0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28,
               0x8495, 0x8128, 0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0, 0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee,
               0xdfff, 0xe9e8, 0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffb5, 0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee,
               0xc91b, 0xbcf4, 0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28, 0x8495, 0x8128]

    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
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
                        (color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / (inc))

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
                        (color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / (inc))

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
                        (color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / (inc))

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
                        (color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int(
                        (color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int(
                        (color_difference[2] * i * (0xffff - dimmer)) / (inc))

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None


############################# main #############################

def main():
    ################ initialization and startup ################

    # create pwm object for light control
    pwm, rtc = initialize_i2c_devices()

    # initialize input bus for hardware inputs
    inputs = initialize_input_bus()

    # load sqlite database for program
    cursor = load_database()

    # blink white 3 times for startup
    startup_blink(pwm)

    # read default scene from database
    function, color_list, cycle_time, dimmer = read_default_scene(cursor)

    ########## start lighting thread and check inputs ##########

    # set lighting function and arguments for lighting thread
    light_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))

    # start thread in background
    light_thread.start()




    # check for connections to become active
    while True:
        states = read_input_bus(inputs)
        if any(states):
            # element of states list that is True is active connection
            connection = states.index(True)

            # stop light thread to run connection thread
            stop_flag.set()
            light_thread.join()
            stop_flag.clear()

            # get connection scene info and run thread (+1 for sqlite 1-indexing)
            conn_function, conn_color_list, conn_cycle_time, conn_dimmer = read_connection_scene(cursor, connection + 1)
            light_thread = threading.Thread(target=conn_function,
                                            args=(pwm, conn_color_list, conn_cycle_time, conn_dimmer))
            light_thread.start()

            while True:
                # check connections while running light_thread
                states = read_input_bus(inputs)
                if any(states):
                    # get index of active connection
                    temp = states.index(True)
                else:
                    # no connections are active so return to default scene
                    stop_flag.set()
                    light_thread.join()
                    stop_flag.clear()

                    # get fresh default info in case table has changed
                    function, color_list, cycle_time, dimmer = read_default_scene(cursor)
                    light_thread = threading.Thread(target=function, args=(pwm, color_list, cycle_time, dimmer))
                    light_thread.start()
                    break

                if temp == connection:
                    # if same connection is still active continue running associated scene
                    time.sleep(0.01)
                    continue
                elif temp != connection:
                    # if a new, lower index connection is active, get new connection value and stop light thread
                    connection = temp
                    stop_flag.set()
                    light_thread.join()
                    stop_flag.clear()

                    # restart light thread with new connection scene info
                    conn_function, conn_color_list, conn_cycle_time, conn_dimmer = read_connection_scene(cursor,
                                                                                                         connection + 1)
                    light_thread = threading.Thread(target=conn_function,
                                                    args=(pwm, conn_color_list, conn_cycle_time, conn_dimmer))
                    light_thread.start()
        else:
            # if no connections are active then continue running default scene
            time.sleep(0.01)








if __name__ == "__main__":

    main()
