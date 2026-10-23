#!/usr/bin/env bash
set -euo pipefail

PACKAGE_NAME="${PACKAGE_NAME:-com.pandanteknik.paspapan.debug}"
ACTIVITY_NAME="${ACTIVITY_NAME:-com.pandanteknik.paspapan.debug/com.pandanteknik.paspapan.MainActivity}"
APK_PATH="${APK_PATH:-android/app/build/outputs/apk/debug/app-debug.apk}"
APP_URL="${APP_URL:-http://127.0.0.1:8000}"
APP_PORT="${APP_PORT:-8000}"
CDP_PORT="${CDP_PORT:-9222}"
E2E_EMAIL="${E2E_EMAIL:-apk.e2e.user@paspapan.test}"
E2E_PASSWORD="${E2E_PASSWORD:-12345678}"
E2E_LOGIN_TOKEN="${E2E_LOGIN_TOKEN:?E2E_LOGIN_TOKEN is required}"
SCREENSHOT_PATH="${SCREENSHOT_PATH:-screenshots/apk-attendance-e2e.png}"
LAUNCH_WAIT_SECONDS="${LAUNCH_WAIT_SECONDS:-8}"
FORCE_REBUILD="${FORCE_REBUILD:-1}"
SMOKE_LATITUDE="${SMOKE_LATITUDE:-"-6.200000"}"
SMOKE_LONGITUDE="${SMOKE_LONGITUDE:-"106.816666"}"
CAP_CONFIG_PATH="${CAP_CONFIG_PATH:-android/app/src/main/assets/capacitor.config.json}"

server_pid=""
cap_config_backup=""

