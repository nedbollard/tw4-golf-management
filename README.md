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
5. Complete the [Initial admin account](#initial-admin-account) step before exposing the site

## Initial admin account

Migration `src/migrations/008_seed_admin.sql` seeds a single `admin` account whose
password hash is committed to this public repository. It exists only so that a fresh
installation can be logged into once. **Anyone who can read this repository knows that
password**, so before the site is reachable by anyone else:

1. Log in as `admin` and create a real admin account for yourself under Admin → Staff.
2. Log in as the new account and deactivate the seeded `admin` account (Admin → Staff → Delete).
3. If you would rather keep the `admin` username, rotate its password instead:

   ```bash
   docker compose exec app php scripts/set-staff-password.php admin
   ```

   The script prompts for the new password on stdin, so it is never written to shell
   history. Do the same for any other seeded account, such as `scorer`.

## Deployment

For a production hosting plan and launch checklist, see [Deployment Guide](docs/deployment/DEPLOYMENT.md).

## User Documentation

For end-user and tester-facing operating guidance, see the [User Guide](docs/USER_GUIDE.md).

The complete documentation index is available in [docs/README.md](docs/README.md).

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
