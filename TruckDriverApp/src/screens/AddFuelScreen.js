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
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'http://192.168.100.116/school_project';

export default function AddFuelScreen() {
  const navigation = useNavigation();
  const [truckId, setTruckId] = useState(null);
  const [liters, setLiters] = useState('');
  const [costPerLiter, setCostPerLiter] = useState('');
  const [totalCost, setTotalCost] = useState('');
  const [odometer, setOdometer] = useState('');
  const [fuelStation, setFuelStation] = useState('');
  const [invoiceNumber, setInvoiceNumber] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadTruckInfo();
  }, []);

  const loadTruckInfo = async () => {
    const truck = await AsyncStorage.getItem('truckId');
    setTruckId(truck);
  };

  const calculateTotal = (litersVal, costVal) => {
    const l = parseFloat(litersVal) || 0;
    const c = parseFloat(costVal) || 0;
    const total = l * c;
    setTotalCost(total.toFixed(2));
  };

  const handleSubmit = async () => {
    if (!liters || !costPerLiter || !invoiceNumber) {
      Alert.alert('Error', 'Please fill in liters, cost per liter, and invoice number');
      return;
    }

    setLoading(true);
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const response = await fetch(`${API_URL}/api/add_fuel_record.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: parseInt(truckId),
          driver_id: parseInt(driverId),
          liters: parseFloat(liters),
          cost_per_liter: parseFloat(costPerLiter),
          total_cost: parseFloat(totalCost),
          odometer_km: odometer ? parseInt(odometer) : null,
          fuel_station: fuelStation,
          invoice_number: invoiceNumber
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Fuel record added successfully');
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
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Add Fuel Record</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.form}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Liters *</Text>
          <TextInput
            style={styles.input}
            placeholder="Enter liters"
            keyboardType="numeric"
            value={liters}
            onChangeText={(val) => {
              setLiters(val);
              calculateTotal(val, costPerLiter);
            }}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Cost per Liter ($) *</Text>
          <TextInput
            style={styles.input}
            placeholder="Enter cost per liter"
            keyboardType="numeric"
            value={costPerLiter}
            onChangeText={(val) => {
              setCostPerLiter(val);
              calculateTotal(liters, val);
            }}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Total Cost ($)</Text>
          <TextInput
            style={[styles.input, styles.disabledInput]}
            placeholder="Auto-calculated"
            value={totalCost}
            editable={false}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Invoice Number *</Text>
          <TextInput
            style={styles.input}
            placeholder="e.g., INV-2024-001"
            value={invoiceNumber}
            onChangeText={setInvoiceNumber}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Odometer (km)</Text>
          <TextInput
            style={styles.input}
            placeholder="Current odometer reading"
            keyboardType="numeric"
            value={odometer}
            onChangeText={setOdometer}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Fuel Station</Text>
          <TextInput
            style={styles.input}
            placeholder="Station name"
            value={fuelStation}
            onChangeText={setFuelStation}
          />
        </View>

        <TouchableOpacity style={styles.submitButton} onPress={handleSubmit} disabled={loading}>
          <Text style={styles.submitButtonText}>
            {loading ? 'Saving...' : 'Save Fuel Record'}
          </Text>
        </TouchableOpacity>
      </View>

      <View style={styles.infoBox}>
        <Text style={styles.infoTitle}>💡 Tip</Text>
        <Text style={styles.infoText}>
          Keep your fuel receipts for verification. The invoice number helps prevent duplicate entries.
        </Text>
      </View>
    </ScrollView>
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
  disabledInput: { backgroundColor: '#f3f4f6', color: '#6b7280' },
  submitButton: {
    backgroundColor: '#10b981',
    padding: 15,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  submitButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  infoBox: {
    backgroundColor: '#d1fae5',
    margin: 20,
    padding: 15,
    borderRadius: 12,
    borderLeftWidth: 4,
    borderLeftColor: '#10b981',
  },
  infoTitle: { fontSize: 14, fontWeight: 'bold', color: '#065f46', marginBottom: 8 },
  infoText: { fontSize: 12, color: '#065f46', lineHeight: 18 },
});