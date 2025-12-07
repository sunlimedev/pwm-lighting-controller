# helpful links:
# https://learn.adafruit.com/circuitpython-on-raspberrypi-linux/installing-circuitpython-on-raspberry-pi
# https://github.com/adafruit/Adafruit_CircuitPython_NeoPixel/issues/43

# run inside virtual environment and in project directory:
# $ cd hardware-pwm-test
# $ source .venv/bin/activate
# $ python filename.py

# Package                                  Version
# ---------------------------------------- -----------
# Adafruit-Blinka                          8.68.0
# Adafruit-Blinka-Raspberry-Pi5-Neopixel   1.0.0rc2
# adafruit-circuitpython-busdevice         5.2.14
# adafruit-circuitpython-connectionmanager 3.1.6
# adafruit-circuitpython-neopixel          6.3.18
# adafruit-circuitpython-pca9685           3.4.20
# adafruit-circuitpython-pixelbuf          2.0.10
# adafruit-circuitpython-register          1.11.1
# adafruit-circuitpython-requests          4.1.15
# adafruit-circuitpython-typing            1.12.3
# Adafruit-GPIO                            1.0.3
# Adafruit-PCA9685                         1.0.1
# Adafruit-PlatformDetect                  3.85.0
# Adafruit-PureIO                          1.1.11
# adafruit-python-shell                    1.11.0
# args                                     0.1.0
# arrow                                    1.4.0
# binho-host-adapter                       0.1.6
# blinker                                  1.9.0
# click                                    8.3.1
# clint                                    0.5.1
# colorzero                                2.0
# Flask                                    3.1.2
# gpiozero                                 2.0.1
# itsdangerous                             2.2.0
# Jinja2                                   3.1.6
# lgpio                                    0.2.2.0
# MarkupSafe                               3.0.3
# numpy                                    2.3.5
# pip                                      25.1.1
# pyftdi                                   0.57.1
# pyserial                                 3.5
# python-dateutil                          2.9.0.post0
# python-time                              0.3.0
# pyusb                                    1.3.1
# RPi.GPIO                                 0.7.1
# rpi_ws281x                               5.0.0
# setuptools                               80.9.0
# six                                      1.17.0
# smbus2                                   0.5.0
# spidev                                   3.8
# sysv_ipc                                 1.1.0
# thread                                   2.0.5
# typing_extensions                        4.15.0
# tzdata                                   2025.2
# Werkzeug                                 3.1.4

# might need to change sine functions so that dimmer doesn't bring brightness under 50%

import board
import busio
# import time
# import numpy as np
from adafruit_pca9685 import PCA9685
from threading import Thread, Event
from gpiozero import PWMLED, Button
from time import sleep

# global variables/flags
i2c = None
pwm = None
inputs = [None, None, None, None, None, None, None, None]
stop_flag = Event()

