#include <WiFi.h>
#include <HTTPClient.h>
#include <Adafruit_Fingerprint.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

// ========================================
// 📡 إعدادات WiFi
// ========================================
const char* ssid = "TurkNet1000Mbps_01B5A";
const char* password = "N7xShYA2";

// ========================================
// 🌐 Laravel API URLs
// ========================================
// ✅ IP جهازك: 192.168.1.24
const char* BASE_URL     = "http://192.168.1.24:8000";
String STATUS_URL;
String LOG_URL;
String REGISTER_URL;

// ========================================
// 📺 LCD Display
// ========================================
const int BUZZER_PIN = 5; // المنفذ الافتراضي
LiquidCrystal_I2C lcd(0x27, 16, 2); 

void beep(int duration, int times = 1) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(duration);
    digitalWrite(BUZZER_PIN, LOW);
    if (times > 1) delay(duration);
  }
}

void updateLCD(String line1, String line2 = "") {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(line1);
  if (line2 != "") {
    lcd.setCursor(0, 1);
    lcd.print(line2);
  }
}

// ========================================
// 📢 Buzzer & LED (Optional)
// ========================================
HardwareSerial mySerial(2);  // RX=16, TX=17
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

// ========================================
// 🎯 متغيرات النظام
// ========================================
bool registerMode = false;
int registerUserId = -1;
unsigned long lastStatusCheck = 0;
unsigned long lastFingerCheck = 0;
const unsigned long STATUS_CHECK_INTERVAL = 2000;  // فحص السيرفر كل ثانيتين
const unsigned long FINGER_CHECK_INTERVAL = 300;   // فحص البصمة كل 0.3 ثانية لتكون أسرع في الاستجابة اللمسية

// ========================================
// 🌐 الاتصال بالـ WiFi
// ========================================
void connectWiFi() {
  Serial.println("\n========================================");
  Serial.println("📡 جاري الاتصال بالـ WiFi...");
  Serial.println("========================================");
  Serial.print("SSID: ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ متصل بنجاح!");
    Serial.print("📍 IP Address: ");
    Serial.println(WiFi.localIP());
    
    // بناء الـ URLs
    STATUS_URL = String(BASE_URL) + "/fingerprint/status";
    LOG_URL = String(BASE_URL) + "/fingerprint/log";
    REGISTER_URL = String(BASE_URL) + "/fingerprint/register-complete";
    
    Serial.println("\n🔗 API Endpoints:");
    Serial.println("  - Status: " + STATUS_URL);
    Serial.println("  - Log: " + LOG_URL);
    Serial.println("  - Register: " + REGISTER_URL);
  } else {
    Serial.println("\n❌ فشل الاتصال بالـ WiFi!");
    Serial.println("تحقق من:");
    Serial.println("  1. اسم الشبكة وكلمة المرور");
    Serial.println("  2. قوة الإشارة");
    while (1) { delay(1000); }
  }
}

// ========================================
// 🔐 تهيئة مستشعر البصمة
// ========================================
void initFingerprint() {
  Serial.println("\n========================================");
  Serial.println("🔐 تهيئة مستشعر البصمة...");
  Serial.println("========================================");
  
  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  delay(100);
  
  finger.begin(57600);
  delay(100);
  
  if (finger.verifyPassword()) {
    Serial.println("✅ تم العثور على مستشعر البصمة!");
    
    // عرض معلومات المستشعر
    finger.getTemplateCount();
    Serial.print("📊 عدد البصمات المخزنة: ");
    Serial.println(finger.templateCount);
    
    Serial.print("📦 سعة التخزين: ");
    Serial.println(finger.capacity);
  } else {
    Serial.println("❌ لم يتم العثور على مستشعر البصمة!");
    Serial.println("\nتحقق من التوصيلات:");
    Serial.println("  AS608 -> ESP32");
    Serial.println("  VCC   -> 3.3V");
    Serial.println("  GND   -> GND");
    Serial.println("  TX    -> GPIO16 (RX2)");
    Serial.println("  RX    -> GPIO17 (TX2)");
    while (1) { delay(1000); }
  }
}

