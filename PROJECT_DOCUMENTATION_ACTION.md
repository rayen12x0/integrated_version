# Connect for Peace - Project Documentation

## ✅ 1. Technology Stack

### Frontend
- **HTML5, CSS3** - Core structure and styling
- **JavaScript (ES6+)** - Client-side functionality
- **Tailwind CSS** - Utility-first CSS framework for rapid UI development
- **jQuery** - AJAX requests and DOM manipulation
- **SweetAlert2** - Enhanced alert dialogs and notifications
- **Leaflet.js** - Interactive maps and location services
- **Three.js** - 3D globe visualization in global explore feature
- **Lucide Icons** - Lightweight icon library
- **Spline Viewer** - 3D graphics rendering

### Backend
- **PHP** - Server-side scripting language
- **PDO (PHP Data Objects)** - Database interaction layer
- **Sessions** - User session management

### Database
- **MySQL** - Relational database management system

### Server
- **Apache** - Web server (via XAMPP)

### APIs & Integration
- **RESTful API Endpoints** - For data communication between frontend and backend
- **Resend API** - For sending transactional emails
- **Geocoding APIs** - For location to coordinates conversion
- **Email Services** - For notification system

---

## ✅ 2. Project Structure

### Monolithic Architecture with Modular Organization

```
my_work_v2/
├── api/                    # RESTful API endpoints organized by domain
│   ├── actions/           # Action-related endpoints
│   ├── resources/         # Resource-related endpoints
│   ├── comments/          # Comments endpoints
│   ├── location/          # Location-related endpoints
│   ├── notifications/     # Notifications endpoints
│   ├── reminders/         # Reminders endpoints
│   ├── reports/           # Reports endpoints
│   ├── users/             # User authentication endpoints
│   └── other/             # Miscellaneous endpoints
├── auth/                  # Authentication system
├── config/                # Configuration files
├── controllers/           # Business logic controllers
├── dashboard/             # Admin and user dashboard interface
├── model/                 # Data models and database schema
├── template_to_use/       # Template files
├── uploads/               # File uploads directory
│   ├── actions/           # Action-related images
│   └── resources/         # Resource-related images
├── utils/                 # Utility functions and helpers
├── vue/                   # Main frontend application
│   ├── assets/            # Frontend assets (CSS, JS, images)
│   │   ├── css/
│   │   ├── js/
│   │   └── Images/
│   ├── globale_explore/   # 3D globe exploration feature
│   ├── index.html         # Main application entry point
│   └── about.html         # About page
├── connect_for_peace_database.sql  # Database schema
├── README.md             # Project overview and setup instructions
└── sample_data.sql       # Sample data for testing
```

---

## ✅ 3. Authentication System

### Multi-Mode Authentication

The system supports both real and mock authentication:

#### Real Authentication
- **Session-based**: Uses PHP sessions for state management
- **Database Integration**: Authenticates against the `users` table
- **Password Security**: Uses hashed passwords with bcrypt

#### Mock Authentication (Development Mode)
- **URL Parameter Override**: Supports `?user_id=1` or `?user_id=2`
- **Mock Users**:
  - User ID 1: Admin user with admin privileges
  - User ID 2: Regular user with standard privileges

#### Roles & Permissions
- **Admin** (`role: admin`):
  - Full CRUD access to all content
  - Content approval/rejection
  - User management capabilities
  - Access to admin dashboard

- **Regular User** (`role: user`):
  - CRUD operations on own content only
  - Participation in actions/resources
  - Commenting abilities
  - Profile management

#### Authentication Flow
1. **Login Process**:
   - User visits `auth/login.html`
   - Selects role (Admin or Community Member)
   - JavaScript calls `api/set_session.php` with selected user ID
   - Session stored server-side in PHP

2. **Session Management**:
   - `api/check_auth.php` - Verifies authentication status
   - `api/set_session.php` - Establishes user session
   - `api/logout.php` - Terminates user session
   - `utils/AuthHelper.php` - Centralized authentication functions

3. **Authorization Checks**:
   - Ownership verification for content modification
   - Role-based access control for sensitive operations
   - Permission validation on API endpoints

---

## ✅ 4. How Modules Communicate

### RESTful API Architecture

#### API Endpoint Structure
```
/api/actions/     - Action management (create, read, update, delete)
/api/resources/   - Resource management (create, read, update, delete)
/api/comments/    - Comment management
/api/notifications/ - Notification system
/api/reminders/   - Reminder management
/api/reports/     - Reporting system
/api/users/       - User authentication and management
/api/location/    - Location services and geocoding
```

#### Communication Patterns

1. **Frontend → Backend Communication**:
   - **AJAX requests** using jQuery
   - **JSON payloads** for data exchange
   - **FormData** for file uploads
   - **HTTP methods**: GET, POST, PUT, DELETE with proper CORS headers

2. **Authentication Headers**:
   - Session tokens passed via cookies
   - Authorization checks on protected endpoints

3. **Real-time Updates**:
   - **Polling** for notifications and updates
   - **Page refresh** after CRUD operations
   - **Socket integration** planned for future (currently uses cron jobs)

4. **Notification System**:
   - Database-based notifications stored in `notifications` table
   - Email notifications via Resend API
   - In-app notifications with read/unread status

5. **Module Dependencies**:
   - Actions and Resources linked to Users (creator_id/publisher_id)
   - Comments related to Actions/Resource IDs
   - Participants connected to Actions via action_participants table

---

## ✅ 5. Database Relations

