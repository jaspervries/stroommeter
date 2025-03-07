# Stroommeter

Stroommeter is a toolchain for Raspberry Pi that allows to read s0 (pulse meter) and p1 (dsmr power meter) energy meters. One or more energy meters are connected to a Raspberry Pi, on which one or two Python script are running that send 5-minute based energy consumption statistics to a remote server running PHP. The remote server stores the data in a MySQL/MariaDB database. The remote server also provides a simple GUI to view energy statistics.


## Hardware requirements and setup

Any Raspberry Pi with networking capabilities should suffice. It is tested on a Pi 3.

### s0 meter
Connect a GPIO pin of your choice to the + side of the s0 connector (the default used are GPIO23 for counter1 and GPIO24 for counter2, but you can change this). Connect the - of the s0 connector to a ground pin on the GPIO header. You can connect any number of meters for as long as you have free digital input pins on the Pi. Ground connections can be shared if you don't have enough pins.
The script supports two meters by default, this can easily be extended.
Note: Raspberry Pi uses 3.3V logic levels. In some cases this may be insufficient for the energy meter's s0 port to function properly. In that case either use a different energy meter or add a level shifter in the circuit. On three different meters I didn't have any problems using 3.3V.

### P1 meter
P1 is the name of user-accessible port on a smart meter. Check that your smart meter has such a port, otherwise you cannot use this tool to read the meter. The P1 port exposes a serial connection that can be read to retrieve readings from the smart meter. The script is developed for a Dutch DSMR meter, it may or may not work in other countries with similar smart meters. It's advised to first get a dump from your meter and adjust the script if necessary.
The easy option and what I used a P1-USB cable. You can get these from respectable resellers for around 25 euro or, if you don't mind the wait, for less from a well-known Chinese webshop. As the meter uses a serial connection, it should be possible to instead connect to the serial GPIO of the Raspberry Pi using a transistor, capacitor and some wires, but I haven't tested this.
Iskra and Kamstrup meters generally use a baudrate of 9600, 7 databits, even parity and 1 stop bit. Kaifa and Landis+Gyr generally use baudrate 115200, 8 databits, no parity and 1 stop bit. Your results may vary. 
Simply connect the cable to the meter and to a USB port on the Raspberry Pi.

## Python script

We assume your Raspberry Pi is already equipped with an operating system. A headless setup will suffice.
Copy the scripts s0meter.py and p1meter.py to a folder in your user directory. For the purpose of this readme we will assume that this folder is in /home/pi/stroommeter

Edit the scripts to set the api url (more on this later).

In s0meter.py, set the amount of pulses (counter1_imp, counter2_imp) your energy meter(s) output per KWh. Common values are 2000 (0.5W/imp) or 1000 (1W/imp), which should be printed on the meter. In case you want to use different GPIO pins than 23 and 24, then you also need to edit the pin numbers in the *#setup gpio callbacks* section of the script. Pin numbers are BCM pin numbers, see the documentation of the gpiozero library for details.

In p1meter.py, set the correct settings for the serial connection in the line ser=serial.Serial('/dev/ttyUSB0', 115200). If you've already installed the external libraries (next section), you can use the following command to find the correct device port for your setup (/dev/ttyUSB0 may be different in your setup):
- python -m serial.tools.miniterm
The parameters for the serial.Serial function are (port=None, baudrate=9600, bytesize=EIGHTBITS, parity=PARITY_NONE, stopbits=STOPBITS_ONE). See the pySerial documentation for details.

### External libraries

The external libraries gpiozero and requests are used. These are generally available by default on Raspberry Pi OS. If not, you can install them manually.
For gpiozero:
- sudo apt install python3-gpiozero
For Requests:
- sudo apt install python3-pip
- sudo python -m pip install requests
- sudo python -m pip install pyserial

For more information about these libraries, visit:
- https://gpiozero.readthedocs.io
- https://requests.readthedocs.io
- https://pyserial.readthedocs.io

### More or less meters

