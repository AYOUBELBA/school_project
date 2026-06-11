import React, { useState, useEffect } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { View, ActivityIndicator } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

import LoginScreen from './src/screens/LoginScreen';
import DashboardScreen from './src/screens/DashboardScreen';
import TripsScreen from './src/screens/TripsScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import ReportIssueScreen from './src/screens/ReportIssueScreen';
import MaintenanceScreen from './src/screens/MaintenanceScreen';
import AddFuelScreen from './src/screens/AddFuelScreen';
import IncidentScreen from './src/screens/IncidentScreen';
import IntegratedMapScreen from './src/screens/IntegratedMapScreen';

// DO NOT import TripOptionsScreen here - it's a component used inside TripsScreen

const Stack = createNativeStackNavigator();

function LoadingScreen() {
  return (
    <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
      <ActivityIndicator size="large" color="#3758f9" />
    </View>
  );
}

export default function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkLogin();
  }, []);

  const checkLogin = async () => {
    try {
      const token = await AsyncStorage.getItem('userToken');
      setIsLoggedIn(!!token);
    } catch (error) {
      console.error('Check login error:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <LoadingScreen />;
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {!isLoggedIn ? (
          <Stack.Screen name="Login">
            {(props) => <LoginScreen {...props} onLogin={() => setIsLoggedIn(true)} />}
          </Stack.Screen>
        ) : (
          <>
            <Stack.Screen name="Dashboard" component={DashboardScreen} />
            <Stack.Screen name="Trips" component={TripsScreen} />
            <Stack.Screen name="Profile" component={ProfileScreen} />
            <Stack.Screen name="ReportIssue" component={ReportIssueScreen} />
            <Stack.Screen name="Maintenance" component={MaintenanceScreen} />
            <Stack.Screen name="AddFuel" component={AddFuelScreen} />
            <Stack.Screen name="Incident" component={IncidentScreen} />
            <Stack.Screen name="IntegratedMap" component={IntegratedMapScreen} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}