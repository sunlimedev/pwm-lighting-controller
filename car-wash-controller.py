# Car Wash Controller
# Developed for Signal-Tech in Erie, PA
# Designed for use on Raspberry Pi 4 Model B 8GB

# Changes from last version:
# COMPLETE REWRITE
# Add three different sequence modes requiring color nested list
# Add sine wave LED switching
# Add more user-friendly speed variable
# Use more functions (modular)
# Add startup procedure for debugging
# Change function names to explain them better
# Change comment style to improve readability
# Move references to README
# Fix sequence_fade() assigning pin >1
# Fix sequence_decay() assigning pin >1
# Rename sequence_decay() to sequence_pulse()
# Reduced max speed for all functions
# Reduced startup_blink() duration
# Add random_solid_anybright() function

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
    red = PWMLED(pin=red_pin, active_high=True, initial_value=0, frequency=200)
    green = PWMLED(pin=green_pin, active_high=True, initial_value=0, frequency=200)
    blue = PWMLED(pin=blue_pin,  active_high=True, initial_value=0, frequency=200)


def startup_blink():
    for i in range(5):
        # set all channels to max (white)
        red.value = 1
        green.value = 1
        blue.value = 1
        # hold white for 0.5 second
        sleep(0.5)

        # set all channels off
        red.value = 0
        green.value = 0
        blue.value = 0
        # hold off for 0.5 second
        sleep(0.5)


def sequence_solid(color_list, speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 5 / speed

    while True:
        for color in color_list:
            # assign color list contents to color channels
            red.value, green.value, blue.value = color

            # hold the current color for the delay length
            sleep(delay)


def sequence_fade(color_list, speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 0.100 / speed

    # create 50 element list with values spaced equally between 0 and 2pi
    steps = np.linspace(0, 2 * PI, 50)

    while True:
        for color in color_list:
            # assign color values to each temp color variables
            temp_red, temp_green, temp_blue = color

            for x in steps:
                # get fade brightness based on sine function
                brightness = (0.5 * (np.sin(x + (4.5 * PI) / 3))) + 0.5
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # hold the current color for the delay length
                sleep(delay)


def sequence_pulse(color_list, speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 0.100 / speed

    while True:
        for color in color_list:
            # assign color values to each temp color variables
            temp_red, temp_green, temp_blue = color

            for x in range(0, 10):
                # quickly increase brightness based on linear function
                brightness = (1 / 9) * x
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # hold the current color for the delay length
                sleep(delay)

            for x in range(10, 49):
                # decrease brightness based on exponential decay function
                brightness = 2.2 * np.exp(-0.08 * x)
                # assign final color with brightness to color channels
                red.value = temp_red * brightness
                green.value = temp_green * brightness
                blue.value = temp_blue * brightness

                # hold the current color for the delay length
                sleep(delay)

            # reset color channel brightness to 0% at last increment
            red.value = 0
            green.value = 0
            blue.value = 0

            # hold channels off for the delay length
            sleep(delay)


def random_solid_anybright(speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 5 / speed

    while True:
        # randomly set color channel brightness between 0 and 1 (both inclusive)
        red.value = random.uniform(0, 1)
        green.value = random.uniform(0, 1)
        blue.value = random.uniform(0, 1)

        # hold the current color for the delay length
        sleep(delay)


def rainbow_spike(speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 0.100 / speed

    # set color channel starting brightness
    temp_red = 0
    temp_green = 34
    temp_blue = 68

    while True:
        # reset color channel brightness to 0% after reaching 100%
        if temp_red == 100:
            temp_red = 0
        elif temp_green == 100:
            temp_green = 0
        elif temp_blue == 100:
            temp_blue = 0

        # increment color channel brightness by 2%
        temp_red += 2
        temp_green += 2
        temp_blue += 2

        # assign brightness to each color channel
        red.value = temp_red / 100
        green.value = temp_green / 100
        blue.value = temp_blue / 100

        # hold the current color for the delay length
        sleep(delay)


def rainbow_smooth(speed):
    # normalize cycle time to 0.5 seconds to 5 seconds
    delay = 0.100 / speed

    # create 100 element list with values spaced equally between 0 and 2pi
    x_values = np.linspace(0, 2 * PI, 50)

    while True:
        for x in x_values:
            red.value = (0.5 * np.sin(x)) + 0.5
            green.value = (0.5 * np.sin(x + ((4 * PI) / 3))) + 0.5
            blue.value = (0.5 * np.sin(x + ((2 * PI) / 3))) + 0.5

            # hold the current color for the delay length
            sleep(delay)


def main():
    # provide GPIO pins to use
    red_pin = 13
    green_pin = 19
    blue_pin = 26

    # choose speed (1 = slowest, 10 = fastest)
    speed = 5

    # initialize GPIO pins for each color channel
    initialize_pwm(red_pin, green_pin, blue_pin)

    # blink white 5 times for startup
    startup_blink()

    # create ordered list of color values -- r o y g b p
    color_list = [[1, 0, 0],
                  [1, 0.5, 0],
                  [1, 1, 0],
                  [0, 1, 0],
                  [0, 0, 1],
                  [1, 0, 1]]

    # test a lighting function
    sequence_solid(color_list, speed)
    #sequence_fade(color_list, speed)
    #sequence_pulse(color_list, speed)
    #random_solid_anybright(speed)
    #rainbow_spike(speed)
    #rainbow_smooth(speed)

if __name__ == "__main__":
    # direct execution check
    main()
