// src/screens/DashboardScreen.js
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
  RefreshControl,
  TextInput,
  Modal,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { API_URL } from '../config/api';
import DocumentUpload from '../components/DocumentUpload';

export default function DashboardScreen({ onLogout }) {
  const navigation = useNavigation();
  const [driver, setDriver] = useState(null);
  const [currentTrip, setCurrentTrip] = useState(null);
  const [isInTruck, setIsInTruck] = useState(false);
  const [locationStatus, setLocationStatus] = useState('Waiting for GPS...');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [locationWatcher, setLocationWatcher] = useState(null);
  const [driverStats, setDriverStats] = useState({
    total_trips: 0,
    completed_trips: 0,
    ongoing_trips: 0,
  });
  const [showCompleteModal, setShowCompleteModal] = useState(false);
  const [tripCompleted, setTripCompleted] = useState(false);
  const [receiverName, setReceiverName] = useState('');
  const [deliveryNotes, setDeliveryNotes] = useState('');
  const [documentUploaded, setDocumentUploaded] = useState(false);

  useEffect(() => {
    loadDriverInfo();
    loadCurrentTrip();
    loadDriverStats();
    startTracking();
    return () => {
      if (locationWatcher) locationWatcher.remove();
    };
  }, []);

  const loadDriverInfo = async () => {
    try {
      // Check if driverId exists, if not try to get it from userData
      let driverId = await AsyncStorage.getItem('driverId');
      console.log('📱 Driver ID from AsyncStorage:', driverId);
      
      if (!driverId || driverId === 'null' || driverId === 'undefined') {
        console.log('⚠️ Driver ID not found, trying to get from userData...');
        
        const userDataStr = await AsyncStorage.getItem('userData');
        console.log('📱 userData string:', userDataStr);
        
        if (userDataStr) {
          try {
            const userData = JSON.parse(userDataStr);
            console.log('📱 userData parsed:', userData);
            
            if (userData.driver_id) {
              driverId = String(userData.driver_id);
              await AsyncStorage.setItem('driverId', driverId);
              console.log('💾 Retrieved driverId from userData:', driverId);
            } else {
              console.error('❌ userData.driver_id not found');
              console.log('🔍 Available keys in userData:', Object.keys(userData));
            }
          } catch (parseError) {
            console.error('❌ Failed to parse userData:', parseError);
          }
        }
      }
      
      if (!driverId || driverId === 'null') {
        console.error('❌ No driverId found');
        setLoading(false);
        return;
      }
      
      const response = await fetch(`${API_URL}/api/get_driver_info.php?driver_id=${driverId}`);
      const data = await response.json();
      console.log('📡 Driver info response:', data);
      
      if (data.success) {
        setDriver(data.driver);
        setIsInTruck(data.driver.is_in_truck || false);
        await AsyncStorage.setItem('isInTruck', data.driver.is_in_truck ? 'true' : 'false');
      } else {
        console.error('❌ Failed to load driver info:', data.message);
      }
    } catch (error) {
      console.error('Error loading driver:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadDriverStats = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      if (!driverId) {
        console.log('No driver ID found, skipping stats');
        return;
      }
      
      console.log('📡 Fetching driver stats for ID:', driverId);
      
      const response = await fetch(`${API_URL}/api/get_driver_stats.php?driver_id=${driverId}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });
      
      const text = await response.text();
      console.log('📡 Raw stats response:', text);
      
      try {
        const data = JSON.parse(text);
        if (data.success) {
          setDriverStats({
            total_trips: data.total_trips || 0,
            completed_trips: data.completed_trips || 0,
            ongoing_trips: data.ongoing_trips || 0,
          });
          console.log('✅ Driver stats loaded:', data);
        } else {
          console.error('❌ API Error:', data.message);
          setDriverStats({
            total_trips: 0,
            completed_trips: 0,
            ongoing_trips: 0,
          });
        }
      } catch (parseError) {
        console.error('❌ JSON Parse Error:', parseError);
        if (text.includes('<!DOCTYPE') || text.includes('<html')) {
          console.error('Server returned HTML instead of JSON - likely a PHP error');
        }
        setDriverStats({
          total_trips: 0,
          completed_trips: 0,
          ongoing_trips: 0,
        });
      }
    } catch (error) {
      console.error('❌ Network error loading stats:', error);
      setDriverStats({
        total_trips: 0,
        completed_trips: 0,
        ongoing_trips: 0,
      });
    }
  };

  const loadCurrentTrip = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      if (!driverId) {
        setLoading(false);
        return;
      }
      
      const response = await fetch(`${API_URL}/api/get_current_trip.php?driver_id=${driverId}`);
      const data = await response.json();
      console.log('📡 Current trip response:', data);
      if (data.success && data.current_trip) {
        setCurrentTrip(data.current_trip);
      } else {
        setCurrentTrip(null);
      }
    } catch (error) {
      console.error('Error loading trip:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadDriverInfo();
    await loadCurrentTrip();
    await loadDriverStats();
    setRefreshing(false);
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

const handleCompleteTrip = async () => {
  if (!receiverName) {
    Alert.alert('Error', 'Please enter the receiver name');
    return;
  }

  if (!documentUploaded) {
    Alert.alert('Error', 'Please upload a delivery document');
    return;
  }

  setLoading(true);
  try {
    const driverId = await AsyncStorage.getItem('driverId');
    const response = await fetch(`${API_URL}/api/complete_trip.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        trip_id: currentTrip?.id,
        driver_id: parseInt(driverId),
        receiver_name: receiverName,
        delivery_notes: deliveryNotes,
      }),
    });

    const data = await response.json();
    console.log('📡 Complete trip response:', data);
    
    if (data.success) {
      // Close modal and refresh
      handleFinish();
      
      // Show single success message
      Alert.alert(
        '✅ Success',
        'Trip completed successfully!',
        [{ text: 'OK' }]
      );
    } else {
      Alert.alert('Error', data.message || 'Failed to complete trip');
    }
  } catch (error) {
    console.error('Complete trip error:', error);
    Alert.alert('Error', 'Network error');
  } finally {
    setLoading(false);
  }
};