cleanup() {
  if [ -n "$cap_config_backup" ] && [ -f "$cap_config_backup" ]; then
    cp "$cap_config_backup" "$CAP_CONFIG_PATH"
  fi

  if [ -n "$server_pid" ]; then
    kill "$server_pid" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

if ! command -v adb >/dev/null 2>&1; then
  echo "adb is required but was not found in PATH." >&2
  exit 1
fi

if ! command -v node >/dev/null 2>&1; then
  echo "node is required for the WebView DevTools E2E driver." >&2
  exit 1
fi

if [ -n "${JAVA_HOME:-}" ] && [ ! -x "$JAVA_HOME/bin/java" ]; then
  unset JAVA_HOME
fi

if [ -z "${JAVA_HOME:-}" ] && command -v /usr/libexec/java_home >/dev/null 2>&1; then
  JAVA_HOME="$(/usr/libexec/java_home -v 21 2>/dev/null || /usr/libexec/java_home 2>/dev/null || true)"
  export JAVA_HOME
fi

device_count="$(adb devices | awk 'NR > 1 && $2 == "device" { count++ } END { print count + 0 }')"

if [ "$device_count" -lt 1 ]; then
  echo "No authorized Android device found. Run adb devices and approve USB debugging on the device." >&2
  exit 1
fi

if [ "$device_count" -gt 1 ] && [ -z "${ANDROID_SERIAL:-}" ]; then
  echo "Multiple devices found. Set ANDROID_SERIAL to choose one." >&2
  adb devices
  exit 1
fi

if ! curl -fsS "$APP_URL/login" >/dev/null 2>&1; then
  php artisan serve --host=127.0.0.1 --port="$APP_PORT" >/tmp/paspapan-apk-attendance-e2e-server.log 2>&1 &
  server_pid="$!"
  for _ in $(seq 1 30); do
    if curl -fsS "$APP_URL/login" >/dev/null 2>&1; then
      break
    fi
    sleep 1
  done
fi

if ! curl -fsS "$APP_URL/login" >/dev/null 2>&1; then
  echo "Laravel app is not reachable at $APP_URL." >&2
  if [ -f /tmp/paspapan-apk-attendance-e2e-server.log ]; then
    tail -40 /tmp/paspapan-apk-attendance-e2e-server.log >&2
  fi
  exit 1
fi

if [ "$FORCE_REBUILD" = "1" ] || [ ! -f "$APK_PATH" ]; then
  cap_config_backup="$(mktemp /tmp/paspapan-cap-config.XXXXXX)"
  cp "$CAP_CONFIG_PATH" "$cap_config_backup"

  APP_URL="$APP_URL" CAP_CONFIG_PATH="$CAP_CONFIG_PATH" node -e '
    const fs = require("node:fs");
    const configPath = process.env.CAP_CONFIG_PATH;
    const appUrl = new URL(process.env.APP_URL);
    const config = JSON.parse(fs.readFileSync(configPath, "utf8"));
    config.server = {
      ...(config.server || {}),
      url: appUrl.toString().replace(/\/$/, ""),
      androidScheme: appUrl.protocol.replace(":", ""),
      cleartext: appUrl.protocol === "http:",
      allowNavigation: [appUrl.host],
    };
    fs.writeFileSync(configPath, `${JSON.stringify(config, null, "\t")}\n`);
  '

  ./android/gradlew -p android assembleDebug

  cp "$cap_config_backup" "$CAP_CONFIG_PATH"
  cap_config_backup=""
fi

prepare_output="$(
  E2E_EMAIL="$E2E_EMAIL" E2E_PASSWORD="$E2E_PASSWORD" SMOKE_LATITUDE="$SMOKE_LATITUDE" SMOKE_LONGITUDE="$SMOKE_LONGITUDE" \
    php scripts/prepare-apk-attendance-e2e.php
)"
export E2E_BARCODE_DATA
E2E_BARCODE_DATA="$(printf '%s' "$prepare_output" | node -e 'let data=""; process.stdin.on("data", chunk => data += chunk); process.stdin.on("end", () => { const line = data.trim().split(/\n/).reverse().find((item) => item.trim().startsWith("{")); process.stdout.write(String(JSON.parse(line).barcode_data)); });')"
export E2E_API_TOKEN
E2E_API_TOKEN="$(printf '%s' "$prepare_output" | node -e 'let data=""; process.stdin.on("data", chunk => data += chunk); process.stdin.on("end", () => { const line = data.trim().split(/\n/).reverse().find((item) => item.trim().startsWith("{")); process.stdout.write(String(JSON.parse(line).api_token)); });')"

adb reverse "tcp:$APP_PORT" "tcp:$APP_PORT" >/dev/null
adb install -r -d "$APK_PATH" >/dev/null

for permission in \
  android.permission.CAMERA \
  android.permission.ACCESS_FINE_LOCATION \
  android.permission.ACCESS_COARSE_LOCATION \
  android.permission.READ_EXTERNAL_STORAGE \
  android.permission.READ_MEDIA_IMAGES \
  android.permission.READ_MEDIA_VIDEO \
  android.permission.READ_MEDIA_VISUAL_USER_SELECTED \
  android.permission.POST_NOTIFICATIONS
do
  adb shell pm grant "$PACKAGE_NAME" "$permission" >/dev/null 2>&1 || true
done

adb shell settings put secure location_mode 3 >/dev/null 2>&1 || true
adb shell appops set "$PACKAGE_NAME" COARSE_LOCATION allow >/dev/null 2>&1 || true
adb shell appops set "$PACKAGE_NAME" FINE_LOCATION allow >/dev/null 2>&1 || true
adb emu geo fix "$SMOKE_LONGITUDE" "$SMOKE_LATITUDE" >/dev/null 2>&1 || true

adb logcat -c
adb shell am force-stop "$PACKAGE_NAME" >/dev/null 2>&1 || true
adb shell am start -W -n "$ACTIVITY_NAME" >/tmp/paspapan-apk-attendance-e2e-start.txt
sleep "$LAUNCH_WAIT_SECONDS"

devtools_socket="$(
  adb shell cat /proc/net/unix \
    | tr -d '\r' \
    | awk -F'@' '/webview_devtools_remote/ { print $2 }' \
    | tail -1
)"

if [ -z "$devtools_socket" ]; then
  echo "No WebView DevTools socket found. Use a debug APK with WebView debugging enabled." >&2
  exit 1
fi

adb forward "tcp:$CDP_PORT" "localabstract:$devtools_socket" >/dev/null

APP_URL="$APP_URL" \
CDP_PORT="$CDP_PORT" \
E2E_EMAIL="$E2E_EMAIL" \
E2E_LOGIN_TOKEN="$E2E_LOGIN_TOKEN" \
E2E_BARCODE_DATA="$E2E_BARCODE_DATA" \
E2E_API_TOKEN="$E2E_API_TOKEN" \
SMOKE_LATITUDE="$SMOKE_LATITUDE" \
SMOKE_LONGITUDE="$SMOKE_LONGITUDE" \
  node scripts/apk-attendance-e2e.mjs

mkdir -p "$(dirname "$SCREENSHOT_PATH")"
adb exec-out screencap -p > "$SCREENSHOT_PATH"

if adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime' | grep -q "$PACKAGE_NAME"; then
  echo "Fatal Android crash detected for $PACKAGE_NAME." >&2
  adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime|com.pandanteknik.paspapan' >&2 || true
  exit 1
fi

echo "APK attendance E2E passed."
echo "Barcode: $E2E_BARCODE_DATA"
echo "Screenshot: $SCREENSHOT_PATH"
