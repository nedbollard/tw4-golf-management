# TW4 Golf Management System - Structure Chart

## System Architecture Overview

```
                    TW4 GOLF MANAGEMENT SYSTEM
                    =========================
                            |
                    ┌───────┴───────┐
                    |  Application  |
                    |   (Entry)     |
                    └───────┬───────┘
                            |
                    ┌───────┴───────┐
                    |     Router    |  ← URL Routing
                    |   (Dispatcher)|
                    └───────┬───────┘
                            |
        ┌───────────────────┼───────────────────┐
        |                   |                   |
┌───────┴───────┐  ┌───────┴───────┐  ┌───────┴───────┐
|   Controllers |  |    Services   |  |     Views     |
|   (Logic)     |  |  (Business)   |  |  (Presentation)|
└───────┬───────┘  └───────┬───────┘  └───────┬───────┘
        |                   |                   |
┌───────┴───────┐  ┌───────┴───────┐  ┌───────┴───────┐
|  HomeController|  | ConfigService |  |  home/index   |
| AuthController |  | AuthService   |  | auth/login    |
| AdminController|  | PlayerService |  | admin/menu    |
| ScorerController|  |               |  | scorer/menu   |
| ConfigController|  |               |  | config/index  |
| PlayerController|  |               |  | players/index |
| RoleSwitchCtrl  |  |               |  | common/404    |
└───────┬───────┘  └───────┬───────┘  └───────┬───────┘
        |                   |                   |
        └───────────────────┼───────────────────┘
                            |
                    ┌───────┴───────┐
                    |   BaseController|
                    |  (Common Func)  |
                    └───────┬───────┘
                            |
                    ┌───────┴───────┐
                    |     Core      |
                    |   (Database)  |
                    └───────┬───────┘
                            |
                    ┌───────┴───────┐
                    |   Database    |
                    |   (MySQL)     |
                    └───────────────┘
```

## Data Flow Diagram

```
USER REQUEST → ROUTER → CONTROLLER → SERVICE → DATABASE → SERVICE → CONTROLLER → VIEW → USER
     ↓              ↓         ↓          ↓         ↓          ↓         ↓        ↓
  HTTP URL      Match     Execute    Business   SQL       Results   Render   HTML
  /login        Route     Method     Logic      Query     Data      Template Response
```

## Component Relationships

### APPLICATION LAYER (Top Level)
- **Router** (Traffic Cop)
- **Controllers** (Department Managers)
- **Services** (Workers)
- **Views** (Display Screens)
- **Database** (File Cabinet)

### CONTROLLER HIERARCHY
- **BaseController** (Parent Class)
  - Common Functions (render, redirect, auth)
  - Child Controllers (Inherit)
    - **HomeController** (Main Menu)
    - **AuthController** (Login/Logout)
    - **AdminController** (Admin Functions)
    - **ScorerController** (Scorer Functions)
    - **ConfigController** (System Config)
    - **PlayerController** (Player Management)
    - **RoleSwitchController** (Role Switching)

### SERVICE LAYER (Business Logic)
- **ConfigService** (System Settings)
- **AuthService** (User Authentication)
- **PlayerService** (Player Data)
- **Database** (Data Access)

### VIEW LAYER (Presentation)
- **home/** (Main Menu)
- **auth/** (Login Forms)
- **admin/** (Admin Interface)
- **scorer/** (Scorer Interface)
- **config/** (Configuration)
- **players/** (Player Lists)
- **common/** (Shared Components)
- **errors/** (Error Pages)

### DATABASE LAYER (Data Storage)
- **staff** (User Accounts)
- **config_application** (System Settings)
- **player** (Player Records)
- **[Other Tables]**

## COBOL vs MVC Comparison

```
COBOL STRUCTURE                    MVC STRUCTURE
─────────────────                  ─────────────────
PROGRAM DIVISION                   Application Class
PERFORM PARAGRAPH                  Router → Controller
CALL SUBPROGRAM                    Service Class
DISPLAY SCREEN                     View Template
FILE I/O                           Database Service
WORKING-STORAGE                    Service Properties
PROCEDURE DIVISION                 Controller Methods
```

## Key Differences from COBOL

1. **Separation of Concerns**: Unlike COBOL's monolithic structure, MVC separates:
   - **Controllers** (like COBOL paragraphs)
   - **Services** (like COBOL subprograms)  
   - **Views** (like COBOL DISPLAY statements)

2. **Dependency Injection**: Controllers get services injected (like COBOL CALL but automatic)

3. **Routing**: URL patterns map to controllers (like COBOL program selection)

4. **Database Abstraction**: Service layer handles all data access (like COBOL file I/O)

5. **Inheritance**: BaseController provides common functions (like COBOL copybooks)

## Flow Analogy to COBOL

```
COBOL:                    MVC:
──────                    ━━━━━
MAIN SECTION             Application Class
  PERFORM INIT           Router initialization
  PERFORM PROCESS        Controller execution
  PERFORM DISPLAY       View rendering
  GOBACK                 Response sent

SUBPROGRAM               Service Class:
  CALL SUBPROG           Service instantiation
  USING DATA             Method parameters
  RETURN                 Method return value
```

## File Structure

```
TW4/
├── src/
│   ├── Controllers/          # Business Logic
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── ScorerController.php
│   │   ├── ConfigController.php
│   │   ├── PlayerController.php
│   │   └── RoleSwitchController.php
│   ├── Services/             # Business Services
│   │   ├── ConfigService.php
│   │   ├── AuthService.php
│   │   └── PlayerService.php
│   ├── Views/                # Presentation
│   │   ├── home/
│   │   ├── auth/
│   │   ├── admin/
│   │   ├── scorer/
│   │   ├── config/
│   │   ├── players/
│   │   ├── common/
│   │   └── errors/
│   ├── Core/                  # System Core
│   │   ├── Application.php
│   │   ├── Router.php
│   │   └── Database.php
│   └── config/                # Configuration
│       ├── routes.php
│       └── config.php
└── public/
    └── index.php             # Entry Point
```

## Request Processing Flow

1. **User Action** → HTTP Request to URL
2. **Router** → Matches URL to Controller/Method
3. **Controller** → Executes business logic
4. **Service** → Handles data operations
5. **Database** → Stores/retrieves data
6. **Service** → Returns results to Controller
7. **Controller** → Passes data to View
8. **View** → Renders HTML response
9. **User** → Sees rendered page

This structure provides the same logical organization you're used to in COBOL, but with modern separation of concerns and web capabilities!
