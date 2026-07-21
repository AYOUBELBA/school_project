// src/screens/LoginScreen.js
import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL } from '../config/api';

const LoginScreen = ({ navigation, onLogin }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Clear any existing tokens on mount
  React.useEffect(() => {
    const clearStaleTokens = async () => {
      try {
        const loggedOut = await AsyncStorage.getItem('loggedOut');
        if (loggedOut === 'true') {
          await AsyncStorage.removeItem('userToken');
          await AsyncStorage.removeItem('userData');
          await AsyncStorage.removeItem('loggedOut');
        }
      } catch (error) {
        console.error('Error clearing tokens:', error);
      }
    };
    clearStaleTokens();
  }, []);

const handleLogin = async () => {
  if (!email || !password) {
    setError('Please fill in all fields');
    return;
  }

  setLoading(true);
  setError('');

  try {
    console.log('Attempting login to:', `${API_URL}/api/login.php`);
    
    const response = await fetch(`${API_URL}/api/login.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        email: email.trim(),
        password: password.trim(),
      }),
    });

    console.log('Response status:', response.status);

    const data = await response.json();
    console.log('Response data:', JSON.stringify(data, null, 2));

    if (data.success) {
      // Store token
      if (data.token) {
        await AsyncStorage.setItem('userToken', data.token);
        console.log('✅ Stored token');
      }
      
      // Store user data
      if (data.user) {
        await AsyncStorage.setItem('userData', JSON.stringify(data.user));
        console.log('✅ Stored userData:', JSON.stringify(data.user));
        
        // IMPORTANT: Store driverId if present
        if (data.user.driver_id) {
          const driverIdStr = String(data.user.driver_id);
          await AsyncStorage.setItem('driverId', driverIdStr);
          console.log('💾 Stored driverId from response:', driverIdStr);
        } else {
          console.warn('⚠️ No driver_id in response, but user role is:', data.user.role);
          
          // If role is driver but no driver_id, try to fetch it
          if (data.user.role === 'driver') {
            console.log('🔄 Role is driver, trying to fetch driver_id from get_driver_by_user...');
            try {
              const driverResponse = await fetch(`${API_URL}/api/get_driver_by_user.php?user_id=${data.user.id}`, {
                headers: {
                  'Accept': 'application/json',
                },
              });
              const driverData = await driverResponse.json();
              console.log('📡 Driver fetch response:', driverData);
              if (driverData.success && driverData.driver_id) {
                const driverIdStr = String(driverData.driver_id);
                await AsyncStorage.setItem('driverId', driverIdStr);
                console.log('💾 Stored driverId from fetch:', driverIdStr);
              }
            } catch (error) {
              console.error('❌ Failed to fetch driver_id:', error);
            }
          }
        }
        
        // Store truckId if present
        if (data.user.truck_id) {
          await AsyncStorage.setItem('truckId', String(data.user.truck_id));
          console.log('💾 Stored truckId:', data.user.truck_id);
        }
      }
      
      await AsyncStorage.removeItem('loggedOut');
      
      // Call onLogin to update app state
      if (onLogin) {
        onLogin();
      }
    } else {
      setError(data.message || 'Login failed');
      Alert.alert('Login Failed', data.message || 'Invalid email or password');
    }
  } catch (error) {
    console.error('Login error:', error);
    setError('Network error. Please check your connection.');
    Alert.alert(
      'Network Error', 
      'Could not connect to the server. Please check:\n\n' +
      '1. Your device is connected to the same network\n' +
      '2. The server is running (XAMPP/WAMP)\n' +
      '3. The IP address is correct: ' + API_URL + '\n\n' +
      'Error: ' + error.message
    );
  } finally {
    setLoading(false);
  }
};
  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      <ScrollView contentContainerStyle={styles.scrollContainer}>
        <View style={styles.loginContainer}>
          <Text style={styles.title}>Fleet Manager</Text>
          <Text style={styles.subtitle}>Driver Login</Text>

          {error ? <Text style={styles.errorText}>{error}</Text> : null}

          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Email or Username"
              placeholderTextColor="#999"
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              editable={!loading}
            />
          </View>

          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Password"
              placeholderTextColor="#999"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              editable={!loading}
            />
          </View>

          <TouchableOpacity
            style={[styles.loginButton, loading && styles.disabledButton]}
            onPress={handleLogin}
            disabled={loading}
          >
            {loading ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.loginButtonText}>Login</Text>
            )}
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  scrollContainer: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
  },
  loginContainer: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 5,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    color: '#3758f9',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    textAlign: 'center',
    marginBottom: 24,
  },
  inputContainer: {
    marginBottom: 16,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ddd',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    backgroundColor: '#fafafa',
  },
  loginButton: {
    backgroundColor: '#3758f9',
    borderRadius: 10,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 8,
  },
  disabledButton: {
    opacity: 0.7,
  },
  loginButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: '600',
  },
  errorText: {
    color: '#ff3b30',
    textAlign: 'center',
    marginBottom: 16,
    fontSize: 14,
  },
});

export default LoginScreen;