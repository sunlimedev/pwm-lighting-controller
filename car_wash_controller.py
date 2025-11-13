# Car Wash Controller
# Developed for Signal-Tech in Erie, PA
# Designed for use on Raspberry Pi 4 Model B 8GB

# Changes from last version:
# PWM frequency increased to 4kHz
# Shortened startup sequence time and blinks
# Remove rainbow spike function
# Remove rainbow anybright function
# Add brightness variable (not fully implemented)

# To-do:
# Add stop time w/ weekday and time

# Packages:
import random
import numpy as np
from gpiozero import PWMLED
from time import sleep

# Global variables:
red = None
green = None
blue = None
PI = 3.1415


def initialize_pwm(red_pin, green_pin, blue_pin):
    # create global variable for the GPIO pins
    global red, green, blue

    # set passed GPIO pin as PWM color channel
    red = PWMLED(pin=red_pin, active_high=True, initial_value=0, frequency=4000)
    green = PWMLED(pin=green_pin, active_high=True, initial_value=0, frequency=4000)
    blue = PWMLED(pin=blue_pin,  active_high=True, initial_value=0, frequency=4000)


def startup_blink():
    for i in range(3):
        # set all channels to max (white)
        red.value = 0.5
        green.value = 0.5
        blue.value = 0.5
        # hold white for 0.25 second
        sleep(0.25)

        # set all channels off
        red.value = 0
        green.value = 0
        blue.value = 0
        # hold off for 0.25 second
        sleep(0.25)


def sequence_solid(color_list, cycle_time):
    while True:
        for color in color_list:
            # assign color list contents to color channels
            red.value, green.value, blue.value = color

            # hold the current color for the cycle time
            sleep(cycle_time)


def sequence_fade(color_list, cycle_time):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    # create 50 element list with values spaced equally between 0 and 2pi
    steps = np.linspace(0, 2 * PI, 50)

    while True:
        for color in color_list:
            # assign color values to temp color variables
            temp_red, temp_green, temp_blue = color

            for x in steps:
                # get fade brightness based on sine function
                brightness = (0.5 * (np.sin(x + (4.5 * PI) / 3))) + 0.5
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # hold the current color for the step time
                sleep(step_time)


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

                # hold the current color for the step time
                sleep(step_time)

            for x in range(10, 49):
                # decrease brightness based on exponential decay function
                brightness = 2.2 * np.exp(-0.08 * x)
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # hold the current color for the step time
                sleep(step_time)

            # reset color channel brightness to 0% at last increment
            red.value = 0
            green.value = 0
            blue.value = 0

            # hold the current color for the step time
            sleep(step_time)


def rainbow_smooth(cycle_time):
    # create smaller time increment for loop
    step_time = cycle_time / 50

    # create 50 element list with values spaced equally between 0 and 2pi
    x_values = np.linspace(0, 2 * PI, 50)

    while True:
        for x in x_values:
            red.value = (0.5 * np.sin(x)) + 0.5
            green.value = (0.5 * np.sin(x + ((4 * PI) / 3))) + 0.5
            blue.value = (0.5 * np.sin(x + ((2 * PI) / 3))) + 0.5

            # hold the current color for the step_time
            sleep(step_time)


def main():
    ################## user-defined variables ##################

    # provide GPIO pins to use
    red_pin = 13
    green_pin = 19
    blue_pin = 26

    # choose speed (1 = slowest, 10 = fastest)
    speed = 9

    # choose brightness (1 = lowest, 10 = brightest)
    brightness = 10

    # create ordered list of color values
    color_list = [[1, 0, 0],     # red
                  [1, 0.5, 0],   # orange
                  [1, 1, 0],     # yellow
                  [0, 1, 0],     # green
                  [0, 0, 1],     # blue
                  [1, 0, 1]]     # purple

    ################ initialization and startup ################

    # initialize GPIO pins for each color channel
    initialize_pwm(red_pin, green_pin, blue_pin)

    # blink white 5 times for startup
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


if __name__ == "__main__":
    # direct execution check
    main()
