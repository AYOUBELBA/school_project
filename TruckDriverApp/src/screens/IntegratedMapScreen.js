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

const IntegratedMapScreen = () => {
  const navigation = useNavigation();
  const route = useRoute();
  const mapRef = useRef(null);
  
  const [origin, setOrigin] = useState(null);
  const [destination, setDestination] = useState(null);
  const [routeCoordinates, setRouteCoordinates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState(null);
  const [speed, setSpeed] = useState(0);
  const [isTracking, setIsTracking] = useState(true);
  const [currentLocation, setCurrentLocation] = useState(null);
  const [tripInfo, setTripInfo] = useState({
    distance: 0,
    duration: 0,
    eta: ''
  });

  // Get destination from route params or use default
useEffect(() => {
  const params = route.params || {};
  
  // Check if coordinates were passed directly
  if (params.origin && params.destination) {
    setOrigin(params.origin);
    setDestination(params.destination);
    setLoading(false);
    
    // Fetch the route
    if (params.origin && params.destination) {
      fetchFreeRoute(params.origin, params.destination);
    }
  } else if (params.tripId) {
    // Fetch trip from API if only ID was passed
    fetchTripById(params.tripId);
  } else {
    // Default demo coordinates (remove this in production)
    setOrigin({
      latitude: 37.7749,
      longitude: -122.4194,
      title: 'San Francisco'
    });
    setDestination({
      latitude: 37.8044,
      longitude: -122.2712,
      title: 'Oakland'
    });
    setLoading(false);
  }
}, [route.params]);
const fetchTripById = async (tripId) => {
  try {
    // USE YOUR ACTUAL API URL HERE TOO
    const response = await fetch(`http://192.168.100.116/school_project/api/get_trip_coords.php?id=${tripId}`);
    const data = await response.json();
    // ... rest of the code
  } catch (error) {
    console.error('Error fetching trip:', error);
    setErrorMsg('Failed to load trip');
    setLoading(false);
  }
};
  // Get current location and setup location tracking
  useEffect(() => {
    getCurrentLocation();
    
    if (isTracking) {
      const interval = setInterval(() => {
        updateCurrentLocation();
      }, 5000);
      return () => clearInterval(interval);
    }
  }, [isTracking]);

  const getCurrentLocation = async () => {
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        setErrorMsg('Permission to access location was denied');
        setLoading(false);
        return;
      }

      const location = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High
      });
      
      const userLocation = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      };
      
      setCurrentLocation(userLocation);
      setOrigin(userLocation);
      setLoading(false);
      
      if (destination) {
        await fetchFreeRoute(userLocation, destination);
      }
    } catch (error) {
      console.error('Error getting location:', error);
      setErrorMsg('Error getting your location');
      setLoading(false);
    }
  };

  const updateCurrentLocation = async () => {
    if (!isTracking) return;
    
    try {
      const location = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High
      });
      
      const userLocation = {
        latitude: location.coords.latitude,
        longitude: location.coords.longitude,
      };
      
      setCurrentLocation(userLocation);
      setOrigin(userLocation);
      
      const speedKph = location.coords.speed ? location.coords.speed * 3.6 : 0;
      setSpeed(Math.round(speedKph));
      
      // Refresh route with new location
      if (destination) {
        await fetchFreeRoute(userLocation, destination);
      }
    } catch (error) {
      console.error('Error updating location:', error);
    }
  };

  // FREE OSRM API - no API key, no billing!
  const fetchFreeRoute = async (originCoord, destCoord) => {
    try {
      const url = `https://router.project-osrm.org/route/v1/driving/${originCoord.longitude},${originCoord.latitude};${destCoord.longitude},${destCoord.latitude}?overview=full&geometries=polyline`;
      
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.routes && data.routes[0]) {
        const points = decodePolyline(data.routes[0].geometry);
        setRouteCoordinates(points);
        
        // Calculate trip info
        const distanceKm = (data.routes[0].distance / 1000).toFixed(1);
        const durationMin = Math.round(data.routes[0].duration / 60);
        const eta = new Date(Date.now() + data.routes[0].duration * 1000);
        
        setTripInfo({
          distance: distanceKm,
          duration: durationMin,
          eta: eta.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
        
        if (mapRef.current && points.length > 0) {
          mapRef.current.fitToCoordinates(points, {
            edgePadding: { top: 80, right: 50, bottom: 200, left: 50 },
            animated: true,
          });
        }
      }
    } catch (error) {
      console.error('Error fetching route:', error);
      Alert.alert('Route Error', 'Could not find a route to destination');
    }
  };

  const decodePolyline = (encoded) => {
    let points = [];
    let index = 0, lat = 0, lng = 0;
    
    while (index < encoded.length) {
      let b, shift = 0, result = 0;
      do {
        b = encoded.charCodeAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      const dlat = ((result & 1) ? ~(result >> 1) : (result >> 1));
      lat += dlat;
      
      shift = 0;
      result = 0;
      do {
        b = encoded.charCodeAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      const dlng = ((result & 1) ? ~(result >> 1) : (result >> 1));
      lng += dlng;
      
      points.push({
        latitude: lat / 1e5,
        longitude: lng / 1e5,
      });
    }
    return points;
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

  const zoomIn = () => {
    if (mapRef.current) {
      mapRef.current.getCamera().then(camera => {
        camera.zoom += 1;
        mapRef.current.animateCamera(camera, { duration: 500 });
      });
    }
  };

  const zoomOut = () => {
    if (mapRef.current) {
      mapRef.current.getCamera().then(camera => {
        camera.zoom -= 1;
        mapRef.current.animateCamera(camera, { duration: 500 });
      });
    }
  };

  const toggleTracking = () => {
    setIsTracking(!isTracking);
    if (!isTracking) {
      centerOnUser();
    }
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
        <Text style={styles.loadingText}>Getting your location...</Text>
      </View>
    );
  }

  if (errorMsg) {
    return (
      <View style={styles.centerContainer}>
        <Text style={{ color: 'red', textAlign: 'center', padding: 20 }}>{errorMsg}</Text>
        <TouchableOpacity style={{ marginTop: 20, backgroundColor: '#3758f9', padding: 10, borderRadius: 8 }} onPress={getCurrentLocation}>
          <Text style={{ color: 'white' }}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Trip Navigation</Text>
        <TouchableOpacity onPress={() => mapRef.current?.fitToCoordinates(routeCoordinates, { edgePadding: { top: 50, right: 50, bottom: 50, left: 50 } })} style={styles.fitButton}>
          <Text style={styles.fitButtonText}>Fit Route</Text>
        </TouchableOpacity>
      </View>

      {/* Map */}
      <MapView
        ref={mapRef}
        style={styles.map}
        provider={PROVIDER_GOOGLE}
        showsUserLocation={true}
        showsMyLocationButton={false}
        followsUserLocation={isTracking}
        initialRegion={{
          latitude: origin?.latitude || 0,
          longitude: origin?.longitude || 0,
          latitudeDelta: 0.0922,
          longitudeDelta: 0.0421,
        }}
      >
        {origin && (
          <Marker
            coordinate={origin}
            title="Your Location"
            pinColor="#4CAF50"
          />
        )}
        
        {destination && (
          <Marker
            coordinate={destination}
            title={destination.title || 'Destination'}
            description={destination.address || ''}
            pinColor="#F44336"
          />
        )}
        
        {routeCoordinates.length > 0 && (
          <Polyline
            coordinates={routeCoordinates}
            strokeColor="#2196F3"
            strokeWidth={4}
          />
        )}
      </MapView>

      {/* Speedometer */}
      <View style={styles.speedometerContainer}>
        <View style={styles.speedCircle}>
          <View style={styles.speedInnerCircle}>
            <Text style={styles.speedValue}>{speed}</Text>
            <Text style={styles.speedUnit}>km/h</Text>
          </View>
        </View>
        <View style={styles.speedLabels}>
          <Text style={styles.speedLabel}>0</Text>
          <Text style={styles.speedLabel}>50</Text>
          <Text style={styles.speedLabel}>100</Text>
        </View>
      </View>

      {/* Zoom Controls */}
      <View style={styles.zoomControls}>
        <TouchableOpacity style={styles.zoomButton} onPress={zoomIn}>
          <Text style={styles.zoomButtonText}>+</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.zoomButton} onPress={zoomOut}>
          <Text style={styles.zoomButtonText}>-</Text>
        </TouchableOpacity>
      </View>

      {/* Tracking Button */}
      <TouchableOpacity 
        style={[styles.trackingButton, isTracking && styles.trackingActive]} 
        onPress={toggleTracking}
      >
        <Text style={styles.trackingButtonText}>
          {isTracking ? 'Tracking ON' : 'Tracking OFF'}
        </Text>
      </TouchableOpacity>

      {/* Center Button */}
      <TouchableOpacity style={styles.centerButton} onPress={centerOnUser}>
        <Text style={styles.centerButtonText}>📍</Text>
      </TouchableOpacity>

      {/* Info Panel */}
      <View style={styles.infoPanel}>
        <View style={styles.infoRow}>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>Distance</Text>
            <Text style={styles.infoValue}>{tripInfo.distance} km</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>Est. Time</Text>
            <Text style={styles.infoValue}>{tripInfo.duration} min</Text>
          </View>
          <View style={styles.infoItem}>
            <Text style={styles.infoLabel}>ETA</Text>
            <Text style={styles.infoValue}>{tripInfo.eta}</Text>
          </View>
        </View>
        <View style={styles.coordRow}>
          <Text style={styles.coordText}>
            {origin?.latitude?.toFixed(6) || '0'}°, {origin?.longitude?.toFixed(6) || '0'}°
          </Text>
        </View>
      </View>
    </View>
  );
};

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

export default IntegratedMapScreen;