### Schema Overview

#### Primary Tables
- **users**: User accounts, profiles, and roles
- **actions**: Community actions and events
- **resources**: Offered/Requested resources
- **action_participants**: Junction table for action participation
- **comments**: User comments on actions/resources
- **reminders**: User reminder system
- **notifications**: Notification system
- **reports**: Content reporting system

#### Relationship Diagram

```
users (1) ←→ (M) actions                # User creates many actions
users (1) ←→ (M) resources             # User publishes many resources
users (1) ←→ (M) comments              # User creates many comments
users (1) ←→ (M) reminders             # User has many reminders
users (1) ←→ (M) notifications         # User receives many notifications
users (1) ←→ (M) reports               # User submits many reports

actions (1) ←→ (M) comments            # Action has many comments
resources (1) ←→ (M) comments          # Resource has many comments

actions (1) ←→ (M) action_participants # Action has many participants
users (1) ←→ (M) action_participants   # User joins many actions
```

#### Detailed Relationships

1. **User → Actions**: One-to-Many
   - `users.id` → `actions.creator_id`
   - Foreign key constraint with CASCADE delete

2. **User → Resources**: One-to-Many
   - `users.id` → `resources.publisher_id`
   - Foreign key constraint with CASCADE delete

3. **Action → Comments**: One-to-Many
   - `actions.id` → `comments.action_id`
   - Only one of (action_id, resource_id) can be set per comment

4. **Resource → Comments**: One-to-Many
   - `resources.id` → `comments.resource_id`
   - Only one of (action_id, resource_id) can be set per comment

5. **Action ↔ User**: Many-to-Many (through action_participants)
   - Junction table with unique constraint on (action_id, user_id)

6. **User → Notifications**: One-to-Many
   - `users.id` → `notifications.user_id`
   - Each notification belongs to one user

7. **User → Reports**: One-to-Many
   - `users.id` → `reports.reporter_id`
   - User can submit multiple reports

---

## ✅ 6. Navigation Structure

### Single Page Application (SPA) Approach

#### Main Navigation
- **Public Interface** (`vue/index.html`):
  - Hero section with call-to-action buttons
  - Filter/search section for actions & resources
  - Grid display of cards
  - Calendar/agenda view
  - Community map
  - Create modal for new content

- **Dashboard Interface** (`dashboard/index.html`):
  - Sidebar navigation with sections:
    - Dashboard (overview with stats)
    - Actions & Resources
    - Stories (planned feature)
    - Challenges (planned feature)
    - Reminders
    - Reports
    - Help Center
  - Dynamic content loading via page system

#### Routing Logic
- **Client-Side Navigation**: No page refreshes, uses JavaScript to manage views
- **Role-Based Access**: Navigation elements change based on user role
- **Modal System**: Create, details, report, and reminder modals
- **Geographic Navigation**: Map interface for location-based discovery

#### Navigation Components
1. **Top Navigation Bar** (Public site):
   - Logo and branding
   - Navigation links (Home, Actions & Aide, Stories, Défis, About)
   - User profile dropdown with dashboard access

2. **Dashboard Sidebar**:
   - Vertical navigation with icons
   - Collapsible sections
   - Active state highlighting

3. **Tabbed Interfaces**:
   - Create modal with Action/Resource tabs
   - Dashboard with multiple view modes

4. **Modal-Based Navigation**:
   - Details modal for content viewing
   - Create modal for content creation
   - Report/Reminder modals for additional actions

---

## ✅ 7. Current Module (Action & Aid)

### Actions Module
**Purpose**: Organize and manage community actions and events

#### Features
- **CRUD Operations**:
  - Create new actions with detailed information
  - Read (view and filter) existing actions
  - Update own actions with proper authorization
  - Delete own actions with proper authorization

- **Advanced Features**:
  - Date/time scheduling with duration calculation
  - Location selection with map integration
  - Image uploads for visual representation
  - Country-based filtering and organization
  - Participant management system
  - Comment system integration

#### Technical Implementation
- **Frontend**: JavaScript functions in `vue/assets/js/script.js`
- **Backend**: `ActionController` in `controllers/actionController.php`
- **API Endpoints**: All in `api/actions/` directory
- **Database**: `actions` table with relationships to users

### Resources Module
**Purpose**: Offer/Request system for resources and assistance

#### Features
- **CRUD Operations**:
  - Create offer/request for resources
  - Read (view and filter) available resources
  - Update own resource listings
  - Delete own resource listings

- **Resource Types**:
  - Offer (giving resources)
  - Request (needing resources)
  - Knowledge (skills/mentoring)

#### Technical Implementation
- **Frontend**: JavaScript functions in `vue/assets/js/script.js`
- **Backend**: `ResourceController` in `controllers/resourceController.php`
- **API Endpoints**: All in `api/resources/` directory
- **Database**: `resources` table with relationships to users

### Integration Points
- **Authentication**: Both modules require user authentication
- **Ownership**: Users can only modify their own content
- **Moderation**: Admin approval required for public display
- **Geographic**: Location-based filtering and mapping
- **Notifications**: System notifies creators of status changes
- **Comments**: Both modules support user comments
- **Reminders**: Users can set reminders for events
- **Reporting**: Content can be reported for moderation

### Global Explore Module
- **3D Globe Interface**: Interactive visualization using Three.js
- **Country-based Data**: Actions and resources organized by country
- **Filtering**: Search and sort capabilities for geographic content
- **Integration**: Connects with main map and location system