# lists for lighting functions
decay_list = [0x8165,0x8777,0x9777,0xb777,0xd7ff,0xffff,0xf306,0xe814,0xde2c,0xd536,0xcd1a,0xc5c4,0xbf20,0xb91e,0xb3af,0xaec4,0xaa50,0xa649,0xa2a5,0x9f59,0x9c5d,0x99aa,0x9739,0x9503,0x9303,0x9134,0x8f91,0x8e15,0x8cbe,0x8b88,0x8a6f,0x8971,0x888b,0x87ba,0x86fe,0x8654,0x85ba,0x852e,0x84b0,0x843e,0x83d6,0x8379,0x8324,0x82d8,0x8292,0x8254,0x821b,0x81e8,0x81b9,0x818f]
sine_list1 = [0xbfff,0xc805,0xcfea,0xd78f,0xded4,0xe59d,0xebcf,0xf14f,0xf609,0xf9e8,0xfcdd,0xfedd,0xffdf,0xffdf,0xfedd,0xfcdd,0xf9e8,0xf609,0xf14f,0xebcf,0xe59d,0xded4,0xd78f,0xcfea,0xc805,0xbfff,0xb7fa,0xb015,0xa870,0xa12a,0x9a61,0x9430,0x8eaf,0x89f6,0x8617,0x8321,0x8122,0x8020,0x8020,0x8122,0x8321,0x8617,0x89f6,0x8eaf,0x9430,0x9a61,0xa12a,0xa870,0xb015,0xb7fa]
sine_list2 = [0xf76c,0xf2f9,0xedb9,0xe7c0,0xe126,0xda07,0xd27f,0xcaac,0xc2ad,0xbaa4,0xb2b1,0xaaf3,0xa38a,0x9c95,0x962e,0x9070,0x8b72,0x8748,0x8403,0x81b0,0x8059,0x8003,0x80af,0x825b,0x8500,0x8893,0x8d05,0x9246,0x983f,0x9ed8,0xa5f7,0xad80,0xb553,0xbd51,0xc55a,0xcd4e,0xd50b,0xdc74,0xe36a,0xe9d1,0xef8f,0xf48d,0xf8b7,0xfbfb,0xfe4e,0xffa5,0xfffb,0xff4f,0xfda3,0xfaff]
sine_list3 = [0x8893,0x8500,0x825b,0x80af,0x8003,0x8059,0x81b0,0x8403,0x8748,0x8b72,0x9070,0x962e,0x9c95,0xa38a,0xaaf3,0xb2b1,0xbaa4,0xc2ad,0xcaac,0xd27f,0xda07,0xe126,0xe7c0,0xedb9,0xf2f9,0xf76c,0xfaff,0xfda3,0xff4f,0xfffb,0xffa5,0xfe4e,0xfbfb,0xf8b7,0xf48d,0xef8f,0xe9d1,0xe36a,0xdc74,0xd50b,0xcd4e,0xc55a,0xbd51,0xb553,0xad80,0xa5f7,0x9ed8,0x983f,0x9246,0x8d05]

# good
def initialize_pwm():
    # create a global variable for the i2c link and pwm bus
    global i2c, pwm

    # create the I2C bus interface
    i2c = busio.I2C(board.SCL, board.SDA)

    # create a simple PCA9685 class instance called pwm
    pwm = PCA9685(i2c)

    # set the PWM frequency to 1KHz
    pwm.frequency = 1000

# good
def initialize_input_bus(input_pins):
    # create a global variable for the input bus pins
    global inputs
    
    # initialize input_pins as gpiozero Button class instances
    inputs[0] = Button(pin=input_pins[0], pull_up=True)
    inputs[1] = Button(pin=input_pins[1], pull_up=True)
    inputs[2] = Button(pin=input_pins[2], pull_up=True)
    inputs[3] = Button(pin=input_pins[3], pull_up=True)
    inputs[4] = Button(pin=input_pins[4], pull_up=True)
    inputs[5] = Button(pin=input_pins[5], pull_up=True)
    inputs[6] = Button(pin=input_pins[6], pull_up=True)
    inputs[7] = Button(pin=input_pins[7], pull_up=True)

# good
def read_input_bus():
    # gather states from inputs global variable
    states = [i.is_held for i in inputs]

    # return states as list to caller function
    return states

# good
def startup_blink():
    for i in range(3):
        # set all channels to max (white)
        pwm.channels[0].duty_cycle = 0xffff
        pwm.channels[1].duty_cycle = 0xffff
        pwm.channels[2].duty_cycle = 0xffff
        # hold white for 0.25 second
        sleep(0.25)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.25 second
        sleep(0.25)