// ========================================
// 🔍 قراءة البصمة (للتحقق فقط)
// ========================================
int readFingerprint() {
  uint8_t p = finger.getImage();
  if (p == FINGERPRINT_NOFINGER) return -1; // لا يوجد إصبع
  if (p != FINGERPRINT_OK) return -1;
  
  Serial.println("🔘 تم اكتشاف إصبع! جاري التحقق...");
  
  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    Serial.println("⚠️ فشل في تحليل الصورة، جرب مرة أخرى.");
    return -1;
  }
  
  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) {
    Serial.println("\n╔════════════════════════════════════════╗");
    Serial.println("║  ❌ بصمة غير معروفة!                ║");
    Serial.println("║  الرجاء التسجيل أولاً                ║");
    Serial.println("╚════════════════════════════════════════╝\n");
    updateLCD("  GECERSIZ BT  ", " Tekrar Deneyin ");
    return -1;
  }
  
  updateLCD(" Parmak Okundu  ", "  Lutfen Bekle  ");
  return finger.fingerID;
}

// ========================================
// 🔄 فحص حالة التسجيل من السيرفر
// ========================================
void checkRegisterStatus() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  static bool lastRegisterState = false; // لمتابعة التغير في الحالة
  
  HTTPClient http;
  String url = STATUS_URL + "?t=" + String(millis());
  http.begin(url);
  http.setTimeout(2000);
  
  int code = http.GET();
  
  if (code == 200) {
    String payload = http.getString();
    
    // البحث عن حالة التسجيل (نبحث عن 1 أو true)
    bool shouldRegister = (payload.indexOf("\"register\":1") >= 0 || payload.indexOf("\"register\":true") >= 0);
    
    // اطبع فقط إذا تغيرت الحالة أو دخلنا وضع التسجيل
    if (shouldRegister && !lastRegisterState) {
       Serial.println("\n📡 [HTTP] تم استلام أمر بدء التسجيل من السيرفر...");
    }
    lastRegisterState = shouldRegister;

    if (shouldRegister) {
      int userIdPos = payload.indexOf("\"user_id\":");
      if (userIdPos >= 0) {
        int startPos = userIdPos + 10;
        while (startPos < payload.length() && !isdigit(payload[startPos])) {
          startPos++;
        }
        
        int endPos = startPos;
        while (endPos < payload.length() && isdigit(payload[endPos])) {
          endPos++;
        }
        
        int newUserId = payload.substring(startPos, endPos).toInt();
        
        if (!registerMode || newUserId != registerUserId) {
          registerMode = true;
          registerUserId = newUserId;
          
          Serial.println("\n🔥 [🚨] وضع التسجيل بدأ الآن!");
          Serial.print("👤 المستخدم المستهدف: ");
          Serial.println(registerUserId);
          updateLCD(" KAYIT MODU AKT", "  ID: " + String(registerUserId));
        }
      }
    } else {
      if (registerMode) {
        Serial.println("\n✅ انتهى وضع التسجيل - العودة للوضع العادي.");
        registerMode = false;
        registerUserId = -1;
      }
    }
  }
  http.end();
}

// ========================================
// ✍️ تسجيل بصمة جديدة في المستشعر
// ========================================
bool enrollFingerprint(int* outFingerId) {
  Serial.println("\n========================================");
  Serial.println("✍️ بدء عملية التسجيل");
  Serial.println("========================================");
  
  // البحث عن موقع فارغ
  int id = getNextFreeID();
  if (id == -1) {
    Serial.println("❌ الذاكرة ممتلئة!");
    return false;
  }
  
  Serial.print("📍 الموقع: ");
  Serial.println(id);
  
  // المرحلة 1: القراءة الأولى
  Serial.println("\n👆 [1/2] ضع إصبعك على المستشعر...");
  
  int p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    delay(50);
  }
  
  Serial.println("✅ تم التقاط الصورة!");
  
  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    Serial.println("❌ خطأ في معالجة الصورة!");
    return false;
  }
  
  Serial.println("✅ تم معالجة الصورة الأولى");
  Serial.println("🖐️ ارفع إصبعك...");
  
  delay(2000);
  
  // انتظار رفع الإصبع
  while (finger.getImage() != FINGERPRINT_NOFINGER) {
    delay(50);
  }
  
  // المرحلة 2: القراءة الثانية
  Serial.println("\n👆 [2/2] ضع نفس الإصبع مرة أخرى...");
  
  p = -1;
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    delay(50);
  }
  
  Serial.println("✅ تم التقاط الصورة!");
  
  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    Serial.println("❌ خطأ في معالجة الصورة!");
    return false;
  }
  
  Serial.println("✅ تم معالجة الصورة الثانية");
  
  // إنشاء النموذج
  Serial.println("\n🔄 جاري إنشاء نموذج البصمة...");
  p = finger.createModel();
  
  if (p == FINGERPRINT_OK) {
    Serial.println("✅ تم إنشاء النموذج بنجاح!");
  } else if (p == FINGERPRINT_ENROLLMISMATCH) {
    Serial.println("❌ البصمتان غير متطابقتان! حاول مرة أخرى.");
    return false;
  } else {
    Serial.println("❌ خطأ في إنشاء النموذج!");
    return false;
  }
  
  // حفظ النموذج
  Serial.println("💾 جاري حفظ البصمة...");
  p = finger.storeModel(id);
  
  if (p == FINGERPRINT_OK) {
    Serial.println("✅ تم حفظ البصمة بنجاح!");
    Serial.print("🆔 Finger ID: ");
    Serial.println(id);
    
    *outFingerId = id;
    return true;
  } else {
    Serial.println("❌ خطأ في حفظ البصمة!");
    return false;
  }
}

