# Written by claude.ai on 11/13/2025
from time import strftime
from threading import Thread, Event

# Create the stop flag
stop_flag = Event()


def printing_loop():
    """This function runs in a separate thread and prints continuously"""
    counter = 0
    print("Print loop started! Press Enter in the main thread to stop.\n")

    while not stop_flag.is_set():
        print(f"Loop iteration {counter} - Time: {strftime('%H:%M:%S')}")
        counter += 1

        # Wait for 1 second OR until stop_flag is set
        # This makes it responsive to the stop signal
        if stop_flag.wait(timeout=1):
            break

    print("\n--- Print loop stopped! ---")


def main():
    print("=== Threading Test Program ===")
    print("Starting a thread that prints continuously...")
    print("Press ENTER at any time to stop it.\n")

    # Create and start the thread
    print_thread = Thread(target=printing_loop)
    print_thread.start()

    # Main thread waits for keyboard input
    input()  # This blocks until user presses Enter

    # User pressed Enter, signal the thread to stop
    print("\nStop signal sent! Waiting for thread to finish...")
    stop_flag.set()

    # Wait for the thread to actually finish
    print_thread.join()

    print("Thread has stopped. Program ending.")


if __name__ == "__main__":
    main()
