
#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include "MAX30105.h"
#include "heartRate.h"

// Wi-Fi credentials
const char* ssid = "YOUR_WIFI_NAME";
const char* password = "YOUR_WIFI_PASSWORD";

// Replace with your computer's IPv4 address.
// Do not use localhost or 127.0.0.1 here.
const char* serverUrl =
  "http://192.168.1.10/heartbeat_monitor/receive_heartbeat.php";

// Must match the secret in receive_heartbeat.php
const char* apiKey =
  "CHANGE_THIS_TO_A_LONG_RANDOM_SECRET";

MAX30105 sensor;

const int SDA_PIN = 21;
const int SCL_PIN = 22;

const long FINGER_THRESHOLD = 50000;
const unsigned long SEND_INTERVAL = 5000;

float bpm = 0;
float bpmSum = 0;
int bpmCount = 0;

unsigned long lastBeat = 0;
unsigned long lastSend = 0;

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.print("Connecting to Wi-Fi");

  unsigned long start = millis();

  while (WiFi.status() != WL_CONNECTED &&
         millis() - start < 20000) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("ESP32 IP address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("Wi-Fi connection failed.");
  }
}

void setup() {
  Serial.begin(115200);

  Wire.begin(SDA_PIN, SCL_PIN);

  if (!sensor.begin(Wire, I2C_SPEED_FAST)) {
    Serial.println(
      "MAX30102 not found. Check SDA, SCL, power and GND."
    );

    while (true) {
      delay(1000);
    }
  }

  // Default SparkFun configuration for heart-rate experiments
  sensor.setup();
  sensor.setPulseAmplitudeRed(0x1F);
  sensor.setPulseAmplitudeIR(0x1F);
  sensor.setPulseAmplitudeGreen(0);

  connectWiFi();

  Serial.println("Place your finger on the sensor.");
}

void loop() {
  long irValue = sensor.getIR();

  // Reset averaged readings when no finger is detected
  if (irValue < FINGER_THRESHOLD) {
    bpm = 0;
    bpmSum = 0;
    bpmCount = 0;
    lastBeat = 0;

    Serial.println("No finger detected.");
    delay(100);
    return;
  }

  if (checkForBeat(irValue)) {
    unsigned long now = millis();

    if (lastBeat != 0) {
      unsigned long delta = now - lastBeat;

      if (delta > 0) {
        float newBpm = 60000.0 / delta;

        if (newBpm >= 30 && newBpm <= 200) {
          bpm = newBpm;
          bpmSum += newBpm;
          bpmCount++;

          // Keep a rolling average of the last 4 detected beats
          if (bpmCount > 4) {
            bpmSum -= bpmSum / bpmCount;
            bpmCount = 4;
          }

          Serial.print("Detected BPM: ");
          Serial.println(bpm);
        }
      }
    }

    lastBeat = now;
  }

  // Send data only when a valid reading is available
  if (bpmCount > 0 &&
      millis() - lastSend >= SEND_INTERVAL) {

    float averageBpm = bpmSum / bpmCount;

    if (WiFi.status() == WL_CONNECTED) {
      WiFiClient client;
      HTTPClient http;

      http.setTimeout(5000);

      if (http.begin(client, serverUrl)) {
        http.addHeader("Content-Type", "application/json");
        http.addHeader("X-API-Key", apiKey);

        String body = "{\"bpm\":" +
          String(averageBpm, 1) +
          ",\"ir_value\":" +
          String((unsigned long)irValue) + "}";

        int responseCode = http.POST(body);

        Serial.print("HTTP response: ");
        Serial.println(responseCode);

        if (responseCode > 0) {
          Serial.println(http.getString());
        }

        http.end();
      } else {
        Serial.println("Could not open HTTP connection.");
      }
    } else {
      Serial.println("Wi-Fi disconnected; reconnecting...");
      connectWiFi();
    }

    lastSend = millis();
  }

  delay(10);
}
