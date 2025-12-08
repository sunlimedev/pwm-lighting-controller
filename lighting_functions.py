import board
import busio
from adafruit_pca9685 import PCA9685
from threading import Thread, Event
from gpiozero import Button
from time import sleep

# global variables/flags
pwm = None
inputs = [None, None, None, None, None, None, None, None]
stop_flag = Event()

# lists for lighting functions
decay = [0x979, 0xc85, 0x15a8, 0x24dd, 0x3a30, 0x5594, 0x770a, 0x9e9d, 0xcc42, 0xffff, 0xffff, 0xf5f6, 0xec4f, 0xe30b, 0xda22, 0xd196, 0xc95e, 0xc17b, 0xb9e1, 0xb29c, 0xab98, 0xa4dd, 0x9e69, 0x9831, 0x923a, 0x8c7e, 0x86fd, 0x81b0, 0x7c9e, 0x77bb, 0x7305, 0x6e83, 0x6a30, 0x6604, 0x6205, 0x5e2e, 0x5a7f, 0x56f0, 0x5388, 0x5041, 0x4d1b, 0x4a16, 0x472b, 0x4460, 0x41b7, 0x3f21, 0x3ca5, 0x3a44, 0x37fd, 0x35c9, 0x33b0, 0x31aa, 0x2fb8, 0x2dd9, 0x2c08, 0x2a51, 0x28a7, 0x2711, 0x2587, 0x2412, 0x22a3, 0x2148, 0x1ff9, 0x1eb8, 0x1d84, 0x1c5d, 0x1b43, 0x1a30, 0x192a, 0x182b, 0x1738, 0x1653, 0x156d, 0x149c, 0x13ca, 0x1305, 0x1247, 0x1190, 0x10df, 0x1034, 0xf91, 0xef3, 0xe5d, 0xdcc, 0xd43, 0xcc0, 0xc3d, 0xbc7, 0xb51, 0xadb, 0xa72, 0xa09, 0x9a0, 0x944, 0x8e2, 0x88d, 0x838, 0x7e2, 0x794, 0x745]
sine0 = [0x0, 0x41, 0x102, 0x244, 0x405, 0x644, 0x8fd, 0xc2f, 0xfd5, 0x13ed, 0x1872, 0x1d60, 0x22b1, 0x2861, 0x2e69, 0x34c3, 0x3b6a, 0x4256, 0x4980, 0x50e1, 0x5872, 0x602b, 0x6803, 0x6ff5, 0x77f6, 0x7fff, 0x8809, 0x900a, 0x97fc, 0x9fd4, 0xa78d, 0xaf1e, 0xb67f, 0xbda9, 0xc495, 0xcb3c, 0xd196, 0xd79e, 0xdd4e, 0xe29f, 0xe78d, 0xec12, 0xf02a, 0xf3d0, 0xf702, 0xf9bb, 0xfbfa, 0xfdbb, 0xfefd, 0xffbe, 0xffff, 0xffbe, 0xfefd, 0xfdbb, 0xfbfa, 0xf9bb, 0xf702, 0xf3d0, 0xf02a, 0xec12, 0xe78d, 0xe29f, 0xdd4e, 0xd79e, 0xd196, 0xcb3c, 0xc495, 0xbda9, 0xb67f, 0xaf1e, 0xa78d, 0x9fd4, 0x97fc, 0x900a, 0x8809, 0x8000, 0x77f6, 0x6ff5, 0x6803, 0x602b, 0x5872, 0x50e1, 0x4980, 0x4256, 0x3b6a, 0x34c3, 0x2e69, 0x2861, 0x22b1, 0x1d60, 0x1872, 0x13ed, 0xfd5, 0xc2f, 0x8fd, 0x644, 0x405, 0x244, 0x102, 0x41]
sine1 = [0xbfff, 0xc6d5, 0xcd63, 0xd3a2, 0xd98e, 0xdf1e, 0xe44f, 0xe91b, 0xed7c, 0xf16e, 0xf4ee, 0xf7f8, 0xfa88, 0xfc9d, 0xfe34, 0xff4b, 0xffe2, 0xfff8, 0xff8c, 0xfe9f, 0xfd33, 0xfb48, 0xf8e0, 0xf5ff, 0xf2a6, 0xeed9, 0xea9c, 0xe5f4, 0xe0e4, 0xdb73, 0xd5a5, 0xcf81, 0xc90c, 0xc24e, 0xbb4d, 0xb40f, 0xac9d, 0xa4fe, 0x9d3a, 0x9558, 0x8d61, 0x855c, 0x7d51, 0x754a, 0x6d4d, 0x6563, 0x5d94, 0x55e7, 0x4e66, 0x4716, 0x4000, 0x392a, 0x329c, 0x2c5d, 0x2671, 0x20e1, 0x1bb0, 0x16e4, 0x1283, 0xe91, 0xb11, 0x807, 0x577, 0x362, 0x1cb, 0xb4, 0x1d, 0x7, 0x73, 0x160, 0x2cc, 0x4b7, 0x71f, 0xa00, 0xd59, 0x1126, 0x1563, 0x1a0b, 0x1f1b, 0x248c, 0x2a5a, 0x307e, 0x36f3, 0x3db1, 0x44b2, 0x4bf0, 0x5362, 0x5b01, 0x62c5, 0x6aa7, 0x729e, 0x7aa3, 0x82ae, 0x8ab5, 0x92b2, 0x9a9c, 0xa26b, 0xaa18, 0xb199, 0xb8e9]
sine2 = [0xbfff, 0xb8e9, 0xb199, 0xaa18, 0xa26b, 0x9a9c, 0x92b2, 0x8ab5, 0x82ae, 0x7aa3, 0x729e, 0x6aa7, 0x62c5, 0x5b01, 0x5362, 0x4bf0, 0x44b2, 0x3db1, 0x36f3, 0x307e, 0x2a5a, 0x248c, 0x1f1b, 0x1a0b, 0x1563, 0x1126, 0xd59, 0xa00, 0x71f, 0x4b7, 0x2cc, 0x160, 0x73, 0x7, 0x1d, 0xb4, 0x1cb, 0x362, 0x577, 0x807, 0xb11, 0xe91, 0x1283, 0x16e4, 0x1bb0, 0x20e1, 0x2671, 0x2c5d, 0x329c, 0x392a, 0x4000, 0x4716, 0x4e66, 0x55e7, 0x5d94, 0x6563, 0x6d4d, 0x754a, 0x7d51, 0x855c, 0x8d61, 0x9558, 0x9d3a, 0xa4fe, 0xac9d, 0xb40f, 0xbb4d, 0xc24e, 0xc90c, 0xcf81, 0xd5a5, 0xdb73, 0xe0e4, 0xe5f4, 0xea9c, 0xeed9, 0xf2a6, 0xf5ff, 0xf8e0, 0xfb48, 0xfd33, 0xfe9f, 0xff8c, 0xfff8, 0xffe2, 0xff4b, 0xfe34, 0xfc9d, 0xfa88, 0xf7f8, 0xf4ee, 0xf16e, 0xed7c, 0xe91b, 0xe44f, 0xdf1e, 0xd98e, 0xd3a2, 0xcd63, 0xc6d5]


