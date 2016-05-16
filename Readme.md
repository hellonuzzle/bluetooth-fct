# Bluetooth Functional Test (FCT) Suite
A PHP development environment for creating a Bluetooth testing suite. Useful for connecting to a device to verify it's Bluetooth service setup as well as testing the device itself by interacting with it through automated BT tests.

## Requirements 
This software relies on the (awesome) NordicSemiconductor Master Control Panel tool running on Android.
* Nordic MCP tool on Android phone
* ADB (Android Debug command line tool)
* PHP 5.5+

## Inception
We are building the Nuzzle pet tracking collar. We need a fast, accurate way to test each of the sensors on our device during development and in a factory setting. Instead of hooking wires up to the device (easy during debug, more onerous in a factory), we want to use a special Bluetooth service to send out data. This automated suite can connect to the device automatically, read or write to characteristics and validate the responses.


# Goals
We have a list of requirements we want to implement in our suite for our needs:

### Done
* Separation of device defintion and test defintion(s)  
* Easy to create automated test files  
* Custom validation of output (Read a characteristic and decide if it 'passes' based on criteria of your choice, not just exact match)
* OS agnostic - Tests run on Windows, Mac, Linux.
* Easy operator testing - Little/no training required. Just run test and get pass/fail

### To-do
* Golden vs DUT testing - Read a value from a golden unit and compare to a DUT
* Interactive prompts - Write to a characteristic and prompt users for Y/N if it passes (like turning on an LED)
* Split tests based on validation type - Currently, all tests are run sequentially, pass or fail. Want to be able to split tests based on fail or if user input required.

### Need updates in Nordic MCP to accomplish
* Automated BT scan and connection. Want to be able to specify a BT device 'name' (or even more details like a advertising profile) and scan for any addresses reporting that name. Then, connect based on criteria (strongest RSSI, never connected before, etc)

# Installation
## Composer - Add To Your project 
Run the following composer command in your project:

>composer require alzander/bluetooth-fct:dev-master

## Create Test Directory/Structure
* Copy the /vendor/alzander/bluetooth-fct/bin/bluetooth-fct file (or whole directory) to where you'll create your test suite
* Edit the bluetooth-fct file and update the autoload paths to point to your /vendor/autoload.php file
* Create your tests, as below.

# Running Tests
The bluetooth-fct php script starts and runs tests. It expects the following sub-directory structure:
/fct
/fct/devices - Device definition files
/fct/suites - Test suites to run on devices
/fct/validtor - Any custom validator files
/fct/xmls - Generated XML test files from the suite files
/fct/results - Results of the most recently performed test

**NOTE**: For the most up-to-date format of each file, see the examples in the /bin/fct directories

## Device Files
Device files are created in JSON an define the Bluetooth profile the device should conform to. There are *no* testing details in this file.

	{
	  "name": "Example Board",
	  "CCCD_UUID": "12345678-0000-1000-8000-00805f9b34fb",
	  "timeouts": {
	  	"connect": 5000,
	  	"discover_services": 10000,
	  	"bond": 10000
	  },
	  "services":
	  [
	  	{
	  		"id": "temperature",
	  		"name": "Temperature Sensor",
	  		"UUID": "deadbeef-1212-efde-1523-785fef13d123",
	  		"characteristics":
	  		[
	  			{
	  				"id": "value",
	  				"UUID": "deadbeef-1212-efde-1234-785fef13d123",
	  				"properties": {
	  					"notify": "mandatory",
	  					"read": "mandatory",
	  					"write": "mandatory",
	  					"write_without_response": "mandatory",
	  					"signed_write": "mandatory"					}
		  		}
			]
		},
		{
			...
		}
	  ]
	}
	
## Test Files
Test files specify the target devices to connect to and the tests that should be performed on each device.

	{
	  "description": "Read the temperature characteristic",
	  "targets": [
	    {
	      "id": "DUT1",
	      "device": "example_device",
	      "address": "E8:A2:E4:A5:25:20"
	    },
	    {
	      "id": "DUT2",
	      "device": "example_device",
	      "address": "CC:BB:CC:DD:EE:FF"
	    }
	
	  ],
	  "tests": [
	    {
	      "description": "Read temperature",
	      "target": "DUT1",
	      "command": "characteristic.read",
	      "service": "temperature",
	      "characteristic": "value",
	      "validation": {
	        "type": "temperature",
	        "min": "30",
	        "max": "35"
	      }
	    },
	    {
	    	...
	    }
	  ]
	}
	
## Output Test File
This is the same format that can be fed directly into the MCP test.bat file or run directly (and validated with) the bluetooth-fct executable

	<?xml version="1.0"?>
	<test-suite>
	 <target id="DUT1" name="Collar-Board" address="E8:A2:E4:A5:25:20"/>
	 <target id="DUT2" name="Collar-Board" address="CC:BB:CC:DD:EE:FF"/>
	 <test target="DUT1" id="test_1">
	  <connect timeout="5000"/>
	  <refresh/>
	  <sleep timeout="5000"/>
	  <discover-services target="DUT1" timeout="10000"/>
	  <read description="Read temp sensor" service-uuid="0000abcd-1212-efde-1523-785fef13d123" characteristic-uuid="0000beef-1212-efde-1523-785fef13d123">
	   <assert-value description="AUTOMATED_TEST_1: " expected="SUCCESS_WARNING_ON_FAIL" value="AUTOTEST_TEST_1_FILLER"/>
	  </read>
	 </test>
	 <run-test ref="test_1"/>
	</test-suite>

## Commands

Generate the test XML files for the test defined in /fct/suites/test_name.json:
> bluetooth-fct generateXMLs test_name  

Generate the XML files and run the same test suite
> bluetooth-fct run test_name

### Example Output
When the 'run' command is issues, the following is an example output

	Generating XML test scripts from read_temp
	Running tests
	List of devices attached
	123456	device
	
	Starting test 1
	Removing old results file...
	Uploading new test file...
	Starting test service...
	Waiting for test results...
	Processed temp: 32C / 89.6F  <-- This is from our custom validator
	Pass - Read temperature. Value = 48

	Test Summary:
	Total Tests: 3
	Tests Passed: 3
	Tests Failed: 0
	+---+------------------+--------+--------+
	|   | Name             | Result | Output |
	+---+------------------+--------+--------+
	| 1 | Read temperature | Pass   |        |
	| 2 | Read temperature | Pass   |        |
	| 3 | Read temperature | Pass   |        |
	+---+------------------+--------+--------+
	ALL TESTS PASSED!
	

# Issues and Feature Requests
This FCT tester is being solely developed for Nuzzle. Any bugs/issues reported will likely be fixed.

Any features requested that aren't required for our testing will likely not be considered for now. We have a mission and a goal.

If you develop a new feature or fix, we'll gladly evaluate any pull requests!
