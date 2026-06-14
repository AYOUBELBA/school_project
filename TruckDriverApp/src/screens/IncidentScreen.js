import React, { useState } from 'react';
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

const API_URL = 'http://192.168.110.20/school_project';

export default function IncidentScreen() {
  const navigation = useNavigation();
  const [description, setDescription] = useState('');
  const [location, setLocation] = useState('');
  const [severity, setSeverity] = useState('medium');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (!description.trim()) {
      Alert.alert('Error', 'Please describe the incident');
      return;
    }

    setLoading(true);
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const truckId = await AsyncStorage.getItem('truckId');
      const response = await fetch(`${API_URL}/api/report_incident.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          truck_id: parseInt(truckId),
          driver_id: parseInt(driverId),
          description: description,
          severity: severity,
          location: location
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', `Incident reported: ${data.incident_number}`);
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

  const getSeverityColor = (sev) => {
    switch(sev) {
      case 'low': return '#10b981';
      case 'medium': return '#f59e0b';
      case 'high': return '#ef4444';
      case 'critical': return '#7f1d1d';
      default: return '#6b7280';
    }
  };

  return (
    <ScrollView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
          <Text style={styles.backIcon}>←</Text>
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Report Incident</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.form}>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Location *</Text>
          <TextInput
            style={styles.input}
            placeholder="Where did the incident occur?"
            value={location}
            onChangeText={setLocation}
          />
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Severity *</Text>
          <View style={styles.severityContainer}>
            <TouchableOpacity
              style={[styles.severityBtn, severity === 'low' && styles.severityActiveLow]}
              onPress={() => setSeverity('low')}
            >
              <Text style={[styles.severityText, severity === 'low' && styles.severityTextActive]}>🟢 Low</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.severityBtn, severity === 'medium' && styles.severityActiveMedium]}
              onPress={() => setSeverity('medium')}
            >
              <Text style={[styles.severityText, severity === 'medium' && styles.severityTextActive]}>🟡 Medium</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.severityBtn, severity === 'high' && styles.severityActiveHigh]}
              onPress={() => setSeverity('high')}
            >
              <Text style={[styles.severityText, severity === 'high' && styles.severityTextActive]}>🔴 High</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.severityBtn, severity === 'critical' && styles.severityActiveCritical]}
              onPress={() => setSeverity('critical')}
            >
              <Text style={[styles.severityText, severity === 'critical' && styles.severityTextActive]}>⚠️ Critical</Text>
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.inputGroup}>
          <Text style={styles.label}>Incident Description *</Text>
          <TextInput
            style={styles.textArea}
            placeholder="Describe what happened in detail..."
            multiline
            numberOfLines={6}
            value={description}
            onChangeText={setDescription}
          />
        </View>

        <TouchableOpacity style={[styles.submitButton, { backgroundColor: getSeverityColor(severity) }]} onPress={handleSubmit} disabled={loading}>
          <Text style={styles.submitButtonText}>
            {loading ? 'Reporting...' : 'Report Incident'}
          </Text>
        </TouchableOpacity>
      </View>

      <View style={styles.infoBox}>
        <Text style={styles.infoTitle}>⚠️ Important</Text>
        <Text style={styles.infoText}>
          • Report incidents immediately after they occur{'\n'}
          • Include as many details as possible{'\n'}
          • Take photos if possible (coming soon){'\n'}
          • Critical incidents will notify managers instantly
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
  textArea: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 12,
    fontSize: 16,
    height: 120,
    textAlignVertical: 'top',
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  severityContainer: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  severityBtn: { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 20, backgroundColor: '#f3f4f6', borderWidth: 1, borderColor: '#e5e7eb' },
  severityActiveLow: { backgroundColor: '#10b981', borderColor: '#10b981' },
  severityActiveMedium: { backgroundColor: '#f59e0b', borderColor: '#f59e0b' },
  severityActiveHigh: { backgroundColor: '#ef4444', borderColor: '#ef4444' },
  severityActiveCritical: { backgroundColor: '#7f1d1d', borderColor: '#7f1d1d' },
  severityText: { fontSize: 14, color: '#374151' },
  severityTextActive: { color: '#fff' },
  submitButton: { padding: 15, borderRadius: 12, alignItems: 'center', marginTop: 10 },
  submitButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  infoBox: {
    backgroundColor: '#fee2e2',
    margin: 20,
    padding: 15,
    borderRadius: 12,
    borderLeftWidth: 4,
    borderLeftColor: '#ef4444',
  },
  infoTitle: { fontSize: 14, fontWeight: 'bold', color: '#991b1b', marginBottom: 8 },
  infoText: { fontSize: 12, color: '#991b1b', lineHeight: 18 },
});