import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.absensi.test',
  appName: 'Absensi Test',
  webDir: 'public',
  server: {
    url: 'http://127.0.0.1:8000', // GANTI dengan IP komputer Anda
    androidScheme: 'https',
    cleartext: true,
    allowNavigation: ['*']
  },
  android: {
    allowMixedContent: true,
    backgroundColor: '#4CAF50',
    captureInput: true,
    loggingBehavior: 'debug'
  }
};

export default config;
