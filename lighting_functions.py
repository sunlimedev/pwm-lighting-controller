import board
import busio
import time
from adafruit_ds3231 import DS3231
from adafruit_pca9685 import PCA9685
from threading import Thread, Event
from gpiozero import Button

# thread stop flag
stop_flag = Event()

# lists for lighting functions
decay = [0x979, 0xc85, 0x15a8, 0x24dd, 0x3a30, 0x5594, 0x770a, 0x9e9d, 0xcc42, 0xffff, 0xffff, 0xf5f6, 0xec4f, 0xe30b, 0xda22, 0xd196, 0xc95e, 0xc17b, 0xb9e1, 0xb29c, 0xab98, 0xa4dd, 0x9e69, 0x9831, 0x923a, 0x8c7e, 0x86fd, 0x81b0, 0x7c9e, 0x77bb, 0x7305, 0x6e83, 0x6a30, 0x6604, 0x6205, 0x5e2e, 0x5a7f, 0x56f0, 0x5388, 0x5041, 0x4d1b, 0x4a16, 0x472b, 0x4460, 0x41b7, 0x3f21, 0x3ca5, 0x3a44, 0x37fd, 0x35c9, 0x33b0, 0x31aa, 0x2fb8, 0x2dd9, 0x2c08, 0x2a51, 0x28a7, 0x2711, 0x2587, 0x2412, 0x22a3, 0x2148, 0x1ff9, 0x1eb8, 0x1d84, 0x1c5d, 0x1b43, 0x1a30, 0x192a, 0x182b, 0x1738, 0x1653, 0x156d, 0x149c, 0x13ca, 0x1305, 0x1247, 0x1190, 0x10df, 0x1034, 0xf91, 0xef3, 0xe5d, 0xdcc, 0xd43, 0xcc0, 0xc3d, 0xbc7, 0xb51, 0xadb, 0xa72, 0xa09, 0x9a0, 0x944, 0x8e2, 0x88d, 0x838, 0x7e2, 0x794, 0x745]
sine0 = [0x0, 0x41, 0x102, 0x244, 0x405, 0x644, 0x8fd, 0xc2f, 0xfd5, 0x13ed, 0x1872, 0x1d60, 0x22b1, 0x2861, 0x2e69, 0x34c3, 0x3b6a, 0x4256, 0x4980, 0x50e1, 0x5872, 0x602b, 0x6803, 0x6ff5, 0x77f6, 0x7fff, 0x8809, 0x900a, 0x97fc, 0x9fd4, 0xa78d, 0xaf1e, 0xb67f, 0xbda9, 0xc495, 0xcb3c, 0xd196, 0xd79e, 0xdd4e, 0xe29f, 0xe78d, 0xec12, 0xf02a, 0xf3d0, 0xf702, 0xf9bb, 0xfbfa, 0xfdbb, 0xfefd, 0xffbe, 0xffff, 0xffbe, 0xfefd, 0xfdbb, 0xfbfa, 0xf9bb, 0xf702, 0xf3d0, 0xf02a, 0xec12, 0xe78d, 0xe29f, 0xdd4e, 0xd79e, 0xd196, 0xcb3c, 0xc495, 0xbda9, 0xb67f, 0xaf1e, 0xa78d, 0x9fd4, 0x97fc, 0x900a, 0x8809, 0x8000, 0x77f6, 0x6ff5, 0x6803, 0x602b, 0x5872, 0x50e1, 0x4980, 0x4256, 0x3b6a, 0x34c3, 0x2e69, 0x2861, 0x22b1, 0x1d60, 0x1872, 0x13ed, 0xfd5, 0xc2f, 0x8fd, 0x644, 0x405, 0x244, 0x102, 0x41]
sine1 = [0xbfff, 0xc6d5, 0xcd63, 0xd3a2, 0xd98e, 0xdf1e, 0xe44f, 0xe91b, 0xed7c, 0xf16e, 0xf4ee, 0xf7f8, 0xfa88, 0xfc9d, 0xfe34, 0xff4b, 0xffe2, 0xfff8, 0xff8c, 0xfe9f, 0xfd33, 0xfb48, 0xf8e0, 0xf5ff, 0xf2a6, 0xeed9, 0xea9c, 0xe5f4, 0xe0e4, 0xdb73, 0xd5a5, 0xcf81, 0xc90c, 0xc24e, 0xbb4d, 0xb40f, 0xac9d, 0xa4fe, 0x9d3a, 0x9558, 0x8d61, 0x855c, 0x7d51, 0x754a, 0x6d4d, 0x6563, 0x5d94, 0x55e7, 0x4e66, 0x4716, 0x4000, 0x392a, 0x329c, 0x2c5d, 0x2671, 0x20e1, 0x1bb0, 0x16e4, 0x1283, 0xe91, 0xb11, 0x807, 0x577, 0x362, 0x1cb, 0xb4, 0x1d, 0x7, 0x73, 0x160, 0x2cc, 0x4b7, 0x71f, 0xa00, 0xd59, 0x1126, 0x1563, 0x1a0b, 0x1f1b, 0x248c, 0x2a5a, 0x307e, 0x36f3, 0x3db1, 0x44b2, 0x4bf0, 0x5362, 0x5b01, 0x62c5, 0x6aa7, 0x729e, 0x7aa3, 0x82ae, 0x8ab5, 0x92b2, 0x9a9c, 0xa26b, 0xaa18, 0xb199, 0xb8e9]
sine2 = [0xbfff, 0xb8e9, 0xb199, 0xaa18, 0xa26b, 0x9a9c, 0x92b2, 0x8ab5, 0x82ae, 0x7aa3, 0x729e, 0x6aa7, 0x62c5, 0x5b01, 0x5362, 0x4bf0, 0x44b2, 0x3db1, 0x36f3, 0x307e, 0x2a5a, 0x248c, 0x1f1b, 0x1a0b, 0x1563, 0x1126, 0xd59, 0xa00, 0x71f, 0x4b7, 0x2cc, 0x160, 0x73, 0x7, 0x1d, 0xb4, 0x1cb, 0x362, 0x577, 0x807, 0xb11, 0xe91, 0x1283, 0x16e4, 0x1bb0, 0x20e1, 0x2671, 0x2c5d, 0x329c, 0x392a, 0x4000, 0x4716, 0x4e66, 0x55e7, 0x5d94, 0x6563, 0x6d4d, 0x754a, 0x7d51, 0x855c, 0x8d61, 0x9558, 0x9d3a, 0xa4fe, 0xac9d, 0xb40f, 0xbb4d, 0xc24e, 0xc90c, 0xcf81, 0xd5a5, 0xdb73, 0xe0e4, 0xe5f4, 0xea9c, 0xeed9, 0xf2a6, 0xf5ff, 0xf8e0, 0xfb48, 0xfd33, 0xfe9f, 0xff8c, 0xfff8, 0xffe2, 0xff4b, 0xfe34, 0xfc9d, 0xfa88, 0xf7f8, 0xf4ee, 0xf16e, 0xed7c, 0xe91b, 0xe44f, 0xdf1e, 0xd98e, 0xd3a2, 0xcd63, 0xc6d5]
morse = [0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644, 0x0, 0x644, 0x1872, 0x34c3, 0x5872, 0x7fff, 0xa78d, 0xcb3c, 0xe78d, 0xf9bb, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xf9bb, 0xe78d, 0xcb3c, 0xa78d, 0x8000, 0x5872, 0x34c3, 0x1872, 0x644]
wigwag = [0x0, 0x0, 0xffff, 0xffff, 0x0, 0xffff, 0xffff, 0x0, 0xffff, 0xffff]
sos = [0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0xffff, 0xffff, 0xffff, 0xffff, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0]
breathe = [0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0, 0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee, 0xdfff, 0xe9e8, 0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffb5, 0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee, 0xc91b, 0xbcf4, 0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28, 0x8495, 0x8128, 0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0, 0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee, 0xdfff, 0xe9e8, 0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffff, 0xffb5, 0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee, 0xc91b, 0xbcf4, 0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28, 0x8495, 0x8128, 0x8000, 0x8128, 0x8495, 0x8a28, 0x91ae, 0x9ae0, 0xa569, 0xb0e9, 0xbcf4, 0xc91b, 0xd4ee, 0xdfff, 0xe9e8, 0xf24e, 0xf8e2, 0xfd67, 0xffb5, 0xffb5, 0xfd67, 0xf8e2, 0xf24e, 0xe9e8, 0xdfff, 0xd4ee, 0xc91b, 0xbcf4, 0xb0e9, 0xa569, 0x9ae0, 0x91ae, 0x8a28, 0x8495, 0x8128]


