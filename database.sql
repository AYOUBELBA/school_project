-- ============================================
-- TRUCK FLEET MANAGEMENT SYSTEM - COMPLETE SCHEMA
-- PostgreSQL
-- Version: 2.0 (Updated July 2026)
-- ============================================

-- Enable UUID extension for secure tokens
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================
-- 1. COMPANIES TABLE (Multi-tenant)
-- ============================================
CREATE TABLE companies (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner_id INTEGER,
    subscription_plan VARCHAR(50) DEFAULT 'standard',
    max_drivers INTEGER DEFAULT 5,
    max_trucks INTEGER DEFAULT 5,
    max_managers INTEGER DEFAULT 2,
    subscription_status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- ============================================
-- 2. USERS TABLE
-- ============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) CHECK (role IN ('super_admin', 'owner', 'manager', 'driver')) DEFAULT 'driver',
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 3. DRIVERS TABLE
-- ============================================
CREATE TABLE drivers (
    id SERIAL PRIMARY KEY,
    user_id INTEGER UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_expiry DATE NOT NULL,
    status VARCHAR(20) CHECK (status IN ('available', 'on_trip', 'off')) DEFAULT 'available',
    hire_date DATE,
    address TEXT,
    is_in_truck BOOLEAN DEFAULT FALSE,
    location_source VARCHAR(20) DEFAULT 'unknown',
    last_status_update TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 4. TRUCKS TABLE
-- ============================================
CREATE TABLE trucks (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    plate_number VARCHAR(20) UNIQUE NOT NULL,
    model VARCHAR(50) NOT NULL,
    brand VARCHAR(50),
    year INTEGER,
    capacity_kg DECIMAL(10,2),
    fuel_type VARCHAR(20) CHECK (fuel_type IN ('diesel', 'petrol', 'electric', 'hybrid')) DEFAULT 'diesel',
    status VARCHAR(20) CHECK (status IN ('active', 'maintenance', 'inactive')) DEFAULT 'active',
    current_driver_id INTEGER REFERENCES drivers(id) ON DELETE SET NULL,
    current_location_lat DECIMAL(10,8),
    current_location_lng DECIMAL(11,8),
    last_maintenance_date DATE,
    odometer_km INTEGER DEFAULT 0,
    last_location_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 5. ROUTES TABLE
-- ============================================
CREATE TABLE routes (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    start_point VARCHAR(255) NOT NULL,
    end_point VARCHAR(255) NOT NULL,
    start_lat DECIMAL(10,8),
    start_lng DECIMAL(11,8),
    end_lat DECIMAL(10,8),
    end_lng DECIMAL(11,8),
    estimated_distance_km DECIMAL(10,2),
    estimated_duration_min INTEGER,
    waypoints JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 6. TRIPS TABLE
-- ============================================
CREATE TABLE trips (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    trip_number VARCHAR(50) UNIQUE NOT NULL,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    driver_id INTEGER NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
    route_id INTEGER REFERENCES routes(id) ON DELETE SET NULL,
    client_name VARCHAR(100),
    client_phone VARCHAR(20),
    start_location VARCHAR(255),
    end_location VARCHAR(255),
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    status VARCHAR(20) CHECK (status IN ('planned', 'ongoing', 'completed', 'cancelled')) DEFAULT 'planned',
    priority VARCHAR(20) CHECK (priority IN ('low', 'medium', 'high')) DEFAULT 'medium',
    waypoints JSONB,
    current_waypoint_index INTEGER DEFAULT 0,
    navigation_started BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 7. SHIPMENTS/CARGO TABLE
-- ============================================
CREATE TABLE shipments (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    shipment_number VARCHAR(50) UNIQUE NOT NULL,
    trip_id INTEGER NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
    description TEXT,
    weight_kg DECIMAL(10,2),
    quantity INTEGER,
    dimensions VARCHAR(100),
    cargo_type VARCHAR(50),
    status VARCHAR(20) CHECK (status IN ('pending', 'loaded', 'in_transit', 'delivered')) DEFAULT 'pending',
    delivery_confirmed_at TIMESTAMP,
    receiver_name VARCHAR(100),
    receiver_signature TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 8. TRUCK LOCATIONS (GPS Tracking)
-- ============================================
CREATE TABLE truck_locations (
    id SERIAL PRIMARY KEY,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    speed_kmh DECIMAL(5,2),
    heading INTEGER,
    accuracy_m INTEGER,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 9. SCHEDULES (Future planned trips)
-- ============================================
CREATE TABLE schedules (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    driver_id INTEGER NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
    route_id INTEGER REFERENCES routes(id) ON DELETE SET NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME,
    repeat_type VARCHAR(20) CHECK (repeat_type IN ('none', 'daily', 'weekly', 'monthly')) DEFAULT 'none',
    status VARCHAR(20) CHECK (status IN ('pending', 'confirmed', 'cancelled')) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 10. TRUCK MAINTENANCE (Enhanced with workflow)
-- ============================================
CREATE TABLE truck_maintenance (
    id SERIAL PRIMARY KEY,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2),
    maintenance_date DATE NOT NULL,
    next_due_date DATE,
    odometer_at_service INTEGER,
    performed_by VARCHAR(100),
    status VARCHAR(20) CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled')) DEFAULT 'scheduled',
    priority VARCHAR(20) CHECK (priority IN ('low', 'medium', 'high', 'critical')) DEFAULT 'medium',
    assigned_to INTEGER REFERENCES users(id) ON DELETE SET NULL,
    estimated_cost DECIMAL(10,2),
    actual_cost DECIMAL(10,2),
    completion_notes TEXT,
    notes TEXT,
    location_lat DECIMAL(10,8),
    location_lng DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 11. INCIDENTS/ACCIDENTS
-- ============================================
CREATE TABLE incidents (
    id SERIAL PRIMARY KEY,
    incident_number VARCHAR(50) UNIQUE NOT NULL,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    driver_id INTEGER NOT NULL REFERENCES drivers(id) ON DELETE CASCADE,
    incident_date TIMESTAMP NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity VARCHAR(20) CHECK (severity IN ('low', 'medium', 'high', 'critical')) DEFAULT 'medium',
    reported_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    resolution_notes TEXT,
    resolved_at TIMESTAMP,
    cost_impact DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 12. FUEL RECORDS (With invoice upload)
-- ============================================
CREATE TABLE fuel_records (
    id SERIAL PRIMARY KEY,
    truck_id INTEGER NOT NULL REFERENCES trucks(id) ON DELETE CASCADE,
    liters DECIMAL(10,2) NOT NULL,
    cost_per_liter DECIMAL(10,2),
    total_cost DECIMAL(10,2),
    odometer_km INTEGER,
    fuel_station VARCHAR(100),
    fuel_date DATE NOT NULL,
    fuel_time TIME,
    invoice_number VARCHAR(100) UNIQUE,
    receipt_image VARCHAR(255),
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 13. NOTIFICATIONS
-- ============================================
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) CHECK (type IN ('info', 'warning', 'alert', 'success')) DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 14. ACTIVITY LOGS
-- ============================================
CREATE TABLE activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    old_data JSONB,
    new_data JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 15. INVITATIONS
-- ============================================
CREATE TABLE invitations (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- 16. PASSWORD RESETS
-- ============================================
CREATE TABLE password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 17. DOCUMENTS (New!)
-- ============================================
CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INTEGER NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER,
    mime_type VARCHAR(100),
    uploaded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- INDEXES
-- ============================================
-- Users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_company ON users(company_id);

-- Companies
CREATE INDEX idx_companies_owner ON companies(owner_id);
CREATE INDEX idx_companies_status ON companies(subscription_status);

-- Drivers
CREATE INDEX idx_drivers_company ON drivers(company_id);
CREATE INDEX idx_drivers_status ON drivers(status);
CREATE INDEX idx_drivers_license_expiry ON drivers(license_expiry);

-- Trucks
CREATE INDEX idx_trucks_company ON trucks(company_id);
CREATE INDEX idx_trucks_status ON trucks(status);
CREATE INDEX idx_trucks_plate ON trucks(plate_number);
CREATE INDEX idx_trucks_driver ON trucks(current_driver_id);

-- Trips
CREATE INDEX idx_trips_company ON trips(company_id);
CREATE INDEX idx_trips_status ON trips(status);
CREATE INDEX idx_trips_truck ON trips(truck_id);
CREATE INDEX idx_trips_driver ON trips(driver_id);
CREATE INDEX idx_trips_trip_number ON trips(trip_number);

-- Locations
CREATE INDEX idx_locations_truck_time ON truck_locations(truck_id, recorded_at);

-- Maintenance
CREATE INDEX idx_maintenance_truck ON truck_maintenance(truck_id);
CREATE INDEX idx_maintenance_next_due ON truck_maintenance(next_due_date);
CREATE INDEX idx_maintenance_status ON truck_maintenance(status);

-- Notifications
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- Activity Logs
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);

-- Invitations
CREATE INDEX idx_invitations_token ON invitations(token);
CREATE INDEX idx_invitations_email ON invitations(email);
CREATE INDEX idx_invitations_company ON invitations(company_id);

-- Fuel Records
CREATE INDEX idx_fuel_truck_date ON fuel_records(truck_id, fuel_date);
CREATE INDEX idx_fuel_invoice ON fuel_records(invoice_number);

-- Password Resets
CREATE INDEX idx_password_resets_email ON password_resets(email);
CREATE INDEX idx_password_resets_token ON password_resets(token);

-- Documents
CREATE INDEX idx_documents_entity ON documents(entity_type, entity_id);
CREATE INDEX idx_documents_company ON documents(company_id);

-- ============================================
-- TRIGGERS
-- ============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_companies_updated_at BEFORE UPDATE ON companies
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_drivers_updated_at BEFORE UPDATE ON drivers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_trucks_updated_at BEFORE UPDATE ON trucks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_trips_updated_at BEFORE UPDATE ON trips
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_maintenance_updated_at BEFORE UPDATE ON truck_maintenance
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- HELPER FUNCTIONS
-- ============================================
CREATE OR REPLACE FUNCTION check_company_limit(p_company_id INTEGER, p_type VARCHAR)
RETURNS TABLE(allowed BOOLEAN, message TEXT) AS $$
DECLARE
    v_current_count INTEGER;
    v_max_limit INTEGER;
    v_plan VARCHAR;
BEGIN
    SELECT subscription_plan INTO v_plan FROM companies WHERE id = p_company_id;
    
    IF v_plan = 'extra' THEN
        RETURN QUERY SELECT TRUE, 'Unlimited'::TEXT;
        RETURN;
    END IF;
    
    IF p_type = 'driver' THEN
        SELECT COUNT(*) INTO v_current_count FROM drivers WHERE company_id = p_company_id;
        SELECT max_drivers INTO v_max_limit FROM companies WHERE id = p_company_id;
    ELSIF p_type = 'truck' THEN
        SELECT COUNT(*) INTO v_current_count FROM trucks WHERE company_id = p_company_id;
        SELECT max_trucks INTO v_max_limit FROM companies WHERE id = p_company_id;
    ELSIF p_type = 'manager' THEN
        SELECT COUNT(*) INTO v_current_count FROM users WHERE company_id = p_company_id AND role = 'manager';
        SELECT max_managers INTO v_max_limit FROM companies WHERE id = p_company_id;
    END IF;
    
    IF v_current_count >= v_max_limit THEN
        RETURN QUERY SELECT FALSE, format('You have reached your %s limit (%s).', p_type, v_max_limit);
    ELSE
        RETURN QUERY SELECT TRUE, 'OK'::TEXT;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- DEFAULT DATA
-- ============================================
INSERT INTO companies (name, subscription_plan, subscription_status, is_active) 
VALUES ('Default Company', 'extra', 'active', true);

-- Super admin (password: SuperAdmin123!)
INSERT INTO users (username, email, password_hash, role, company_id, is_active, created_at) 
VALUES ('super_admin', 'super@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, true, CURRENT_TIMESTAMP);

UPDATE companies SET owner_id = 1 WHERE id = 1;

-- ============================================
-- VERIFICATION QUERY
-- ============================================
-- Run this to verify all tables were created:
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;