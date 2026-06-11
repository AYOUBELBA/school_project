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
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import TripOptionsScreen from './TripOptionsScreen';

const API_URL = 'http://192.168.100.116/school_project';

export default function TripsScreen() {
  const navigation = useNavigation();
  const [currentTrip, setCurrentTrip] = useState(null);
  const [pastTrips, setPastTrips] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedTrip, setSelectedTrip] = useState(null);
  const [modalVisible, setModalVisible] = useState(false);

  useEffect(() => {
    loadTrips();
  }, []);

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

  const navigateInApp = () => {
    setModalVisible(false);
    if (selectedTrip) {
      navigation.navigate('IntegratedMap', {
        tripId: selectedTrip.id,
        endLocation: selectedTrip.end_location,
        tripNumber: selectedTrip.trip_number,
        startLocation: selectedTrip.start_location,
      });
    }
  };

  const openGoogleMaps = async () => {
    setModalVisible(false);
    if (!selectedTrip) return;

    // Get current location first
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
  };

  if (loading) {
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
        <View style={{ width: 40 }} />
      </View>

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
              <Text style={styles.navigateText}>Choose Navigation →</Text>
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
        onNavigateInApp={navigateInApp}
        onOpenGoogleMaps={openGoogleMaps}
        trip={selectedTrip}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  centerContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { backgroundColor: '#3758f9', padding: 15, paddingTop: 50, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  backButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#fff' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  section: { margin: 15 },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginBottom: 10 },
  tripCard: { backgroundColor: '#fff', padding: 15, borderRadius: 12, marginBottom: 10 },
  pastTripCard: { backgroundColor: '#fff', padding: 15, borderRadius: 12, marginBottom: 10, opacity: 0.8 },
  tripNumber: { fontSize: 16, fontWeight: 'bold', color: '#1f2937', marginBottom: 5 },
  routeText: { fontSize: 14, color: '#374151', marginBottom: 8 },
  tripFooter: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  tripStatus: { fontSize: 12, fontWeight: '500' },
  navigateText: { fontSize: 12, color: '#3758f9', fontWeight: '500' },
  emptyContainer: { alignItems: 'center', justifyContent: 'center', padding: 60 },
  emptyIcon: { fontSize: 64, marginBottom: 20 },
  emptyText: { fontSize: 18, color: '#6b7280' },
});