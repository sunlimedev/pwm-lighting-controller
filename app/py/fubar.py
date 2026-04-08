import os
import time
import sqlite3
import gpiozero
import threading


def reset_database_and_services(reset_LED):
    # turn on reset_LED to indicate process has started
    reset_LED.blink(on_time=0.5, off_time=0.5)

    while True:
        # reset all tables in lighting.db
        try:
            # connect to lighting.db
            conn = sqlite3.connect('/home/user/project/database/lighting.db')
            cursor = conn.cursor()
            
            # set database busy timeout
            cursor.execute("PRAGMA busy_timeout = 5000")

            # begin transaction in case anything happens
            cursor.execute("BEGIN")
            print("Database reset started:")

            # connect factory_settings.db
            cursor.execute("ATTACH DATABASE '/home/user/project/database/factory_settings.db' AS factory")

            # reset scenes and connections table
            cursor.execute("DELETE FROM scenes")
            cursor.execute("INSERT INTO scenes SELECT * FROM factory.scenes")
            cursor.execute("DELETE FROM connections")
            cursor.execute("INSERT INTO connections SELECT * FROM factory.connections")
            cursor.execute("UPDATE sqlite_sequence SET seq = 11 WHERE name = 'scenes'")
            print("scenes table reset\nconnections table reset")

            # reset events table
            cursor.execute("DELETE FROM events")
            cursor.execute("INSERT INTO events SELECT * FROM factory.events")
            cursor.execute("UPDATE sqlite_sequence SET seq = 2 WHERE name = 'events'")
            print("events table reset")

            # reset time table
            cursor.execute("DELETE FROM time")
            cursor.execute("INSERT INTO time SELECT * FROM factory.time")
            print("time table reset")

            # reset users table
            cursor.execute("DELETE FROM users")
            cursor.execute("UPDATE sqlite_sequence SET seq = 0 WHERE name = 'users'")
            print("users table reset")

            # detach factory_settings.db and commit changes
            conn.commit()
            cursor.execute("DETACH DATABASE factory")
            conn.close()
            print("\nDatabase reset completed.")
            break

        except sqlite3.Error as e:
            conn.rollback()
            print("Reset unsuccessful.")
            print(e)
            try:
                cursor.execute("DETACH DATABASE factory")
                conn.close()
            except:
                pass
            time.sleep(2)

	# restart lighting control service
    os.system("sudo systemctl restart controller.service")
    print("Lighting control service restarted.")

    # restart clock sync service
    os.system("sudo systemctl restart sync_clocks.service")
    print("Clock sync service restarted.")

    # restart rtc control service
    os.system("sudo systemctl restart set_rtc.service")
    print("RTC control service restarted.")

    # restart apache
    os.system("sudo systemctl restart apache2")
    print("Apache restarted.")

    # sleep for 7 seconds to give services time
    time.sleep(7)

    # turn off reset LED
    reset_LED.off()


def main():
    # STAT1 on board using GPIO23 (board pin 16)
    reset_LED = gpiozero.LED(23)
    reset_LED.off()

    # RESET on board using GPIO24 (board pin 18)
    reset_button = gpiozero.Button(24, pull_up = False, hold_time = 9.5)

    # assign function call to button held state
    reset_button.when_held = lambda: reset_database_and_services(reset_LED)

    # loop forever so button is always watched
    while True:
        time.sleep(1)


if __name__ == "__main__":
    main()
