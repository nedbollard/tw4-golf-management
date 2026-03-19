# TW4 Database Schema Review

## Overview
Clean, normalized database structure designed for golf management system with proper separation of concerns between staff and players.

## Key Design Principles

### 1. Role-Based Access Control
- **Staff Table**: Replaces "users" with clear admin/scorer roles
- **Players Table**: No login required for public access
- **Proper Separation**: Staff handle scoring, players play golf

### 2. Data Normalization
- **Atomic Operations**: Each table has single responsibility
- **Foreign Keys**: Proper relationships with cascade deletes
- **Indexes**: Strategic indexing for performance

## Table Structure

### Staff (Admins/Scorers)
```sql
staff_id: INT AUTO_INCREMENT PRIMARY KEY
username: VARCHAR(50) NOT NULL UNIQUE
password_hash: VARCHAR(255) NOT NULL
first_name: VARCHAR(100) NOT NULL
last_name: VARCHAR(100) NOT NULL
role: ENUM('admin', 'scorer') NOT NULL
is_active: BOOLEAN DEFAULT TRUE
last_login: TIMESTAMP NULL
```

### Players (Golfers - No Login Required)
```sql
player_id: INT AUTO_INCREMENT PRIMARY KEY
member_identifier: VARCHAR(50) NOT NULL UNIQUE
first_name: VARCHAR(100) NOT NULL
last_name: VARCHAR(100) NOT NULL
alias: VARCHAR(50) NULL UNIQUE
gender: ENUM('male', 'female') NOT NULL
handicap: INT DEFAULT 0
status: ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
```

**Key Changes Made:**
- **Removed Email Requirement**: Simplified registration process
- **Streamlined Validation**: Less friction for user onboarding
- **Maintained Security**: Still uses proper password hashing and validation
- **Added Member Identifier**: Auto-generated from first_name + last_name_initial
- **Enhanced Alias System**: Aliases must be unique against both aliases and member identifiers
- **Separated Name Fields**: Individual first_name and last_name columns for both Staff and Players
- **Removed Phone Field**: Simplified data collection from Players table
- **Status Field**: Explicit active/inactive status for scoring eligibility
- **Human-Readable IDs**: Member identifiers like "JohnS" instead of numeric IDs

## Privacy & User Experience Benefits

### Alias/Nickname System
- **Privacy**: Players can use aliases instead of real names in public results
- **Flexibility**: Supports nicknames, preferred names, or member numbers
- **Simplicity**: No need for complex naming conventions
- **Professional**: Clean presentation with player-chosen identifiers

### Implementation Strategy
- **Member Identifier Generation**: Auto-generated as `FirstName + LastInitial` (e.g., "JohnS")
- **Collision Handling**: Append numbers if identifier exists (e.g., "JohnS1", "JohnS2")
- **Public Results**: Display `alias` if present, otherwise `member_identifier`
- **Search**: Allow search by alias, member identifier, or real name
- **Registration**: Optional alias during player signup
- **Uniqueness**: Aliases must be unique against both aliases and member identifiers
- **Scoring Eligibility**: Only players with status = "active" available for scoring functions

### Member Identifier Logic
- **Base Format**: `FirstName + LastInitial` (e.g., "John Smith" → "JohnS")
- **Debugging Friendly**: Human-readable identifiers instead of numeric IDs
- **Auto-Increment**: "JohnS", "JohnS1", "JohnS2" for duplicates
- **Name Changes**: Automatically regenerates when names change
- **Cross-Validation**: Aliases cannot conflict with existing member identifiers

### Status Management
- **Active Players**: Available for scoring, search, and all functions
- **Inactive Players**: Hidden from scoring functions but retained for historical data
- **Soft Delete**: Players marked inactive rather than deleted to preserve data integrity
- **Reactivation**: Inactive players can be reactivated if needed

### Courses & Holes
```sql
courses: course_id, name, par_total, tee_color, slope_rating, course_rating
holes: hole_id, course_id, hole_number, par, stroke_index, hole_description
```

### Rounds & Scoring
```sql
rounds: round_id, course_id, staff_id, round_number, round_date, status
score_cards: card_id, round_id, player_id, total_score, total_points, handicap_used
hole_scores: score_id, card_id, hole_id, score, shots, points
```

## Key Features

### Security
- **Password Hashing**: Modern PHP password_hash() with proper verification
- **Session Management**: Secure session lifecycle with timeout
- **Role Validation**: Proper authorization checks

### Audit Trail
- **audit_log**: Complete tracking of all CRUD operations
- **Change Tracking**: old_values/new_values JSON storage
- **IP/User Agent Logging**: Security monitoring

### Configuration Management
- **config_basic**: Dynamic settings with type validation
- **Initial Setup**: Bootstrap configuration for first admin

## Benefits Over Old Schema

1. **Clear Separation**: Staff vs Players eliminates confusion
2. **No Player Login**: Simplifies public access requirements
3. **Proper Relationships**: Foreign keys ensure data integrity
4. **Audit Trail**: Complete change tracking for compliance
5. **Scalability**: Normalized structure supports growth
6. **Security**: Modern authentication practices

## Migration Strategy
- **Backup First**: Always backup existing data
- **Incremental Migrations**: Version-controlled schema changes
- **Data Migration**: Scripts to convert from old to new structure

## Questions for Review

1. **Member Numbers**: Are the current member_number formats correct?
2. **Handicap System**: Does the handicap calculation method need adjustment?
3. **Course Rating**: Are slope/course rating fields sufficient?
4. **Scoring Rules**: Any specific golf association requirements?
5. **Audit Requirements**: Any compliance or reporting needs?

## Next Steps
1. Review and approve schema
2. Create migration scripts
3. Implement Player management classes
4. Set up new database
5. Test data migration
6. Deploy incrementally