const handleDocumentUploadComplete = (file) => {
  setDocumentUploaded(true);
  // Don't show alert here - let the user see the green checkmark
  console.log('✅ Document uploaded:', file);
};

const handleFinish = () => {
  setShowCompleteModal(false);
  setTripCompleted(false);
  setDocumentUploaded(false);
  setReceiverName('');
  setDeliveryNotes('');
  
  // Force refresh the current trip
  loadCurrentTrip();
  loadDriverStats();
  loadDriverInfo();
};


  const updateDriverStatus = async (inTruck) => {
    const driverId = await AsyncStorage.getItem('driverId');
    console.log('📱 Current driverId from AsyncStorage:', driverId);
    
    if (!driverId || driverId === 'null' || driverId === 'undefined' || driverId === '0') {
      console.error('❌ Invalid driver ID:', driverId);
      Alert.alert('Error', 'Driver ID not found. Please login again.');
      return;
    }

    setIsInTruck(inTruck);
    await AsyncStorage.setItem('isInTruck', inTruck ? 'true' : 'false');
    console.log('💾 Saved isInTruck to AsyncStorage:', inTruck);
    
    try {
      console.log('📡 Sending update to server:', {
        driver_id: parseInt(driverId),
        is_in_truck: inTruck,
        location_source: inTruck ? 'truck_gps' : 'phone_gps'
      });
      
      const response = await fetch(`${API_URL}/api/update_driver_status.php`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          driver_id: parseInt(driverId),
          is_in_truck: inTruck,
          location_source: inTruck ? 'truck_gps' : 'phone_gps'
        })
      });
      
      const text = await response.text();
      console.log('📡 Raw server response:', text);
      
      try {
        const data = JSON.parse(text);
        if (data.success) {
          console.log('✅ Status updated successfully');
        } else {
          console.log('❌ Server error:', data.message);
          setIsInTruck(!inTruck);
          await AsyncStorage.setItem('isInTruck', (!inTruck) ? 'true' : 'false');
          Alert.alert('Error', 'Failed to update status: ' + data.message);
        }
      } catch (parseError) {
        console.error('❌ JSON Parse Error:', parseError);
        setIsInTruck(!inTruck);
        await AsyncStorage.setItem('isInTruck', (!inTruck) ? 'true' : 'false');
        Alert.alert('Error', 'Failed to update status. Please try again.');
      }
    } catch (error) {
      console.error('❌ Network error:', error);
      setIsInTruck(!inTruck);
      await AsyncStorage.setItem('isInTruck', (!inTruck) ? 'true' : 'false');
      Alert.alert('Network Error', 'Could not connect to server.');
    }
  };

  const startTripNavigation = async () => {
    if (!isInTruck) {
      Alert.alert(
        '⚠️ Safety Check',
        'You must be INSIDE the truck to start navigation.\n\n' +
        'Please toggle the "In Truck" switch to ON before navigating.',
        [{ text: 'OK', style: 'default' }]
      );
      return;
    }

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
      loadDriverStats();
    }
  };

  const handleLogout = async () => {
    Alert.alert('Logout', 'Are you sure?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Logout',
        style: 'destructive',
        onPress: async () => {
          try {
            if (locationWatcher) {
              locationWatcher.remove();
            }
            
            await AsyncStorage.clear();
            
            if (onLogout) {
              onLogout();
            }
          } catch (error) {
            console.error('Logout error:', error);
            Alert.alert('Error', 'Failed to logout properly');
          }
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
    <>
      <ScrollView 
        style={styles.container}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        <View style={styles.header}>
          <View>
            <Text style={styles.welcomeText}>Welcome back,</Text>
            <Text style={styles.driverName}>{driver?.full_name || 'Driver'}</Text>
          </View>
          <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
            <Text style={styles.logoutText}>Logout</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.statsContainer}>
          <View style={styles.statCard}>
            <Text style={styles.statNumber}>{driverStats.total_trips}</Text>
            <Text style={styles.statLabel}>Total Trips</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statNumber}>{driverStats.completed_trips}</Text>
            <Text style={styles.statLabel}>Completed</Text>
          </View>
          <View style={styles.statCard}>
            <Text style={styles.statNumber}>{driverStats.ongoing_trips}</Text>
            <Text style={styles.statLabel}>Ongoing</Text>
          </View>
        </View>

        <View style={[styles.statusCard, isInTruck ? styles.inTruckCard : styles.outsideCard]}>
          <View style={styles.statusRow}>
            <View>
              <Text style={styles.statusLabel}>Driver Status</Text>
              <Text style={[styles.statusValue, isInTruck ? styles.inTruckText : styles.outsideText]}>
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
            {isInTruck 
              ? '✅ GPS data from truck device - Navigation enabled' 
              : '⚠️ GPS data from your phone - Switch to "In Truck" to navigate'}
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
                <TouchableOpacity 
                  style={[
                    styles.navButton,
                    !isInTruck && styles.navButtonDisabled
                  ]} 
                  onPress={startTripNavigation}
                  disabled={!isInTruck}
                >
                  <Text style={styles.navButtonText}>
                    {!isInTruck ? '🔒 Get in Truck to Navigate' : '🗺️ Start Navigation'}
                  </Text>
                </TouchableOpacity>
                
                {currentTrip.status === 'planned' && (
                  <TouchableOpacity style={styles.startButton} onPress={() => updateTripStatus('ongoing')}>
                    <Text style={styles.startButtonText}>▶ Start Trip</Text>
                  </TouchableOpacity>
                )}
                {currentTrip.status === 'ongoing' && (
                  <TouchableOpacity 
                    style={styles.completeButton} 
                    onPress={() => setShowCompleteModal(true)}
                  >
                    <Text style={styles.completeButtonText}>✅ Complete Trip</Text>
                  </TouchableOpacity>
                )}
              </View>
              
              {!isInTruck && (
                <View style={styles.warningContainer}>
                  <Text style={styles.warningText}>
                    ⚠️ Please toggle "In Truck" switch to enable navigation
                  </Text>
                </View>
              )}
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
          <Text style={styles.truckStatus}>
            Status: {driver?.status || 'Unknown'}
          </Text>
        </View>
      </ScrollView>

<Modal
  visible={showCompleteModal}
  transparent={true}
  animationType="slide"
  onRequestClose={() => {
    Alert.alert('Cancel', 'Cancel completing trip?', [
      { text: 'Stay', style: 'default' },
      { text: 'Cancel', style: 'destructive', onPress: () => {
        setShowCompleteModal(false);
        setTripCompleted(false);
        setReceiverName('');
        setDeliveryNotes('');
        setDocumentUploaded(false);
      }}
    ]);
  }}
>
  <View style={styles.modalOverlay}>
    <View style={styles.modalContent}>
      <View style={styles.modalHeader}>
        <Text style={styles.modalTitle}>Complete Trip</Text>
        <TouchableOpacity 
          onPress={() => {
            Alert.alert('Cancel', 'Cancel completing trip?', [
              { text: 'Stay', style: 'default' },
              { text: 'Cancel', style: 'destructive', onPress: () => {
                setShowCompleteModal(false);
                setTripCompleted(false);
                setReceiverName('');
                setDeliveryNotes('');
                setDocumentUploaded(false);
              }}
            ]);
          }}
        >
          <Text style={styles.modalClose}>✕</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.modalBody}>
        <Text style={styles.modalSubtext}>
          Trip #{currentTrip?.trip_number}
        </Text>
        
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Receiver Name *</Text>
          <TextInput
            style={styles.input}
            placeholder="Enter receiver name"
            value={receiverName}
            onChangeText={setReceiverName}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Delivery Notes</Text>
          <TextInput
            style={[styles.input, styles.textArea]}
            placeholder="Any delivery notes..."
            value={deliveryNotes}
            onChangeText={setDeliveryNotes}
            multiline
            numberOfLines={3}
          />
        </View>

        <View style={styles.uploadSection}>
          <Text style={styles.uploadLabel}>Delivery Document *</Text>
          <Text style={styles.uploadSubtext}>
            Please upload proof of delivery (photo of signature, delivery confirmation, etc.)
          </Text>
          <DocumentUpload
            entityType="trip"
            entityId={currentTrip?.id}
            documentType="delivery"
            buttonText={documentUploaded ? '✅ Document Uploaded' : '📄 Upload Delivery Document'}
            onUploadComplete={handleDocumentUploadComplete}
            onUploadError={(error) => {
              console.error('Upload error:', error);
            }}
          />
          {documentUploaded && (
            <View style={styles.uploadSuccessContainer}>
              <Text style={styles.uploadSuccessText}>✅ Document uploaded successfully</Text>
            </View>
          )}
        </View>
      </View>

      <TouchableOpacity
        style={[
          styles.completeButton,
          (!receiverName || !documentUploaded) && styles.completeButtonDisabled
        ]}
        onPress={handleCompleteTrip}
        disabled={loading || !receiverName || !documentUploaded}
      >
        <Text style={styles.completeButtonText}>
          {loading ? 'Processing...' : '✅ Complete Trip'}
        </Text>
      </TouchableOpacity>
      
      {(!receiverName || !documentUploaded) && (
        <Text style={styles.requiredText}>
          ⚠️ Please fill in receiver name and upload a document to complete
        </Text>
      )}
    </View>
  </View>
