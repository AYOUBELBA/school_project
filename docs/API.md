# 📡 API Documentation

## Authentication
POST /api/mobile_login.php
- email
- password

## Company Registration
POST /api/register_company.php
- username
- email
- password
- company_name
- plan

## GPS Tracking
POST /api/update_location.php
- truck_id
- latitude
- longitude
- speed

GET /api/trucks_location.php

## Driver
GET /api/get_driver_info.php
GET /api/get_current_trip.php

## Reports & Actions
POST /api/report_issue.php
POST /api/add_maintenance.php
POST /api/add_fuel_record.php
