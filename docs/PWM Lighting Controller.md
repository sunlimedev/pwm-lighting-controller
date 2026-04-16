# Overview
This document outlines all programs that comprise the PWM Lighting Controller. Hardware control is implemented in Python, databases use SQLite, and the web application is written in PHP.

System setup and requirements are discussed first, followed by hardware control, then database structure, and finally the web application. These sections are outlined and can be navigated via the Table of Contents.

[Gitea](http://stserver:3000/andrewr/pwm-lighting-controller)

# Table of Contents
### [System](#System)
[Required Hardware](#Required%20Hardware)
[Setup Commands and File Structure](#Setup%20Commands%20and%20File%20Structure)
[Network](#Network)
### [Hardware Control](#Hardware%20Control)
Lighting
Timing
Reset
### [Databases](#Databases)
Lighting Settings
Default Settings
### [Web Application](#Web%20Application)
Users
Home
Scenes
Connections
Schedule
Setup Guide
Settings

# System
### Required Hardware
This project was designed to run on a Raspberry Pi 4 Model B 4GB running Raspberry Pi OS Lite (64-bit). The Pi interfaces with the Signal-Tech Light Tube Controller board, which handles PWM signaling, manages timing, and has a system reset button and status LEDs.

Any Raspberry Pi 4 Model B with at least 4GB of RAM will work. A Pi 5 with sufficient RAM should work, but this has not been tested.

### Setup Commands and File Structure
When flashing Raspberry Pi OS Lite (64-bit), the hostname should be `raspberrypi`, the user should be `user`, and the password should be `pass`.

On a new device connected to a network, run the following commands to update and upgrade the system packages:
```
$ sudo apt update -y
$ sudo apt upgrade -y
$ sudo reboot
```

The following system-wide commands are required to use the I<sup>2</sup>C bus and control GPIO:
```
$ sudo raspi-config nonint do_i2c 0

$ sudo apt install swig python3-dev build-essential -y
$ sudo apt install liblgpio-dev
```

##### `/home/user/project` Directory
A Python virtual environment is required to run the [Hardware Control](#Hardware%20Control) files, it can be created and configured with the following commands:
```
$ mkdir project
$ cd project

/project $ python -m venv venv
/project $ source venv/bin/activate

(venv) /project $ pip install numpy
(venv) /project $ pip install gpiozero
(venv) /project $ pip install adafruit-blinka
(venv) /project $ pip install adafruit-circuitpython-pca9685
(venv) /project $ pip install adafruit-circuitpython-ds3231
(venv) /project $ pip install lgpio
```

Alternatively, the virtual environment dependencies can be installed automatically via `requirements.txt`. This file is available in the GitHub repository at `pwm-lighting-controller/docs`. Once the requirements file is placed in the `project` directory, run the following command:
```
(venv) /project $ pip install -r requirements.txt
```

You can deactivate the Python virtual environment with:
```
(venv) /project $ deactivate
```

Within the `project` directory, two additional directories are required. Create them with the following command:
```
/project $ sudo mkdir backend database
```

The `backend` and `database` directories hold all Python and SQLite files, respectively. In the GitHub repository these files are found in `pwm-lighting-controller/app/py` and `pwm-lighting-controller/app/db`. Move all of the `.py` and `.db` files found there into their directories. The file structure of the `project` directory should be:
```
project
├─ backend
│   ├─ controller.py
│   ├─ fubar.py
│   ├─ set_rtc.py
│   └─ sync_clocks.py
├─ database
│   ├─ factory_settings.db
│   └─ lighting.db
└─ venv
```

##### `/var/www/html` Directory
The web application uses Apache, PHP, and SQLite, all of which need to be installed:
```
$ sudo apt install apache2 -y
$ sudo apt install php -y
$ sudo apt install libapache2-mod-php 
$ sudo apt install sqlite3
$ sudo apt install php-sqlite3 -y
```

The Apache service should be started after installation:
```
$ sudo systemctl enable apache2
$ sudo systemctl start apache2
```

Once Apache is installed, a new folder will be created. Navigate to it with the following command:
```
$ cd /var/www/html
```

The `html` directory holds all PHP files. In the GitHub repository these files are found in `pwm-lighting-controller/app/php`. Move all of the `.php` files found there into the `html` directory. Within the `html` directory, three additional directories are required. Create them with the following command:
```
/var/www/html $ sudo mkdir assets css includes
```

The `assets`, `css`, and `includes` directories hold all project images and icons, the Tailwind CSS file, and commonly used PHP files, respectively. In the GitHub repository these files are found in `pwm-lighting-controller/assets`, `pwm-lighting-controller/app/css`, and `pwm-lighting-controller/app/php/includes`, respectively. All files and folders in each of these locations should be placed in their corresponding directories. The file structure of the `html` directory should be:
```
html
├─ assets
│   ├─ colors
│   │   ├─ blue1.svg
│   │   ├─ blue2.svg
│   │   ├─ blue3.svg
│   │   └─ 61 more...
│   ├─ back.svg
│   ├─ help.svg
│   ├─ home.svg
│   ├─ logo.svg
│   ├─ pencil.svg
│   ├─ plus.svg
│   └─ refresh.svg
├─ css
│   └─ tailwind.min.css
├─ includes
│   ├─ session-check.php
│   └─ user-check.php
├─ add-event.php
├─ add-scene.php
├─ connections.php
├─ edit-connection.php
├─ edit-date-time.php
├─ edit-event.php
├─ edit-lighting-schedule.php
├─ edit-scene.php
├─ forgot-user-pass.php
├─ home.php
├─ index.php
├─ index.html
├─ register.php
├─ reset.php
├─ scenes.php
├─ schedule.php
├─ settings.php
├─ setup-guide.php
└─ test.php
```

The `index.html` file needs to be removed.
```
/var/www/html $ sudo rm index.html
```

Once the `html` directory is complete, the Apache user `www-data` will require specific file permissions, and certain files and directories must be modified. Run the following commands:
```
$ sudo chown -R user:www-data /home/user/project/database  
$ sudo chmod -R 775 /home/user/project/database

$ chmod 2775 /home/user/project/database  
  
$ chmod 664 /home/user/project/database/lighting.db  
$ chmod 664 /home/user/project/database/factory_settings.db  
  
$ chmod 755 /home/user
$ chmod 755 /home/user/project
```

##### `/etc/systemd/system` Directory
The final directory to configure is the `system` directory. Navigate to it with the following command:
```
$ cd /etc/systemd/system
```

The `system` directory holds all systemd service files. In the GitHub repository these files are found in `pwm-lighting-controller/app/service`. Move all of the `.service` files found there into the `system` directory. Many system files are stored here, but the files for this project will be structured as follows:
```
system
├─ controller.service
├─ fubar.service
├─ set_rtc.service
└─ sync_clocks.service
```

These services need to be enabled and started once they are moved:
```
$ sudo systemctl enable controller.service
$ sudo systemctl start controller.service

$ sudo systemctl enable fubar.service
$ sudo systemctl start fubar.service

$ sudo systemctl enable set_rtc.service
$ sudo systemctl start set_rtc.service

$ sudo systemctl enable sync_clocks.service
$ sudo systemctl start sync_clocks.service

$ sudo systemctl restart apache2
```

The light tubes should flash red, then yellow, then green and finally transition to a rainbow.

### Network
The Raspberry Pi 4 Model B can be configured to host a Wi-Fi network (hotspot). This is the intended method for connecting to the web application. Run the following commands to configure and enable it:
```
$ nmcli device wifi hotspot ssid st-lighting password SignalTech26
$ nmcli connection show
$ nmcli connection modify Hotspot 802-11-wireless.hidden yes

$ nmcli connection modify "netplan-wlan0-signaltech-guest" connection.autoconnect no
$ nmcli connection modify "Hotspot" connection.autoconnect yes

$ nmcli connection modify "Hotspot" connection.autoconnect-priority 100
$ nmcli connection modify "netplan-wlan0-signaltech-guest" connection.autoconnect-priority 0

$ nmcli connection up "Hotspot"
```

Connect to the hidden SSID `st-lighting` with the password `SignalTech26`. Once connected, open a browser and go to `http://10.42.0.1/`. The account registration page should be displayed.

# Hardware Control
The Raspberry Pi, PCA9685 PWM IC, and DS3231 Real-Time Clock are controlled by 
### Lighting
controller.py
### Timing
set_rtc.py
sync_clocks.py
### Reset
fubar.py

# Databases
### Lighting Settings
### Default Settings

# Web Application
```
register.php
index.php
└── forgot-user-pass.php
└── home.php
     ├── scenes.php
     |    ├── add-scene.php
     |    └── edit-scene.php
     ├── connections.php
     |    └── edit-connection.php
     ├── schedule.php
     |    ├── add-event.php
     |    ├── edit-event.php
     |    └── edit-lighting-schedule.php
     ├── setup-guide.php
     └── settings.php
          └── edit-date-time.php
          └── reset.php
```
### Users
### Home
### Scenes
### Connections
### Schedule
### Setup Guide
### Settings