// ========================================
// 🔢 البحث عن موقع فارغ (نسخة محسنة)
// ========================================
int getNextFreeID() {
  Serial.println("🔍 جاري فحص الذاكرة عن موقع فارغ...");
  
  for (int i = 1; i <= 127; i++) {
    uint8_t p = finger.loadModel(i);
    // إذا لم يجد موديل في هذا الموقع (FINGERPRINT_IDMISMATCH 0x0B) أو أي خطأ آخر
    // فهذا يعني أن الموقع غالباً فارغ وجاهز للاستخدام
    if (p != FINGERPRINT_OK) {
      return i;
    }
  }
  
  return -1; // الذاكرة ممتلئة فعلياً
}

// دالة لمسح الذاكرة بالكامل (تُستدعى عند الحاجة)
void emptySensorDatabase() {
  Serial.println("🧨 جاري مسح ذاكرة البصمات بالكامل...");
  if (finger.emptyDatabase() == FINGERPRINT_OK) {
    Serial.println("✨ تم مسح الذاكرة بنجاح! الذاكرة الآن فارغة.");
  } else {
    Serial.println("❌ فشل مسح الذاكرة!");
  }
}

// ========================================
// 📤 إرسال سجل الحضور
// ========================================
void sendLog(int fingerID) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ WiFi غير متصل!");
    return;
  }
  
  HTTPClient http;
  http.begin(LOG_URL);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);
  
  String json = "{\"finger_id\":" + String(fingerID) + "}";
  
  Serial.println("\n📤 إرسال سجل الحضور...");
  Serial.println("Payload: " + json);
  
  int code = http.POST(json);
  
  if (code > 0) {
    String response = http.getString();
    Serial.println("📥 استجابة السيرفر:");
    Serial.println(response);
    
    if (code == 200 || code == 201) {
      Serial.println("✅ تم تسجيل الحضور بنجاح!");
      
      // استخراج النوع (in/out) من الاستجابة
      if (response.indexOf("\"type\":\"in\"") >= 0) {
        Serial.println("🟢 دخول");
      } else if (response.indexOf("\"type\":\"out\"") >= 0) {
        Serial.println("🔴 خروج");
      }
      
      // 🎉 عرض رسالة الترحيب
      int msgStart = response.indexOf("\"message\":\"");
      if (msgStart >= 0) {
        msgStart += 11; // طول "message":"
        int msgEnd = response.indexOf("\"", msgStart);
        if (msgEnd > msgStart) {
          String message = response.substring(msgStart, msgEnd);
          
          Serial.println("\n╔════════════════════════════════════════╗");
          Serial.print("║  📢 ");
          Serial.print(message);
          // إضافة مسافات للمحاذاة
          int spaces = 34 - message.length();
          for (int i = 0; i < spaces; i++) Serial.print(" ");
          Serial.println("║");
          Serial.println("╚════════════════════════════════════════╝\n");
          
          // 📺 العرض على الشاشة
          if (response.indexOf("\"type\":\"in\"") >= 0) {
            updateLCD("  HOS GELDINIZ  ", message.substring(8)); // عرض الاسم فقط
          } else {
            updateLCD("   GULE GULE    ", message.substring(9));
          }
        }
      }
    } else {
      Serial.println("⚠️ خطأ في التسجيل!");
    }
  } else {
    Serial.print("❌ خطأ HTTP: ");
    Serial.println(http.errorToString(code));
  }
  
  http.end();
}

