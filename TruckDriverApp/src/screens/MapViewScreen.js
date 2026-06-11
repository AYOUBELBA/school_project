import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Dimensions,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import * as Location from 'expo-location';

const { width, height } = Dimensions.get('window');

export default function MapViewScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const { endLocation, tripNumber } = route.params || {};
  const [currentLocation, setCurrentLocation] = useState(null);
  const [destination, setDestination] = useState(null);
  const [loading, setLoading] = useState(true);
  const mapRef = useRef(null);

  useEffect(() => {
    getCurrentLocation();
    geocodeDestination();
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
  };

  const geocodeDestination = async () => {
    try {
      const response = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(endLocation)}`
      );
      const data = await response.json();
      if (data.length > 0) {
        setDestination({
          latitude: parseFloat(data[0].lat),
          longitude: parseFloat(data[0].lon),
        });
      }
    } catch (error) {
      console.error('Geocoding error:', error);
    } finally {
      setLoading(false);
    }
  };

  const centerOnUser = () => {
    if (currentLocation && mapRef.current) {
      mapRef.current.animateToRegion({
        latitude: currentLocation.latitude,
        longitude: currentLocation.longitude,
        latitudeDelta: 0.01,
        longitudeDelta: 0.01,
      }, 1000);
    }
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
        <Text style={styles.loadingText}>Loading map...</Text>
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
        <TouchableOpacity onPress={centerOnUser} style={styles.centerButton}>
          <Text style={styles.centerButtonText}>📍</Text>
        </TouchableOpacity>
      </View>

      <MapView
        ref={mapRef}
        style={styles.map}
        provider={PROVIDER_GOOGLE}
        showsUserLocation={true}
        showsMyLocationButton={false}
        initialRegion={{
          latitude: currentLocation?.latitude || 31.7917,
          longitude: currentLocation?.longitude || -7.0926,
          latitudeDelta: 0.05,
          longitudeDelta: 0.05,
        }}
      >
        {currentLocation && (
          <Marker
            coordinate={currentLocation}
            title="You are here"
            pinColor="#3758f9"
          />
        )}
        {destination && (
          <Marker
            coordinate={destination}
            title={endLocation}
            pinColor="#ef4444"
          />
        )}
        {currentLocation && destination && (
          <Polyline
            coordinates={[currentLocation, destination]}
            strokeColor="#3b82f6"
            strokeWidth={4}
            lineDashPattern={[5, 10]}
          />
        )}
      </MapView>

      <View style={styles.infoPanel}>
        <Text style={styles.destinationText}>Destination: {endLocation}</Text>
        <Text style={styles.instructionText}>
          Follow the blue line on the map. Your GPS will update in real-time.
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
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
  centerButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 20 },
  centerButtonText: { fontSize: 20, color: '#fff' },
  map: { width: width, height: height - 120 },
  infoPanel: {
    position: 'absolute',
    bottom: 20,
    left: 15,
    right: 15,
    backgroundColor: 'rgba(0,0,0,0.85)',
    borderRadius: 12,
    padding: 15,
  },
  destinationText: { fontSize: 14, fontWeight: 'bold', color: '#fff', marginBottom: 8 },
  instructionText: { fontSize: 11, color: '#94a3b8', lineHeight: 16 },
});