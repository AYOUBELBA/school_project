import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const API_URL = 'http://192.168.100.116/SCHOOL_PROJECT';

export default function ReportIssueScreen() {
  const navigation = useNavigation();
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    if (!description.trim()) {
      Alert.alert('Error', 'Please describe the issue');
      return;
    }

    setLoading(true);
    try {
      const driverId = await AsyncStorage.getItem('driverId');
      const truckId = await AsyncStorage.getItem('truckId');
      const response = await fetch(`${API_URL}/api/report_issue.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          driver_id: parseInt(driverId),
          truck_id: parseInt(truckId),
          description: description
        })
      });
      const data = await response.json();
      if (data.success) {
        Alert.alert('Success', 'Issue reported successfully');
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
        <Text style={styles.headerTitle}>Report Issue</Text>
        <View style={{ width: 40 }} />
      </View>
      <View style={styles.form}>
        <TextInput
          style={styles.textArea}
          placeholder="Describe the issue..."
          multiline
          numberOfLines={6}
          value={description}
          onChangeText={setDescription}
        />
        <TouchableOpacity style={styles.submitButton} onPress={handleSubmit} disabled={loading}>
          <Text style={styles.submitButtonText}>{loading ? 'Submitting...' : 'Submit Report'}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f0f2f5' },
  header: { backgroundColor: '#3758f9', padding: 15, paddingTop: 50, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  backButton: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  backIcon: { fontSize: 24, color: '#fff' },
  headerTitle: { fontSize: 18, fontWeight: 'bold', color: '#fff' },
  form: { padding: 20 },
  textArea: { backgroundColor: '#fff', borderRadius: 12, padding: 15, height: 150, textAlignVertical: 'top', fontSize: 16 },
  submitButton: { backgroundColor: '#3758f9', padding: 15, borderRadius: 12, alignItems: 'center', marginTop: 20 },
  submitButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
});