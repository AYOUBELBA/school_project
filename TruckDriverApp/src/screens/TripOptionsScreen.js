// src/screens/TripOptionsScreen.js
import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  TouchableOpacity,
  TouchableWithoutFeedback,
} from 'react-native';

export default function TripOptionsScreen({
  visible,
  onClose,
  onNavigateInApp,
  onOpenGoogleMaps,
  onViewTripOnMap,
  trip,
  isInTruck = false,
}) {
  if (!trip) return null;

  return (
    <Modal
      transparent
      visible={visible}
      animationType="fade"
      onRequestClose={onClose}
    >
      <TouchableWithoutFeedback onPress={onClose}>
        <View style={styles.overlay}>
          <TouchableWithoutFeedback>
            <View style={styles.modalContainer}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>{trip.trip_number}</Text>
                <TouchableOpacity onPress={onClose} style={styles.closeButton}>
                  <Text style={styles.closeIcon}>✕</Text>
                </TouchableOpacity>
              </View>

              <View style={styles.tripInfo}>
                <Text style={styles.infoText}>
                  📍 {trip.start_location || 'Start'} → {trip.end_location || 'Destination'}
                </Text>
                <Text style={[styles.statusText, { color: trip.status === 'ongoing' ? '#10b981' : '#f59e0b' }]}>
                  Status: {trip.status}
                </Text>
              </View>

              {/* Navigation Options - Disabled if not in truck */}
              <View style={styles.optionsContainer}>
                <Text style={styles.optionsTitle}>Navigation Options</Text>
                
                {!isInTruck && (
                  <View style={styles.warningContainer}>
                    <Text style={styles.warningText}>
                      ⚠️ You must be INSIDE the truck to use navigation
                    </Text>
                  </View>
                )}

                <TouchableOpacity
                  style={[styles.optionButton, !isInTruck && styles.optionButtonDisabled]}
                  onPress={onOpenGoogleMaps}
                  disabled={!isInTruck}
                >
                  <Text style={styles.optionIcon}>🌐</Text>
                  <View style={styles.optionTextContainer}>
                    <Text style={[styles.optionText, !isInTruck && styles.optionTextDisabled]}>
                      Open Google Maps
                    </Text>
                    <Text style={styles.optionSubtext}>
                      {isInTruck ? 'Navigate with Google Maps' : '🔒 Locked - Get in truck'}
                    </Text>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.optionButton, !isInTruck && styles.optionButtonDisabled]}
                  onPress={onNavigateInApp}
                  disabled={!isInTruck}
                >
                  <Text style={styles.optionIcon}>📱</Text>
                  <View style={styles.optionTextContainer}>
                    <Text style={[styles.optionText, !isInTruck && styles.optionTextDisabled]}>
                      Navigate In-App
                    </Text>
                    <Text style={styles.optionSubtext}>
                      {isInTruck ? 'Use built-in navigation' : '🔒 Locked - Get in truck'}
                    </Text>
                  </View>
                </TouchableOpacity>
              </View>

              <TouchableOpacity style={styles.cancelButton} onPress={onClose}>
                <Text style={styles.cancelButtonText}>Close</Text>
              </TouchableOpacity>
            </View>
          </TouchableWithoutFeedback>
        </View>
      </TouchableWithoutFeedback>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalContainer: {
    backgroundColor: '#fff',
    borderRadius: 20,
    padding: 20,
    width: '90%',
    maxWidth: 400,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  closeButton: {
    padding: 4,
  },
  closeIcon: {
    fontSize: 20,
    color: '#6b7280',
  },
  tripInfo: {
    backgroundColor: '#f3f4f6',
    padding: 12,
    borderRadius: 10,
    marginBottom: 16,
  },
  infoText: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 4,
  },
  statusText: {
    fontSize: 14,
    fontWeight: '500',
  },
  optionsContainer: {
    marginBottom: 16,
  },
  optionsTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
    marginBottom: 10,
  },
  warningContainer: {
    backgroundColor: '#fef3c7',
    padding: 12,
    borderRadius: 10,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#f59e0b',
  },
  warningText: {
    color: '#92400e',
    fontSize: 13,
    fontWeight: '500',
    textAlign: 'center',
  },
  optionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    backgroundColor: '#f8fafc',
    borderRadius: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#e5e7eb',
  },
  optionButtonDisabled: {
    opacity: 0.6,
    backgroundColor: '#f1f5f9',
  },
  optionIcon: {
    fontSize: 24,
    marginRight: 12,
  },
  optionTextContainer: {
    flex: 1,
  },
  optionText: {
    fontSize: 16,
    fontWeight: '500',
    color: '#1f2937',
  },
  optionTextDisabled: {
    color: '#9ca3af',
  },
  optionSubtext: {
    fontSize: 12,
    color: '#6b7280',
    marginTop: 2,
  },
  cancelButton: {
    padding: 14,
    alignItems: 'center',
    backgroundColor: '#f3f4f6',
    borderRadius: 12,
  },
  cancelButtonText: {
    fontSize: 16,
    color: '#6b7280',
    fontWeight: '500',
  },
});