import os
import time
import board
import busio
import sqlite3
import adafruit_ds3231

# initialize i2c bus
i2c = busio.I2C(board.SCL, board.SDA)

# initialize rtc object
rtc = adafruit_ds3231.DS3231(i2c)

# connect to database and create query
conn = sqlite3.connect('/home/user/project/database/lighting.db')
cursor = conn.cursor()
statement = """UPDATE clock SET year = ?, month = ?, day = ?, hour = ?, minute = ?"""

while True:
	# get current time from rtc
	now = rtc.datetime

	# create ISO8601 string from rtc values
	date_string = "{:04d}-{:02d}-{:02d}T{:02d}:{:02d}:{:02d}".format(now.tm_year, now.tm_mon, now.tm_mday, now.tm_hour, now.tm_min, now.tm_sec)

	# run command to set raspberry pi clock
	os.system(f'sudo date --set "{date_string}"')
    
	# save time to sqlite clock table
	print("committed")
	try:
		cursor.execute(statement, (now.tm_year, now.tm_mon, now.tm_mday, now.tm_hour, now.tm_min))
		conn.commit()
	except sqlite3.Error:
		conn.rollback()

	# wait 1 minutes to sync clocks and update table again
	time.sleep(60)
