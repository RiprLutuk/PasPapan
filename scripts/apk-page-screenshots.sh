#!/usr/bin/env bash
set -euo pipefail

PACKAGE_NAME="${PACKAGE_NAME:-com.pandanteknik.paspapan.debug}"
ACTIVITY_NAME="${ACTIVITY_NAME:-com.pandanteknik.paspapan.debug/com.pandanteknik.paspapan.MainActivity}"
APK_PATH="${APK_PATH:-android/app/build/outputs/apk/debug/app-debug.apk}"
APP_URL="${APP_URL:-http://127.0.0.1:8000}"
APP_PORT="${APP_PORT:-8000}"
CDP_PORT="${CDP_PORT:-9222}"
E2E_LOGIN_TOKEN="${E2E_LOGIN_TOKEN:?E2E_LOGIN_TOKEN is required}"
APK_SCREENSHOT_USER_EMAIL="${APK_SCREENSHOT_USER_EMAIL:-apk.demo.user@paspapan.test}"
APK_SCREENSHOT_ADMIN_EMAIL="${APK_SCREENSHOT_ADMIN_EMAIL:-apk.demo.superadmin@paspapan.test}"
APK_SCREENSHOT_PASSWORD="${APK_SCREENSHOT_PASSWORD:-12345678}"
SCREENSHOT_DIR="${SCREENSHOT_DIR:-screenshots/apk-pages}"
LAUNCH_WAIT_SECONDS="${LAUNCH_WAIT_SECONDS:-8}"
FORCE_REBUILD="${FORCE_REBUILD:-1}"
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
  echo "node is required for the WebView DevTools screenshot driver." >&2
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
  php artisan serve --host=127.0.0.1 --port="$APP_PORT" >/tmp/paspapan-apk-screenshots-server.log 2>&1 &
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
  if [ -f /tmp/paspapan-apk-screenshots-server.log ]; then
    tail -40 /tmp/paspapan-apk-screenshots-server.log >&2
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

APK_SCREENSHOT_USER_EMAIL="$APK_SCREENSHOT_USER_EMAIL" \
APK_SCREENSHOT_ADMIN_EMAIL="$APK_SCREENSHOT_ADMIN_EMAIL" \
APK_SCREENSHOT_PASSWORD="$APK_SCREENSHOT_PASSWORD" \
  php scripts/prepare-apk-screenshots-demo.php >/tmp/paspapan-apk-screenshots-demo.json

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

adb logcat -c
adb shell am force-stop "$PACKAGE_NAME" >/dev/null 2>&1 || true
adb shell svc power stayon true >/dev/null 2>&1 || true
adb shell input keyevent 224 >/dev/null 2>&1 || true
adb shell wm dismiss-keyguard >/dev/null 2>&1 || true
adb shell am start -W -n "$ACTIVITY_NAME" >/tmp/paspapan-apk-screenshots-start.txt
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
E2E_LOGIN_TOKEN="$E2E_LOGIN_TOKEN" \
APK_SCREENSHOT_USER_EMAIL="$APK_SCREENSHOT_USER_EMAIL" \
APK_SCREENSHOT_ADMIN_EMAIL="$APK_SCREENSHOT_ADMIN_EMAIL" \
SCREENSHOT_DIR="$SCREENSHOT_DIR" \
  node scripts/apk-page-screenshots.mjs

if adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime' | grep -q "$PACKAGE_NAME"; then
  echo "Fatal Android crash detected for $PACKAGE_NAME." >&2
  adb logcat -d -t 400 | grep -E 'FATAL EXCEPTION|AndroidRuntime|com.pandanteknik.paspapan' >&2 || true
  exit 1
fi

echo "APK page screenshots captured."
echo "Output: $SCREENSHOT_DIR"
