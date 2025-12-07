# Voices of Peace - Project Documentation

## ✅ 1. Technology Stack

### Frontend
- **HTML5, CSS3, JavaScript** - Core structure and styling
- **CSS** - Custom styling in `/public/css/` directory
- **JavaScript** - Custom client-side functionality in `/public/js/`
- **Client-side Validation** - Real-time form validation with JavaScript
- **AJAX** - Asynchronous communication for reactions and comments

### Backend
- **PHP 7.4+** - Server-side scripting language with OOP
- **MVC Architecture** - Model-View-Controller pattern implementation
- **PDO (PHP Data Objects)** - Database interaction layer with prepared statements
- **Sessions** - User session management
- **Password Hashing** - bcrypt for secure password storage

### Database
- **MySQL** - Relational database management system
- **utf8mb4** - Character set for full Unicode support

### Server
- **Apache** - Web server (compatible with XAMPP)
- **mod_rewrite** - For clean URL routing

### Framework
- **Custom MVC Framework** - Homegrown MVC implementation with front controller pattern

---

## ✅ 2. Project Structure

### MVC Architecture with Modular Organization

```
voices-of-peace/
├── index.php                          # Front Controller / Router
│
├── config/
│   └── database.php                   # Database Connection Class (PDO)
│
├── controllers/                       # APPLICATION CONTROLLERS
│   ├── AuthController.php            # Authentication & Authorization
│   ├── StoryController.php           # Front-end Story Management
│   ├── BackOfficeStoryController.php # Admin Story Management
│   ├── ReactionController.php        # Story Reactions System
│   ├── CommentController.php         # Comments Management
│   ├── ReportController.php          # Content Reporting
│   ├── ModerationController.php      # Admin Moderation Tools
│   └── ProfileController.php         # User Profile Management
│
├── models/                            # DATA MODELS (OOP Classes)
│   ├── User.php                      # User Model with Authentication
│   ├── Story.php                     # Story Model with CRUD
│   ├── Reaction.php                  # Reaction Model with Aggregations
│   ├── Comment.php                   # Comment Model with Moderation
│   └── Report.php                    # Report Model for Content Flagging
│
├── views/                             # VIEW TEMPLATES
│   │
│   ├── auth/                         # Authentication Views
│   │   ├── login.php                 # Login Form
│   │   └── register.php              # Registration Form
│   │
│   ├── frontoffice/                  # PUBLIC INTERFACE
│   │   ├── layouts/
│   │   │   ├── header.php           # Front-end Header with Navigation
│   │   │   └── footer.php           # Front-end Footer
│   │   │
│   │   └── stories/
│   │       ├── index.php            # Stories Listing (with filters)
│   │       ├── show.php             # Single Story View (with reactions)
│   │       ├── create.php           # Create Story Form
│   │       └── edit.php             # Edit Story Form
│   │
│   ├── backoffice/                   # ADMIN INTERFACE
│   │   ├── layouts/
│   │   │   ├── header.php           # Admin Header with Navigation
│   │   │   └── footer.php           # Admin Footer
│   │   │
│   │   ├── stories/
│   │   │   ├── index.php            # Admin Stories Dashboard
│   │   │   ├── show.php             # Story Details (with reactions)
│   │   │   ├── create.php           # Admin Create Story
│   │   │   └── edit.php             # Admin Edit Story
│   │   │
│   │   └── moderation/
│   │       ├── index.php            # Moderation Dashboard
│   │       ├── reports.php          # All Reports
│   │       ├── review_report.php    # Review Single Report
│   │       ├── flagged_comments.php # Flagged Comments
│   │       └── banned_users.php     # Banned Users Management
│   │
│   ├── profile/                      # USER PROFILE VIEWS
│   │   ├── index.php                # Profile Dashboard
│   │   ├── edit.php                 # Edit Profile
│   │   ├── change_password.php      # Change Password Form
│   │   └── my_stories.php           # User's Stories Management
│   │
│   └── reports/
│       └── form.php                  # Report Story Form
│
├── public/                            # PUBLIC ASSETS
│   ├── css/
│   │   ├── frontoffice.css          # Front-end Styles (with reactions)
│   │   └── backoffice.css           # Admin Styles (with reaction stats)
│   │
│   └── js/
│       ├── validation.js            # Client-side Form Validation
│       └── reactions.js             # Reaction System AJAX Handler
│
└── voices_peace_layout.txt          # Project Architecture Documentation
```

---

## ✅ 3. Authentication System

### Session-Based Authentication

