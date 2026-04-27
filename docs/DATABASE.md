# 🗄 Database Design

## Core Tables
- **companies** - Multi-tenant companies with subscription plans
- **users** - User accounts (super_admin, owner, manager, driver)
- **drivers** - Driver profiles linked to users
- **trucks** - Fleet vehicles belonging to a company
- **trips** - Trip assignments linking trucks and drivers

## Full Table List
**Company & Users:** companies, users, invitations
**Fleet:** trucks, drivers, routes, trips, shipments
**Tracking:** truck_locations, schedules
**Maintenance:** truck_maintenance, incidents, fuel_records
**System:** notifications, activity_logs

## Key Concept
**Every business table includes `company_id`** → complete data isolation between companies.

## Relationships

companies (1) ──┬── (∞) users

├── (∞) drivers

└── (∞) trucks

users (1) ─────── (1) drivers

drivers (1) ───── (∞) trips
trucks (1) ────── (∞) trips
routes (1) ────── (∞) trips

trucks (1) ────── (∞) truck_locations
trucks (1) ────── (∞) truck_maintenance
trucks (1) ────── (∞) fuel_records
trucks (1) ────── (∞) incidents

users (1) ────── (∞) notifications
users (1) ────── (∞) activity_logs


## Foreign Keys
| Table | FK | References |
|-------|----|------------|
| users | company_id | companies.id |
| drivers | user_id, company_id | users.id, companies.id |
| trucks | company_id, current_driver_id | companies.id, drivers.id |
| trips | company_id, truck_id, driver_id, route_id, created_by | companies.id, trucks.id, drivers.id, routes.id, users.id |
| truck_locations | truck_id | trucks.id |
| truck_maintenance | truck_id | trucks.id |
| fuel_records | truck_id, created_by | trucks.id, users.id |
| incidents | truck_id, driver_id, reported_by | trucks.id, drivers.id, users.id |
| notifications | user_id | users.id |
| activity_logs | user_id | users.id |
| invitations | company_id, created_by | companies.id, users.id |

## Indexes
- **users:** email, role, company_id
- **drivers:** status, license_expiry, company_id
- **trucks:** status, plate_number, company_id
- **trips:** status, trip_number, company_id
- **truck_locations:** (truck_id, recorded_at)
- **invitations:** token, email

## Subscription Limits
| Plan | Max Drivers | Max Trucks | Max Managers |
|------|-------------|------------|--------------|
| Standard | 5 | 5 | 2 |
| Premium | 10 | 10 | 5 |
| Extra | Unlimited | Unlimited | Unlimited |

## Data Isolation Flow

Login → Session stores company_id → All queries filter by company_id → User sees ONLY their company's data
