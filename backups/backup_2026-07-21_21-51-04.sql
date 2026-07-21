-- ============================================================
-- DATABASE BACKUP - 2026-07-21 21:51:04
-- Database: truck_fleet_db
-- Schema only: YES
-- ============================================================

SET session_replication_role = 'replica';

-- Table: activity_logs
DROP TABLE IF EXISTS activity_logs CASCADE;
CREATE TABLE activity_logs (
    id integer NOT NULL DEFAULT nextval('activity_logs_id_seq'::regclass),
    user_id integer,
    action character varying(255) NOT NULL,
    entity_type character varying(50),
    entity_id integer,
    old_data jsonb,
    new_data jsonb,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.activity_logs_id_seq', COALESCE((SELECT MAX(id) FROM activity_logs), 1), true);

-- Table: companies
DROP TABLE IF EXISTS companies CASCADE;
CREATE TABLE companies (
    id integer NOT NULL DEFAULT nextval('companies_id_seq'::regclass),
    name character varying(255) NOT NULL,
    owner_id integer,
    subscription_plan character varying(50) DEFAULT 'standard'::character varying,
    max_drivers integer DEFAULT 5,
    max_trucks integer DEFAULT 5,
    max_managers integer DEFAULT 2,
    subscription_status character varying(50) DEFAULT 'active'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_active boolean DEFAULT true,
    PRIMARY KEY (id)
);

SELECT setval('public.companies_id_seq', COALESCE((SELECT MAX(id) FROM companies), 1), true);

-- Table: documents
DROP TABLE IF EXISTS documents CASCADE;
CREATE TABLE documents (
    id integer NOT NULL DEFAULT nextval('documents_id_seq'::regclass),
    company_id integer NOT NULL,
    entity_type character varying(50) NOT NULL,
    entity_id integer NOT NULL,
    document_type character varying(50) NOT NULL,
    file_name character varying(255) NOT NULL,
    file_path character varying(500) NOT NULL,
    file_size integer,
    mime_type character varying(100),
    uploaded_by integer,
    description text,
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.documents_id_seq', COALESCE((SELECT MAX(id) FROM documents), 1), true);

-- Table: drivers
DROP TABLE IF EXISTS drivers CASCADE;
CREATE TABLE drivers (
    id integer NOT NULL DEFAULT nextval('drivers_id_seq'::regclass),
    user_id integer,
    full_name character varying(100) NOT NULL,
    phone character varying(20),
    license_number character varying(50) NOT NULL,
    license_expiry date NOT NULL,
    status character varying(20) DEFAULT 'available'::character varying,
    hire_date date,
    address text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    location_source character varying(20) DEFAULT 'unknown'::character varying,
    is_in_truck boolean DEFAULT false,
    last_status_update timestamp without time zone,
    PRIMARY KEY (id)
);

SELECT setval('public.drivers_id_seq', COALESCE((SELECT MAX(id) FROM drivers), 1), true);

-- Table: fuel_records
DROP TABLE IF EXISTS fuel_records CASCADE;
CREATE TABLE fuel_records (
    id integer NOT NULL DEFAULT nextval('fuel_records_id_seq'::regclass),
    truck_id integer NOT NULL,
    liters numeric NOT NULL,
    cost_per_liter numeric,
    total_cost numeric,
    odometer_km integer,
    fuel_station character varying(100),
    fuel_date date NOT NULL,
    fuel_time time without time zone,
    receipt_image character varying(255),
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    invoice_number character varying(100),
    PRIMARY KEY (id)
);

SELECT setval('public.fuel_records_id_seq', COALESCE((SELECT MAX(id) FROM fuel_records), 1), true);

-- Table: incidents
DROP TABLE IF EXISTS incidents CASCADE;
CREATE TABLE incidents (
    id integer NOT NULL DEFAULT nextval('incidents_id_seq'::regclass),
    incident_number character varying(50) NOT NULL,
    truck_id integer NOT NULL,
    driver_id integer NOT NULL,
    incident_date timestamp without time zone NOT NULL,
    location character varying(255),
    description text NOT NULL,
    severity character varying(20) DEFAULT 'medium'::character varying,
    reported_by integer,
    resolution_notes text,
    resolved_at timestamp without time zone,
    cost_impact numeric,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.incidents_id_seq', COALESCE((SELECT MAX(id) FROM incidents), 1), true);

-- Table: invitations
DROP TABLE IF EXISTS invitations CASCADE;
CREATE TABLE invitations (
    id integer NOT NULL DEFAULT nextval('invitations_id_seq'::regclass),
    company_id integer NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(50) NOT NULL,
    token character varying(255) NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    is_used boolean DEFAULT false,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_by integer,
    PRIMARY KEY (id)
);

SELECT setval('public.invitations_id_seq', COALESCE((SELECT MAX(id) FROM invitations), 1), true);

-- Table: notifications
DROP TABLE IF EXISTS notifications CASCADE;
CREATE TABLE notifications (
    id integer NOT NULL DEFAULT nextval('notifications_id_seq'::regclass),
    user_id integer NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    type character varying(20) DEFAULT 'info'::character varying,
    is_read boolean DEFAULT false,
    link character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.notifications_id_seq', COALESCE((SELECT MAX(id) FROM notifications), 1), true);

-- Table: routes
DROP TABLE IF EXISTS routes CASCADE;
CREATE TABLE routes (
    id integer NOT NULL DEFAULT nextval('routes_id_seq'::regclass),
    name character varying(100) NOT NULL,
    start_point character varying(255) NOT NULL,
    end_point character varying(255) NOT NULL,
    start_lat numeric,
    start_lng numeric,
    end_lat numeric,
    end_lng numeric,
    estimated_distance_km numeric,
    estimated_duration_min integer,
    waypoints jsonb,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    PRIMARY KEY (id)
);

SELECT setval('public.routes_id_seq', COALESCE((SELECT MAX(id) FROM routes), 1), true);

-- Table: schedules
DROP TABLE IF EXISTS schedules CASCADE;
CREATE TABLE schedules (
    id integer NOT NULL DEFAULT nextval('schedules_id_seq'::regclass),
    truck_id integer NOT NULL,
    driver_id integer NOT NULL,
    route_id integer,
    scheduled_date date NOT NULL,
    scheduled_time time without time zone,
    repeat_type character varying(20) DEFAULT 'none'::character varying,
    status character varying(20) DEFAULT 'pending'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    PRIMARY KEY (id)
);

SELECT setval('public.schedules_id_seq', COALESCE((SELECT MAX(id) FROM schedules), 1), true);

-- Table: shipments
DROP TABLE IF EXISTS shipments CASCADE;
CREATE TABLE shipments (
    id integer NOT NULL DEFAULT nextval('shipments_id_seq'::regclass),
    shipment_number character varying(50) NOT NULL,
    trip_id integer NOT NULL,
    description text,
    weight_kg numeric,
    quantity integer,
    dimensions character varying(100),
    cargo_type character varying(50),
    status character varying(20) DEFAULT 'pending'::character varying,
    delivery_confirmed_at timestamp without time zone,
    receiver_name character varying(100),
    receiver_signature text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    PRIMARY KEY (id)
);

SELECT setval('public.shipments_id_seq', COALESCE((SELECT MAX(id) FROM shipments), 1), true);

-- Table: trips
DROP TABLE IF EXISTS trips CASCADE;
CREATE TABLE trips (
    id integer NOT NULL DEFAULT nextval('trips_id_seq'::regclass),
    trip_number character varying(50) NOT NULL,
    truck_id integer NOT NULL,
    driver_id integer NOT NULL,
    route_id integer,
    client_name character varying(100),
    client_phone character varying(20),
    start_location character varying(255),
    end_location character varying(255),
    start_time timestamp without time zone,
    end_time timestamp without time zone,
    status character varying(20) DEFAULT 'planned'::character varying,
    priority character varying(20) DEFAULT 'medium'::character varying,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    company_id integer NOT NULL,
    waypoints jsonb,
    current_waypoint_index integer,
    navigation_started boolean DEFAULT false,
    start_lat numeric,
    start_lng numeric,
    end_lat numeric,
    end_lng numeric,
    PRIMARY KEY (id)
);

SELECT setval('public.trips_id_seq', COALESCE((SELECT MAX(id) FROM trips), 1), true);

-- Table: truck_locations
DROP TABLE IF EXISTS truck_locations CASCADE;
CREATE TABLE truck_locations (
    id integer NOT NULL DEFAULT nextval('truck_locations_id_seq'::regclass),
    truck_id integer NOT NULL,
    latitude numeric NOT NULL,
    longitude numeric NOT NULL,
    speed_kmh numeric,
    heading integer,
    accuracy_m integer,
    recorded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.truck_locations_id_seq', COALESCE((SELECT MAX(id) FROM truck_locations), 1), true);

-- Table: truck_maintenance
DROP TABLE IF EXISTS truck_maintenance CASCADE;
CREATE TABLE truck_maintenance (
    id integer NOT NULL DEFAULT nextval('truck_maintenance_id_seq'::regclass),
    truck_id integer NOT NULL,
    maintenance_type character varying(100) NOT NULL,
    description text,
    cost numeric,
    maintenance_date date NOT NULL,
    next_due_date date,
    odometer_at_service integer,
    performed_by character varying(100),
    status character varying(20) DEFAULT 'scheduled'::character varying,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    location_lat numeric,
    location_lng numeric,
    company_id integer NOT NULL,
    assigned_to integer,
    estimated_cost numeric,
    actual_cost numeric,
    completion_notes text,
    priority character varying(20) DEFAULT 'medium'::character varying,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

SELECT setval('public.truck_maintenance_id_seq', COALESCE((SELECT MAX(id) FROM truck_maintenance), 1), true);

-- Table: trucks
DROP TABLE IF EXISTS trucks CASCADE;
CREATE TABLE trucks (
    id integer NOT NULL DEFAULT nextval('trucks_id_seq'::regclass),
    plate_number character varying(20) NOT NULL,
    model character varying(50) NOT NULL,
    brand character varying(50),
    year integer,
    capacity_kg numeric,
    fuel_type character varying(20) DEFAULT 'diesel'::character varying,
    status character varying(20) DEFAULT 'active'::character varying,
    current_driver_id integer,
    current_location_lat numeric,
    current_location_lng numeric,
    last_maintenance_date date,
    odometer_km integer,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    last_location_update timestamp without time zone,
    company_id integer NOT NULL,
    PRIMARY KEY (id)
);

SELECT setval('public.trucks_id_seq', COALESCE((SELECT MAX(id) FROM trucks), 1), true);

-- Table: users
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
    id integer NOT NULL DEFAULT nextval('users_id_seq'::regclass),
    username character varying(50) NOT NULL,
    email character varying(100) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'driver'::character varying,
    is_active boolean DEFAULT true,
    last_login timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    is_super_admin boolean DEFAULT false,
    company_id integer NOT NULL,
    PRIMARY KEY (id)
);

SELECT setval('public.users_id_seq', COALESCE((SELECT MAX(id) FROM users), 1), true);

SET session_replication_role = 'origin';

-- ============================================================
-- BACKUP COMPLETED AT 2026-07-21 21:51:04
-- ============================================================
