/*
Based on:
WifiPortal.ino

Simple web configuration portal
When it is connected to a WiFi network, everything is fine
When connection fails, it create a portal to insert a new SSID and password,
If the new credentials works, it will be stored in ESP32 non volatile storage (NVS).
In a nutshell: it's a https://github.com/tzapu/WiFiManager for ESP32 with a non blocking implementation and simple design.
Connect to wifi network generated by ESP32 and tip in your browser:
AP IPv4: 192.168.4.1  or  AP IPv6
by Evandro Luis Copercini - 2017
Public Domain
*/


// --- wifi ---
#include "WiFi.h"
#include <Preferences.h>

#define AP_SSID  "lov-iot"       //can set ap hostname here

WiFiServer server(80);
Preferences preferences;
static volatile bool wifi_connected = false;
String wifiSSID, wifiPassword;

const char* host = "tiigbg.se";

// --- sensors ---
#include <Wire.h>
#include <Adafruit_Sensor.h>
int maxAttempts = 5;

// BME680 sensor
#include "Adafruit_BME680.h"

#define SEALEVELPRESSURE_HPA (1002)
Adafruit_BME680 bme; // I2C
bool bme680Found = false; // will auto detect

// CJMCU4541 sensor
bool cjmcu4541Installed = false; // change manually
int redPin = 2;
int noxPin = 32;
int prePin = 4;
int preheatSeconds = 10;

// SGP30 sensor
#include "Adafruit_SGP30.h"
Adafruit_SGP30 sgp30sensor;
bool sgp30Found = false; // auto detect
/**/
// --- begin functions ---

void WiFiEvent(WiFiEvent_t event)
{
	switch (event)
	{
		case SYSTEM_EVENT_AP_START:
			//can set ap hostname here
			WiFi.softAPsetHostname(AP_SSID);
			//enable ap ipv6 here
			WiFi.softAPenableIpV6();
			break;

		case SYSTEM_EVENT_STA_START:
			//set sta hostname here
			WiFi.setHostname(AP_SSID);
			break;
		case SYSTEM_EVENT_STA_CONNECTED:
			//enable sta ipv6 here
			WiFi.enableIpV6();
			break;
		case SYSTEM_EVENT_AP_STA_GOT_IP6:
			//both interfaces get the same event
			Serial.print("STA IPv6: ");
			Serial.println(WiFi.localIPv6());
			Serial.print("AP IPv6: ");
			Serial.println(WiFi.softAPIPv6());
			break;
		case SYSTEM_EVENT_STA_GOT_IP:
			wifiOnConnect();
			wifi_connected = true;
			break;
		case SYSTEM_EVENT_STA_DISCONNECTED:
			if(wifi_connected)
			{
				Serial.println("Starting AP");
				WiFi.mode(WIFI_MODE_APSTA);
				WiFi.softAP(AP_SSID);
			}
			wifi_connected = false;
			wifiOnDisconnect();
			break;
		default:
			break;
	}
}

void setup()
{
	//Serial.begin(115200);
	Serial.begin(9600);
	WiFi.onEvent(WiFiEvent);
	WiFi.mode(WIFI_MODE_APSTA);
	WiFi.softAP(AP_SSID);
	Serial.println("AP Started");
	Serial.print("AP SSID: ");
	Serial.println(AP_SSID);
	Serial.print("AP IPv4: ");
	Serial.println(WiFi.softAPIP());

	preferences.begin("wifi", false);
	wifiSSID =  preferences.getString("ssid", "none");           //NVS key ssid
	wifiPassword =  preferences.getString("password", "none");   //NVS key password
	preferences.end();
	Serial.print("Stored SSID: ");
	Serial.println(wifiSSID);

	WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
	Serial.println("Starting server");
	server.begin();
	Serial.println("Started server");

	Serial.println("BME680");
	// start BME680 sensor
	bme680Found = bme.begin();
	for(int attempts = 1; attempts <= maxAttempts && !bme680Found; attempts++) {
		Serial.println("Could not find a valid BME280 sensor, check wiring! (attempt nr "+String(attempts)+")");
		bme680Found = bme.begin();
		delay(1000);
		if(attempts == maxAttempts)
		{
			Serial.println("Failed to find BME680 sensor. Giving up.");

		}
	}

	if(bme680Found) {
		Serial.println("BME680 found.");
		// Set up oversampling and filter initialization
		bme.setTemperatureOversampling(BME680_OS_8X);
		bme.setHumidityOversampling(BME680_OS_2X);
		bme.setPressureOversampling(BME680_OS_4X);
		bme.setIIRFilterSize(BME680_FILTER_SIZE_3);
		bme.setGasHeater(320, 150); // 320*C for 150 ms
	}

	// cjmcu4541 sensor
	Serial.println("CJMCU4541");
	if(cjmcu4541Installed)
	{
		pinMode(prePin, OUTPUT);
	  Serial.print("Preheating cjmcu4541 for "+String(preheatSeconds)+" seconds...");
	  // Wait for preheating
	  digitalWrite(prePin, 1);
	  delay(preheatSeconds * 1000);
	  digitalWrite(prePin, 0);
	  Serial.println("Done preheating.");
	}

	// SGP30 sensor
	Serial.println("SGP30");
	sgp30Found = sgp30sensor.begin();
	for(int attempts = 1; attempts <= maxAttempts && !sgp30Found; attempts++) {
		Serial.println("Could not find a valid SGP30 sensor, check wiring! (attempt nr "+String(attempts)+")");
		sgp30Found = sgp30sensor.begin();
		delay(1000);
		if(attempts == maxAttempts)
		{
			Serial.println("Failed to find SGP30 sensor. Giving up.");
		}
	}
	if(sgp30Found)
	{
		Serial.print("Found SGP30 serial #");
		Serial.print(sgp30sensor.serialnumber[0], HEX);
		Serial.print(sgp30sensor.serialnumber[1], HEX);
		Serial.println(sgp30sensor.serialnumber[2], HEX);

	}
}