def initialize_i2c_devices():
    # create the I2C bus interface
    i2c = busio.I2C(board.SCL, board.SDA)

    # create a PCA9685 object and set the frequency for LED control
    pwm = PCA9685(i2c)
    pwm.frequency = 1000

    # create a DS3231 object
    rtc = DS3231(i2c)

    # set example time of Friday, December 19, 2025 14:36:00 Eastern Standard Time
    # tm_year=2025, tm_mon=12, tm_mday=19, tm_hour=15, tm_min=44, tm_sec=00, tm_wday=4, tm_yday=335, tm_isdst=-1
    rtc.datetime = time.struct_time((2025,12,19,14,36,0,4,353,-1))

    return pwm, rtc


def initialize_input_bus(input_pins):
    # create a list for the input bus pins
    inputs = [None, None, None, None, None, None, None, None]
    
    # initialize input_pins as gpiozero Button class instances
    inputs[0] = Button(pin=input_pins[0], pull_up=True)
    inputs[1] = Button(pin=input_pins[1], pull_up=True)
    inputs[2] = Button(pin=input_pins[2], pull_up=True)
    inputs[3] = Button(pin=input_pins[3], pull_up=True)
    inputs[4] = Button(pin=input_pins[4], pull_up=True)
    inputs[5] = Button(pin=input_pins[5], pull_up=True)
    inputs[6] = Button(pin=input_pins[6], pull_up=True)
    inputs[7] = Button(pin=input_pins[7], pull_up=True)
    
    return inputs


