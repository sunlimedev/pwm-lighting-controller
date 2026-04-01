import os
from gpiozero import Button

def reset_all():
	print("button held for 10s")

reset_button = Button(18, pull_up = False, hold_time = 10)

while True:
	reset_button.when_held = reset_all
