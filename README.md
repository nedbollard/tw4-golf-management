# TW4 - Twilight Golf Scoring System (MVC Version)

## Overview
TW4 is a complete rewrite of the Twilight golf scoring application using modern Object-Oriented PHP and MVC architecture.

## Architecture

### MVC Structure
- **Models**: Data layer (User, Course, Round, Score)
- **Controllers**: Business logic (AuthController, ScoreController)
- **Views**: Presentation templates with clean separation

### Key Features
- User authentication with role-based access
- Score input with real-time calculations
- Leaderboard system
- Round management
- Clean URL routing

## Installation

1. Copy database configuration from original twilight project
2. Install dependencies: `composer install`
3. Configure web server to point to `public/` directory
4. Access via: `http://localhost/tw4/public/`

## Docker Development

### Quick Start
```bash
cd TW4
cp .env.example .env
# Edit .env and set DB_PASSWORD
docker-compose up --build
```

### Access Points
- **TW4 Application**: http://localhost:8084
- **phpMyAdmin**: http://localhost:8085
  - Server: `db`
  - Username: `root`
  - Password: `DB_PASSWORD` value from `.env`
- **MySQL Direct**: localhost:3307

## URL Structure
- Login: `?controller=auth&action=login`
- Register: `?controller=auth&action=register`
- Score Input: `?controller=score&action=input`
- Leaderboard: `?controller=score&action=leaderboard`

## Database
Uses existing Twilight database schema. No migration needed.

## Development
- Add new controllers in `src/Controllers/`
- Add new models in `src/Models/`
- Add new views in `src/Views/`
- Update router in `public/index.php`

## Migration from Twilight
This is a clean MVC rewrite that can run alongside the original Twilight application for gradual migration.
