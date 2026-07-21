// src/screens/TripsScreen.js
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Linking,
  Platform,
  Alert,
} from 'react-native';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import TripOptionsScreen from './TripOptionsScreen';

const API_URL = 'http://192.168.100.116/SCHOOL_PROJECT';

export default function TripsScreen() {
  const navigation = useNavigation();
  const [currentTrip, setCurrentTrip] = useState(null);
  const [pastTrips, setPastTrips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedTrip, setSelectedTrip] = useState(null);
  const [modalVisible, setModalVisible] = useState(false);
  const [isInTruck, setIsInTruck] = useState(false);
  const [statusLoading, setStatusLoading] = useState(true);

  // Load trips on mount
  useEffect(() => {
    loadDriverStatus();
    loadTrips();
  }, []);

  // Refresh when screen comes into focus
  useFocusEffect(
    React.useCallback(() => {
      loadDriverStatus();
      return () => {};
    }, [])
  );

  const loadDriverStatus = async () => {
    try {
      setStatusLoading(true);
      
      // Step 1: Check AsyncStorage first (for immediate response)
      const cachedStatus = await AsyncStorage.getItem('isInTruck');
      console.log('📱 Cached status from AsyncStorage:', cachedStatus);
      
      if (cachedStatus !== null) {
        const status = cachedStatus === 'true';
        console.log('📱 Setting isInTruck from cache:', status);
        setIsInTruck(status);
      }

      // Step 2: Get driver ID
      const driverId = await AsyncStorage.getItem('driverId');
      console.log('📱 Driver ID from AsyncStorage:', driverId);
      
      if (!driverId) {
        console.log('❌ No driver ID found');
        setStatusLoading(false);
        return;
      }

      // Step 3: Verify with server
      console.log('📡 Fetching driver status from server...');
      const response = await fetch(`${API_URL}/api/get_driver_info.php?driver_id=${driverId}`);
      const data = await response.json();
      console.log('📡 Server response:', data);
      
      if (data.success && data.driver) {
        const serverStatus = data.driver.is_in_truck === true || data.driver.is_in_truck === 1 || data.driver.is_in_truck === 't';
        console.log('📡 Server status:', serverStatus);
        
        // Update state with server status
        setIsInTruck(serverStatus);
        
        // Update AsyncStorage cache to match server
        await AsyncStorage.setItem('isInTruck', serverStatus ? 'true' : 'false');
        console.log('💾 Updated AsyncStorage cache to:', serverStatus);
      } else {
        console.log('⚠️ Server returned no driver data, keeping cached status');
      }
    } catch (error) {
      console.error('❌ Error loading driver status:', error);
      // Keep cached status on error
    } finally {
      setStatusLoading(false);
    }
  };

  // Function to manually sync status from Dashboard
  const syncStatusFromDashboard = async () => {
    console.log('🔄 Manual sync triggered...');
    await loadDriverStatus();
  };

  const loadTrips = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const response = await fetch(`${API_URL}/api/get_driver_trips.php?driver_id=${driverId}`);
      const data = await response.json();
      if (data.success) {
        setCurrentTrip(data.current_trip);
        setPastTrips(data.past_trips || []);
      }
    } catch (error) {
      console.error('Error:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadDriverStatus();
    loadTrips();
  };

  const getStatusColor = (status) => {
    switch(status) {
      case 'ongoing': return '#10b981';
      case 'planned': return '#f59e0b';
      case 'completed': return '#6b7280';
      default: return '#6b7280';
    }
  };

  const openTripOptions = (trip) => {
    setSelectedTrip(trip);
    setModalVisible(true);
  };

  const viewTripOnMap = async (trip) => {
    // Safety check - must be in truck
    if (!isInTruck) {
      Alert.alert(
        '⚠️ Safety Check',
        'You must be INSIDE the truck to view trip navigation.\n\n' +
        'Please make sure you are in the truck before using navigation features.',
        [{ text: 'OK', style: 'default' }]
      );
      return;
    }

    try {
      setLoading(true);
      
      const driverId = await AsyncStorage.getItem('driverId');
      
      const response = await fetch(`${API_URL}/api/get_trip_coords.php?id=${trip.id}&driver_id=${driverId}`);
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
          tripId: trip.id,
          tripNumber: trip.trip_number
        });
        setModalVisible(false);
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

  const openGoogleMaps = async () => {
    // Safety check - must be in truck
    if (!isInTruck) {
      Alert.alert(
        '⚠️ Safety Check',
        'You must be INSIDE the truck to use navigation.\n\n' +
        'Please make sure you are in the truck before opening maps.',
        [{ text: 'OK', style: 'default' }]
      );
      return;
    }

    setModalVisible(false);
    if (!selectedTrip) return;

    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      let currentLat = 31.7917;
      let currentLng = -7.0926;
      
      if (status === 'granted') {
        const location = await Location.getCurrentPositionAsync({});
        currentLat = location.coords.latitude;
        currentLng = location.coords.longitude;
      }

      const url = Platform.select({
        ios: `maps://app?daddr=${encodeURIComponent(selectedTrip.end_location)}&saddr=${currentLat},${currentLng}`,
        android: `https://www.google.com/maps/dir/?api=1&origin=${currentLat},${currentLng}&destination=${encodeURIComponent(selectedTrip.end_location)}&travelmode=driving`
      });

      Linking.openURL(url).catch(() => {
        Alert.alert('Error', 'Could not open Google Maps');
      });
    } catch (error) {
      console.error('Error opening Google Maps:', error);
      Alert.alert('Error', 'Could not open Google Maps');
    }
  };

  if (loading || statusLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
      </View>
    );
  }

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>My Trips</Text>
        <TouchableOpacity onPress={syncStatusFromDashboard} style={styles.syncButton}>
          <Text style={styles.syncButtonText}>🔄</Text>
        </TouchableOpacity>
      </View>

      {/* Driver Status Indicator */}
      <TouchableOpacity 
        style={[styles.statusIndicator, isInTruck ? styles.inTruck : styles.outsideTruck]}
        onPress={syncStatusFromDashboard}
        activeOpacity={0.7}
      >
        <Text style={styles.statusIndicatorText}>
          {isInTruck ? '🚛 In Truck - Navigation Available' : '📱 Outside Truck - Navigation Locked'}
        </Text>
        {!isInTruck && (
          <Text style={styles.statusSubText}>
            Tap to refresh status • Toggle "In Truck" in Dashboard
          </Text>
        )}
        {isInTruck && (
          <Text style={styles.statusSubText}>
            ✅ Navigation enabled • Tap to refresh
          </Text>
        )}
      </TouchableOpacity>

      {currentTrip && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Current Trip</Text>
          <TouchableOpacity 
            style={styles.tripCard}
            onPress={() => openTripOptions(currentTrip)}
          >
            <Text style={styles.tripNumber}>{currentTrip.trip_number}</Text>
            <Text style={styles.routeText}>
              📍 {currentTrip.start_location || 'Start'} → {currentTrip.end_location || 'Destination'}
            </Text>
            <View style={styles.tripFooter}>
              <Text style={[styles.tripStatus, { color: getStatusColor(currentTrip.status) }]}>
                {currentTrip.status}
              </Text>
              <Text style={[
                styles.navigateText,
                !isInTruck && styles.navigateTextDisabled
              ]}>
                {!isInTruck ? '🔒 Get in Truck' : 'Choose Navigation →'}
              </Text>
            </View>
          </TouchableOpacity>
        </View>
      )}

      {pastTrips.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Past Trips</Text>
          {pastTrips.map((trip, index) => (
            <View key={index} style={styles.pastTripCard}>
              <Text style={styles.tripNumber}>{trip.trip_number}</Text>
              <Text style={styles.routeText}>
                📍 {trip.start_location || 'Start'} → {trip.end_location || 'Destination'}
              </Text>
              <Text style={[styles.tripStatus, { color: getStatusColor(trip.status) }]}>
                {trip.status}
              </Text>
            </View>
          ))}
        </View>
      )}

      {!currentTrip && pastTrips.length === 0 && (
        <View style={styles.emptyContainer}>
          <Text style={styles.emptyIcon}>📋</Text>
          <Text style={styles.emptyText}>No trips found</Text>
        </View>
      )}

      <TripOptionsScreen
        visible={modalVisible}
        onClose={() => setModalVisible(false)}
        onNavigateInApp={async () => {
          setModalVisible(false);
          if (selectedTrip) {
            if (!isInTruck) {
              Alert.alert(
                '⚠️ Safety Check',
                'You must be INSIDE the truck to start navigation.\n\n' +
                'Please make sure you are in the truck before using navigation features.',
                [{ text: 'OK', style: 'default' }]
              );
              return;
            }
            
            try {
              setLoading(true);
              const driverId = await AsyncStorage.getItem('driverId');
              
              // Fetch the coordinates for this trip
              const response = await fetch(`${API_URL}/api/get_trip_coords.php?id=${selectedTrip.id}&driver_id=${driverId}`);
              const data = await response.json();
              console.log('📡 Trip coordinates:', data);
              
              if (data.success && data.start_lat && data.end_lat) {
                navigation.navigate('IntegratedMap', {
                  origin: {
                    latitude: parseFloat(data.start_lat),
                    longitude: parseFloat(data.start_lng),
                    title: data.start_location || 'Start',
                    address: data.start_location || 'Start Location'
                  },
                  destination: {
                    latitude: parseFloat(data.end_lat),
                    longitude: parseFloat(data.end_lng),
                    title: data.end_location || 'Destination',
                    address: data.end_location || 'Destination Location'
                  },
                  tripId: selectedTrip.id,
                  tripNumber: selectedTrip.trip_number
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
          }
        }}
        onOpenGoogleMaps={openGoogleMaps}
        onViewTripOnMap={viewTripOnMap}
        trip={selectedTrip}
        isInTruck={isInTruck}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  centerContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { 
    backgroundColor: '#3758f9', 
    padding: 15, 
    paddingTop: 50, 
    flexDirection: 'row', 
    alignItems: 'center', 
    justifyContent: 'space-between' 
  },
  backButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#fff' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  syncButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  syncButtonText: { fontSize: 20 },
  section: { margin: 15 },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginBottom: 10 },
  tripCard: { backgroundColor: '#fff', padding: 15, borderRadius: 12, marginBottom: 10 },
  pastTripCard: { backgroundColor: '#fff', padding: 15, borderRadius: 12, marginBottom: 10, opacity: 0.8 },
  tripNumber: { fontSize: 16, fontWeight: 'bold', color: '#1f2937', marginBottom: 5 },
  routeText: { fontSize: 14, color: '#374151', marginBottom: 8 },
  tripFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  tripStatus: { fontSize: 12, fontWeight: '500' },
  navigateText: { fontSize: 12, color: '#3758f9', fontWeight: '500' },
  navigateTextDisabled: { color: '#9ca3af' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', padding: 60 },
  emptyIcon: { fontSize: 64, marginBottom: 20 },
  emptyText: { fontSize: 18, color: '#6b7280' },
  statusIndicator: {
    margin: 15,
    padding: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  inTruck: {
    backgroundColor: '#d1fae5',
    borderWidth: 1,
    borderColor: '#10b981',
  },
  outsideTruck: {
    backgroundColor: '#fef3c7',
    borderWidth: 1,
    borderColor: '#f59e0b',
  },
  statusIndicatorText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#1f2937',
  },
  statusSubText: {
    fontSize: 11,
    color: '#6b7280',
    marginTop: 4,
  },
});