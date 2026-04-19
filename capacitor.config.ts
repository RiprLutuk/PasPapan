import { CapacitorConfig } from '@capacitor/cli';

const defaultServerUrl = 'https://paspapan.pandanteknik.com';
const serverUrl = process.env.CAP_SERVER_URL?.trim() || defaultServerUrl;
const serverOrigin = new URL(serverUrl);
const usesCleartext = serverOrigin.protocol === 'http:';

const config: CapacitorConfig = {
  appId: 'com.pandanteknik.paspapan',
  appName: 'PasPapan',
  webDir: 'public',
  server: {
    url: serverUrl,
    androidScheme: usesCleartext ? 'http' : 'https',
    cleartext: usesCleartext,
    allowNavigation: [serverOrigin.host]
  },
  android: {
    backgroundColor: '#00000000',
    captureInput: true
  },
  plugins: {
    Camera: {
      permissions: ['camera', 'photos']
    },
    Geolocation: {
      permissions: ['location']
    },
    SplashScreen: {
      launchShowDuration: 1500,
      launchAutoHide: true,
      backgroundColor: "#ffffff",
      androidSplashResourceName: "splash",
      showSpinner: false,
      androidScaleType: "CENTER_INSIDE",
      splashFullScreen: true,
      splashImmersive: true
    }
  }
};

export default config;