# good
def sequence_solid(color_list, cycle_time, dimmer):
    while True:
        for color in range(len(color_list)):
            # assign color list contents to color channels with brightness
            pwm.channels[0].duty_cycle = int(color_list[color][0]*(65535 - dimmer))
            pwm.channels[1].duty_cycle = int(color_list[color][1]*(65535 - dimmer))
            pwm.channels[2].duty_cycle = int(color_list[color][2]*(65535 - dimmer))

            # check for raised flag during the cycle_time timeout
            if stop_flag.wait(timeout=cycle_time):
                return None

#  need to change the sine table so it starts at 0 (desmos)
def sequence_fade(color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    while True:
        for color in range(len(color_list)):
            for i in range(0, 50):
                # assign final color with brightness to color channels
                pwm.channels[0].duty_cycle = int(color_list[color][0] * (sine_list1[i] - dimmer))
                pwm.channels[1].duty_cycle = int(color_list[color][1] * (sine_list1[i] - dimmer))
                pwm.channels[2].duty_cycle = int(color_list[color][2] * (sine_list1[i] - dimmer))

                # check for raised flag during the step time timeout
                if stop_flag.wait(timeout=step_time):
                    return None

# need to make it so table can reach zero rather than stopping at 50%
def sequence_decay(color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    while True:
        for color in range(len(color_list)):
            for i in range(0, 50):
                # assign final color with brightness to color channels
                pwm.channels[0].duty_cycle = int(color_list[color][0] * (decay_list[i] - dimmer))
                pwm.channels[1].duty_cycle = int(color_list[color][1] * (decay_list[i] - dimmer))
                pwm.channels[2].duty_cycle = int(color_list[color][2] * (decay_list[i] - dimmer))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None

# stays very white at 100% brightness, need to change sine table
def rainbow_smooth(_, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    while True:
        for i in range(0, 50):
            #                            0-65535     -     scaled dimmer value
            pwm.channels[0].duty_cycle = sine_list1[i] - int((sine_list1[i] / 65535) * dimmer)
            pwm.channels[1].duty_cycle = sine_list2[i] - int((sine_list1[i] / 65535) * dimmer)
            pwm.channels[2].duty_cycle = sine_list3[i] - int((sine_list1[i] / 65535) * dimmer)

            # check for raised flag during the step time timeout
            if stop_flag.wait(timeout=step_time):
                return None


def main():
    ################## user-defined variables ##################

    # provide input pins to use
    input_pins = [22, 10, 19, 11, 5, 6, 13, 26]

    # choose speed (1 = slowest, 10 = fastest)
    speed = 10

    # choose brightness (1 = lowest, 10 = brightest)
    brightness = 10

    # create ordered list of color values
    color_list = [[1, 0, 0],     # red
                  [1, 0.3, 0],   # orange
                  [1, 1, 0],     # yellow
                  [0, 1, 0],     # green
                  [0, 0, 1],     # blue
                  [1, 0, 1]]     # purple

    ################ initialization and startup ################

    # initialize PWM bus for color channels
    initialize_pwm()

    # initialize GPIO pins for each input bit
    initialize_input_bus(input_pins)

    # blink white 3 times for startup
    startup_blink()

    ############## rework variables for functions ##############

    # derive cycle time from speed (1 = 5s, 10 = 0.5s)
    cycle_time = -(speed / 2) + 5.5

    # adjust brightness value (max dimming is 50% -- 0x7FFF or 32767)
    dimmer = int((32768 / 9) * (10 - brightness))

    ################ choose a lighting function ################

    # test a lighting function
    # sequence_solid(color_list, cycle_time, dimmer)
    # sequence_fade(color_list, cycle_time, dimmer)
    # sequence_decay(color_list, cycle_time, dimmer)
    # rainbow_smooth(color_list, cycle_time, dimmer)

    light_thread = Thread(target=sequence_decay, args=(color_list, cycle_time, dimmer))
    light_thread.start()

    while True:
        states = read_input_bus()
        if states[0] == True:
            stop_flag.set()
            break
        else:
            sleep(0.01)

    light_thread.join()


if __name__ == "__main__":
    # direct execution check
    main()
