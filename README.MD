# 🚛 FleetManager - Complete Truck Fleet Management System

A production-ready, multi-tenant SaaS platform for truck fleet management with real-time GPS tracking, mobile app, subscription billing, and complete role-based access control.

## 📋 Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Installation Guide](#installation-guide)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Role-Based Access](#role-based-access)
- [Subscription Plans](#subscription-plans)
- [Mobile App Setup](#mobile-app-setup)
- [API Documentation](#api-documentation)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

## 🎯 Overview

FleetManager is a comprehensive fleet management solution that helps transportation companies track their vehicles, manage drivers, plan trips, monitor fuel consumption, and maintain their fleet - all from a single platform. Built as a multi-tenant SaaS application, it supports multiple companies with complete data isolation.

### Key Capabilities
- **Real-time GPS tracking** with live map view
- **Driver mobile app** for location sharing and trip updates
- **Complete fleet management** (trucks, drivers, trips)
- **Maintenance tracking** with cost analysis
- **Fuel consumption monitoring**
- **Multi-company support** with subscription plans
- **Role-based access** (Super Admin, Owner, Manager, Driver)

## ✨ Features

### 🏢 Multi-Tenant SaaS
- ✅ Complete company isolation with `company_id` filtering
- ✅ Subscription plans with usage limits
- ✅ Company owner dashboard
- ✅ Team member invitations
- ✅ Usage analytics and limits

### 🚚 Fleet Management
- ✅ Truck CRUD operations
- ✅ Driver management with license tracking
- ✅ Trip planning and assignment
- ✅ Route management
- ✅ Shipment/Cargo tracking

### 📍 Real-Time GPS Tracking
- ✅ Live truck locations on interactive map
- ✅ Speed monitoring
- ✅ Last known location for offline trucks
- ✅ Stale location detection (>10 min)
- ✅ Color-coded markers (Active/Inactive/Maintenance)

### 🔧 Maintenance & Incidents
- ✅ Maintenance scheduling
- ✅ Cost tracking per truck
- ✅ Incident reporting with severity levels
- ✅ Driver-submitted reports via mobile app

### ⛽ Fuel Management
- ✅ Fuel purchase logging
- ✅ Cost per liter tracking
- ✅ Fuel station records
- ✅ Monthly consumption reports

### 👥 User Management
- ✅ Role-based access (Super Admin, Owner, Manager, Driver)
- ✅ Invitation system for team members
- ✅ User activity logging
- ✅ Session management

### 📊 Reports & Analytics
- ✅ Trip statistics dashboard
- ✅ Fleet utilization charts
- ✅ Fuel consumption analytics
- ✅ Maintenance cost reports

### 📱 Mobile App (React Native)
- ✅ Driver login/authentication
- ✅ Real-time GPS tracking
- ✅ Current trip display
- ✅ Issue reporting
- ✅ Fuel logging
- ✅ Maintenance requests
- ✅ Incident reporting

### 🔐 Security
- ✅ Password hashing (bcrypt)
- ✅ Session-based authentication
- ✅ SQL injection prevention (PDO)
- ✅ XSS protection
- ✅ CSRF protection
- ✅ Complete data isolation by company

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | PHP 8.0+ |
| **Database** | PostgreSQL 12+ |
| **Frontend** | HTML5, TailwindCSS, JavaScript |
| **Maps** | Leaflet.js with OpenStreetMap |
| **Charts** | Chart.js |
| **Mobile** | React Native, Expo |
| **Server** | Apache/Nginx |

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/yourusername/fleet-manager.git
cd fleet-manager

# Set up database
psql -U postgres -c "CREATE DATABASE truck_fleet_db;"
psql -U postgres -d truck_fleet_db -f database/complete_schema.sql

# Configure database connection
cp config/database.example.php config/database.php
# Edit database.php with your credentials

# Start web server
php -S localhost:8000

# Access the application
# Open browser: http://localhost:8000
# Login: super@admin.com / SuperAdmin123!

📥 Installation Guide

Prerequisites
Requirement	Version	Check Command
PHP	8.0+	php -v
PostgreSQL	12+	psql --version
Composer	Latest	composer --version
Node.js	16+	node --version
npm	8+	npm --version


## Step 1: Download & Extract

# Download the project
wget https://github.com/yourusername/fleet-manager/archive/main.zip
unzip main.zip
cd fleet-manager-main


Step 2: Database Setup

# Create database
psql -U postgres -c "CREATE DATABASE truck_fleet_db;"

# Run schema (using the complete SQL file provided)
psql -U postgres -d truck_fleet_db -f database/complete_schema.sql

# Verify tables
psql -U postgres -d truck_fleet_db -c "\dt"


## Step 3: Configure Database Connection
Edit config/database.php:

<?php
class Database {
    private $host = "localhost";
    private $db_name = "truck_fleet_db";
    private $username = "postgres";
    private $password = "your_password_here";
    private $port = "5432";
    public $conn;
    // ... rest of the class
}
?>


## Step 4: Web Server Configuration

For XAMPP:
# Copy to htdocs
cp -r fleet-manager C:\xampp\htdocs\
# Access at: http://localhost/fleet-manager


For Apache (Linux):

<VirtualHost *:80>
    ServerName fleet.local
    DocumentRoot "/var/www/fleet-manager"
    <Directory "/var/www/fleet-manager">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>



## For PHP Built-in Server:

php -S 0.0.0.0:8000
# Access at: http://localhost:8000


## Step 5: Set Permissions


# Linux/Mac
chmod -R 755 storage/
chmod -R 755 config/
chmod 777 config/session.php

# Windows - usually not needed

🔧 Configuration


Application Settings
Create config/app.php:


<?php
// Application Configuration
define('APP_NAME', 'FleetManager');
define('APP_URL', 'http://localhost/fleet-manager');
define('APP_ENV', 'production'); // development, production
define('APP_DEBUG', false);

// Map Settings
define('MAP_CENTER_LAT', 31.7917);
define('MAP_CENTER_LNG', -7.0926);
define('MAP_ZOOM', 6);
define('MAP_REFRESH_INTERVAL', 10000); // milliseconds

// GPS Tracking Settings
define('GPS_UPDATE_INTERVAL', 5000); // milliseconds
define('GPS_STALE_TIMEOUT', 600); // seconds (10 minutes)

// Pagination
define('ITEMS_PER_PAGE', 20);
?>


Email Configuration (for Invitations)


// config/email.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@fleetmanager.com');

👥 Role-Based Access
Role	Icon	Access Level	Permissions
Super Admin	👑	Platform-wide	Full system control, manage all companies, view all data
Owner	🏢	Company	Full company control, billing, team management
Manager	📊	Operational	Trucks, drivers, trips, reports, maintenance
Driver	🚛	Limited	Mobile app only, live tracking, profile
Permission Matrix
Feature	Super Admin	Owner	Manager	Driver
View Dashboard	✅	✅	✅	✅
Manage Trucks	✅	✅	✅	❌
Manage Drivers	✅	✅	✅	❌
Create Trips	✅	✅	✅	❌
View Reports	✅	✅	✅	❌
Live Tracking	✅	✅	✅	✅
Maintenance	✅	✅	✅	❌
Fuel Records	✅	✅	✅	❌
Team Management	✅	✅	❌	❌
Company Settings	✅	✅	❌	❌
Mobile App	✅	✅	✅	✅
💰 Subscription Plans
Plan	Price	Drivers	Trucks	Managers	Features
Standard	$49/mo	5	5	2	Basic fleet management
Premium	$99/mo	20	20	5	Advanced analytics + API
Extra	$199/mo	Unlimited	Unlimited	Unlimited	All features + Priority support
Plan Limits are Enforced:
Cannot add more drivers than plan allows

Cannot add more trucks than plan allows

Cannot invite more managers than plan allows

Upgrade required to exceed limits

📱 Mobile App Setup
Prerequisites
Node.js 16+

Expo CLI

Expo Go app on your phone (iOS/Android)

Installation

cd TruckDriverApp

# Install dependencies
npm install

# Install Expo packages
npx expo install expo-location
npx expo install @react-native-async-storage/async-storage
npx expo install react-native-maps
npx expo install expo-secure-store

# Start the development server
npx expo start


Configure API URL
Edit src/services/api.js:

// Change this to your server IP
const API_URL = 'http://192.168.1.100/fleet-manager';


Build for Production


# Install EAS CLI
npm install -g eas-cli

# Configure build
eas build:configure

# Build Android APK
eas build -p android --profile preview

# Build iOS IPA
eas build -p ios --profile preview


