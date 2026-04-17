import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.pandanteknik.paspapan',
  appName: 'PasPapan',
  webDir: 'public',
  server: {
    url: 'https://paspapan.pandanteknik.com',
    androidScheme: 'https',
    cleartext: false,
    allowNavigation: ['paspapan.pandanteknik.com']
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