#### User Roles
- **User** (`role: user`):
  - Create and share stories
  - React to stories
  - Comment on stories
  - Manage personal profile
  - View own stories and statistics

- **Admin** (`role: admin`):
  - Full access to user stories
  - Content moderation tools
  - User management capabilities
  - Admin dashboard access
  - Report handling functionality

#### Authentication Flow
1. **Registration Process**:
   - User visits registration form
   - Input validation (username, email, password strength)
   - Password hashing with bcrypt
   - Avatar generation from full name initials
   - Auto-login after successful registration

2. **Login Process**:
   - User provides username/email and password
   - Password verification with `password_verify()`
   - Session variables set upon successful login
   - Role-based redirection (admin vs regular user)

3. **Session Management**:
   - Session-based authentication
   - Middleware functions for route protection
   - Role-based access control

4. **Security Features**:
   - Password hashing with bcrypt
   - Input sanitization
   - Session-based authentication
   - Role-based access control
   - Middleware to protect sensitive routes

#### Authentication Middleware
- `requireLogin()` - Ensures user authentication
- `requireAdmin()` - Ensures user is admin with required privileges

---

## ✅ 4. How Modules Communicate

### Front Controller Pattern

#### Routing System
The application uses a front controller pattern where all requests go through `index.php`, which routes to appropriate controllers based on URL parameters:

**URL Pattern**: `index.php?controller=CONTROLLER&action=ACTION&id=ID`

#### Communication Patterns

1. **Frontend → Backend Communication**:
   - **GET requests**: For page navigation and data retrieval
   - **POST requests**: For form submissions and data modifications
   - **AJAX**: For real-time interactions (reactions, comments)

2. **Controller → Model Communication**:
   - Controllers instantiate Models with database connections
   - Models handle all database operations
   - Controllers pass data between Models and Views

3. **Module Dependencies**:
   - **Stories** linked to **Users** via `user_id` foreign key
   - **Reactions** linked to **Stories** and **Users**
   - **Comments** linked to **Stories** and **Users**
   - **Reports** linked to **Stories** and **Users**

#### API Communication
- **Reaction System**: Real-time AJAX API endpoints
- **Comment System**: AJAX-based comment loading
- **Validation**: Client-side validation with server-side backup

---

## ✅ 5. Database Relations

### Schema Overview

#### Primary Tables
- **users**: User accounts and profiles
- **stories**: User-generated stories
- **reactions**: Story reactions system
- **comments**: Story comments with moderation
- **reports**: Content reporting system
- **flagged_words**: Auto-moderation dictionary
- **content_violations**: Flagged content tracking
- **ban_log**: User ban history

#### Relationship Diagram

```
users (1) ←→ (M) stories              # User creates many stories
users (1) ←→ (M) reactions            # User creates many reactions
users (1) ←→ (M) comments             # User creates many comments
users (1) ←→ (M) reports              # User submits many reports

stories (1) ←→ (M) reactions          # Story has many reactions
stories (1) ←→ (M) comments           # Story has many comments
stories (1) ←→ (M) reports            # Story receives many reports

comments (1) ←→ (M) content_violations # Comment may have violations
```

#### Detailed Relationships

1. **User → Stories**: One-to-Many
   - `users.id` → `stories.user_id` (nullable for anonymous stories)
   - Foreign key constraint with optional CASCADE

2. **User → Reactions**: One-to-Many
   - `users.id` → `reactions.user_id` (nullable for anonymous reactions)
   - Allows anonymous reactions

3. **Story → Reactions**: One-to-Many
   - `stories.id` → `reactions.story_id`
   - Foreign key constraint with CASCADE delete

4. **Story → Comments**: One-to-Many
   - `stories.id` → `comments.story_id`
   - Foreign key constraint

5. **Story → Reports**: One-to-Many
   - `stories.id` → `reports.story_id`
   - Foreign key constraint

6. **User → Reports**: One-to-Many
   - `users.id` → `reports.reported_by`
   - Foreign key constraint

---

## ✅ 6. Navigation Structure

### Multi-Interface Architecture

#### Frontoffice Interface (`/views/frontoffice/`)
- **Public Story Sharing**:
  - Stories listing with filtering options
  - Single story view with reactions
  - Create/edit story forms
  - User authentication pages

- **Navigation Components**:
  - Logo with home link ("🕊️ Voices of Peace")
  - Stories link for story browsing
  - "✏️ Share Story" button (visible when logged in)
  - User menu dropdown with profile options
  - Login/Register buttons (visible when not logged in)

