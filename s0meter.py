#!/usr/bin/env python3
#
#   stroommeter - toolchain for reading energy meters
#   Copyright (C) 2022-2025  Jasper Vries
#
#   This program is free software: you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation, either version 3 of the License, or
#   (at your option) any later version.
#
#   This program is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this program.  If not, see <http://www.gnu.org/licenses/>.
#

import time
from gpiozero import Button
import requests

api_url = 'http://example.com/api/index.php'
debug = 0

counter1_imp = 2000
counter2_imp = 2000

#init counters
counter1 = 0
counter2 = 0
counter1_time = 0
counter2_time = 0
counter1_prev = 0
counter2_prev = 0

#counter callbacks
def counter1_increment():
	global counter1
	global counter1_time
	if (time.perf_counter() - counter1_time > 0.2):
		counter1_time = time.perf_counter()
		counter1 += 1
		if debug:
			print('counter1++')

def counter2_increment():
	global counter2
	global counter2_time
	if (time.perf_counter() - counter2_time > 0.2):
		counter2_time = time.perf_counter()
		counter2 += 1
		if debug:
			print('counter2++')

#setup gpio callbacks
counter1io = Button(23, pull_up=True)
counter1io.when_pressed = counter1_increment
counter2io = Button(24, pull_up=True)
counter2io.when_pressed = counter2_increment

#output every five minutes
while (True):
	prev_time = time.localtime()
	#find time until next 5 minute mark
	sleep = 300 - ((int(time.strftime('%M')) * 60 + int(time.strftime('%S'))) % 300)
	time.sleep(sleep)

	#calculate usage since last post
	counter1_usage = format((counter1 - counter1_prev) / counter1_imp, '.4f')
	counter1_prev = counter1
	counter2_usage = format((counter2 - counter2_prev) / counter2_imp, '.4f')
	counter2_prev = counter2
	
	if debug:
		print('counter1 usage (kWh): ', end='')
		print(counter1_usage)
		print('counter2 usage (kWh): ', end='')
		print(counter2_usage)
	
	#post data
	requests.post(api_url, json={'key': 'MfbFdky9UtrwrYC2BCLZhK5Q', 'time': time.strftime('%Y-%m-%d %H:%M:%S', prev_time), 'counter1': counter1_usage, 'counter2': counter2_usage})
