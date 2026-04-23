# 🚛 FleetManager - Truck Fleet Management System

A complete multi-tenant SaaS platform for truck fleet management with web dashboard, mobile app, real-time GPS tracking, and subscription-based billing.

## 📋 Table of Contents
- [Features](#features)
- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Role-Based Access](#role-based-access)
- [Mobile App Setup](#mobile-app-setup)
- [API Endpoints](#api-endpoints)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

### 🏢 Multi-Tenant SaaS Platform
- Multiple companies with isolated data
- Subscription plans (Standard, Premium, Extra)
- Company owner dashboard
- Team member invitations
- Usage limits per plan

### 🚚 Fleet Management
- Truck management (CRUD operations)
- Driver management with license tracking
- Trip planning and assignment
- Route optimization
- Shipment/Cargo tracking

### 📍 Real-Time GPS Tracking
- Live truck locations on interactive map
- Speed and status monitoring
- Location history
- Geofencing capabilities
- Stale location detection

### 🔧 Maintenance & Incidents
- Maintenance scheduling
- Cost tracking
- Incident reporting
- Severity levels
- Driver-submitted reports

### ⛽ Fuel Management
- Fuel purchase tracking
- Cost per liter analytics
- Fuel station records
- Monthly consumption reports

### 👥 User Management
- Role-based access (Super Admin, Owner, Manager, Driver)
- Invitation system for team members
- User activity logging
- Session management

### 📊 Reports & Analytics
- Trip statistics
- Fleet utilization
- Fuel consumption charts
- Maintenance costs
- Driver performance

### 📱 Mobile App (React Native)
- Driver login
- Real-time GPS tracking
- Current trip display
- Issue reporting
- Fuel logging
- Maintenance requests
- Incident reporting

### 🔐 Security Features
- Password hashing (bcrypt)
- Session-based authentication
- SQL injection prevention (PDO)
- XSS protection
- CSRF protection
- Company data isolation

## 🛠 Tech Stack

### Backend
- **PHP 8.0+** - Core backend logic
- **PostgreSQL** - Database
- **PDO** - Database abstraction
- **Apache/Nginx** - Web server

### Frontend (Web)
- **HTML5/CSS3** - Structure & styling
- **TailwindCSS** - Utility-first CSS framework
- **JavaScript** - Client-side logic
- **Leaflet.js** - Interactive maps
- **Chart.js** - Data visualization

### Mobile App
- **React Native** - Cross-platform mobile framework
- **Expo** - Development platform
- **React Navigation** - Navigation
- **Expo Location** - GPS tracking

## 🏗 System Architecture
┌─────────────────────────────────────────────────────────────┐
│ Clients │
├──────────────┬───────────────┬──────────────────────────────┤
│ Web Admin │ Web Manager │ Driver Mobile App │
└──────┬───────┴───────┬───────┴──────────┬───────────────────┘
│ │ │
▼ ▼ ▼
┌─────────────────────────────────────────────────────────────┐
│ PHP API Layer │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ Auth │ │ Trucks │ │ Drivers │ │ Trips │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ Fuel │ │Reports │ │Tracking │ │ Users │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
└─────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────┐
│ PostgreSQL Database │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ users │ │companies│ │ trucks │ │ drivers │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ │
│ │ trips │ │ routes │ │ fuel │ │ maint │ │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘ │
└─────────────────────────────────────────────────────────────┘

text

## 💻 Installation

### Prerequisites
- PHP 8.0 or higher
- PostgreSQL 12 or higher
- Composer (optional)
- Node.js 16+ (for mobile app)
- Expo CLI (for mobile app)

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/fleet-manager.git
cd fleet-manager
Step 2: Database Setup
bash
# Create PostgreSQL database
psql -U postgres -c "CREATE DATABASE truck_fleet_db;"

# Run migration scripts
psql -U postgres -d truck_fleet_db -f database/schema.sql
psql -U postgres -d truck_fleet_db -f database/migration_saas_fixed.sql
Step 3: Configure Database Connection
Edit config/database.php:

php
private $host = "localhost";
private $db_name = "truck_fleet_db";
private $username = "postgres";
private $password = "your_password";
private $port = "5432";
Step 4: Web Server Configuration
For XAMPP:
bash
# Copy project to htdocs
cp -r fleet-manager C:\xampp\htdocs\

# Access at: http://localhost/fleet-manager
For Apache:
apache
<VirtualHost *:80>
    ServerName fleet.local
    DocumentRoot "/var/www/fleet-manager"
    <Directory "/var/www/fleet-manager">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
Step 5: Set Permissions
bash
chmod -R 755 storage/
chmod -R 755 config/
chmod 777 config/session.php
🔧 Configuration
Environment Variables
Create config/config.php:

php
<?php
// App Settings
define('APP_NAME', 'FleetManager');
define('APP_URL', 'http://localhost/fleet-manager');
define('APP_ENV', 'production'); // development, production

// Email Settings (for invitations)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-password');

// Map Settings
define('MAP_CENTER_LAT', 31.7917);
define('MAP_CENTER_LNG', -7.0926);
define('MAP_ZOOM', 6);
?>
Create Super Admin Account
bash
# Run this PHP script
php scripts/create_super_admin.php
Default credentials:

Email: super@admin.com

Password: SuperAdmin123!

👥 Role-Based Access
Role	Access Level	Permissions
Super Admin	Full platform	Manage all companies, users, system settings
Owner	Company level	Full company control, team management, billing
Manager	Operational	Trucks, drivers, trips, reports, tracking
Driver	Limited	Mobile app, live tracking, profile
📱 Mobile App Setup
Prerequisites
Node.js 16+

Expo CLI

Android Studio (for Android) or Xcode (for iOS)

Physical device with Expo Go app

Installation
bash
cd TruckDriverApp

# Install dependencies
npm install

# Install Expo packages
npx expo install expo-location
npx expo install @react-native-async-storage/async-storage
npx expo install react-native-maps

# Start the app
npx expo start
Build for Production
bash
# Android APK
eas build -p android --profile preview

# iOS IPA
eas build -p ios --profile preview
API Configuration
Update src/services/api.js:

javascript
const API_URL = 'https://your-domain.com/school_project';
🔌 API Endpoints
Authentication
Endpoint	Method	Description
/api/mobile_login.php	POST	Driver login
/api/register_company.php	POST	Company registration
Tracking
Endpoint	Method	Description
/api/update_location.php	POST	Update GPS location
/api/trucks_location.php	GET	Get all truck locations
Management
Endpoint	Method	Description
/api/get_driver_info.php	GET	Get driver details
/api/get_current_trip.php	GET	Get active trip
/api/report_issue.php	POST	Report issue
/api/add_maintenance.php	POST	Request maintenance
/api/add_fuel_record.php	POST	Add fuel record
📁 Project Structure
text
fleet-manager/
├── api/                    # API endpoints
│   ├── mobile_login.php
│   ├── update_location.php
│   └── ...
├── config/                 # Configuration files
│   ├── database.php
│   ├── auth_saas.php
│   └── session.php
├── models/                 # Database models
│   ├── Truck.php
│   ├── Driver.php
│   └── Trip.php
├── pages/                  # Web interface
│   ├── dashboard.php
│   ├── trucks.php
│   ├── drivers.php
│   ├── tracking.php
│   └── super_admin/       # Super admin panel
├── includes/               # Reusable components
│   ├── sidebar.php
│   ├── header.php
│   └── check_auth.php
├── database/               # SQL migrations
│   ├── schema.sql
│   └── migration_saas_fixed.sql
├── TruckDriverApp/         # React Native mobile app
│   ├── app/
│   ├── src/
│   └── package.json
├── assets/                 # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── signin.php              # Login page
├── signup.php              # Registration choice
├── register_company.php    # Company registration
├── register_invite.php     # Invitation acceptance
└── index.html              # Landing page
🗄️ Database Schema
Core Tables
users - User accounts and authentication

companies - Multi-tenant companies

drivers - Driver profiles

trucks - Fleet vehicles

Operations Tables
trips - Trip assignments

routes - Route definitions

shipments - Cargo tracking

truck_locations - GPS history

Management Tables
truck_maintenance - Service records

fuel_records - Fuel purchases

incidents - Accidents/issues

notifications - System alerts

System Tables
invitations - Team invitations

activity_logs - Audit trail

📸 Screenshots
Web Dashboard
https://screenshots/dashboard.png

Live Tracking
https://screenshots/tracking.png

Mobile App
https://screenshots/mobile_app.png

🔒 Security Considerations
Password Storage: All passwords are hashed using password_hash() with bcrypt

SQL Injection: Prepared statements with PDO throughout

XSS Protection: All output is escaped with htmlspecialchars()

Session Security: HTTP-only cookies, session regeneration on login

Data Isolation: All queries filtered by company_id

Rate Limiting: Implement rate limiting on API endpoints

🚀 Deployment
Production Server Requirements
bash
# Install PHP extensions
sudo apt-get install php8.0-pgsql php8.0-pdo

# Install PostgreSQL
sudo apt-get install postgresql-12

# Configure Apache
sudo a2enmod rewrite
sudo systemctl restart apache2
Performance Optimization
Enable OPcache in PHP

Configure PostgreSQL indexes

Implement Redis for caching (optional)

Use CDN for static assets

Enable gzip compression

🧪 Testing
bash
# Run PHP unit tests
php vendor/bin/phpunit tests/

# Test API endpoints
php scripts/test_api.php

# Mobile app tests
cd TruckDriverApp
npm test
📈 Monitoring
Logs: Check logs/error.log for errors

Performance: Monitor /api/performance.php

Database: Run ANALYZE on tables periodically

Backup: Schedule daily database backups

🤝 Contributing
Fork the repository

Create a feature branch (git checkout -b feature/AmazingFeature)

Commit changes (git commit -m 'Add AmazingFeature')

Push to branch (git push origin feature/AmazingFeature)

Open a Pull Request

Coding Standards
PSR-12 for PHP

Airbnb for JavaScript

ESLint for React Native

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

🙏 Acknowledgments
Leaflet.js for maps

TailwindCSS for styling

Chart.js for analytics

Expo for mobile development

PostgreSQL community

📞 Support
Documentation: Wiki

Issues: GitHub Issues

Email: support@fleetmanager.com

🚦 Roadmap
Payment integration (Stripe)

Driver mobile app enhancements

Real-time notifications (WebSockets)

Advanced analytics dashboard

API rate limiting

Two-factor authentication

PDF report generation

Email notifications

Geofencing alerts

Vehicle maintenance reminders

🎯 Quick Start Commands
bash
# Clone and setup
git clone https://github.com/yourusername/fleet-manager.git
cd fleet-manager

# Database setup
psql -U postgres -c "CREATE DATABASE truck_fleet_db;"
psql -U postgres -d truck_fleet_db -f database/schema.sql

# Configure
cp config/database.example.php config/database.php
# Edit database.php with your credentials

# Start web server
php -S localhost:8000

# Mobile app
cd TruckDriverApp
npm install
npx expo start
Built with ❤️ for the trucking industry

text

This README provides comprehensive documentation for your project including installation, configuration, API documentation, and everything needed to get started. You can customize the repository URL, screenshots, and other placeholders with your actual project details.
