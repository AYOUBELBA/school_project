import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Linking,
  Platform,
  Dimensions,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import * as Location from 'expo-location';

const { width, height } = Dimensions.get('window');

export default function NavigationScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const { tripId, startLocation, endLocation, tripNumber } = route.params || {};
  const [currentLocation, setCurrentLocation] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getCurrentLocation();
  }, []);

  const getCurrentLocation = async () => {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status === 'granted') {
      const location = await Location.getCurrentPositionAsync({});
      setCurrentLocation({
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      });
    }
    setLoading(false);
  };

  const openGoogleMaps = () => {
    if (!currentLocation) {
      Alert.alert('Error', 'Getting your location...');
      return;
    }

    const url = Platform.select({
      ios: `maps://app?daddr=${encodeURIComponent(endLocation)}&saddr=${currentLocation.latitude},${currentLocation.longitude}`,
      android: `https://www.google.com/maps/dir/?api=1&origin=${currentLocation.latitude},${currentLocation.longitude}&destination=${encodeURIComponent(endLocation)}&travelmode=driving`
    });

    Linking.openURL(url).catch(() => {
      Alert.alert('Error', 'Could not open maps');
    });
  };

  const openWaze = () => {
    const url = `https://waze.com/ul?q=${encodeURIComponent(endLocation)}&navigate=yes`;
    Linking.openURL(url).catch(() => {
      Alert.alert('Error', 'Waze not installed');
    });
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
        <Text style={styles.loadingText}>Getting your location...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Trip #{tripNumber}</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.infoCard}>
        <Text style={styles.destinationTitle}>Destination</Text>
        <Text style={styles.destination}>{endLocation}</Text>
        {currentLocation && (
          <Text style={styles.currentLocation}>
            📍 Your location: {currentLocation.latitude.toFixed(4)}, {currentLocation.longitude.toFixed(4)}
          </Text>
        )}
      </View>

      <View style={styles.mapPlaceholder}>
        <Text style={styles.mapIcon}>🗺️</Text>
        <Text style={styles.mapTitle}>Open Navigation App</Text>
        <Text style={styles.mapSubtext}>
          Tap below to open Google Maps or Waze for turn-by-turn directions
        </Text>
      </View>

      <View style={styles.buttonContainer}>
        <TouchableOpacity style={[styles.navButton, styles.googleButton]} onPress={openGoogleMaps}>
          <Text style={styles.navButtonText}>🗺️ Open Google Maps</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={[styles.navButton, styles.wazeButton]} onPress={openWaze}>
          <Text style={styles.navButtonText}>🧭 Open Waze</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.instructionsCard}>
        <Text style={styles.instructionsTitle}>📋 Instructions</Text>
        <Text style={styles.instructionsText}>
          1. Tap "Open Google Maps" to start navigation{'\n'}
          2. Follow the voice-guided directions{'\n'}
          3. Your location is still being tracked in the background{'\n'}
          4. Return to this app to update trip status
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  centerContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  loadingText: { marginTop: 10, color: '#6b7280' },
  header: {
    backgroundColor: '#3758f9',
    padding: 15,
    paddingTop: 50,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#fff' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  infoCard: {
    backgroundColor: '#fff',
    margin: 15,
    padding: 20,
    borderRadius: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    elevation: 2,
  },
  destinationTitle: { fontSize: 12, color: '#6b7280', marginBottom: 5 },
  destination: { fontSize: 18, fontWeight: 'bold', color: '#1f2937' },
  currentLocation: { fontSize: 12, color: '#6b7280', marginTop: 10 },
  mapPlaceholder: {
    backgroundColor: '#f3f4f6',
    margin: 15,
    padding: 40,
    borderRadius: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderStyle: 'dashed',
  },
  mapIcon: { fontSize: 48, marginBottom: 15 },
  mapTitle: { fontSize: 18, fontWeight: '600', color: '#1f2937', marginBottom: 8 },
  mapSubtext: { fontSize: 12, color: '#6b7280', textAlign: 'center' },
  buttonContainer: { padding: 15, gap: 12 },
  navButton: { padding: 16, borderRadius: 12, alignItems: 'center' },
  googleButton: { backgroundColor: '#4285F4' },
  wazeButton: { backgroundColor: '#33CCFF' },
  navButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  instructionsCard: {
    backgroundColor: '#fef3c7',
    margin: 15,
    padding: 15,
    borderRadius: 12,
  },
  instructionsTitle: { fontSize: 14, fontWeight: 'bold', color: '#92400e', marginBottom: 8 },
  instructionsText: { fontSize: 12, color: '#92400e', lineHeight: 20 },
});