// ========================================
// 📢 إرسال تأكيد التسجيل للسيرفر
// ========================================
void sendRegisterComplete(int fingerID) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ WiFi غير متصل!");
    return;
  }
  
  HTTPClient http;
  http.begin(REGISTER_URL);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);
  
  String json = "{";
  json += "\"user_id\":" + String(registerUserId) + ",";
  json += "\"finger_id\":" + String(fingerID);
  json += "}";
  
  Serial.println("\n📤 إرسال تأكيد التسجيل...");
  Serial.println("Payload: " + json);
  
  int code = http.POST(json);
  
  if (code > 0) {
    String response = http.getString();
    Serial.println("📥 استجابة السيرفر:");
    Serial.println(response);
    
    if (code == 200 || code == 201) {
      Serial.println("\n🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉");
      Serial.println("🎉 تم ربط البصمة بالمستخدم بنجاح!");
      Serial.println("🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉🎉");
    } else {
      Serial.println("⚠️ خطأ في الربط!");
    }
  } else {
    Serial.print("❌ خطأ HTTP: ");
    Serial.println(http.errorToString(code));
  }
  
  http.end();
  
  // إنهاء وضع التسجيل
  registerMode = false;
  registerUserId = -1;
  
  Serial.println("\n🔵 العودة للوضع العادي...");
}

// ========================================
// ⚙️ SETUP
// ========================================
void setup() {
  Serial.begin(115200);
  delay(1000);
  
  // تهيئة الشاشة
  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  updateLCD("  Sistem Hazir  ", "   Bekleniyor   ");
  
  // Assuming BUZZER_PIN is defined elsewhere or will be added.
  // For now, commenting it out to avoid compilation error if not defined.
  // pinMode(BUZZER_PIN, OUTPUT); 
  Serial.println("\n\n");
  Serial.println("╔════════════════════════════════════════╗");
  Serial.println("║   🚀 نظام البصمة - بدء التشغيل 🚀   ║");
  Serial.println("╚════════════════════════════════════════╝");
  
  // 1. الاتصال بالـ WiFi
  connectWiFi();
  
  // 2. تهيئة مستشعر البصمة
  initFingerprint();
  
  Serial.println("\n╔════════════════════════════════════════╗");
  Serial.println("║    🚀 [V5] SYSTEM SILENT & READY     ║");
  Serial.println("╚════════════════════════════════════════╝");
  Serial.println("\n🔵 الوضع: عادي | بانتظار البصمات...");
}

// ========================================
// 🔁 LOOP (نسخة محسنة للسرعة القصوى)
// ========================================
void loop() {
  unsigned long currentMillis = millis();
  
  // 1️⃣ قراءة البصمة فوراً (أسرع استجابة ممكنة)
  if (!registerMode) {
    int id = readFingerprint();
    if (id >= 0) {
      beep(100, 1);
      updateLCD(" OKUNDU! ID: " + String(id), "  Gonderiliyor  ");
      sendLog(id);
      
      delay(2000); // وقت مستقطع بعد النجاح
      while (finger.getImage() != FINGERPRINT_NOFINGER) delay(10);
      updateLCD("  Sistem Hazir  ", "   Bekleniyor   ");
    }
  } else {
    // وضع التسجيل
    int fingerId = 0;
    if (enrollFingerprint(&fingerId)) {
      sendRegisterComplete(fingerId);
      delay(2000);
      updateLCD("  Sistem Hazir  ", "   Bekleniyor   ");
    }
  }

  // 2️⃣ فحص السيرفر (فقط إذا كان الحساس فارغاً لضمان عدم التعليق)
  if (currentMillis - lastStatusCheck >= STATUS_CHECK_INTERVAL) {
    lastStatusCheck = currentMillis;
    checkRegisterStatus();
  }

  // 3️⃣ أوامر السيريال
  if (Serial.available()) {
    char cmd = Serial.read();
    if (cmd == 'C' || cmd == 'c') emptySensorDatabase();
  }
}
