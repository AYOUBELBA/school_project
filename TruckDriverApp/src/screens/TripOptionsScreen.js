import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Modal,
} from 'react-native';

export default function TripOptionsScreen({ visible, onClose, onNavigateInApp, onOpenGoogleMaps, trip }) {
  if (!trip) return null;

  return (
    <Modal
      animationType="slide"
      transparent={true}
      visible={visible}
      onRequestClose={onClose}
    >
      <View style={styles.modalOverlay}>
        <View style={styles.modalContent}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Trip #{trip.trip_number}</Text>
            <TouchableOpacity onPress={onClose} style={styles.closeButton}>
              <Text style={styles.closeButtonText}>✕</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.tripInfo}>
            <Text style={styles.routeText}>
              📍 {trip.start_location || 'Start'} → {trip.end_location || 'Destination'}
            </Text>
            <Text style={styles.statusText}>Status: {trip.status}</Text>
          </View>

          <View style={styles.divider} />

          <Text style={styles.optionsTitle}>Choose Navigation Method</Text>

          <TouchableOpacity style={[styles.optionButton, styles.inAppButton]} onPress={onNavigateInApp}>
            <Text style={styles.optionIcon}>🗺️</Text>
            <View style={styles.optionTextContainer}>
              <Text style={styles.optionTitle}>In-App Navigation</Text>
              <Text style={styles.optionDescription}>Use built-in map with real-time tracking</Text>
            </View>
            <Text style={styles.arrowIcon}>→</Text>
          </TouchableOpacity>

          <TouchableOpacity style={[styles.optionButton, styles.googleButton]} onPress={onOpenGoogleMaps}>
            <Text style={styles.optionIcon}>📍</Text>
            <View style={styles.optionTextContainer}>
              <Text style={styles.optionTitle}>Google Maps</Text>
              <Text style={styles.optionDescription}>Full turn-by-turn voice navigation</Text>
            </View>
            <Text style={styles.arrowIcon}>→</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.cancelButton} onPress={onClose}>
            <Text style={styles.cancelButtonText}>Cancel</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    padding: 20,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 15,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#1f2937',
  },
  closeButton: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: '#f3f4f6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  closeButtonText: {
    fontSize: 16,
    color: '#6b7280',
  },
  tripInfo: {
    backgroundColor: '#f3f4f6',
    padding: 12,
    borderRadius: 10,
    marginBottom: 15,
  },
  routeText: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 5,
  },
  statusText: {
    fontSize: 12,
    color: '#6b7280',
  },
  divider: {
    height: 1,
    backgroundColor: '#e5e7eb',
    marginVertical: 15,
  },
  optionsTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#6b7280',
    marginBottom: 12,
  },
  optionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 15,
    borderRadius: 12,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e5e7eb',
    backgroundColor: '#fff',
  },
  inAppButton: {
    backgroundColor: '#eff6ff',
    borderColor: '#bfdbfe',
  },
  googleButton: {
    backgroundColor: '#fff',
    borderColor: '#e5e7eb',
  },
  optionIcon: {
    fontSize: 28,
    marginRight: 15,
  },
  optionTextContainer: {
    flex: 1,
  },
  optionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1f2937',
    marginBottom: 2,
  },
  optionDescription: {
    fontSize: 12,
    color: '#6b7280',
  },
  arrowIcon: {
    fontSize: 18,
    color: '#9ca3af',
  },
  cancelButton: {
    padding: 14,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 8,
    backgroundColor: '#f3f4f6',
  },
  cancelButtonText: {
    fontSize: 15,
    fontWeight: '500',
    color: '#6b7280',
  },
});