</Modal>
    </>
  );
}

const styles = StyleSheet.create({
  uploadSuccessContainer: {
  marginTop: 8,
  padding: 8,
  backgroundColor: '#d1fae5',
  borderRadius: 8,
  alignItems: 'center',
},
uploadSuccessText: {
  color: '#065f46',
  fontSize: 14,
  fontWeight: '500',
},
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  centerContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { backgroundColor: '#3758f9', padding: 20, paddingTop: 50, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  welcomeText: { color: '#c7d2fe', fontSize: 14 },
  driverName: { color: '#fff', fontSize: 24, fontWeight: 'bold' },
  logoutButton: { backgroundColor: 'rgba(255,255,255,0.2)', paddingHorizontal: 15, paddingVertical: 8, borderRadius: 8 },
  logoutText: { color: '#fff', fontSize: 14 },
  statsContainer: { flexDirection: 'row', padding: 10, marginTop: -20 },
  statCard: { flex: 1, backgroundColor: '#fff', margin: 5, padding: 12, borderRadius: 12, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.1, shadowRadius: 4, elevation: 3 },
  statNumber: { fontSize: 22, fontWeight: 'bold', color: '#3758f9' },
  statLabel: { fontSize: 12, color: '#6b7280', marginTop: 4 },
  statusCard: { backgroundColor: '#fff', margin: 15, padding: 15, borderRadius: 12, borderWidth: 2, borderColor: '#e5e7eb' },
  inTruckCard: { borderColor: '#10b981', backgroundColor: '#f0fdf4' },
  outsideCard: { borderColor: '#f59e0b', backgroundColor: '#fffbeb' },
  statusRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  statusLabel: { fontSize: 14, color: '#6b7280' },
  statusValue: { fontSize: 16, fontWeight: '600', marginTop: 4 },
  inTruckText: { color: '#10b981' },
  outsideText: { color: '#f59e0b' },
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
  navButtonDisabled: { backgroundColor: '#9ca3af', opacity: 0.7 },
  navButtonText: { color: '#fff', fontWeight: '600' },
  startButton: { backgroundColor: '#3758f9', padding: 12, borderRadius: 8, alignItems: 'center' },
  startButtonText: { color: '#fff', fontWeight: '600' },
  completeButton: { backgroundColor: '#10b981', padding: 12, borderRadius: 8, alignItems: 'center' },
  completeButtonText: { color: '#fff', fontWeight: '600' },
  completeButtonDisabled: { backgroundColor: '#9ca3af', opacity: 0.6 },
  noTripText: { color: '#9ca3af', textAlign: 'center', padding: 20 },
  sectionTitle: { fontSize: 16, fontWeight: '600', color: '#1f2937', marginHorizontal: 15, marginTop: 10, marginBottom: 10 },
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: 10 },
  actionCard: { width: '30%', backgroundColor: '#fff', margin: '1.5%', padding: 12, borderRadius: 12, alignItems: 'center', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.1, shadowRadius: 2, elevation: 2 },
  actionIcon: { fontSize: 24, marginBottom: 6 },
  actionText: { fontSize: 11, color: '#374151', textAlign: 'center' },
  truckPlate: { fontSize: 20, fontWeight: 'bold', color: '#1f2937' },
  truckModel: { color: '#6b7280', marginTop: 4 },
  truckStatus: { color: '#6b7280', marginTop: 4, fontSize: 14 },
  warningContainer: { 
    backgroundColor: '#fef3c7', 
    padding: 10, 
    borderRadius: 8, 
    marginTop: 10,
    borderWidth: 1,
    borderColor: '#f59e0b'
  },
  warningText: { 
    color: '#92400e', 
    fontSize: 13,
    textAlign: 'center',
    fontWeight: '500'
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 20,
    width: '100%',
    maxHeight: '90%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  modalClose: {
    fontSize: 24,
    color: '#6b7280',
    padding: 4,
  },
  modalBody: {
    marginBottom: 16,
  },
  modalSubtext: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 16,
    textAlign: 'center',
  },
  inputGroup: {
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '500',
    color: '#374151',
    marginBottom: 6,
  },
  input: {
    backgroundColor: '#f9fafb',
    borderRadius: 10,
    padding: 12,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  textArea: {
    height: 80,
    textAlignVertical: 'top',
  },
  uploadSection: {
    marginTop: 10,
    marginBottom: 10,
    padding: 12,
    backgroundColor: '#f8fafc',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  uploadLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 4,
  },
  uploadSubtext: {
    fontSize: 12,
    color: '#6b7280',
    marginBottom: 10,
  },
  uploadSuccess: {
    color: '#10b981',
    fontSize: 14,
    fontWeight: '500',
    textAlign: 'center',
    marginTop: 8,
  },
  requiredText: {
    color: '#ef4444',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 8,
  },
  successContainer: {
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#d1fae5',
    borderRadius: 12,
    marginBottom: 16,
  },
  successIcon: {
    fontSize: 48,
    marginBottom: 8,
  },
  successTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#065f46',
  },
  successSubtext: {
    fontSize: 14,
    color: '#065f46',
    textAlign: 'center',
    marginTop: 4,
  },
  finishButton: {
    backgroundColor: '#3758f9',
    padding: 15,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
  },
  finishButtonText: {
    color: '#fff',
    fontWeight: '600',
    fontSize: 16,
  },
});