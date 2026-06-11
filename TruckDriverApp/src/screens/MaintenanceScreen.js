import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'http://192.168.100.116/school_project';

export default function MaintenanceScreen() {
  const navigation = useNavigation();
  const [maintenanceType, setMaintenanceType] = useState('');
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (!maintenanceType.trim()) {
      Alert.alert('Error', 'Please enter maintenance type');
      return;
    }

    setLoading(true);
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const truckId = await AsyncStorage.getItem('truckId');
      const response = await fetch(`${API_URL}/api/add_maintenance.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: parseInt(truckId),
          maintenance_type: maintenanceType,
          notes: notes,
          reported_by: parseInt(driverId)
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Maintenance request submitted');
        navigation.goBack();
      } else {
        Alert.alert('Error', data.message);
      }
    } catch (error) {
      Alert.alert('Error', 'Network error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Maintenance Request</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.form}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Maintenance Type *</Text>
          <TextInput
            style={styles.input}
            placeholder="e.g., Oil Change, Tire Rotation, Brake Check"
            value={maintenanceType}
            onChangeText={setMaintenanceType}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Additional Notes</Text>
          <TextInput
            style={styles.textArea}
            placeholder="Describe any issues or specific requirements..."
            multiline
            numberOfLines={4}
            value={notes}
            onChangeText={setNotes}
          />
        </View>

        <TouchableOpacity style={styles.submitButton} onPress={handleSubmit} disabled={loading}>
          <Text style={styles.submitButtonText}>
            {loading ? 'Submitting...' : 'Submit Request'}
          </Text>
        </TouchableOpacity>
      </View>

      <View style={styles.infoBox}>
        <Text style={styles.infoTitle}>📋 Maintenance Types</Text>
        <Text style={styles.infoText}>• Oil Change</Text>
        <Text style={styles.infoText}>• Tire Rotation</Text>
        <Text style={styles.infoText}>• Brake Inspection</Text>
        <Text style={styles.infoText}>• Engine Tune-up</Text>
        <Text style={styles.infoText}>• Transmission Service</Text>
        <Text style={styles.infoText}>• Other (please specify)</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
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
  form: { padding: 20 },
  inputGroup: { marginBottom: 20 },
  label: { fontSize: 14, fontWeight: '500', color: '#374151', marginBottom: 8 },
  input: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  textArea: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    fontSize: 16,
    height: 100,
    textAlignVertical: 'top',
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  submitButton: {
    backgroundColor: '#f59e0b',
    padding: 15,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  submitButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  infoBox: {
    backgroundColor: '#fef3c7',
    margin: 20,
    padding: 15,
    borderRadius: 12,
    borderLeftWidth: 4,
    borderLeftColor: '#f59e0b',
  },
  infoTitle: { fontSize: 14, fontWeight: 'bold', color: '#92400e', marginBottom: 8 },
  infoText: { fontSize: 12, color: '#92400e', marginBottom: 4 },
});