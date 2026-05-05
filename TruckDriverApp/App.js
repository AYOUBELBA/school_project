import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  ScrollView,
  Modal,
  TextInput as TextInputRN,
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Location from 'expo-location';
import { Picker } from '@react-native-picker/picker';

const API_URL = 'http://192.168.110.20/school_project';

// Login Screen
function LoginScreen({ onLogin }) {
  const [email, setEmail] = useState('driver3@driver.com');
  const [password, setPassword] = useState('Driver123!');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Please fill in all fields');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch(`${API_URL}/api/mobile_login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await response.json();
      
      if (data.success) {
        await AsyncStorage.setItem('userToken', data.token);
        await AsyncStorage.setItem('userId', String(data.user_id));
        await AsyncStorage.setItem('driverId', String(data.driver_id));
        await AsyncStorage.setItem('truckId', String(data.truck_id || 0));
        await AsyncStorage.setItem('driverName', data.name || 'Driver');
        onLogin();
      } else {
        Alert.alert('Login Failed', data.message || 'Invalid credentials');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.loginContainer}>
        <View style={styles.logo}>
          <Text style={styles.logoText}>🚛</Text>
        </View>
        <Text style={styles.title}>FleetDriver</Text>
        <Text style={styles.subtitle}>Truck Tracking System</Text>
        
        <TextInput
          style={styles.input}
          placeholder="Email"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
        />
        <TextInput
          style={styles.input}
          placeholder="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
        />
        
        <TouchableOpacity style={styles.loginButton} onPress={handleLogin} disabled={loading}>
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginButtonText}>Login</Text>}
        </TouchableOpacity>
      </View>
    </View>
  );
}

