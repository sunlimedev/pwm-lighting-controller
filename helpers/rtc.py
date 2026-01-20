import board
import busio
import time
from adafruit_ds3231 import DS3231

def make_struct_time(year, month, day, hour, minute, weekday):
    # Temporary struct so Python computes tm_yday
    temp = time.struct_time((
        year, month, day,
        hour, minute,
        0,    # placeholder second
        0,    # placeholder weekday
        0,    # placeholder yearday
        -1    # let system decide DST
    ))

    epoch = time.mktime(temp)
    computed = time.localtime(epoch)

    return time.struct_time((
        year,
        month,
        day,
        hour,
        minute,
        0,                 # second = 0 for simplicity
        weekday,           # user-supplied weekday
        computed.tm_yday,  # computed day of year
        -1                 # DST not used by DS3231
    ))


def main():
	i2c = busio.I2C(board.SCL, board.SDA)
	
	rtc = DS3231(i2c)
	
	year = 2026
	month = 1
	day = 20
	hour = 9
	minute = 0
	weekday = 1

	rtc.datetime = make_struct_time(year, month, day, hour, minute, weekday)

	while True:
		t = rtc.datetime
		print(t.tm_hour, t.tm_min, t.tm_sec)
		
		time.sleep(1)


if __name__ == "__main__":
	main()
