# Me2U — C2C E-Commerce Platform

A consumer-to-consumer e-commerce platform built for South Africa's informal economy.

## Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Server:** Apache (XAMPP)

## Features

- User registration and login with role-based access
- Product listings with search and category filters
- Perishable-aware delivery routing
- Same-day delivery for perishable goods
- National courier option for non-perishable goods
- Order tracking dashboard
- Admin panel with RBAC
- Seller verification system
- Review and rating system

## Local Setup

1. Install XAMPP
2. Clone this repository into `C:\xampp\htdocs\me2u`
3. Import `database/etrade_sa.sql` into phpMyAdmin
4. Copy `config/db.example.php` to `config/db.php`
5. Update `config/db.php` with your local database credentials
6. Start Apache and MySQL in XAMPP
7. Visit `http://localhost/pages/listings.php`

## Default Admin Login

- Email: `admin@etrade.co.za`
- Password: `admin123`

## Module

ITECA3-12 — Web Development and e-Commerce