### Overview
The backend for the Light Tube Controller manages all hardware for the project.

### Dependencies
1. System
2. Virtual Environment

### Lighting Control
PWM signaling to the light tubes is managed by `controller.py`. It is partitioned into four sections: initialization functions, data functions, lighting functions, and the main function.



talk about overarching design of file (thread and thread manager)


#### Initialization
The initialization functions prepare the PCA9685 PWM LED Driver, SQLite Database, and the 8 bit input bus. There are a total of six initialization functions, three manage hardware and three indicate initialization success/failure to the operator.
```python
def initialize_pwm()
def pwm_good(pwm)        # blink red 3 times

def initialize_database()
def database_good(pwm)   # blink yellow 3 times

def initialize_input_bus()
def input_bus_good(pwm)  # blink green 3 times
```

`initialize_pwm()` configures the PCA9685 PWM LED Driver and uses the `board`,`busio`, and `adafruit_pca9685` modules. The PWM frequency is set to 1kHz to avoid flicker. Once finished, this function returns a PCA9685 object `pwm` to be modified by other functions.
```python
def initialize_pwm():  
    # create the I2C bus interface  
    i2c = busio.I2C(board.SCL, board.SDA)
    
    # create a PCA9685 object and set the frequency for LED control  
    pwm = adafruit_pca9685.PCA9685(i2c)  
    pwm.frequency = 1000
    
    return pwm
```

`initialize_database()` configures the SQLite Database `lighting.db` and uses the `sqlite3` module. This function sets a variety of database parameters (PRAGMAs) to prevent blocking between the Python backend and PHP frontend. Once finished, this function returns the database connection `conn` and cursor object `cursor`.
```python
def initialize_database():  
    # connect to SQLite database and get cursor  
    conn = sqlite3.connect('lighting.db')  
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
```

