// src/components/DocumentUpload.js
import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { File } from 'expo-file-system'; // Use the new File API
import { API_URL } from '../config/api';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function DocumentUpload({
  entityType,
  entityId,
  documentType,
  onUploadComplete,
  onUploadError,
  buttonText = 'Upload Document',
}) {
  const [loading, setLoading] = useState(false);
  const [uploadedFiles, setUploadedFiles] = useState([]);

  const pickDocument = async () => {
    try {
      const { status: mediaStatus } = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (mediaStatus !== 'granted') {
        Alert.alert('Permission Denied', 'Need camera roll permissions to upload documents');
        return;
      }

      Alert.alert(
        'Select Document',
        'Choose how to upload',
        [
          { text: '📷 Take Photo', onPress: takePhoto },
          { text: '🖼️ Choose from Gallery', onPress: pickFromGallery },
          { text: 'Cancel', style: 'cancel' },
        ],
        { cancelable: true }
      );
    } catch (error) {
      console.error('Error picking document:', error);
      Alert.alert('Error', 'Failed to pick document');
    }
  };

  const takePhoto = async () => {
    try {
      const { status } = await ImagePicker.requestCameraPermissionsAsync();
      if (status !== 'granted') {
        Alert.alert('Permission Denied', 'Need camera permissions to take photos');
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        quality: 0.7,
        base64: false,
      });

      console.log('📸 Camera result:', result);

      if (!result.canceled && result.assets && result.assets.length > 0) {
        await uploadDocumentBase64(result.assets[0]);
      }
    } catch (error) {
      console.error('Error taking photo:', error);
      Alert.alert('Error', 'Failed to take photo');
    }
  };

  const pickFromGallery = async () => {
    try {
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        quality: 0.7,
        base64: false,
      });

      console.log('🖼️ Gallery result:', result);

      if (!result.canceled && result.assets && result.assets.length > 0) {
        await uploadDocumentBase64(result.assets[0]);
      }
    } catch (error) {
      console.error('Error picking from gallery:', error);
      Alert.alert('Error', 'Failed to pick image');
    }
  };

  // Base64 upload method using new File API
const uploadDocumentBase64 = async (file) => {
  if (!entityId) {
    Alert.alert('Error', 'No entity ID provided');
    return;
  }

  setLoading(true);
  try {
    const token = await AsyncStorage.getItem('userToken');
    
    console.log('📤 Reading file as base64...');
    console.log('📤 File URI:', file.uri);
    
    // Create a File instance using the new API
    const fileInstance = new File(file.uri);
    
    // Read the file as base64 using the new API
    const base64 = await fileInstance.base64();
    
    // Get filename and extension
    const uriParts = file.uri.split('.');
    const fileExtension = uriParts[uriParts.length - 1] || 'jpg';
    const fileName = file.fileName || `document_${Date.now()}.${fileExtension}`;
    
    console.log('📤 File read successfully, size:', base64.length);
    console.log('📤 File name:', fileName);
    
    // Prepare the request body - make sure it's clean JSON
    const requestBody = {
      image: base64,
      filename: fileName,
      entity_type: entityType,
      entity_id: String(entityId),
      document_type: documentType,
    };
    
    // Log the request body (truncated for readability)
    console.log('📤 Request body keys:', Object.keys(requestBody));
    console.log('📤 entity_type:', entityType);
    console.log('📤 entity_id:', entityId);
    console.log('📤 document_type:', documentType);
    
    const url = `${API_URL}/api/upload_base64.php`;
    console.log('📤 Uploading to:', url);
    
    // Make sure we're sending valid JSON
    const jsonBody = JSON.stringify(requestBody);
    console.log('📤 JSON body length:', jsonBody.length);
    
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': token ? `Bearer ${token}` : '',
      },
      body: jsonBody,
    });
    
    console.log('📡 Response status:', response.status);
    
    const text = await response.text();
    console.log('📡 Raw response:', text);
    
    try {
      const data = JSON.parse(text);
      if (data.success) {
        setUploadedFiles([...uploadedFiles, data.file]);
        Alert.alert('Success', 'Document uploaded successfully!');
        if (onUploadComplete) {
          onUploadComplete(data.file);
        }
      } else {
        Alert.alert('Upload Failed', data.message || 'Failed to upload document');
        if (onUploadError) {
          onUploadError(data.message);
        }
      }
    } catch (parseError) {
      console.error('❌ JSON Parse Error:', parseError);
      console.error('❌ Response was:', text);
      Alert.alert('Error', 'Server returned invalid response. Please try again.');
      if (onUploadError) {
        onUploadError('Invalid server response');
      }
    }
    
  } catch (error) {
    console.error('❌ Upload error:', error);
    Alert.alert('Upload Error', error.message || 'Failed to upload file');
    if (onUploadError) {
      onUploadError(error.message);
    }
  } finally {
    setLoading(false);
  }
};
  return (
    <View style={styles.container}>
      <TouchableOpacity 
        style={[styles.uploadButton, loading && styles.uploadButtonDisabled]}
        onPress={pickDocument}
        disabled={loading}
      >
        {loading ? (
          <ActivityIndicator color="#fff" size="small" />
        ) : (
          <Text style={styles.uploadButtonText}>{buttonText}</Text>
        )}
      </TouchableOpacity>
      
      {uploadedFiles.length > 0 && (
        <View style={styles.filesContainer}>
          <Text style={styles.filesLabel}>✅ {uploadedFiles.length} file(s) uploaded</Text>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginVertical: 10,
  },
  uploadButton: {
    backgroundColor: '#3758f9',
    padding: 14,
    borderRadius: 10,
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'center',
  },
  uploadButtonDisabled: {
    opacity: 0.6,
  },
  uploadButtonText: {
    color: '#fff',
    fontWeight: '600',
    fontSize: 16,
  },
  filesContainer: {
    marginTop: 10,
    backgroundColor: '#d1fae5',
    padding: 10,
    borderRadius: 8,
  },
  filesLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#065f46',
    textAlign: 'center',
  },
});