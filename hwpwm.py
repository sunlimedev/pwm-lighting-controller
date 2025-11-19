# helpful links:
# https://learn.adafruit.com/circuitpython-on-raspberrypi-linux/installing-circuitpython-on-raspberry-pi
# https://github.com/adafruit/Adafruit_CircuitPython_NeoPixel/issues/43

# run inside virtual environment and in project directory:
# $ cd hardware-pwm-test
# $ source .venv/bin/activate
# $ python hwpwm.py

# required packages:
# Adafruit-Blinka                          8.67.0
# adafruit-circuitpython-busdevice         5.2.14
# adafruit-circuitpython-connectionmanager 3.1.6
# adafruit-circuitpython-neopixel          6.3.18
# adafruit-circuitpython-pca9685           3.4.20
# adafruit-circuitpython-pixelbuf          2.0.10
# adafruit-circuitpython-register          1.11.1
# adafruit-circuitpython-requests          4.1.15
# adafruit-circuitpython-typing            1.12.3
# Adafruit_GPIO                            1.0.3
# Adafruit_PCA9685                         1.0.1
# Adafruit-PlatformDetect                  3.84.1
# Adafruit-PureIO                          1.1.11
# adafruit-python-shell                    1.11.0
# args                                     0.1.0
# arrow                                    1.4.0
# binho-host-adapter                       0.1.6
# clint                                    0.5.1
# colorzero                                2.0
# gpiozero                                 2.0.1
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


import board
import busio
import time
import numpy as np
from adafruit_pca9685 import PCA9685
from threading import Thread, Event
from gpiozero import PWMLED, Button
from time import sleep

# global variables/flags
i2c = None
pwm = None
inputs = [None, None, None, None, None, None, None, None]
stop_flag = Event()


def initialize_pwm(red_pin, green_pin, blue_pin):
    global i2c, pwm
    
    # create the I2C bus interface
    i2c = busio.I2C(board.SCL, board.SDA)
    
    # create a simple PCA9685 class instance called pwm
    pwm = PCA9685(i2c)
    
    # set the PWM frequency to 1KHz
    pwm.frequency = 1000


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


def read_input_bus():
	# gather states from inputs global variable
	states = [i.is_held for i in inputs]
	
    # return states as list to caller function
	return states


def startup_blink():
    for i in range(3):
        # set all channels to max (white)
        pwm.channels[0].duty_cycle = 0xFFFF
        pwm.channels[1].duty_cycle = 0xFFFF
        pwm.channels[2].duty_cycle = 0xFFFF
        # hold white for 0.25 second
        sleep(0.25)

        # set all channels off
        pwm.channels[0].duty_cycle = 0x0000
        pwm.channels[1].duty_cycle = 0x0000
        pwm.channels[2].duty_cycle = 0x0000
        # hold off for 0.25 second
        sleep(0.25)


def sequence_solid(color_list, cycle_time):
    while True:
        for color in color_list:
            # assign color list contents to color channels
            red.value, green.value, blue.value = color

            # check for raised flag during the cycle time timeout
            if stop_flag.wait(timeout=cycle_time):
                return None


def sequence_fade(color_list, cycle_time):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    # create 50 element list with values spaced equally between 0 and 2pi
    steps = np.linspace(0, 2 * np.pi, 50)

    while True:
        for color in color_list:
            # assign color values to temp color variables
            temp_red, temp_green, temp_blue = color

            for x in steps:
                # get fade brightness based on sine function
                brightness = (0.5 * (np.sin(x + (4.5 * np.pi) / 3))) + 0.5
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # check for raised flag during the step time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_pulse(color_list, cycle_time):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    while True:
        for color in color_list:
            # assign color values to temp color variables
            temp_red, temp_green, temp_blue = color

            for x in range(0, 10):
                # quickly increase brightness based on linear function
                brightness = (1 / 9) * x
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # check for raised flag during the step time timeout
                if stop_flag.wait(timeout=step_time):
                    return None

            for x in range(10, 50):
                # decrease brightness based on exponential decay function
                brightness = 2.2 * np.exp(-0.08 * x)
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # check for raised flag during the step time timeout
                if stop_flag.wait(timeout=step_time):
                    return None

            # check for raised flag during the step time timeout
            if stop_flag.wait(timeout=step_time):
                return None


def rainbow_smooth(color_list, cycle_time):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    # create 50 element list with values spaced equally between 0 and 2pi
    x_values = np.linspace(0, 2 * np.pi, 50)

    while True:
        for x in x_values:
            red.value = (0.5 * np.sin(x)) + 0.5
            green.value = (0.5 * np.sin(x + ((4 * np.pi) / 3))) + 0.5
            blue.value = (0.5 * np.sin(x + ((2 * np.pi) / 3))) + 0.5

            # check for raised flag during the step time timeout
            if stop_flag.wait(timeout=step_time):
                return None


def main():
    ################## user-defined variables ##################

    # provide GPIO pins to use
    red_pin = 13
    green_pin = 19
    blue_pin = 26

	# provide input pins to use
    input_pins = [14, 15, 18, 23, 24, 25, 8, 7]

    # choose speed (1 = slowest, 10 = fastest)
    speed = 5

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

    # initialize GPIO pins for each color channel
    initialize_pwm(red_pin, green_pin, blue_pin)

    # initialize GPIO pins for each input bit
    initialize_input_bus(input_pins)

    # blink white 3 times for startup
    startup_blink()

    ############## rework variables for functions ##############

    # derive cycle time from speed (1 = 5s, 10 = 0.5s)
    cycle_time = -(speed / 2) + 5.5

    # adjust brightness value
    brightness = 1

    ################ choose a lighting function ################

    # test a lighting function

    # sequence_solid(color_list, cycle_time)
    # sequence_fade(color_list, cycle_time)
    # sequence_pulse(color_list, cycle_time)
    # rainbow_smooth(cycle_time)

    light_thread = Thread(target=rainbow_smooth, args=(color_list, cycle_time))
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

