import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  Switch,
  Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';

const API_URL = 'http://192.168.100.116/school_project';

export default function DashboardScreen() {
  const navigation = useNavigation();
  const [driver, setDriver] = useState(null);
  const [currentTrip, setCurrentTrip] = useState(null);
  const [isInTruck, setIsInTruck] = useState(false);
  const [locationStatus, setLocationStatus] = useState('Waiting for GPS...');
  const [loading, setLoading] = useState(true);
  const [locationWatcher, setLocationWatcher] = useState(null);

  useEffect(() => {
    loadDriverInfo();
    loadCurrentTrip();
    startTracking();
    return () => {
      if (locationWatcher) locationWatcher.remove();
    };
  }, []);

  const loadDriverInfo = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const response = await fetch(`${API_URL}/api/get_driver_info.php?driver_id=${driverId}`);
      const data = await response.json();
      if (data.success) {
        setDriver(data.driver);
        setIsInTruck(data.driver.is_in_truck || false);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  const loadCurrentTrip = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const response = await fetch(`${API_URL}/api/get_current_trip.php?driver_id=${driverId}`);
      const data = await response.json();
      if (data.success && data.current_trip) {
        setCurrentTrip(data.current_trip);
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
    }
  };

  const startTracking = async () => {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') {
      setLocationStatus('❌ Location permission denied');
      return;
    }

    setLocationStatus('📍 Tracking active...');

    const watcher = await Location.watchPositionAsync(
      { accuracy: Location.Accuracy.High, timeInterval: 3000, distanceInterval: 5 },
      async (newLocation) => {
        const { latitude, longitude, speed } = newLocation.coords;
        const speedKmh = (speed || 0) * 3.6;
        setLocationStatus(`📍 Tracking | Speed: ${speedKmh.toFixed(1)} km/h`);

        const truckId = await AsyncStorage.getItem('truckId');
        const driverId = await AsyncStorage.getItem('driverId');
        
        if (truckId && truckId !== '0') {
          fetch(`${API_URL}/api/update_location.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              truck_id: parseInt(truckId),
              driver_id: parseInt(driverId || '0'),
              latitude,
              longitude,
              speed: speedKmh,
              is_in_truck: isInTruck,
              accuracy: 10
            })
          }).catch(err => console.error('Send error:', err));
        }
      }
    );
    setLocationWatcher(watcher);
  };

  const updateDriverStatus = async (inTruck) => {
    setIsInTruck(inTruck);
    const driverId = await AsyncStorage.getItem('driverId');
    
    fetch(`${API_URL}/api/update_driver_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        driver_id: parseInt(driverId || '0'),
        is_in_truck: inTruck,
        location_source: inTruck ? 'truck_gps' : 'phone_gps'
      })
    }).catch(err => console.error('Status update error:', err));
  };

  // FIXED: Fetch coordinates before navigating
  const startTripNavigation = async () => {
    if (!currentTrip) {
      Alert.alert('No Trip', 'No active trip to navigate');
      return;
    }
    
    try {
      setLoading(true);
      const driverId = await AsyncStorage.getItem('driverId');
      
      
      const response = await fetch(`${API_URL}/api/get_trip_coords.php?id=${currentTrip.id}&driver_id=${driverId}`);
      const data = await response.json();

      
      if (data.success && data.start_lat && data.end_lat) {
        navigation.navigate('IntegratedMap', {
          origin: {
            latitude: parseFloat(data.start_lat),
            longitude: parseFloat(data.start_lng),
            title: data.start_location,
            address: data.start_location
          },
          destination: {
            latitude: parseFloat(data.end_lat),
            longitude: parseFloat(data.end_lng),
            title: data.end_location,
            address: data.end_location
          },
          tripId: currentTrip.id,
          tripNumber: currentTrip.trip_number
        });
      } else {
        Alert.alert('Error', data.message || 'Coordinates not available for this trip');
      }
    } catch (error) {
      console.error('Error fetching trip coordinates:', error);
      Alert.alert('Error', 'Failed to load trip map. Check your network connection.');
    } finally {
      setLoading(false);
    }
  };

  const updateTripStatus = async (status) => {
    const driverId = await AsyncStorage.getItem('driverId');
    const response = await fetch(`${API_URL}/api/update_trip_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        trip_id: currentTrip?.id,
        driver_id: parseInt(driverId || '0'),
        status: status
      })
    });
    const data = await response.json();
    if (data.success) {
      Alert.alert('Success', `Trip marked as ${status}`);
      loadCurrentTrip();
    }
  };

  const handleLogout = async () => {
    Alert.alert('Logout', 'Are you sure?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Logout',
        style: 'destructive',
        onPress: async () => {
          if (locationWatcher) locationWatcher.remove();
          await AsyncStorage.clear();
          navigation.replace('Login');
        }
      }
    ]);
  };

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <View>
          <Text style={styles.welcomeText}>Welcome back,</Text>
          <Text style={styles.driverName}>{driver?.full_name || 'Driver'}</Text>
        </View>
        <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.statusCard}>
        <View style={styles.statusRow}>
          <View>
            <Text style={styles.statusLabel}>Driver Status</Text>
            <Text style={styles.statusValue}>
              {isInTruck ? '🚛 In Truck' : '📱 Outside Truck'}
            </Text>
          </View>
          <Switch
            value={isInTruck}
            onValueChange={updateDriverStatus}
            trackColor={{ false: '#767577', true: '#10b981' }}
            thumbColor={isInTruck ? '#fff' : '#f4f3f4'}
          />
        </View>
        <Text style={styles.statusNote}>
          {isInTruck ? 'GPS data from truck device' : 'GPS data from your phone'}
        </Text>
      </View>

      <View style={[styles.gpsCard, { backgroundColor: locationStatus.includes('❌') ? '#ef4444' : '#10b981' }]}>
        <Text style={styles.statusText}>{locationStatus}</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Current Trip</Text>
        {currentTrip ? (
          <>
            <Text style={styles.tripNumber}>Trip #{currentTrip.trip_number}</Text>
            <View style={styles.routeContainer}>
              <Text style={styles.routeText}>
                📍 {currentTrip.start_location || 'Start'} → {currentTrip.end_location || 'Destination'}
              </Text>
            </View>
            <View style={styles.buttonGroup}>
              <TouchableOpacity style={styles.navButton} onPress={startTripNavigation}>
                <Text style={styles.navButtonText}>🗺️ Start Navigation</Text>
              </TouchableOpacity>
              {currentTrip.status === 'planned' && (
                <TouchableOpacity style={styles.startButton} onPress={() => updateTripStatus('ongoing')}>
                  <Text style={styles.startButtonText}>▶ Start Trip</Text>
                </TouchableOpacity>
              )}
              {currentTrip.status === 'ongoing' && (
                <TouchableOpacity style={styles.completeButton} onPress={() => updateTripStatus('completed')}>
                  <Text style={styles.completeButtonText}>✅ Complete Trip</Text>
                </TouchableOpacity>
              )}
            </View>
          </>
        ) : (
          <Text style={styles.noTripText}>No active trip assigned</Text>
        )}
      </View>

      <Text style={styles.sectionTitle}>Quick Actions</Text>
      <View style={styles.actionGrid}>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('Trips')}>
          <Text style={styles.actionIcon}>📋</Text>
          <Text style={styles.actionText}>My Trips</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('ReportIssue')}>
          <Text style={styles.actionIcon}>📢</Text>
          <Text style={styles.actionText}>Report Issue</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('Maintenance')}>
          <Text style={styles.actionIcon}>🔧</Text>
          <Text style={styles.actionText}>Maintenance</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('AddFuel')}>
          <Text style={styles.actionIcon}>⛽</Text>
          <Text style={styles.actionText}>Add Fuel</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('Incident')}>
          <Text style={styles.actionIcon}>⚠️</Text>
          <Text style={styles.actionText}>Incident</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.actionCard} onPress={() => navigation.navigate('Profile')}>
          <Text style={styles.actionIcon}>👤</Text>
          <Text style={styles.actionText}>Profile</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Your Truck</Text>
        <Text style={styles.truckPlate}>{driver?.truck_plate || 'Not assigned'}</Text>
        <Text style={styles.truckModel}>{driver?.truck_model || ''}</Text>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  centerContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { backgroundColor: '#3758f9', padding: 20, paddingTop: 50, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  welcomeText: { color: '#c7d2fe', fontSize: 14 },
  driverName: { color: '#fff', fontSize: 24, fontWeight: 'bold' },
  logoutButton: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 15, paddingVertical: 8, borderRadius: 8 },
  logoutText: { color: '#fff', fontSize: 14 },
  statusCard: { backgroundColor: '#fff', margin: 15, padding: 15, borderRadius: 12 },
  statusRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  statusLabel: { fontSize: 14, color: '#6b7280' },
  statusValue: { fontSize: 16, fontWeight: '600', marginTop: 4 },
  statusNote: { fontSize: 11, color: '#9ca3af', marginTop: 10 },
  gpsCard: { margin: 15, padding: 15, borderRadius: 12 },
  statusText: { color: '#fff', fontSize: 14, textAlign: 'center', fontWeight: '500' },
  card: { backgroundColor: '#fff', margin: 15, padding: 15, borderRadius: 12 },
  cardTitle: { fontSize: 18, fontWeight: '600', color: '#1f2937', marginBottom: 12 },
  tripNumber: { fontSize: 16, fontWeight: '500', color: '#3758f9', marginBottom: 8 },
  routeContainer: { backgroundColor: '#f3f4f6', padding: 10, borderRadius: 8, marginBottom: 10 },
  routeText: { fontSize: 14, color: '#374151' },
  buttonGroup: { gap: 10 },
  navButton: { backgroundColor: '#10b981', padding: 12, borderRadius: 8, alignItems: 'center' },
  navButtonText: { color: '#fff', fontWeight: '600' },
  startButton: { backgroundColor: '#3758f9', padding: 12, borderRadius: 8, alignItems: 'center' },
  startButtonText: { color: '#fff', fontWeight: '600' },
  completeButton: { backgroundColor: '#10b981', padding: 12, borderRadius: 8, alignItems: 'center' },
  completeButtonText: { color: '#fff', fontWeight: '600' },
  noTripText: { color: '#9ca3af', textAlign: 'center', padding: 20 },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginHorizontal: 15, marginTop: 10, marginBottom: 10 },
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 10 },
  actionCard: { width: '30%', backgroundColor: '#fff', margin: '1.5%', padding: 12, borderRadius: 12, alignItems: 'center' },
  actionIcon: { fontSize: 24, marginBottom: 6 },
  actionText: { fontSize: 11, color: '#374151', textAlign: 'center' },
  truckPlate: { fontSize: 20, fontWeight: 'bold', color: '#1f2937' },
  truckModel: { color: '#6b7280', marginTop: 4 },
});