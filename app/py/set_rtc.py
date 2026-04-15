import os
import time
import board
import busio
import sqlite3
import datetime
import adafruit_ds3231

# initialize i2c bus
i2c = busio.I2C(board.SCL, board.SDA)

# initialize rtc object
rtc = adafruit_ds3231.DS3231(i2c)

# connect to database
conn = sqlite3.connect('/home/user/project/database/lighting.db')
cursor = conn.cursor()

while True:
	# query db for changed time flag
	cursor.execute("SELECT change FROM clock")
	change_flag = cursor.fetchone()[0]

	if (change_flag == 1):
		# get new time since it has just been modified
		cursor.execute("SELECT * FROM clock")
		row = cursor.fetchone()
		year = row[0]
		month = row[1]
		day = row[2]
		hour = row[3]
		minute = row[4]

		# get weekday for DS3231
		date_object = datetime.date(year, month, day)
		weekday = date_object.weekday()

		# get yearday for DS3231
		temp_struct = time.struct_time((year, month, day, hour, minute, 0, 0, 0, -1))
		epoch = time.mktime(temp_struct)
		computed = time.localtime(epoch)

		# set rtc with string info, weekday, and yearday (seconds = 0, daylight savings not required)
		rtc.datetime = time.struct_time((year, month, day, hour, minute, 0, weekday, computed.tm_yday, -1))

		# reset change flag
		cursor.execute("UPDATE clock SET change = 0")
		conn.commit()

		# restart clock sync service
		os.system("systemctl restart sync_clocks.service")

	# wait 1s before checking again
	time.sleep(1)
