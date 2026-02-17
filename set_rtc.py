import time
import board
import busio
import argparse
import datetime
import adafruit_ds3231

# initialize i2c bus
i2c = busio.I2C(board.SCL, board.SDA)

# initialize rtc object
rtc = adafruit_ds3231.DS3231(i2c)

# create argument for ISO8601 date string -- YYYY-MM-DDTHH:MM:SS -- seconds optional, T can be any character
parser = argparse.ArgumentParser()
parser.add_argument("ISO_date")

# get date string
args = parser.parse_args()
date = args.ISO_date

# slice string into variables
year = int(date[:4])
month = int(date[5:7])
day = int(date[8:10])
hour = int(date[11:13])
minute = int(date[14:16])

if len(date) >= 19:
    second = int(date[17:19])
else:
    second = 0

# get weekday for DS3231
date_object = datetime.date(year, month, day)
weekday = date_object.weekday()

# get yearday for DS3231
temp_struct = time.struct_time((year, month, day, hour, minute, second, 0, 0, -1))
epoch = time.mktime(temp_struct)
computed = time.localtime(epoch)

# set rtc with string info, weekday, and yearday (seconds = 0, daylight savings not required)
rtc.datetime = time.struct_time((year, month, day, hour, minute, 0, weekday, computed.tm_yday, -1))