void loop()
{
	if (wifi_connected) {
		wifiConnectedLoop();
	} else {
		wifiDisconnectedLoop();
	}
}

//when wifi connects
void wifiOnConnect()
{
	Serial.println("STA Connected");
	Serial.print("STA SSID: ");
	Serial.println(WiFi.SSID());
	Serial.print("STA IPv4: ");
	Serial.println(WiFi.localIP());
	Serial.print("STA IPv6: ");
	Serial.println(WiFi.localIPv6());
	WiFi.mode(WIFI_MODE_STA);     //close AP network
}

//when wifi disconnects
void wifiOnDisconnect()
{
	Serial.println("STA Disconnected");
	delay(1000);
	WiFi.begin(wifiSSID.c_str(), wifiPassword.c_str());
}

//while wifi is connected
void wifiConnectedLoop()
{
	Serial.print("RSSI: ");
	Serial.println(WiFi.RSSI());

	readSensorData();

	delay(1000);
}

void wifiDisconnectedLoop()
{
	WiFiClient client = server.available();   // listen for incoming clients

	if (client) {                             // if you get a client,
		Serial.println("New client");           // print a message out the serial port
		String currentLine = "";                // make a String to hold incoming data from the client
		while (client.connected()) {            // loop while the client's connected
			if (client.available()) {             // if there's bytes to read from the client,
			char c = client.read();             // read a byte, then
			Serial.write(c);                    // print it out the serial monitor
			if (c == '\n') {                    // if the byte is a newline character
				// if the current line is blank, you got two newline characters in a row.
				// that's the end of the client HTTP request, so send a response:
				if (currentLine.length() == 0)
				{
					// HTTP headers always start with a response code (e.g. HTTP/1.1 200 OK)
					// and a content-type so the client knows what's coming, then a blank line:
					client.println("HTTP/1.1 200 OK");
					client.println("Content-type:text/html");
					client.println();

					// the content of the HTTP response follows the header:
					client.print("<form method='get' action='a'><label>SSID: </label><input name='ssid' length=32><input name='pass' length=64><input type='submit'></form>");
					// The HTTP response ends with another blank line:
					client.println();
					// break out of the while loop:
					break;
				}
				else
				{    // if you got a newline, then clear currentLine:
					currentLine = "";
				}
				} else if (c != '\r')
				{  // if you got anything else but a carriage return character,
					currentLine += c;      // add it to the end of the currentLine
					continue;
				}

				if (currentLine.startsWith("GET /a?ssid=") )
				{
					//Expecting something like:
					//GET /a?ssid=blahhhh&pass=poooo
					Serial.println("");
					Serial.println("Cleaning old WiFi credentials from ESP32");
					// Remove all preferences under opened namespace
					preferences.clear();

					String qsid;
					qsid = currentLine.substring(12, currentLine.indexOf('&')); //parse ssid
					Serial.println(qsid);
					Serial.println("");
					String qpass;
					qpass = currentLine.substring(currentLine.lastIndexOf('=') + 1, currentLine.lastIndexOf(' ')); //parse password
					Serial.println(qpass);
					Serial.println("");

					preferences.begin("wifi", false); // Note: Namespace name is limited to 15 chars
					Serial.println("Writing new ssid");
					preferences.putString("ssid", qsid);

					Serial.println("Writing new pass");
					preferences.putString("password", qpass);
					delay(300);
					preferences.end();

					client.println("HTTP/1.1 200 OK");
					client.println("Content-type:text/html");
					client.println();

					// the content of the HTTP response follows the header:
					client.print("<h1>OK! Restarting in 5 seconds...</h1>");
					client.println();
					Serial.println("Restarting in 5 seconds...");
					delay(5000);
					ESP.restart();
				}
			}
		}
		// close the connection:
		client.stop();
		Serial.println("client disconnected");
	}
}




