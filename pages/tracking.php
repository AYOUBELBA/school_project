<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Truck Tracking | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Marker Cluster -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <style>
        #map {
            height: calc(100vh - 200px);
            width: 100%;
            border-radius: 12px;
            z-index: 1;
        }
        .map-container {
            position: relative;
        }
        .filter-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 250px;
        }
        .truck-card {
            transition: all 0.2s ease;
        }
        .truck-card:hover {
            transform: translateX(5px);
            background: #f3f4f6;
        }
        .status-active { color: #10b981; }
        .status-maintenance { color: #f59e0b; }
        .status-inactive { color: #6b7280; }
        .legend {
            position: absolute;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(31, 41, 55, 0.95);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            color: #f3f4f6;
            backdrop-filter: blur(4px);
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .refresh-indicator {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
            @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }

        /* DO NOT REMOVE */
        .leaflet-control-attribution {
            display: none !important;
        }

        .custom-map-credit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            pointer-events: none;
            color: #9ca3af;
        }
        /* Prevent popup from closing when clicking inside */
        .leaflet-popup-content {
            pointer-events: auto;
        }

        .leaflet-popup-close-button {
            cursor: pointer;
        }
        /* Dark mode marker glow */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .custom-div-icon div {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-4">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Live Truck Tracking</h2>
            <p class="text-gray-500">Real-time GPS tracking of your fleet</p>
        </div>
        
        <div class="map-container relative">
            <!-- Filter Panel -->
            <div class="filter-panel">
                <h4 class="font-semibold text-gray-800 mb-3">Filters</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Truck Status</label>
                        <select id="truckStatusFilter" class="w-full px-2 py-1 text-sm border rounded-lg">
                            <option value="all">All Trucks</option>
                            <option value="active">Active</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Trip Status</label>
                        <select id="tripStatusFilter" class="w-full px-2 py-1 text-sm border rounded-lg">
                            <option value="all">All Trips</option>
                            <option value="planned">Planned</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Driver</label>
                        <select id="driverFilter" class="w-full px-2 py-1 text-sm border rounded-lg">
                            <option value="all">All Drivers</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Truck</label>
                        <select id="truckFilter" class="w-full px-2 py-1 text-sm border rounded-lg">
                            <option value="all">All Trucks</option>
                        </select>
                    </div>
                    <button onclick="applyFilters()" class="w-full bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 transition">
                        Apply Filters
                    </button>
                    <button onclick="resetFilters()" class="w-full border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:bg-gray-50 transition">
                        Reset
                    </button>
                </div>
            </div>
            
            <!-- Legend (Dark mode for map only) -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: #10b981;"></div>
                    <span>Active Truck</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #f59e0b;"></div>
                    <span>Maintenance</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #6b7280;"></div>
                    <span>Inactive</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #3b82f6; border: 2px solid white; outline: 2px solid #3b82f6;"></div>
                    <span>Ongoing Trip</span>
                </div>
            </div>
            
            <!-- Refresh Indicator -->
            <div class="refresh-indicator">
                <i id="refreshIcon" class="ri-refresh-line"></i>
                <span id="lastUpdate">Auto-refreshing...</span>
            </div>
            
            <!-- Map Container with Dark Mode Tiles -->
            <div id="map"></div>
        </div>
        
        <!-- Truck List Sidebar -->
        <div class="mt-4 bg-white rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Fleet List</h3>
                <p class="text-xs text-gray-500">Click on any truck to center map</p>
            </div>
            <div id="truckList" class="max-h-64 overflow-y-auto divide-y divide-gray-200">
                <!-- Truck list will be populated via JS -->
            </div>
        </div>
    </div>
</main>

<script>
let map;
let markers = {};
let markerCluster;
let updateInterval;
let allTrucks = [];
let currentlyOpenPopup = null;
let currentOpenTruckId = null;

function initMap() {
    map = L.map('map').setView([31.7917, -7.0926], 6);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: 'Made by YourName',
        subdomains: 'abcd',
        maxZoom: 19,
        minZoom: 3
    }).addTo(map);
    
    // Initialize marker cluster
    markerCluster = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 50
    });
    map.addLayer(markerCluster);
    
    // Load trucks
    loadTrucks();
    loadFilters();
    
    // Start auto-refresh every 10 seconds
    updateInterval = setInterval(loadTrucks, 10000);
}
// Load trucks with current locations
async function loadTrucks() {
    try {
        const statusFilter = document.getElementById('truckStatusFilter')?.value || 'all';
        const tripFilter = document.getElementById('tripStatusFilter')?.value || 'all';
        const driverFilter = document.getElementById('driverFilter')?.value || 'all';
        const truckFilter = document.getElementById('truckFilter')?.value || 'all';
        
        const url = `../api/trucks_location.php?status=${statusFilter}&trip_status=${tripFilter}&driver=${driverFilter}&truck=${truckFilter}`;
        const response = await fetch(url);
        const data = await response.json();
        
        if(data.success) {
            allTrucks = data.trucks;
            updateMap(data.trucks);
            updateTruckList(data.trucks);
            updateLastUpdateTime();
        }
    } catch(error) {
        console.error('Error loading trucks:', error);
    }
}

