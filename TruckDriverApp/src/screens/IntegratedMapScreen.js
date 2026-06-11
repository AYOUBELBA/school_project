import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Dimensions,
  Alert,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import MapView, { Marker, Polyline, PROVIDER_GOOGLE } from 'react-native-maps';
import * as Location from 'expo-location';

const { width, height } = Dimensions.get('window');

export default function IntegratedMapScreen() {
  const navigation = useNavigation();
  const route = useRoute();
  const { tripId, endLocation, tripNumber, startLocation } = route.params || {};
  const [currentLocation, setCurrentLocation] = useState(null);
  const [destination, setDestination] = useState(null);
  const [loading, setLoading] = useState(true);
  const [tracking, setTracking] = useState(true);
  const [speed, setSpeed] = useState(0);
  const [zoomLevel, setZoomLevel] = useState(0.05);
  const mapRef = useRef(null);
  const locationWatcher = useRef(null);

  useEffect(() => {
    startLocationTracking();
    setDestination({
      latitude: 31.7917,
      longitude: -7.0926,
    });
    
    return () => {
      if (locationWatcher.current) {
        locationWatcher.current.remove();
      }
    };
  }, []);

  const startLocationTracking = async () => {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission Denied', 'Location permission is required for navigation');
      setLoading(false);
      return;
    }

    const location = await Location.getCurrentPositionAsync({});
    setCurrentLocation({
      latitude: location.coords.latitude,
      longitude: location.coords.longitude,
    });
    setSpeed(location.coords.speed || 0);
    setLoading(false);

    locationWatcher.current = await Location.watchPositionAsync(
      {
        accuracy: Location.Accuracy.High,
        timeInterval: 2000,
        distanceInterval: 5,
      },
      (newLocation) => {
        const newSpeed = (newLocation.coords.speed || 0) * 3.6;
        setSpeed(newSpeed);
        setCurrentLocation({
          latitude: newLocation.coords.latitude,
          longitude: newLocation.coords.longitude,
        });
        
        if (tracking && mapRef.current) {
          mapRef.current.animateToRegion({
            latitude: newLocation.coords.latitude,
            longitude: newLocation.coords.longitude,
            latitudeDelta: 0.01,
            longitudeDelta: 0.01,
          }, 500);
        }
      }
    );
  };

  const centerOnUser = () => {
    setTracking(true);
    if (currentLocation && mapRef.current) {
      mapRef.current.animateToRegion({
        latitude: currentLocation.latitude,
        longitude: currentLocation.longitude,
        latitudeDelta: 0.01,
        longitudeDelta: 0.01,
      }, 1000);
    }
  };

  const toggleTracking = () => {
    setTracking(!tracking);
    if (!tracking && currentLocation) {
      centerOnUser();
    }
  };

  const zoomIn = () => {
    if (mapRef.current) {
      const newZoom = Math.max(0.001, zoomLevel / 1.5);
      setZoomLevel(newZoom);
      mapRef.current.animateToRegion({
        latitude: currentLocation?.latitude || 31.7917,
        longitude: currentLocation?.longitude || -7.0926,
        latitudeDelta: newZoom,
        longitudeDelta: newZoom,
      }, 500);
    }
  };

  const zoomOut = () => {
    if (mapRef.current) {
      const newZoom = Math.min(0.5, zoomLevel * 1.5);
      setZoomLevel(newZoom);
      mapRef.current.animateToRegion({
        latitude: currentLocation?.latitude || 31.7917,
        longitude: currentLocation?.longitude || -7.0926,
        latitudeDelta: newZoom,
        longitudeDelta: newZoom,
      }, 500);
    }
  };

  const fitRoute = () => {
    setTracking(false);
    if (currentLocation && destination && mapRef.current) {
      mapRef.current.fitToCoordinates(
        [
          { latitude: currentLocation.latitude, longitude: currentLocation.longitude },
          { latitude: destination.latitude, longitude: destination.longitude },
        ],
        {
          edgePadding: { top: 80, right: 80, bottom: 80, left: 80 },
          animated: true,
        }
      );
    }
  };

  // Custom Speedometer Component
  const Speedometer = ({ speed }) => {
    const maxSpeed = 120;
    const percentage = Math.min((speed / maxSpeed) * 100, 100);
    const getSpeedColor = () => {
      if (speed < 30) return '#10b981';
      if (speed < 70) return '#f59e0b';
      return '#ef4444';
    };

    return (
      <View style={styles.speedometerContainer}>
        <View style={styles.speedCircle}>
          <View style={styles.speedNeedleContainer}>
            <View style={[styles.speedNeedle, { transform: [{ rotate: `${(percentage * 1.8) - 90}deg` }] }]} />
          </View>
          <View style={styles.speedInnerCircle}>
            <Text style={[styles.speedValue, { color: getSpeedColor() }]}>
              {Math.round(speed)}
            </Text>
            <Text style={styles.speedUnit}>km/h</Text>
          </View>
        </View>
        <View style={styles.speedLabels}>
          <Text style={styles.speedLabel}>0</Text>
          <Text style={styles.speedLabel}>60</Text>
          <Text style={styles.speedLabel}>120</Text>
        </View>
      </View>
    );
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
        <TouchableOpacity onPress={fitRoute} style={styles.fitButton}>
          <Text style={styles.fitButtonText}>Fit Route</Text>
        </TouchableOpacity>
      </View>

      <MapView
        ref={mapRef}
        style={styles.map}
        provider={PROVIDER_GOOGLE}
        showsUserLocation={true}
        showsMyLocationButton={false}
        showsTraffic={true}
        showsBuildings={true}
        showsCompass={true}
        showsScale={true}
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
            description={`Speed: ${Math.round(speed)} km/h`}
            pinColor="#3758f9"
          />
        )}
        
        {destination && (
          <Marker
            coordinate={destination}
            title={endLocation}
            description="Destination"
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

      {/* Custom Speedometer */}
      <Speedometer speed={speed} />

      {/* Zoom Controls */}
      <View style={styles.zoomControls}>
        <TouchableOpacity style={styles.zoomButton} onPress={zoomIn}>
          <Text style={styles.zoomButtonText}>+</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.zoomButton} onPress={zoomOut}>
          <Text style={styles.zoomButtonText}>-</Text>
        </TouchableOpacity>
      </View>

      {/* Tracking Toggle Button */}
      <TouchableOpacity style={[styles.trackingButton, tracking && styles.trackingActive]} onPress={toggleTracking}>
        <Text style={styles.trackingButtonText}>
          {tracking ? '📍 Following' : '📍 Free'}
        </Text>
      </TouchableOpacity>

      {/* Center Button */}
      <TouchableOpacity style={styles.centerButton} onPress={centerOnUser}>
        <Text style={styles.centerButtonText}>🎯</Text>
      </TouchableOpacity>

      {/* Info Panel */}
      <View style={styles.infoPanel}>
        <View style={styles.infoRow}>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>Destination</Text>
            <Text style={styles.infoValue} numberOfLines={1}>{endLocation}</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>Mode</Text>
            <Text style={styles.infoValue}>{tracking ? 'Following' : 'Free'}</Text>
          </View>
        </View>
        
        <View style={styles.coordRow}>
          <Text style={styles.coordText}>
            📍 {currentLocation?.latitude.toFixed(6) || '--'}° | {currentLocation?.longitude.toFixed(6) || '--'}°
          </Text>
        </View>
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
  fitButton: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8 },
  fitButtonText: { color: '#fff', fontSize: 12, fontWeight: '500' },
  map: { width: width, height: height - 120 },
  
  // Custom Speedometer Styles
  speedometerContainer: {
    position: 'absolute',
    bottom: 180,
    left: 15,
    zIndex: 10,
    alignItems: 'center',
  },
  speedCircle: {
    width: 90,
    height: 90,
    borderRadius: 45,
    backgroundColor: 'rgba(0,0,0,0.8)',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 3,
    borderColor: '#3b82f6',
  },
  speedInnerCircle: {
    width: 70,
    height: 70,
    borderRadius: 35,
    backgroundColor: 'rgba(0,0,0,0.9)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  speedNeedleContainer: {
    position: 'absolute',
    width: '100%',
    height: '100%',
    justifyContent: 'center',
    alignItems: 'center',
  },
  speedNeedle: {
    position: 'absolute',
    width: 3,
    height: 35,
    backgroundColor: '#ef4444',
    borderRadius: 2,
    top: 10,
  },
  speedValue: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#10b981',
  },
  speedUnit: {
    fontSize: 9,
    color: '#94a3b8',
    marginTop: 2,
  },
  speedLabels: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: 90,
    marginTop: 5,
    paddingHorizontal: 8,
  },
  speedLabel: {
    fontSize: 9,
    color: '#94a3b8',
  },
  
  // Zoom Controls
  zoomControls: {
    position: 'absolute',
    bottom: 180,
    right: 15,
    zIndex: 10,
  },
  zoomButton: {
    backgroundColor: 'white',
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    elevation: 5,
  },
  zoomButtonText: { fontSize: 22, fontWeight: 'bold', color: '#374151' },
  
  // Tracking Button
  trackingButton: {
    position: 'absolute',
    bottom: 180,
    right: 70,
    zIndex: 10,
    backgroundColor: '#374151',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 25,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    elevation: 5,
  },
  trackingActive: {
    backgroundColor: '#3b82f6',
  },
  trackingButtonText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  
  // Center Button
  centerButton: {
    position: 'absolute',
    bottom: 180,
    left: 120,
    zIndex: 10,
    backgroundColor: 'white',
    width: 45,
    height: 45,
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    elevation: 5,
  },
  centerButtonText: { fontSize: 22 },
  
  // Info Panel
  infoPanel: {
    position: 'absolute',
    bottom: 20,
    left: 15,
    right: 15,
    backgroundColor: 'rgba(0,0,0,0.85)',
    borderRadius: 12,
    padding: 15,
  },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 12 },
  infoItem: { flex: 1 },
  infoLabel: { fontSize: 11, color: '#94a3b8', marginBottom: 4 },
  infoValue: { fontSize: 13, fontWeight: 'bold', color: '#fff' },
  coordRow: { borderTopWidth: 1, borderTopColor: 'rgba(255,255,255,0.1)', paddingTop: 8 },
  coordText: { fontSize: 10, color: '#6b7280', textAlign: 'center' },
});