// Dashboard Screen
function DashboardScreen({ onLogout }) {
  const [driverName, setDriverName] = useState('');
  const [driverId, setDriverId] = useState('');
  const [truckId, setTruckId] = useState('');
  const [truckPlate, setTruckPlate] = useState('');
  const [currentTrip, setCurrentTrip] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [locationStatus, setLocationStatus] = useState('Waiting for GPS...');
  const [loading, setLoading] = useState(true);
  
  // Modal states
  const [reportModalVisible, setReportModalVisible] = useState(false);
  const [maintenanceModalVisible, setMaintenanceModalVisible] = useState(false);
  const [fuelModalVisible, setFuelModalVisible] = useState(false);
  const [incidentModalVisible, setIncidentModalVisible] = useState(false);
  
  // Form states
  const [reportText, setReportText] = useState('');
  const [maintenanceType, setMaintenanceType] = useState('');
  const [maintenanceNotes, setMaintenanceNotes] = useState('');
  const [fuelLiters, setFuelLiters] = useState('');
  const [fuelCost, setFuelCost] = useState('');
  const [fuelStation, setFuelStation] = useState('');
  const [incidentDescription, setIncidentDescription] = useState('');
  const [incidentSeverity, setIncidentSeverity] = useState('medium');
  const [incidentLocation, setIncidentLocation] = useState('');

  useEffect(() => {
    loadUserData();
    loadCurrentTrip();
    startTracking();
  }, []);

  const loadUserData = async () => {
    try {
      const name = await AsyncStorage.getItem('driverName');
      const dId = await AsyncStorage.getItem('driverId');
      const tId = await AsyncStorage.getItem('truckId');
      setDriverName(name || 'Driver');
      setDriverId(dId || '');
      setTruckId(tId || '');
      
      // Get truck details
      if (tId && tId !== '0') {
        const response = await fetch(`${API_URL}/api/get_truck_details.php?truck_id=${tId}`);
        const data = await response.json();
        if (data.success) {
          setTruckPlate(data.truck.plate_number);
        }
      }
    } catch (error) {
      console.error('Error loading user data:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadCurrentTrip = async () => {
    try {
      const dId = await AsyncStorage.getItem('driverId');
      if (dId && dId !== '0') {
        const response = await fetch(`${API_URL}/api/get_current_trip.php?driver_id=${dId}`);
        const data = await response.json();
        if (data.success && data.current_trip) {
          setCurrentTrip(data.current_trip);
        }
      }
    } catch (error) {
      console.error('Error loading trip:', error);
    }
  };

  const startTracking = async () => {
    try {
      const { status } = await Location.requestForegroundPermissionsAsync();
      if (status !== 'granted') {
        setLocationStatus('❌ Location permission denied');
        return;
      }
      
      setLocationStatus('📍 Tracking active...');
      
      Location.watchPositionAsync(
        {
          accuracy: Location.Accuracy.High,
          timeInterval: 2000,
          distanceInterval: 5,
        },
        async (newLocation) => {
          const { latitude, longitude, speed } = newLocation.coords;
          const speedKmh = (speed || 0) * 3.6;
          setLocationStatus(`📍 LIVE | Speed: ${speedKmh.toFixed(1)} km/h`);
          
          const tId = await AsyncStorage.getItem('truckId');
          if (tId && tId !== '0') {
            fetch(`${API_URL}/api/update_location.php`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                truck_id: parseInt(tId),
                latitude,
                longitude,
                speed: speedKmh,
                accuracy: newLocation.coords.accuracy || 10
              })
            }).catch(err => console.error('Send error:', err));
          }
        }
      ).catch(error => {
        console.error('Watch error:', error);
        setLocationStatus('❌ GPS error');
      });
    } catch (error) {
      console.error('Start tracking error:', error);
      setLocationStatus('❌ Failed to start GPS');
    }
  };

  const handleReportIssue = async () => {
    if (!reportText.trim()) {
      Alert.alert('Error', 'Please describe the issue');
      return;
    }
    
    try {
      const response = await fetch(`${API_URL}/api/report_issue.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          driver_id: driverId,
          truck_id: truckId,
          description: reportText,
          type: 'issue'
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Issue reported successfully');
        setReportText('');
        setReportModalVisible(false);
      } else {
        Alert.alert('Error', data.message || 'Failed to report');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error');
    }
  };

  const handleMaintenance = async () => {
    if (!maintenanceType.trim()) {
      Alert.alert('Error', 'Please enter maintenance type');
      return;
    }
    
    try {
      const response = await fetch(`${API_URL}/api/add_maintenance.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: truckId,
          maintenance_type: maintenanceType,
          notes: maintenanceNotes,
          reported_by: driverId
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Maintenance request submitted');
        setMaintenanceType('');
        setMaintenanceNotes('');
        setMaintenanceModalVisible(false);
      } else {
        Alert.alert('Error', data.message || 'Failed to submit');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error');
    }
  };

  const handleFuelRecord = async () => {
    if (!fuelLiters || !fuelCost) {
      Alert.alert('Error', 'Please fill in liters and cost');
      return;
    }
    
    try {
      const response = await fetch(`${API_URL}/api/add_fuel_record.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: truckId,
          liters: parseFloat(fuelLiters),
          cost_per_liter: parseFloat(fuelCost) / parseFloat(fuelLiters),
          total_cost: parseFloat(fuelCost),
          fuel_station: fuelStation,
          driver_id: driverId
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Fuel record added');
        setFuelLiters('');
        setFuelCost('');
        setFuelStation('');
        setFuelModalVisible(false);
      } else {
        Alert.alert('Error', data.message || 'Failed to add record');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error');
    }
  };

  const handleIncident = async () => {
    if (!incidentDescription.trim()) {
      Alert.alert('Error', 'Please describe the incident');
      return;
    }
    
    try {
      const response = await fetch(`${API_URL}/api/report_incident.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: truckId,
          driver_id: driverId,
          description: incidentDescription,
          severity: incidentSeverity,
          location: incidentLocation
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Incident reported');
        setIncidentDescription('');
        setIncidentLocation('');
        setIncidentSeverity('medium');
        setIncidentModalVisible(false);
      } else {
        Alert.alert('Error', data.message || 'Failed to report');
      }
    } catch (error) {
      Alert.alert('Error', 'Network error');
    }
  };

  const handleLogout = async () => {
    Alert.alert('Logout', 'Are you sure?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Logout',
        style: 'destructive',
        onPress: async () => {
          await AsyncStorage.clear();
          onLogout();
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

  const renderDashboard = () => (
    <ScrollView>
      <View style={styles.statusCard}>
        <Text style={styles.statusText}>{locationStatus}</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Current Trip</Text>
        {currentTrip ? (
          <>
            <Text style={styles.tripNumber}>Trip #{currentTrip.trip_number}</Text>
            <Text style={styles.routeText}>
              {currentTrip.start_location || 'Start'} → {currentTrip.end_location || 'Destination'}
            </Text>
            <Text style={styles.statusBadge}>Status: {currentTrip.status}</Text>
          </>
        ) : (
          <Text style={styles.noDataText}>No active trip assigned</Text>
        )}
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Your Truck</Text>
        <Text style={styles.truckPlate}>{truckPlate || 'Not assigned'}</Text>
      </View>

      <View style={styles.buttonGrid}>
        <TouchableOpacity style={styles.gridButton} onPress={() => setReportModalVisible(true)}>
          <Text style={styles.gridButtonIcon}>📢</Text>
          <Text style={styles.gridButtonText}>Report Issue</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.gridButton} onPress={() => setMaintenanceModalVisible(true)}>
          <Text style={styles.gridButtonIcon}>🔧</Text>
          <Text style={styles.gridButtonText}>Maintenance</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.gridButton} onPress={() => setFuelModalVisible(true)}>
          <Text style={styles.gridButtonIcon}>⛽</Text>
          <Text style={styles.gridButtonText}>Add Fuel</Text>
        </TouchableOpacity>
        
        <TouchableOpacity style={styles.gridButton} onPress={() => setIncidentModalVisible(true)}>
          <Text style={styles.gridButtonIcon}>⚠️</Text>
          <Text style={styles.gridButtonText}>Incident</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );

  const renderMap = () => (
    <View style={styles.mapPlaceholder}>
      <Text style={styles.mapPlaceholderText}>🗺️</Text>
      <Text style={styles.mapPlaceholderTitle}>Live Map</Text>
      <Text style={styles.mapPlaceholderSubtext}>Full map view coming soon</Text>
      <Text style={styles.mapPlaceholderInfo}>Your current location is being tracked</Text>
      {currentTrip && (
        <View style={styles.mapRouteInfo}>
          <Text style={styles.mapRouteText}>Route: {currentTrip.start_location} → {currentTrip.end_location}</Text>
        </View>
      )}
    </View>
  );

  const renderTrips = () => (
    <ScrollView>
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Current Trip</Text>
        {currentTrip ? (
          <>
            <Text style={styles.tripNumber}>{currentTrip.trip_number}</Text>
            <Text style={styles.routeText}>{currentTrip.start_location} → {currentTrip.end_location}</Text>
            <View style={styles.tripDetails}>
              <Text>Status: {currentTrip.status}</Text>
              <Text>Priority: {currentTrip.priority}</Text>
            </View>
            <TouchableOpacity style={styles.updateButton}>
              <Text style={styles.updateButtonText}>Update Trip Status</Text>
            </TouchableOpacity>
          </>
        ) : (
          <Text style={styles.noDataText}>No active trips</Text>
        )}
      </View>
      
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Recent Trips</Text>
        <Text style={styles.noDataText}>No previous trips found</Text>
      </View>
    </ScrollView>
  );

  const renderProfile = () => (
    <ScrollView>
      <View style={styles.profileHeader}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{driverName.charAt(0)}</Text>
        </View>
        <Text style={styles.profileName}>{driverName}</Text>
        <Text style={styles.profileRole}>Driver</Text>
      </View>
      
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Driver Information</Text>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Driver ID:</Text>
          <Text style={styles.infoValue}>#{driverId}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Assigned Truck:</Text>
          <Text style={styles.infoValue}>{truckPlate || 'Not assigned'}</Text>
        </View>
      </View>
    </ScrollView>
  );

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>FleetDriver</Text>
        <TouchableOpacity onPress={handleLogout} style={styles.logoutButton}>
          <Text style={styles.logoutText}>Logout</Text>
        </TouchableOpacity>
      </View>

      <View style={styles.tabBar}>
        <TouchableOpacity style={[styles.tab, activeTab === 'dashboard' && styles.activeTab]} onPress={() => setActiveTab('dashboard')}>
          <Text style={styles.tabIcon}>📊</Text>
          <Text style={styles.tabText}>Home</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tab, activeTab === 'map' && styles.activeTab]} onPress={() => setActiveTab('map')}>
          <Text style={styles.tabIcon}>🗺️</Text>
          <Text style={styles.tabText}>Map</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tab, activeTab === 'trips' && styles.activeTab]} onPress={() => setActiveTab('trips')}>
          <Text style={styles.tabIcon}>📋</Text>
          <Text style={styles.tabText}>Trips</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tab, activeTab === 'profile' && styles.activeTab]} onPress={() => setActiveTab('profile')}>
          <Text style={styles.tabIcon}>👤</Text>
          <Text style={styles.tabText}>Profile</Text>
        </TouchableOpacity>
      </View>

      {activeTab === 'dashboard' && renderDashboard()}
      {activeTab === 'map' && renderMap()}
      {activeTab === 'trips' && renderTrips()}
      {activeTab === 'profile' && renderProfile()}

      {/* Report Issue Modal */}
      <Modal visible={reportModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Report Issue</Text>
            <TextInputRN
              style={styles.modalTextArea}
              placeholder="Describe the issue..."
              multiline
              numberOfLines={4}
              value={reportText}
              onChangeText={setReportText}
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.modalCancel} onPress={() => setReportModalVisible(false)}>
                <Text>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.modalSubmit} onPress={handleReportIssue}>
                <Text style={{ color: '#fff' }}>Submit</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Maintenance Modal */}
      <Modal visible={maintenanceModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Request Maintenance</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="Maintenance type (e.g., Oil Change, Tire Rotation)"
              value={maintenanceType}
              onChangeText={setMaintenanceType}
            />
            <TextInputRN
              style={styles.modalTextArea}
              placeholder="Additional notes..."
              multiline
              numberOfLines={3}
              value={maintenanceNotes}
              onChangeText={setMaintenanceNotes}
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.modalCancel} onPress={() => setMaintenanceModalVisible(false)}>
                <Text>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.modalSubmit} onPress={handleMaintenance}>
                <Text style={{ color: '#fff' }}>Submit</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Fuel Modal */}
      <Modal visible={fuelModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Add Fuel Record</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="Liters"
              keyboardType="numeric"
              value={fuelLiters}
              onChangeText={setFuelLiters}
            />
            <TextInput
              style={styles.modalInput}
              placeholder="Total Cost ($)"
              keyboardType="numeric"
              value={fuelCost}
              onChangeText={setFuelCost}
            />
            <TextInput
              style={styles.modalInput}
              placeholder="Fuel Station"
              value={fuelStation}
              onChangeText={setFuelStation}
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.modalCancel} onPress={() => setFuelModalVisible(false)}>
                <Text>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.modalSubmit} onPress={handleFuelRecord}>
                <Text style={{ color: '#fff' }}>Save</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Incident Modal */}
      <Modal visible={incidentModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalContainer}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Report Incident</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="Location"
              value={incidentLocation}
              onChangeText={setIncidentLocation}
            />
            <Picker selectedValue={incidentSeverity} onValueChange={setIncidentSeverity} style={styles.modalPicker}>
              <Picker.Item label="Low" value="low" />
              <Picker.Item label="Medium" value="medium" />
              <Picker.Item label="High" value="high" />
              <Picker.Item label="Critical" value="critical" />
            </Picker>
            <TextInputRN
              style={styles.modalTextArea}
              placeholder="Describe the incident..."
              multiline
              numberOfLines={3}
              value={incidentDescription}
              onChangeText={setIncidentDescription}
            />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.modalCancel} onPress={() => setIncidentModalVisible(false)}>
                <Text>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.modalSubmit} onPress={handleIncident}>
                <Text style={{ color: '#fff' }}>Report</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
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
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#3758f9" />
      </View>
    );
  }

  return isLoggedIn ? (
    <DashboardScreen onLogout={() => setIsLoggedIn(false)} />
  ) : (
    <LoginScreen onLogin={() => setIsLoggedIn(true)} />
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f0f2f5',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loginContainer: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    alignItems: 'center',
    margin: 20,
  },
  logo: {
    width: 80,
    height: 80,
    backgroundColor: '#3758f9',
    borderRadius: 40,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 15,
  },
  logoText: {
    fontSize: 40,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  subtitle: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 30,
  },
  input: {
    width: '100%',
    backgroundColor: '#f9fafb',
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 8,
    paddingHorizontal: 15,
    paddingVertical: 12,
    marginBottom: 15,
    fontSize: 16,
  },
  loginButton: {
    width: '100%',
    backgroundColor: '#3758f9',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 10,
  },
  loginButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  header: {
    backgroundColor: '#3758f9',
    padding: 15,
    paddingTop: 50,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#fff',
  },
  logoutButton: {
    backgroundColor: 'rgba(255,255,255,0.2)',
    paddingHorizontal: 15,
    paddingVertical: 8,
    borderRadius: 8,
  },
  logoutText: {
    color: '#fff',
    fontSize: 14,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  tab: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 8,
  },
  activeTab: {
    borderBottomWidth: 2,
    borderBottomColor: '#3758f9',
  },
  tabIcon: {
    fontSize: 20,
  },
  tabText: {
    fontSize: 12,
    marginTop: 4,
    color: '#6b7280',
  },
  statusCard: {
    backgroundColor: '#10b981',
    margin: 15,
    padding: 15,
    borderRadius: 12,
  },
  statusText: {
    color: '#fff',
    fontSize: 14,
    textAlign: 'center',
    fontWeight: '500',
  },
  card: {
    backgroundColor: '#fff',
    margin: 15,
    padding: 20,
    borderRadius: 12,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1f2937',
    marginBottom: 12,
  },
  tripNumber: {
    fontSize: 16,
    fontWeight: '600',
    color: '#3758f9',
    marginBottom: 8,
  },
  routeText: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 8,
  },
  statusBadge: {
    fontSize: 12,
    color: '#6b7280',
  },
  truckPlate: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  noDataText: {
    color: '#9ca3af',
    textAlign: 'center',
    padding: 20,
  },
  buttonGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    padding: 10,
  },
  gridButton: {
    width: '45%',
    backgroundColor: '#fff',
    margin: '2.5%',
    padding: 20,
    borderRadius: 12,
    alignItems: 'center',
  },
  gridButtonIcon: {
    fontSize: 30,
    marginBottom: 8,
  },
  gridButtonText: {
    fontSize: 14,
    color: '#374151',
  },
  mapPlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  mapPlaceholderText: {
    fontSize: 80,
    marginBottom: 20,
  },
  mapPlaceholderTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1f2937',
    marginBottom: 10,
  },
  mapPlaceholderSubtext: {
    fontSize: 14,
    color: '#6b7280',
    marginBottom: 20,
  },
  mapPlaceholderInfo: {
    fontSize: 12,
    color: '#9ca3af',
    textAlign: 'center',
  },
  mapRouteInfo: {
    marginTop: 20,
    padding: 15,
    backgroundColor: '#f3f4f6',
    borderRadius: 8,
  },
  mapRouteText: {
    fontSize: 14,
    color: '#374151',
  },
  profileHeader: {
    alignItems: 'center',
    padding: 30,
    backgroundColor: '#fff',
    marginBottom: 15,
  },
  avatar: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: '#3758f9',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 15,
  },
  avatarText: {
    fontSize: 40,
    color: '#fff',
    fontWeight: 'bold',
  },
  profileName: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  profileRole: {
    fontSize: 16,
    color: '#6b7280',
    marginTop: 4,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#e5e7eb',
  },
  infoLabel: {
    fontSize: 14,
    color: '#6b7280',
  },
  infoValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#1f2937',
  },
  tripDetails: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
    marginBottom: 15,
  },
  updateButton: {
    backgroundColor: '#3758f9',
    padding: 12,
    borderRadius: 8,
    alignItems: 'center',
  },
  updateButtonText: {
    color: '#fff',
    fontWeight: '600',
  },
  modalContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.5)',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    width: '90%',
    maxHeight: '80%',
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: 20,
    textAlign: 'center',
  },
  modalInput: {
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 8,
    padding: 12,
    marginBottom: 15,
    fontSize: 16,
  },
  modalTextArea: {
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 8,
    padding: 12,
    marginBottom: 15,
    fontSize: 16,
    minHeight: 80,
    textAlignVertical: 'top',
  },
  modalPicker: {
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 8,
    marginBottom: 15,
  },
  modalButtons: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 10,
  },
  modalCancel: {
    flex: 1,
    padding: 12,
    alignItems: 'center',
    marginRight: 10,
    borderWidth: 1,
    borderColor: '#e5e7eb',
    borderRadius: 8,
  },
  modalSubmit: {
    flex: 1,
    padding: 12,
    alignItems: 'center',
    backgroundColor: '#3758f9',
    borderRadius: 8,
    marginLeft: 10,
  },
});