#### Backoffice Interface (`/views/backoffice/`)
- **Admin Dashboard**:
  - Stories management
  - Moderation tools
  - Statistics and analytics
  - Report handling

- **Admin Navigation**:
  - "🛡️ Admin Panel" link for admins only
  - Stories management section
  - Moderation tools section
  - User management capabilities

#### Profile Interface (`/views/profile/`)
- **User Profile Management**:
  - Profile dashboard
  - Profile editing
  - Password change
  - User's stories management

#### Navigation Flow
- **Public Interface**: Accessible to all users
- **User Interface**: Requires authentication
- **Admin Interface**: Requires admin role
- **Responsive Design**: Adapts to different screen sizes

---

## ✅ 7. Current Modules

### Core Modules Analysis

#### 1. Stories Module
**Purpose**: Story creation, sharing, and management platform

**Features**:
- **CRUD Operations**:
  - Create stories with rich content (title, content, excerpt, images)
  - Read stories with filtering by theme, language, status
  - Update own stories with proper authorization
  - Delete own stories with proper authorization

- **Advanced Features**:
  - Content filtering by theme, language, status
  - Image upload and management system
  - View counting functionality
  - Excerpt generation
  - Privacy settings (public, private)
  - Status management (draft, published)

**Technical Implementation**:
- **Frontend**: StoryController handles business logic
- **Backend**: Story model with PDO database operations
- **API Endpoints**: Multiple controller methods for different operations
- **Database**: `stories` table with relationships to users and reactions

#### 2. User Authentication Module
**Purpose**: User management, registration, and authentication system

**Features**:
- **Registration System**:
  - Username and email validation
  - Password strength requirements
  - Avatar generation from initials
  - Duplicate prevention

- **Login/Logout**:
  - Username or email login
  - Session management
  - Role-based redirection

- **Profile Management**:
  - Profile editing
  - Password change
  - Bio management
  - Statistics display

**Technical Implementation**:
- **Frontend**: AuthController with session management
- **Backend**: User model with bcrypt password hashing
- **Security**: Input validation, session security, role-based access

#### 3. Reactions Module
**Purpose**: Interactive engagement system for stories

**Features**:
- **Four Reaction Types**:
  - Heart (love/appreciation)
  - Support (show support)
  - Inspiration (this inspired you)
  - Solidarity (stand in solidarity)

- **Advanced Features**:
  - Toggle functionality (add/remove reactions)
  - Real-time AJAX updates
  - Anonymous and authenticated reactions
  - Reaction statistics and analytics
  - Aggregated counts display

**Technical Implementation**:
- **Frontend**: reactions.js with AJAX communication
- **Backend**: ReactionController and Reaction model
- **Database**: `reactions` table linked to stories and users

#### 4. Comments Module
**Purpose**: Community discussion and feedback system

**Features**:
- **Comment Management**:
  - Add comments to stories
  - Comment moderation system
  - Character limit enforcement
  - Soft delete functionality

- **Moderation Features**:
  - Auto-flagging system for inappropriate content
  - Admin moderation tools
  - Content violation tracking

**Technical Implementation**:
- **Frontend**: CommentController with AJAX handling
- **Backend**: Comment model with auto-moderation
- **Database**: `comments` table linked to stories and users

#### 5. Reporting Module
**Purpose**: Content moderation and reporting system

**Features**:
- **Report Submission**:
  - Multiple report reasons (inappropriate, spam, hate speech, etc.)
  - Report description field
  - Prevention of duplicate reports

- **Moderation Tools**:
  - Admin review workflow
  - Action tracking (dismiss, delete, ban)
  - Ban management system
  - Report statistics

**Technical Implementation**:
- **Frontend**: ReportController with form handling
- **Backend**: Report model with moderation workflow
- **Database**: `reports` table with comprehensive tracking

#### 6. Moderation Module
**Purpose**: Admin tools for content and user management

**Features**:
- **Dashboard**: Overview of reports, flagged content, statistics
- **Content Review**: Review and action reports
- **Comment Moderation**: Handle flagged comments
- **User Management**: Ban/unban users
- **Analytics**: Content and engagement statistics

**Technical Implementation**:
- **Frontend**: ModerationController with admin interface
- **Backend**: Comprehensive moderation logic
- **Database**: Multiple tables for violation tracking

### Integration Points
- **Authentication**: All modules require proper authentication
- **Authorization**: Role-based access control for sensitive operations
- **Database Relations**: All modules interconnected through foreign keys
- **Security**: Input validation and sanitization across all modules
- **User Experience**: Consistent UI/UX across all interfaces
- **Data Integrity**: Foreign key constraints and validation rules