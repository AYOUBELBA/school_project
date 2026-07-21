import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  ActivityIndicator,
  TouchableOpacity,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'http://192.168.100.116/SCHOOL_PROJECT';

export default function ProfileScreen() {
  const navigation = useNavigation();
  const [driver, setDriver] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadProfile();
  }, []);

  const loadProfile = async () => {
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const response = await fetch(`${API_URL}/api/get_driver_info.php?driver_id=${driverId}`);
      const data = await response.json();
      if (data.success) {
        setDriver(data.driver);
      }
    } catch (error) {
      console.error('Error:', error);
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

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>My Profile</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.avatarContainer}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{driver?.full_name?.charAt(0) || 'D'}</Text>
        </View>
        <Text style={styles.name}>{driver?.full_name}</Text>
        <Text style={styles.role}>Driver</Text>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Driver Information</Text>
        <View style={styles.infoRow}>
          <Text style={styles.label}>Driver ID</Text>
          <Text style={styles.value}>#{driver?.id}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.label}>Phone</Text>
          <Text style={styles.value}>{driver?.phone || 'Not provided'}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.label}>License Number</Text>
          <Text style={styles.value}>{driver?.license_number}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.label}>License Expiry</Text>
          <Text style={styles.value}>{driver?.license_expiry}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.label}>Status</Text>
          <Text style={styles.value}>{driver?.status}</Text>
        </View>
        <View style={styles.infoRow}>
          <Text style={styles.label}>Hire Date</Text>
          <Text style={styles.value}>{driver?.hire_date}</Text>
        </View>
      </View>

      <View style={styles.card}>
        <Text style={styles.cardTitle}>Assigned Truck</Text>
        <Text style={styles.truckPlate}>{driver?.truck_plate || 'Not assigned'}</Text>
        <Text style={styles.truckModel}>{driver?.truck_model || ''}</Text>
      </View>
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
    justifyContent: 'space-between',
  },
  backButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#fff' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  avatarContainer: { alignItems: 'center', padding: 30, backgroundColor: '#fff' },
  avatar: { width: 100, height: 100, borderRadius: 50, backgroundColor: '#3758f9', justifyContent: 'center', alignItems: 'center', marginBottom: 15 },
  avatarText: { fontSize: 40, color: '#fff', fontWeight: 'bold' },
  name: { fontSize: 24, fontWeight: 'bold', color: '#1f2937' },
  role: { fontSize: 16, color: '#6b7280', marginTop: 4 },
  card: { backgroundColor: '#fff', margin: 15, padding: 15, borderRadius: 12 },
  cardTitle: { fontSize: 18, fontWeight: '600', color: '#1f2937', marginBottom: 12 },
  infoRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#e5e7eb' },
  label: { fontSize: 14, color: '#6b7280' },
  value: { fontSize: 14, fontWeight: '500', color: '#1f2937' },
  truckPlate: { fontSize: 20, fontWeight: 'bold', color: '#1f2937' },
  truckModel: { color: '#6b7280', marginTop: 4 },
});