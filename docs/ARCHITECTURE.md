# 🧱 System Architecture

## Overview
Multi-tenant SaaS system with:

- Backend (PHP)
- Database (PostgreSQL)
- Mobile App (React Native)

## Flow
User → API → Database

## Multi-Tenancy
All tables include:
- company_id

## Roles
- super_admin
- owner
- manager
- driver
