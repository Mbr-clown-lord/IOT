#include <ESP8266WiFi.h>
#include <WebSocketsServer.h>
#include <ArduinoJson.h>
#include <LittleFS.h> // Thư viện để sử dụng LittleFS

// Định nghĩa các chân GPIO
int buzzer = 5;     // D1
int relay = 4;      // D2
int ledMode = 14;   // D5
int button = 0;     // D3

// Các biến toàn cục
int mucCanhbao = 20;
float mq2_value;
boolean buttonState = HIGH;
int runMode = 0;  // Bật/tắt chế độ cảnh báo
int canhbaoState = 0;

// WiFi cấu hình
const char* ssid = "HTC";         
const char* password = "00000000"; 

// Khởi tạo WebSocket server trên port 81
WebSocketsServer webSocket = WebSocketsServer(81);

// Biến theo dõi thời gian gửi dữ liệu khí gas
unsigned long lastGasSendTime = 0;
const unsigned long gasSendInterval = 1000;  // 1 giây

// Hàm lưu ngưỡng cảnh báo và chế độ hoạt động vào LittleFS
void saveSettingsToFS(int threshold, int mode) {
  File file = LittleFS.open("/settings.txt", "w");
  if (file) {
    file.printf("%d,%d", threshold, mode);
    file.close();
    Serial.println("Đã lưu ngưỡng cảnh báo và chế độ vào bộ nhớ.");
  } else {
    Serial.println("Không thể lưu cài đặt!");
  }
}

// Hàm đọc ngưỡng cảnh báo và chế độ hoạt động từ LittleFS
void loadSettingsFromFS() {
  if (LittleFS.exists("/settings.txt")) {
    File file = LittleFS.open("/settings.txt", "r");
    if (file) {
      String data = file.readString();
      file.close();
      Serial.println("Đã đọc cài đặt từ bộ nhớ: " + data);
      
      // Tách chuỗi thành các giá trị
      int commaIndex = data.indexOf(',');
      if (commaIndex > 0) {
        mucCanhbao = data.substring(0, commaIndex).toInt();
        runMode = data.substring(commaIndex + 1).toInt();
      }
    }
  } else {
    Serial.println("Không tìm thấy cài đặt trong bộ nhớ, sử dụng giá trị mặc định.");
  }
}

void setup() {
  Serial.begin(115200);
  pinMode(button, INPUT_PULLUP);
  pinMode(buzzer, OUTPUT);
  pinMode(relay, OUTPUT);
  pinMode(ledMode, OUTPUT);
  digitalWrite(buzzer, LOW); 
  digitalWrite(relay, LOW);

  // Khởi tạo LittleFS
  if (!LittleFS.begin()) {
    Serial.println("Lỗi khi khởi tạo LittleFS");
    return;
  }
  
  // Đọc cài đặt từ bộ nhớ
  loadSettingsFromFS();
  
  // Thiết lập trạng thái ban đầu của LED và chế độ
  digitalWrite(ledMode, runMode);

  // Thiết lập kết nối WiFi
  setupWiFi();

  // Thiết lập WebSocket server và callback xử lý
  webSocket.begin();
  webSocket.onEvent(webSocketEvent);
}

void setupWiFi() {
  Serial.print("Đang kết nối WiFi...");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Đang kết nối...");
  }
  
  Serial.println("Kết nối WiFi thành công!");
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
}

void handleTimer() {
  int mq2 = analogRead(A0);
  float voltage = mq2 / 1024.0 * 5.0;
  float ratio = voltage / 1.4;
  mq2_value = 1000.0 * pow(10, ((log10(ratio) - 1.0278) / 0.6629));
  Serial.println("Gas: " + String(mq2_value, 0) + "ppm");
  
  if (runMode == 1) {
    if (mq2_value > mucCanhbao) {
      canhbaoState = 1;
      Serial.println("Cảnh báo: Khí gas vượt quá mức cho phép!");

      // Gửi cảnh báo dưới dạng JSON
      StaticJsonDocument<200> doc;
      doc["canhbao"] = canhbaoState;
      char output[200];
      serializeJson(doc, output);
      webSocket.broadcastTXT(output);

      digitalWrite(buzzer, HIGH);
      digitalWrite(relay, HIGH);
    } else {
      canhbaoState = 0;
      digitalWrite(buzzer, LOW);
      digitalWrite(relay, LOW);
      
      // Gửi trạng thái không cảnh báo dưới dạng JSON
      StaticJsonDocument<200> doc;
      doc["canhbao"] = canhbaoState;
      char output[200]; 
      serializeJson(doc, output);
      webSocket.broadcastTXT(output);
    }
  } else {
    digitalWrite(buzzer, LOW);
    digitalWrite(relay, LOW);
    
    // Gửi trạng thái không cảnh báo dưới dạng JSON
    StaticJsonDocument<200> doc;
    doc["canhbao"] = canhbaoState;
    char output[200];
    serializeJson(doc, output);
    webSocket.broadcastTXT(output);
    Serial.println("Cảnh báo đã tắt.");
  }
}

void webSocketEvent(uint8_t num, WStype_t type, uint8_t * payload, size_t length) {
  if (type == WStype_TEXT) {
    StaticJsonDocument<200> doc;
    DeserializationError error = deserializeJson(doc, payload, length);

    if (error) {
      Serial.print("Lỗi giải mã JSON: ");
      Serial.println(error.c_str());
      return;
    }

    // Xử lý dữ liệu điều khiển từ client
    mucCanhbao = doc["mucCanhbao"] | mucCanhbao;
    runMode = doc["chedo"] | runMode;
    digitalWrite(ledMode, runMode);
    Serial.println("Cập nhật: Mức cảnh báo " + String(mucCanhbao) + " và chế độ " + String(runMode));

    // Gửi phản hồi JSON đến client
    StaticJsonDocument<200> response;
    response["status"] = runMode;
    response["threshold"] = mucCanhbao;
    char output[200];
    serializeJson(response, output);
    webSocket.sendTXT(num, output);

    // Lưu cài đặt khi có thay đổi
    saveSettingsToFS(mucCanhbao, runMode);
  }
}

void app_loop() {
  if (digitalRead(button) == LOW) {
    if (buttonState == HIGH) {
      buttonState = LOW;
      runMode = !runMode;
      digitalWrite(ledMode, runMode);
      Serial.println("Run mode: " + String(runMode));

      // Gửi dữ liệu chế độ dưới dạng JSON
      StaticJsonDocument<200> doc;
      doc["status"] = runMode;
      char output[200];
      serializeJson(doc, output);
      webSocket.broadcastTXT(output);

      delay(200);
    }
  } else {
    buttonState = HIGH;
  }
  handleTimer();
}

void loop() {
  webSocket.loop();
  
  // Các hoạt động khác như đọc cảm biến, điều khiển thiết bị, gửi dữ liệu
  app_loop();

  // Tạo một đối tượng JSON mới để gửi dữ liệu khí gas
  StaticJsonDocument<200> doc;
  char output[200];

  // Gửi dữ liệu khí gas dưới dạng JSON mỗi giây
  static unsigned long lastGasSendTime = 0;
  if (millis() - lastGasSendTime >= 1000) { // Kiểm tra thời gian gửi (mỗi giây)
    doc["gas"] = mq2_value;
    serializeJson(doc, output);
    webSocket.broadcastTXT(output);
    lastGasSendTime = millis(); // Cập nhật thời gian gửi lần cuối
  }

  // Gửi trạng thái hoạt động của hệ thống
  doc.clear();
  doc["status"] = runMode;
  serializeJson(doc, output);
  webSocket.broadcastTXT(output);
}
