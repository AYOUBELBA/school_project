<?php
require_once '../includes/check_auth.php';

$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Fleet Tracking | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        #map { height: calc(100vh - 200px); width: 100%; border-radius: 12px; z-index: 1; background: #1a1a2e; }
        
        .stats-panel {
            position: absolute; top: 20px; left: 20px; z-index: 1000;
            background: rgba(15, 25, 35, 0.95); backdrop-filter: blur(10px);
            padding: 8px 16px; border-radius: 12px; display: flex; gap: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-item { text-align: center; }
        .stat-value { font-size: 18px; font-weight: bold; }
        .stat-label { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        
        .filter-toggle {
            position: absolute; top: 20px; right: 20px; z-index: 1000;
            background: #3b82f6; border: none; padding: 8px 14px; border-radius: 10px;
            color: white; font-weight: 500; cursor: pointer; display: flex; align-items: center; gap: 6px;
            font-size: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .legend {
            position: absolute; bottom: 20px; right: 20px; z-index: 1000;
            background: rgba(15, 25, 35, 0.95); padding: 10px 14px; border-radius: 10px;
            font-size: 10px; color: #fff; border: 1px solid rgba(255,255,255,0.1);
        }
        .legend-item { display: flex; align-items: center; margin-bottom: 5px; gap: 8px; }
        .legend-color { width: 10px; height: 10px; border-radius: 50%; }
        .legend-line { width: 20px; height: 2px; background: #3b82f6; border-radius: 2px; }
        
        .refresh-indicator {
            position: absolute; bottom: 20px; left: 20px; z-index: 1000;
            background: rgba(15, 25, 35, 0.95); padding: 5px 10px; border-radius: 16px;
            font-size: 10px; display: flex; align-items: center; gap: 6px;
            color: #94a3b8; border: 1px solid rgba(255,255,255,0.1);
        }
        .spinner { animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.8; } 100% { transform: scale(1); opacity: 1; } }
        
        .drawer-container {
            position: absolute; bottom: 20px; right: 20px; z-index: 1001; pointer-events: none;
        }
        .drawer-toggle {
            position: absolute; bottom: 0; right: 0; background: #3b82f6; border: none;
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; pointer-events: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.3); transition: all 0.2s;
        }
        .drawer-toggle:hover { background: #2563eb; transform: scale(1.05); }
        .drawer-content {
            position: absolute; bottom: 50px; right: 0; width: 280px; max-height: 350px;
            background: rgba(15, 25, 35, 0.98); backdrop-filter: blur(12px); border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1); overflow: hidden; transition: all 0.25s ease;
            opacity: 0; visibility: hidden; transform: translateY(10px); pointer-events: auto;
        }
        .drawer-content.open { opacity: 1; visibility: visible; transform: translateY(0); }
        .drawer-header {
            padding: 10px 15px; background: rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-between; align-items: center; cursor: pointer;
        }
        .drawer-header h4 { color: white; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .truck-list-items { max-height: 290px; overflow-y: auto; }
        .truck-list-item {
            padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; transition: background 0.15s;
        }
        .truck-list-item:hover { background: rgba(59,130,246,0.2); }
        .truck-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .truck-name { font-weight: 500; font-size: 12px; color: white; }
        .truck-speed { font-size: 9px; color: #94a3b8; margin-top: 2px; }
        .truck-status-text { font-size: 10px; color: #94a3b8; }
        
        .filter-sidebar {
            position: fixed; top: 0; right: -350px; width: 350px; height: 100vh;
            background: rgba(15, 25, 35, 0.98); backdrop-filter: blur(12px); z-index: 2000;
            transition: right 0.25s ease; border-left: 1px solid rgba(255,255,255,0.1);
            display: flex; flex-direction: column;
        }
        .filter-sidebar.open { right: 0; }
        .filter-header { padding: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center; }
        .filter-header h3 { color: white; font-size: 16px; font-weight: 600; }
        .close-filter { background: rgba(255,255,255,0.1); border: none; color: white; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; }
        .close-filter:hover { background: #ef4444; }
        .filter-body { flex: 1; overflow-y: auto; padding: 18px; }
        .filter-section { margin-bottom: 22px; }
        .filter-section-title {
            color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
        }
        .filter-option {
            background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 8px 12px; margin-bottom: 6px; cursor: pointer;
            display: flex; align-items: center; justify-content: space-between; transition: all 0.15s;
        }
        .filter-option:hover { background: rgba(59,130,246,0.2); border-color: #3b82f6; }
        .filter-option.active { background: #3b82f6; border-color: #3b82f6; }
        .filter-option-label { color: white; font-size: 13px; }
        .filter-option-count { color: #94a3b8; font-size: 11px; background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 12px; }
        .filter-option.active .filter-option-count { background: rgba(255,255,255,0.2); color: white; }
        .filter-actions { padding: 16px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 12px; }
        .filter-actions button { flex: 1; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-apply { background: #3b82f6; border: none; color: white; }
        .btn-reset { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; }
        
        .truck-list-items::-webkit-scrollbar { width: 3px; }
        .truck-list-items::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .truck-list-items::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 3px; }
        .filter-body::-webkit-scrollbar { width: 3px; }
        .filter-body::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .filter-body::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 3px; }
        
        .leaflet-control-attribution { display: none !important; }
        .leaflet-top.leaflet-left { top: 70px; }
        .leaflet-control-zoom { margin-top: 10px; }
        .custom-marker { background: transparent !important; border: none !important; box-shadow: none !important; }
        .leaflet-marker-icon.custom-marker { background: transparent !important; }
        .leaflet-div-icon { background: transparent !important; border: none !important; }
        .leaflet-marker-icon { background: transparent !important; }
        .leaflet-marker-pane > * { background: transparent !important; }
    </style>
</head>
<body class="bg-gray-900">

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-4">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-white">Live Fleet Tracking</h2>
            <p class="text-gray-400 text-sm">Real-time GPS tracking with route visualization</p>
        </div>
        
        <div class="map-container relative">
            <!-- Stats Panel -->
            <div class="stats-panel">
                <div class="stat-item"><span class="stat-value text-blue-400" id="statTotal">0</span><div class="stat-label">Total</div></div>
                <div class="stat-item"><span class="stat-value text-green-400" id="statActive">0</span><div class="stat-label">Active</div></div>
                <div class="stat-item"><span class="stat-value text-yellow-400" id="statMoving">0</span><div class="stat-label">Moving</div></div>
                <div class="stat-item"><span class="stat-value text-orange-400" id="statMaintenance">0</span><div class="stat-label">Maint</div></div>
                <div class="stat-item"><span class="stat-value text-purple-400" id="statOnline">0</span><div class="stat-label">Online</div></div>
            </div>
            
            <!-- Filter Button -->
            <button class="filter-toggle" onclick="toggleFilter()">
                <i class="ri-filter-3-line"></i> Filters
            </button>
            
            <!-- Legend -->
            <div class="legend">
                <div class="legend-item"><div class="legend-color" style="background: #10b981;"></div><span>Active</span></div>
                <div class="legend-item"><div class="legend-color" style="background: #8b5cf6;"></div><span>Stale</span></div>
                <div class="legend-item"><div class="legend-color" style="background: #f59e0b;"></div><span>Maintenance</span></div>
                <div class="legend-item"><div class="legend-color" style="background: #6b7280;"></div><span>Inactive</span></div>
                <div class="legend-item"><div class="legend-line"></div><span>Active Route</span></div>
            </div>
            
            <!-- Refresh Indicator -->
            <div class="refresh-indicator">
                <i id="refreshIcon" class="ri-refresh-line"></i>
                <span id="lastUpdate">Loading...</span>
            </div>
            
            <!-- Drawer -->
            <div class="drawer-container">
                <button class="drawer-toggle" onclick="toggleDrawer()">
                    <i class="ri-truck-line"></i>
                </button>
                <div class="drawer-content" id="fleetDrawer">
                    <div class="drawer-header" onclick="toggleDrawer()">
                        <h4><i class="ri-truck-line"></i> Fleet List <span id="drawerTruckCount" class="text-gray-400 text-xs">0 trucks</span></h4>
                        <i class="ri-arrow-up-s-line"></i>
                    </div>
                    <div class="truck-list-items" id="truckList">
                        <div class="truck-list-item" style="justify-content:center; color:#94a3b8; padding:20px;">Loading trucks...</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Sidebar -->
            <div id="filterSidebar" class="filter-sidebar">
                <div class="filter-header">
                    <h3><i class="ri-filter-3-line"></i> Advanced Filters</h3>
                    <button class="close-filter" onclick="toggleFilter()"><i class="ri-close-line"></i></button>
                </div>
                <div class="filter-body">
                    <!-- Status filters -->
                    <div class="filter-section">
                        <div class="filter-section-title"><i class="ri-truck-line"></i> Truck Status</div>
                        <div id="statusFilterGroup">
                            <div class="filter-option active" data-status="all" onclick="selectStatus('all')">
                                <span class="filter-option-label">🚛 All Trucks</span>
                                <span class="filter-option-count" id="countAll">0</span>
                            </div>
                            <div class="filter-option" data-status="active" onclick="selectStatus('active')">
                                <span class="filter-option-label">✅ Active</span>
                                <span class="filter-option-count" id="countActive">0</span>
                            </div>
                            <div class="filter-option" data-status="maintenance" onclick="selectStatus('maintenance')">
                                <span class="filter-option-label">🔧 Maintenance</span>
                                <span class="filter-option-count" id="countMaintenance">0</span>
                            </div>
                            <div class="filter-option" data-status="inactive" onclick="selectStatus('inactive')">
                                <span class="filter-option-label">❌ Inactive</span>
                                <span class="filter-option-count" id="countInactive">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="filter-section">
                        <div class="filter-section-title"><i class="ri-map-pin-line"></i> Trip Status</div>
                        <div id="tripFilterGroup">
                            <div class="filter-option active" data-trip="all" onclick="selectTrip('all')"><span class="filter-option-label">📋 All</span></div>
                            <div class="filter-option" data-trip="ongoing" onclick="selectTrip('ongoing')"><span class="filter-option-label">🔄 Ongoing</span></div>
                            <div class="filter-option" data-trip="planned" onclick="selectTrip('planned')"><span class="filter-option-label">📅 Planned</span></div>
                        </div>
                    </div>
                    <div class="filter-section">
                        <div class="filter-section-title"><i class="ri-speed-line"></i> Speed</div>
                        <div id="speedFilterGroup">
                            <div class="filter-option active" data-speed="all" onclick="selectSpeed('all')"><span class="filter-option-label">⚡ All</span></div>
                            <div class="filter-option" data-speed="stationary" onclick="selectSpeed('stationary')"><span class="filter-option-label">🅿️ 0 km/h</span></div>
                            <div class="filter-option" data-speed="moving" onclick="selectSpeed('moving')"><span class="filter-option-label">🚚 Moving</span></div>
                        </div>
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn-apply" onclick="applyAdvancedFilters()">Apply</button>
                    <button class="btn-reset" onclick="resetAllFilters()">Reset</button>
                </div>
            </div>
            
            <!-- Map -->
            <div id="map"></div>
        </div>
    </div>
</main>

<script>
let map, markerCluster, markers = {}, routeLines = {}, updateInterval;
let currentTrucks = [], filteredTrucks = [];
let selectedStatus = 'all', selectedTrip = 'all', selectedSpeed = 'all';

function initMap() {
    try {
        map = L.map('map').setView([31.7917, -7.0926], 6);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '© CartoDB | FleetManager',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);
        
        L.control.scale({ metric: true, imperial: false, position: 'bottomleft' }).addTo(map);
        
        markerCluster = L.markerClusterGroup({ chunkedLoading: true, maxClusterRadius: 50, disableClusteringAtZoom: 15 });
        map.addLayer(markerCluster);
        
        loadTrucks();
        updateInterval = setInterval(loadTrucks, 10000);
    } catch(e) {
        console.error('Map init error:', e);
    }
}

function toggleDrawer() { document.getElementById('fleetDrawer').classList.toggle('open'); }
function toggleFilter() { document.getElementById('filterSidebar').classList.toggle('open'); }

function selectStatus(status) {
    selectedStatus = status;
    document.querySelectorAll('#statusFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector(`#statusFilterGroup .filter-option[data-status="${status}"]`).classList.add('active');
}

function selectTrip(trip) {
    selectedTrip = trip;
    document.querySelectorAll('#tripFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector(`#tripFilterGroup .filter-option[data-trip="${trip}"]`).classList.add('active');
}

function selectSpeed(speed) {
    selectedSpeed = speed;
    document.querySelectorAll('#speedFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector(`#speedFilterGroup .filter-option[data-speed="${speed}"]`).classList.add('active');
}

function applyAdvancedFilters() {
    filteredTrucks = currentTrucks.filter(truck => {
        if(selectedStatus !== 'all' && truck.truck_status !== selectedStatus) return false;
        if(selectedTrip !== 'all' && truck.trip_status !== selectedTrip) return false;
        const speed = truck.speed_kmh || 0;
        if(selectedSpeed === 'stationary' && speed > 0) return false;
        if(selectedSpeed === 'moving' && speed === 0) return false;
        return true;
    });
    updateMap(filteredTrucks);
    updateTruckList(filteredTrucks);
    updateStats(filteredTrucks);
    toggleFilter();
}

function resetAllFilters() {
    selectedStatus = 'all'; selectedTrip = 'all'; selectedSpeed = 'all';
    document.querySelectorAll('#statusFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector('#statusFilterGroup .filter-option[data-status="all"]').classList.add('active');
    document.querySelectorAll('#tripFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector('#tripFilterGroup .filter-option[data-trip="all"]').classList.add('active');
    document.querySelectorAll('#speedFilterGroup .filter-option').forEach(opt => opt.classList.remove('active'));
    document.querySelector('#speedFilterGroup .filter-option[data-speed="all"]').classList.add('active');
    updateMap(currentTrucks);
    updateTruckList(currentTrucks);
    updateStats(currentTrucks);
    toggleFilter();
}

async function loadTrucks() {
    try {
        const response = await fetch('../api/trucks_location.php');
        const data = await response.json();
        if(data.success) {
            currentTrucks = data.trucks;
            filteredTrucks = currentTrucks;
            updateFilterCounts(currentTrucks);
            updateMap(currentTrucks);
            updateTruckList(currentTrucks);
            updateStats(currentTrucks);
            document.getElementById('lastUpdate').innerHTML = new Date().toLocaleTimeString();
            const icon = document.getElementById('refreshIcon');
            icon.classList.add('spinner');
            setTimeout(() => icon.classList.remove('spinner'), 300);
        } else {
            console.error('API error:', data);
            document.getElementById('truckList').innerHTML = '<div class="truck-list-item" style="justify-content:center; color:#ef4444; padding:20px;">No data received</div>';
        }
    } catch(error) { 
        console.error('Load error:', error);
        document.getElementById('truckList').innerHTML = '<div class="truck-list-item" style="justify-content:center; color:#ef4444; padding:20px;">Error loading trucks</div>';
    }
}

function updateFilterCounts(trucks) {
    document.getElementById('countAll').innerHTML = trucks.length;
    document.getElementById('countActive').innerHTML = trucks.filter(t => t.truck_status === 'active').length;
    document.getElementById('countMaintenance').innerHTML = trucks.filter(t => t.truck_status === 'maintenance').length;
    document.getElementById('countInactive').innerHTML = trucks.filter(t => t.truck_status === 'inactive').length;
}

function updateStats(trucks) {
    const total = trucks.length;
    const active = trucks.filter(t => t.truck_status === 'active' && t.driver_name).length;
    const moving = trucks.filter(t => (t.speed_kmh || 0) > 5).length;
    const maintenance = trucks.filter(t => t.truck_status === 'maintenance').length;
    const online = trucks.filter(t => t.location_status === 'fresh').length;
    document.getElementById('statTotal').innerHTML = total;
    document.getElementById('statActive').innerHTML = active;
    document.getElementById('statMoving').innerHTML = moving;
    document.getElementById('statMaintenance').innerHTML = maintenance;
    document.getElementById('statOnline').innerHTML = online;
    document.getElementById('drawerTruckCount').innerHTML = total + ' trucks';
}

function updateMap(trucks) {
    Object.values(routeLines).forEach(line => map.removeLayer(line));
    routeLines = {};
    markerCluster.clearLayers();
    markers = {};
    
    trucks.forEach(truck => {
        if(truck.latitude && truck.longitude) {
            let markerColor, hasPulse = false;
            if(!truck.driver_name) markerColor = '#6b7280';
            else if(truck.truck_status === 'maintenance') markerColor = '#f59e0b';
            else if(truck.location_status === 'stale') markerColor = '#8b5cf6';
            else { markerColor = '#10b981'; hasPulse = true; }
            
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color: ${markerColor}; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); ${hasPulse ? 'animation: pulse 1.5s ease-in-out infinite;' : ''}"></div>
                ${truck.speed_kmh > 0 ? `<div style="position:absolute;top:-18px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.75);padding:2px 6px;border-radius:10px;font-size:9px;color:white;white-space:nowrap;font-weight:bold;pointer-events:none;">${Math.round(truck.speed_kmh)} km/h</div>` : ''}`,
                iconSize: [20, 20],
                popupAnchor: [0, -10],
                bgSize: [0, 0]
            });
            
            const marker = L.marker([truck.latitude, truck.longitude], { icon: icon })
                .bindPopup(`
                    <div style="min-width:200px;">
                        <b style="font-size:14px;">🚛 ${truck.plate_number}</b><br>
                        👤 ${truck.driver_name || 'No driver'}<br>
                        ⚡ ${truck.speed_kmh || 0} km/h<br>
                        📊 ${truck.truck_status}<br>
                        🕐 ${truck.last_update || 'Never'}
                    </div>
                `);
            
            markerCluster.addLayer(marker);
            markers[truck.id] = marker;
            
            if(truck.trip_status === 'ongoing' && truck.route && truck.route.start_lat && truck.route.end_lat) {
                const points = [[truck.route.start_lat, truck.route.start_lng], [truck.route.end_lat, truck.route.end_lng]];
                const line = L.polyline(points, { color: '#3b82f6', weight: 4, opacity: 0.6, dashArray: '8, 8', lineCap: 'round', lineJoin: 'round' })
                    .bindPopup(`<b>📍 Route</b><br>${truck.route.start_point || 'Start'} → ${truck.route.end_point || 'End'}`);
                line.addTo(map);
                routeLines[truck.id] = line;
            }
        }
    });
}

function updateTruckList(trucks) {
    const container = document.getElementById('truckList');
    if(!container) return;
    
    if(trucks.length === 0) {
        container.innerHTML = '<div class="truck-list-item" style="justify-content:center; color:#94a3b8; padding:20px;">No trucks found</div>';
        return;
    }
    
    container.innerHTML = trucks.map(t => `
        <div class="truck-list-item" onclick="centerOnTruck(${t.latitude || 0}, ${t.longitude || 0}, ${t.id})">
            <div>
                <span class="truck-status-dot" style="background: ${!t.driver_name ? '#6b7280' : t.truck_status === 'maintenance' ? '#f59e0b' : t.location_status === 'stale' ? '#8b5cf6' : '#10b981'};"></span>
                <span class="truck-name">${t.plate_number}</span>
                <div class="truck-speed">${t.driver_name || 'No driver'} • ${t.speed_kmh || 0} km/h</div>
            </div>
            <div class="truck-status-text">${t.trip_status === 'ongoing' ? '🔄 En route' : t.trip_status === 'planned' ? '📅 Planned' : '⚪ Idle'}</div>
        </div>
    `).join('');
}

function centerOnTruck(lat, lng, id) {
    if(lat && lng && lat !== 0) { map.setView([lat, lng], 14); if(markers[id]) markers[id].openPopup(); }
}

document.addEventListener('DOMContentLoaded', initMap);
</script>
</body>
</html>