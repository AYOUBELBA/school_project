import { Platform } from 'react-native';

// Get your computer's IP address (run 'ipconfig' on Windows)
// Replace with your actual IP
const LOCAL_IP = '192.168.100.116';

const getBaseUrl = () => {
  if (__DEV__) {
    return `http://${LOCAL_IP}/SCHOOL_PROJECT`;
  }
  // Production URL
  return 'https://your-production-domain.com';
};

export const API_URL = getBaseUrl();
export default { API_URL };