// Load filter dropdowns
async function loadFilters() {
    try {
        const response = await fetch('../api/get_filters.php');
        const data = await response.json();
        
        if(data.success) {
            const driverSelect = document.getElementById('driverFilter');
            const truckSelect = document.getElementById('truckFilter');
            
            data.drivers.forEach(driver => {
                const option = document.createElement('option');
                option.value = driver.id;
                option.textContent = driver.full_name;
                driverSelect.appendChild(option);
            });
            
            data.trucks.forEach(truck => {
                const option = document.createElement('option');
                option.value = truck.id;
                option.textContent = `${truck.plate_number} - ${truck.model}`;
                truckSelect.appendChild(option);
            });
        }
    } catch(error) {
        console.error('Error loading filters:', error);
    }
}

// Update map with truck markers
function updateMap(trucks) {
    // Clear all existing layers from cluster
    markerCluster.clearLayers();
    
    // Reset markers object
    markers = {};
    
    trucks.forEach(truck => {
        if(truck.latitude && truck.longitude) {
            let markerColor = '#10b981';
            let markerSize = 14;
            let hasPulse = false;
            let isStale = false;
            
            if(!truck.driver_name) {
                markerColor = '#9ca3af';
            } else if(truck.truck_status === 'inactive') {
                markerColor = '#6b7280';
            } else if(truck.truck_status === 'maintenance') {
                markerColor = '#f59e0b';
            } else if(truck.location_status === 'stale') {
                markerColor = '#8b5cf6';
                isStale = true;
            } else if(truck.truck_status === 'active' && truck.driver_name) {
                markerColor = '#10b981';
                hasPulse = true;
            }
            
            const truckIcon = L.divIcon({
                html: `<div style="
                    background-color: ${markerColor}; 
                    width: ${markerSize}px; 
                    height: ${markerSize}px; 
                    border-radius: 50%; 
                    border: 2px solid white; 
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    ${hasPulse ? 'animation: pulse 2s ease-in-out infinite;' : ''}
                "></div>`,
                className: 'custom-div-icon',
                iconSize: [markerSize + 4, markerSize + 4],
                popupAnchor: [0, -8]
            });
            
            let driverInfo = '';
            let locationInfo = '';
            
            if(!truck.driver_name) {
                driverInfo = '<span class="text-orange-500">⚠️ No driver assigned</span>';
                locationInfo = '<span class="text-gray-500">📍 Last known position</span>';
            } else if(truck.truck_status === 'inactive') {
                driverInfo = `Driver: ${truck.driver_name}`;
                locationInfo = '<span class="text-gray-500">🚫 Truck inactive</span>';
            } else if(truck.truck_status === 'maintenance') {
                driverInfo = `Driver: ${truck.driver_name}`;
                locationInfo = '<span class="text-orange-500">🔧 In maintenance</span>';
            } else if(isStale) {
                driverInfo = `Driver: ${truck.driver_name}`;
                locationInfo = `<span class="text-purple-500">⚠️ No recent updates (${truck.minutes_since_update} min ago)</span>`;
            } else {
                driverInfo = `Driver: ${truck.driver_name}`;
                locationInfo = '<span class="text-green-500">🟢 Live tracking</span>';
            }
            
            // Create marker
            const marker = L.marker([truck.latitude, truck.longitude], { icon: truckIcon });
            
            // Bind popup
            marker.bindPopup(`
                <div style="min-width: 220px;">
                    <div class="font-bold text-gray-800 text-base">${truck.plate_number}</div>
                    <div class="text-sm text-gray-600">${truck.model} ${truck.brand || ''}</div>
                    <hr class="my-2">
                    <div><i class="ri-user-line"></i> ${driverInfo}</div>
                    <div><i class="ri-speed-line"></i> Speed: ${truck.speed_kmh || 0} km/h</div>
                    <div><i class="ri-truck-line"></i> Status: <span class="status-${truck.truck_status}">${truck.truck_status}</span></div>
                    <div class="text-xs mt-2">
                        ${locationInfo}
                    </div>
                    <div class="text-xs text-gray-400">Last update: ${truck.last_update || 'Never'}</div>
                </div>
            `, {
                autoPan: false,
                closeOnClick: false,
                autoClose: false,
                closeButton: true
            });
            
            // Store reference
            markers[truck.id] = marker;
            
            // Add to cluster
            markerCluster.addLayer(marker);
        }
    });
    
    // Log for debugging
    console.log('Markers added:', Object.keys(markers).length);
}
// Update truck list sidebar
function updateTruckList(trucks) {
    const container = document.getElementById('truckList');
    if(!container) return;
    
    container.innerHTML = trucks.map(truck => {
        let statusIcon = '';
        let statusColor = '';
        let locationText = '';
        
        if(!truck.driver_name) {
            statusIcon = '⚠️';
            statusColor = 'text-orange-500';
            locationText = '<span class="text-gray-400">📍 Last known position</span>';
        } else if(truck.truck_status === 'inactive') {
            statusIcon = '❌';
            statusColor = 'text-gray-500';
            locationText = '<span class="text-gray-400">🚫 Inactive</span>';
        } else if(truck.truck_status === 'maintenance') {
            statusIcon = '🔧';
            statusColor = 'text-orange-500';
            locationText = '<span class="text-orange-500">🔧 Maintenance</span>';
        } else if(truck.location_status === 'stale') {
            statusIcon = '⚠️';
            statusColor = 'text-purple-500';
            locationText = `<span class="text-purple-500">⚠️ No update (${truck.minutes_since_update} min)</span>`;
        } else if(truck.truck_status === 'active' && truck.driver_name) {
            statusIcon = '✅';
            statusColor = 'text-green-500';
            locationText = '<span class="text-green-500">🟢 Live</span>';
        } else {
            statusIcon = '❓';
            statusColor = 'text-gray-500';
            locationText = '<span class="text-gray-400">📍 Unknown</span>';
        }
        
        return `
            <div class="truck-card p-3 cursor-pointer hover:bg-gray-50 transition" onclick="centerOnTruck(${truck.latitude || 0}, ${truck.longitude || 0}, '${truck.plate_number}', ${truck.id})">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium text-gray-800">${statusIcon} ${truck.plate_number}</div>
                        <div class="text-xs text-gray-500">${truck.model}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-medium ${statusColor}">${truck.truck_status}</div>
                        <div class="text-xs text-gray-500">${truck.speed_kmh || 0} km/h</div>
                    </div>
                </div>
                <div class="text-xs text-gray-600 mt-1">
                    <i class="ri-user-line"></i> ${truck.driver_name || '<span class="text-orange-500">No driver</span>'}
                </div>
                <div class="text-xs mt-1">
                    ${locationText}
                </div>
            </div>
        `;
    }).join('');
}
// Center map on a specific truck
function centerOnTruck(lat, lng, plate, truckId) {
    if(lat && lng) {
        map.setView([lat, lng], 14);
        if(truckId && markers[truckId]) {
            markers[truckId].openPopup();
            currentOpenTruckId = truckId;
        } else {
            // Fallback: search by plate number
            Object.values(markers).forEach(marker => {
                if(marker.getPopup() && marker.getPopup().getContent().includes(plate)) {
                    marker.openPopup();
                    currentOpenTruckId = Object.keys(markers).find(key => markers[key] === marker);
                }
            });
        }
    }
}
// Apply filters
function applyFilters() {
    loadTrucks();
}

// Reset filters
function resetFilters() {
    document.getElementById('truckStatusFilter').value = 'all';
    document.getElementById('tripStatusFilter').value = 'all';
    document.getElementById('driverFilter').value = 'all';
    document.getElementById('truckFilter').value = 'all';
    loadTrucks();
}

// Update last update time
function updateLastUpdateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    document.getElementById('lastUpdate').innerHTML = `Last update: ${timeString}`;
    
    // Animate refresh icon
    const icon = document.getElementById('refreshIcon');
    icon.classList.add('spinner');
    setTimeout(() => icon.classList.remove('spinner'), 500);
}

// Initialize map when page loads
document.addEventListener('DOMContentLoaded', initMap);
</script>
</body>
</html>