def read_input_bus(inputs):
    # gather states from inputs global variable
    states = [i.is_held for i in inputs]

    return states


def startup_blink(pwm):
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
    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(sine0[i] - ((sine0[i] / 0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(sine0[i] - ((sine0[i] / 0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(sine0[i] - ((sine0[i] / 0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_decay(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(decay[i] - ((decay[i]/0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(decay[i] - ((decay[i]/0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(decay[i] - ((decay[i]/0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_morse(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(morse[i] - ((morse[i]/0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(morse[i] - ((morse[i]/0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(morse[i] - ((morse[i]/0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_wigwag(pwm, color_list, cycle_time, dimmer):
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
    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(sos[i] - ((sos[i]/0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(sos[i] - ((sos[i]/0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(sos[i] - ((sos[i]/0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_breathe(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    # get color_list length
    num_colors = len(color_list)

    while True:
        for color in range(num_colors):
            for i in range(100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(breathe[i] - ((breathe[i]/0xffff) * dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(breathe[i] - ((breathe[i]/0xffff) * dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(breathe[i] - ((breathe[i]/0xffff) * dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def crossfade(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop and set increment count
    step_time = 0.025

    increment_dict = {
        1 : 10,
        2 : 20,
        3 : 30,
        4 : 40,
        5 : 50
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
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int((color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int((color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int((color_difference[2] * i * (0xffff - dimmer)) / (inc))

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
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int((color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int((color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int((color_difference[2] * i * (0xffff - dimmer)) / (inc))

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None


def crossfade_hold(pwm, color_list, cycle_time, dimmer):
    # create smaller time increment for loop and set increment count
    step_time = 0.015

    increment_dict = {
        1 : 10,
        2 : 20,
        3 : 30,
        4 : 40,
        5 : 50
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
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int((color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int((color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int((color_difference[2] * i * (0xffff - dimmer)) / (inc))

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
                    pwm.channels[0].duty_cycle = int(current_color[0] * (0xffff - dimmer)) + int((color_difference[0] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[1].duty_cycle = int(current_color[1] * (0xffff - dimmer)) + int((color_difference[1] * i * (0xffff - dimmer)) / (inc))
                    pwm.channels[2].duty_cycle = int(current_color[2] * (0xffff - dimmer)) + int((color_difference[2] * i * (0xffff - dimmer)) / (inc))

                    # check for raised flag during the step_time timeout
                    if stop_flag.wait(timeout=step_time):
                        return None


def main():
    ################## user-defined variables ##################

    # choose lighting speed (1 = slowest, 5 = fastest)
    speed = 4

    # choose brightness (1 = lowest, 5 = brightest)
    brightness = 5

    # create ordered list of color values
    color_list = [[1, 0, 0],
                  [0, 1, 0],
                  [0, 0, 1]]


    ################ initialization and startup ################

    # create pwm object for light control and rtc object for timing
    pwm, rtc = initialize_i2c_devices()

    # provide input pins to use
    input_pins = [22, 10, 9, 11, 5, 6, 13, 26]
    
    # initialize input bus for hardware inputs
    inputs = initialize_input_bus(input_pins)

    # blink white 3 times for startup
    startup_blink(pwm)


    ############## rework variables for functions ##############

    # derive cycle time from speed
    cycle_time = -speed + 6

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x3333 * (5 - brightness))


    ####### start lighting thread and read button inputs #######

    # set lighting function and arguments for lighting thread
    light_thread = Thread(target=sequence_wigwag, args=(pwm, color_list, cycle_time, dimmer))
    
    # start thread in background
    light_thread.start()

    # run thread until input[0] is true
    while True:
        states = read_input_bus(inputs)
        if states[0] == True:
            stop_flag.set()
            break
        else:
            time.sleep(0.01)

    # gracefully terminate thread
    light_thread.join()
    stop_flag.clear()


if __name__ == "__main__":
    main()