`initialize_input_bus()` configures the 8 bit input bus and uses the `gpiozero` module. All input bus pins use a single side of the Raspberry Pi 4 GPIO. The pinout for the 40-pin connector is available [here](https://www.raspberrypi.com/documentation/computers/raspberry-pi.html#gpio). Once finished, this function returns a list of pin objects `inputs` to be checked by other functions.
```python
def initialize_input_bus():  
    # provide input pins to use  
    input_pins = [22, 10, 9, 11, 5, 6, 13, 26]
    
    # initialize input_pins as gpiozero DigitalInputDevice class objects  
    inputs = [gpiozero.DigitalInputDevice(pin=pin, pull_up=True) for pin in                      input_pins]
    
    return inputs
```

All `_good()` functions verify the success of a previous initialization function. Once the PWM object has been created, the light tubes will blink red three times. Once the database has been connected, the light tubes will blink yellow three times. Once the input bus objects have been created, the light tubes will blink green three times. This behavior allows the operator to perform very basic troubleshooting. For more information on how this function works, please reference [Lighting](#Lighting).
```python
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
```
#### Data
placeholder

#### Lighting
The lighting functions communicate with the PCA9685 PWM LED Driver to create a variety of visual effects. There are nine lighting functions that can be selected by the operator. The first three channels of the PCA9685 control the red, green, and blue channels of the light tubes, respectively. Each channel accepts an integer value 0-65535, or 0x0000-0xffff.
```python
# set green color  
pwm.channels[0].duty_cycle = 0x0000  
pwm.channels[1].duty_cycle = 0xffff  
pwm.channels[2].duty_cycle = 0x0000
```

Each function uses the same four arguments:
* `pwm`, the PWM object 
* `color_list`, a nested list of floats representing colors (any length)
* `cycle_time`, an integer for the number of seconds to loop each function (1-5)
* `dimmer`, an integer to reduce the brightness of the light tubes (0-65535)
```python
# nested list of floats for colors
color_list = [[0, 1, 1], [0.5, 0, 1], [0.0, 1.0, 0]]

# integer for loop time in seconds
cycle_time = 3

# integer to reduce brightness
dimmer = 0x3333
```

Additionally, each function is run by a thread and loops indefinitely until a flag to stop is raised.
```python
# thread global flag  
stop_flag = threading.Event()

# inside function --- check for raised flag during the cycle_time timeout  
if stop_flag.wait(timeout=cycle_time):  
    return None
```

There are two types of lighting functions, `sequence` and `crossfade`. The `sequence` functions perform a unique behavior while holding a single color, then perform the behavior on the next color in `color_list`. 
```python
def sequence_solid(pwm, color_list, cycle_time, dimmer)
def sequence_fade(pwm, color_list, cycle_time, dimmer)
def sequence_decay(pwm, color_list, cycle_time, dimmer)
def sequence_morse(pwm, color_list, cycle_time, dimmer)
def sequence_wigwag(pwm, color_list, cycle_time, dimmer)
def sequence_sos(pwm, color_list, cycle_time, dimmer)
def sequence_breathe(pwm, color_list, cycle_time, dimmer)
```

The `crossfade` functions gradually transition between each color in `color_list` and may also perform a behavior on each listed color.
```python
def crossfade(pwm, color_list, cycle_time, dimmer)
def crossfade_hold(pwm, color_list, cycle_time, dimmer)
```

Aside from `sequence_solid()`, which holds each color in `color_list` at the same brightness, the `sequence` functions use a lookup table (LUT) to create various effects. In `sequence_fade()`, the brightness of each color follows a sine wave, meaning it gradually fades in and out. The `cycle_time` argument is divided by 100 to reflect the length of the lookup table, and the length of the `color_list` argument is used to define the number of loops each time the function restarts. Since each color channel will not always be set to the maximum brightness, the `dimmer` value scales so it behaves as a percentage.
```python
def sequence_fade(pwm, color_list, cycle_time, dimmer):  
    # lookup table for function  
    fade = [0x0, 0x41, 0x102, 0x244, 0x405, 0x644, 0x8fd, 0xc2f, 0xfd5, 0x13ed, 0x1872, 0x1d60, 0x22b1, 0x2861, 0x2e69, 0x34c3, 0x3b6a, 0x4256, 0x4980, 0x50e1, 0x5872, 0x602b, 0x6803, 0x6ff5, 0x77f6, 0x7fff, 0x8809, 0x900a, 0x97fc, 0x9fd4, 0xa78d, 0xaf1e, 0xb67f, 0xbda9, 0xc495, 0xcb3c, 0xd196, 0xd79e, 0xdd4e, 0xe29f, 0xe78d, 0xec12, 0xf02a, 0xf3d0, 0xf702, 0xf9bb, 0xfbfa, 0xfdbb, 0xfefd, 0xffbe, 0xffff, 0xffbe, 0xfefd, 0xfdbb, 0xfbfa, 0xf9bb, 0xf702, 0xf3d0, 0xf02a, 0xec12, 0xe78d, 0xe29f, 0xdd4e, 0xd79e, 0xd196, 0xcb3c, 0xc495, 0xbda9, 0xb67f, 0xaf1e, 0xa78d, 0x9fd4, 0x97fc, 0x900a, 0x8809, 0x8000, 0x77f6, 0x6ff5, 0x6803, 0x602b, 0x5872, 0x50e1, 0x4980, 0x4256, 0x3b6a, 0x34c3, 0x2e69, 0x2861, 0x22b1, 0x1d60, 0x1872, 0x13ed, 0xfd5, 0xc2f, 0x8fd, 0x644, 0x405, 0x244, 0x102, 0x41]
      
    # create smaller time increment for loop
    step_time = cycle_time / 100
      
    # get color_list length
    num_colors = len(color_list)
    
    while True:  
        for color in range(num_colors):  
            for i in range(100):  
                # assign LUT value to color channels minus the scaled dimmer value
                pwm.channels[0].duty_cycle = int((color_list[color][0]
                    * int(fade[i] - ((fade[i] / 0xffff) * dimmer))))  
                pwm.channels[1].duty_cycle = int((color_list[color][1]
					* int(fade[i] - ((fade[i] / 0xffff) * dimmer))))  
                pwm.channels[2].duty_cycle = int((color_list[color][2]
	                * int(fade[i] - ((fade[i] / 0xffff) * dimmer))))  
		        
                # check for raised flag during the step_time timeout
                if stop_flag.wait(timeout=step_time):  
                    return None
```










#### Main()
placeholder



### Timing Control
1. `set_rtc.py`
2. `sync_clocks.py`