def initialize_pwm():
    # create a global variable for the i2c link and pwm bus
    global pwm

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


def sequence_solid(color_list, cycle_time, dimmer):
    while True:
        for color in range(len(color_list)):
            # assign lookup table value to color channels minus the scaled dimmer value
            pwm.channels[0].duty_cycle = int(color_list[color][0] * (0xffff - dimmer))
            pwm.channels[1].duty_cycle = int(color_list[color][1] * (0xffff - dimmer))
            pwm.channels[2].duty_cycle = int(color_list[color][2] * (0xffff - dimmer))

            # check for raised flag during the cycle_time timeout
            if stop_flag.wait(timeout=cycle_time):
                return None


def sequence_fade(color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    while True:
        for color in range(len(color_list)):
            for i in range(0, 100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(sine0[i] - ((sine0[i]/0xffff)*dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(sine0[i] - ((sine0[i]/0xffff)*dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(sine0[i] - ((sine0[i]/0xffff)*dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def sequence_decay(color_list, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    while True:
        for color in range(len(color_list)):
            for i in range(0, 100):
                # assign lookup table value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0] * int(decay[i] - ((decay[i]/0xffff)*dimmer))))
                pwm.channels[1].duty_cycle = int((color_list[color][1] * int(decay[i] - ((decay[i]/0xffff)*dimmer))))
                pwm.channels[2].duty_cycle = int((color_list[color][2] * int(decay[i] - ((decay[i]/0xffff)*dimmer))))

                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):
                    return None


def rainbow_smooth(_, cycle_time, dimmer):
    # create smaller time increment for loop
    step_time = cycle_time / 100

    while True:
        for i in range(0, 100):
            # assign lookup table value to color channels minus the scaled dimmer value
            pwm.channels[0].duty_cycle = sine0[i] - int((sine0[i] / 0xffff) * dimmer)
            pwm.channels[1].duty_cycle = sine1[i] - int((sine1[i] / 0xffff) * dimmer)
            pwm.channels[2].duty_cycle = sine2[i] - int((sine2[i] / 0xffff) * dimmer)

            # check for raised flag during the step_time timeout
            if stop_flag.wait(timeout=step_time):
                return None


def main():
    ################## user-defined variables ##################

    # choose lighting speed (1 = slowest, 10 = fastest)
    speed = 7

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

    # provide input pins to use
    input_pins = [22, 10, 19, 11, 5, 6, 13, 26]

    # initialize GPIO pins for each input bit
    initialize_input_bus(input_pins)

    # blink white 3 times for startup
    startup_blink()


    ############## rework variables for functions ##############

    # derive cycle time from speed (1 = 5s, 10 = 0.5s)
    cycle_time = -(speed / 2) + 5.5

    # derive dimmer from brightness (1 = 10%, 10 = 100%)
    dimmer = int(0x1999 * (10 - brightness))


    ####### start lighting thread and read button inputs #######

    # set lighting function and arguments for lighting thread
    light_thread = Thread(target=rainbow_smooth, args=(color_list, cycle_time, dimmer))
    
    # start thread in background
    light_thread.start()

    # run thread until input[0] is true
    while True:
        states = read_input_bus()
        if states[0] == True:
            stop_flag.set()
            break
        else:
            sleep(0.01)

    # gracefully terminate thread
    light_thread.join()


if __name__ == "__main__":
    main()
