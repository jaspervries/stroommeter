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
import serial
import re
import requests

api_url = 'http://example.com/api/index.php'
debug = 0

#init counters
counter1_1 = 0
counter1_2 = 0
counter2_1 = 0
counter2_2 = 0
counter1_1_prev = 0
counter1_2_prev = 0
counter2_1_prev = 0
counter2_2_prev = 0
counter1_usage = 0
counter2_usage = 0

#function to get meter data
def getmeter():
	global counter1_1
	global counter1_2
	global counter2_1
	global counter2_2

	ser=serial.Serial('/dev/ttyUSB0', 115200)
	for i in range(0,100):
		line = ser.readline().decode() 
		matches = re.match('^1-0:(1|2)\.8\.(1|2)\((.+)\*kWh\).*', line)
		if matches:
			if matches.group(1) == '1' and matches.group(2) == '1':
				counter1_1 = float(matches.group(3))
				if debug:
					print('counter1_1 value (kWh): ', end='')
					print(counter1_1)
			if matches.group(1) == '1' and matches.group(2) == '2':
				counter1_2 = float(matches.group(3))
				if debug:
					print('counter1_2 value (kWh): ', end='')
					print(counter1_2)
			if matches.group(1) == '2' and matches.group(2) == '1':
				counter2_1 = float(matches.group(3))
				if debug:
					print('counter2_1 value (kWh): ', end='')
					print(counter2_1)
			if matches.group(1) == '2' and matches.group(2) == '2':
				counter2_2 = float(matches.group(3))
				if debug:
					print('counter2_2 value (kWh): ', end='')
					print(counter2_2)
		#end at end of telegram
		if re.match('^!.+', line):
			break
	ser.close()

#get meter values at first script run
getmeter()
counter1_1_prev = counter1_1
counter1_2_prev = counter1_2
counter2_1_prev = counter2_1
counter2_2_prev = counter2_2

#output every five minutes
while (True):
	prev_time = time.localtime()
	#find time until 2 seconds past next 5 minute mark
	sleep = 302 - ((int(time.strftime('%M')) * 60 + int(time.strftime('%S'))) % 300)
	time.sleep(sleep)

	getmeter()

	#calculate usage since last post
	counter1_usage = format((counter1_1 - counter1_1_prev) + (counter1_2 - counter1_2_prev), '.4f')
	counter1_1_prev = counter1_1
	counter1_2_prev = counter1_2
	counter2_usage = format((counter2_1 - counter2_1_prev) + (counter2_2 - counter2_2_prev), '.4f')
	counter2_1_prev = counter2_1
	counter2_2_prev = counter2_2

	if debug:
		print('counter1 usage (kWh): ', end='')
		print(counter1_usage)
		print('counter2 usage (kWh): ', end='')
		print(counter2_usage)
	
	#post data
	requests.post(api_url, json={'key': 'MfbFdky9UtrwrYC2BCLZhK5Q', 'time': time.strftime('%Y-%m-%d %H:%M:%S', prev_time), 'counter4': counter1_usage, 'counter5': counter2_usage})