void sendData(int sensor_ids[], float values[], int arraySize) {
	Serial.print("connecting to ");
	Serial.println(host);

	// Use WiFiClient class to create TCP connections
	WiFiClient client;
	const int httpPort = 80;
	if (!client.connect(host, httpPort)) {
		Serial.println("connection failed");
		return;
	}

	// We now create a URI for the request
	String url = "/lov-iot/addDatas.php?";
	for(int i = 0; i < arraySize; i++)
	{
		if(i>0)
			url += "&";
		url += "sensor_ids[]=";
		url += sensor_ids[i];
		url += "&data_values[]=";
		url += values[i];  
	}

	//http://tiigbg.se/lov-iot/addDatas.php?sensor_ids[]=2&data_values[]=1337&sensor_ids[]=3&data_values[]=1338


	Serial.print("Requesting URL: ");
	Serial.println(url);

	// This will send the request to the server
	client.print(String("GET ") + url + " HTTP/1.1\r\n" +
		"Host: " + host + "\r\n" +
		"Connection: close\r\n\r\n");
	unsigned long timeout = millis();
	while (client.available() == 0) {
		if (millis() - timeout > 5000) {
			Serial.println(">>> Client Timeout !");
			client.stop();
			return;
		}
	}

	// Read all the lines of the reply from server and print them to Serial
	while(client.available()) {
		String line = client.readStringUntil('\r');
		Serial.print(line);
	}
	Serial.println();
	Serial.println("closing connection");
}




// ------ sensor stuff ------

void readSensorData()
{
	if(bme680Found) 
	{
		Serial.println("<BME860 sensor>");
		if (! bme.performReading()) {
			Serial.println("Failed to perform reading BME680 sensor");
		}
		else
		{
			Serial.print("Temperature = ");
			Serial.print(bme.temperature);
			Serial.println(" *C");

			Serial.print("Pressure = ");
			Serial.print(bme.pressure / 100.0);
			Serial.println(" hPa");

			Serial.print("Humidity = ");
			Serial.print(bme.humidity);
			Serial.println(" %");

			Serial.print("Gas = ");
			Serial.print(bme.gas_resistance / 1000.0);
			Serial.println(" KOhms");

			Serial.print("Approx. Altitude = ");
			Serial.print(bme.readAltitude(SEALEVELPRESSURE_HPA));
			Serial.println(" m");

			int sensor_ids[] = {1, 2, 3, 4};
			float values[] = {bme.gas_resistance / 1000.0, bme.temperature, bme.pressure / 100.0, bme.humidity};
			sendData(sensor_ids, values, 4);
		}
	}

	if(cjmcu4541Installed)
	{
		int redValue = analogRead(redPin);
	  int noxValue = analogRead(noxPin);

	  Serial.println("<CJMCU4541 sensor>");
	  Serial.print("red: ");
	  Serial.println(redValue);
	  Serial.print("nox: ");
	  Serial.println(noxValue);
	  int sensor_ids[] = {5, 6};
		float values[] = {redValue, noxValue};
		sendData(sensor_ids, values, 2);
	}

	if(sgp30Found)
	{
		Serial.println("<SGP30 sensor>");
		if (! sgp30sensor.IAQmeasure()) {
			Serial.println("Measurement failed");
		}
		else
		{
			Serial.print("TVOC "); Serial.print(sgp30sensor.TVOC); Serial.print(" ppb\t");
			Serial.print("eCO2 "); Serial.print(sgp30sensor.eCO2); Serial.println(" ppm");
			int sensor_ids[] = {7, 8};
			float values[] = {sgp30sensor.TVOC, sgp30sensor.eCO2};
			sendData(sensor_ids, values, 2);

			if (millis()%60000 == 0) {

				uint16_t TVOC_base, eCO2_base;
				if (! sgp30sensor.getIAQBaseline(&eCO2_base, &TVOC_base)) {
					Serial.println("Failed to get baseline readings");
					return;
				}
				Serial.print("****Baseline values: eCO2: 0x"); Serial.print(eCO2_base, HEX);
				Serial.print(" & TVOC: 0x"); Serial.println(TVOC_base, HEX);
			}
		}
	}
}