The script supports two s0 inputs. If you have only one meter, remove all code that pertains counter2. If you have more meters, add code for counter3 etc. following the examples of counter1 and counter2.
Note that the identifiers counter1, counter2, etc. need to be unique between both scripts. They should furthermore match the counter values in config.inc.php.

### Debugging

The scripts have extensive debugging features. Enable this by setting debug = 1 in the script. Run the script from terminal to see the debug output on screen:
- python s0meter.py
- python p1meter.py


## Automating the script

You probably want the script to run when your Raspberry Pi starts. We can use a systemd script for this. The following example lists the steps for s0meter. Substitute p1meter to set up a second service for the other script.

Edit s0meter.service to make sure that the path to s0meter.py is correct. This is the case if you used the recommended path.
- sudo cp s0meter.service /lib/systemd/system/s0meter.service
- sudo chmod 644 /lib/systemd/system/s0meter.service
- sudo systemctl daemon-reload
- sudo systemctl enable s0meter.service

Next time you restart you Pi, the script will run automatically in the background.
If you want to start the script without restarting you Pi, you can
- sudo systemctl start s0meter.service

If you want to see the status of the service
- sudo systemctl status s0meter.service


## Server-side

The s0 and/or p1 script on the Raspberry Pi calculates power consumption per five minutes and sends that data every fifth minute over HTTP to a remote PHP script, which in turn stores the data in a MySQL/MariaDB database. The advantage of a remote server is that the SD card of the Pi doesn't get killed so quickly by lots of writes. And that you can easily run it on a shared hosting environment to access your energy usage statistics from anywhere. But you could very well run a webserver on the Pi and post the data to localhost.
Either way the script in api/index.php receives the posted data. This must be accessible on a webserver. 

Configure your database credentials in dbconnect.inc.php. The database tables can be set up with setup/install.php.

The counters must be configured in config.inc.php. Again, there are two counters, remove one if you only need one or add accordingly.

The script calculatedaily.php must be set up to run as a cronjob once a day. It is recommended to run it shortly after midnight to have the daily aggregates available as soon as possible. The daily aggregates are used by some of the graphs in the GUI. In config.inc.php you can set not to use the table with daily aggregates if you don't want to or cannot set up the cronjob. Then the five-minute-data is used instead. This however results in slower performance for the graphs that would otherwise use the aggregate data.

### Things to check

- In config.inc.php, each counter has a key. Make sure these keys are identical to the key in line 66 of s0.py. No data is stored if keys mismatch. For security reasons, you may want to set a key of your own.
- The api_url in stroommeter.py points to a absolute url where api/index.php is located.
- If you expect more than 9.9 kWh per five minutes or more than 99.9 kWh per day, then adjust the table structure accordingly.

### Debugging

The script test/index.php can be used to test if data is sent correctly from stroommeter.py. Point api_url to to the location of this file and every time data is received it will be stored in files in the same directory. No database is needed for this test script.
The script test/testpost.php can be used to test the api script. Edit it to make sure the url post to the api/index.php script and that the key used is correct. You can then invoke test/testpost.php from the commandline or by entering the url to it in a webbrowser.
Make sure to remove the test folder from a production environment.


## GUI

The gui folder provides a web interface to view the stored data in graphs. The following graphs are available:
- daily per hour
- weekly per day
- montly per day
- yearly per month
- average per hour
- maximum per hour
- total per year
- comparison of monthly totals over multiple years
- custom (see below)

Access to the gui is protected by username and password in config.inc.php. You can have multiple user accounts. You may want to change/remove the default user. You can encode a password using encodepassword.php.

### Custom charts

There is an option for custom charts, which can be configured in config.inc.php. The chart type is identical to the comparison type (monthly totals over multiple years). Where the comparison type chart shows one counter at a time, the custom type allows to add/subtract multiple counters.


## Troubleshooting

https://superuser.com/questions/1602999/ubuntu-systemctl-service-fails-with-main-process-exited-code-exited-status-1


## License

stroommeter - toolchain for reading energy meters
Copyright (C) 2022-2025  Jasper Vries

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
