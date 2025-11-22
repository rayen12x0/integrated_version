# Connect for Peace - Community Action & Resource Platform

## Project Idea
"Connect for Peace" aims to build a community platform where users can organize and participate in various actions and offer/request resources to help each other. The goal is to foster peace, inclusion, and community aid through small, impactful acts.

## Progress Made
We have successfully implemented and fixed the complete CRUD (Create, Read, Update, Delete) functionality for both **Actions** and **Resources** with a robust authentication and authorization system, and have now added **Image Upload Functionality**.


### 2. Complete CRUD Functionality for Actions
*   **Action Creation:** Users can create new actions through the dedicated form in the modal. The form data is validated on the client-side and then sent via AJAX to the backend API (`/my_work/api/create_action.php`). The backend processes this data, assigns the action to the current user, and returns a success message.
*   **Action Display:** Actions stored in the database are fetched and displayed in both the public site and user dashboard. The JavaScript (`vue/assets/js/script.js` and `dashboard/script.js`) retrieves data from `/my_work/api/get_actions.php`, properly formats it, and renders action cards.
*   **Action Update:** Users can edit their own actions using the "Edit" button in the dashboard. The edit modal populates with existing action data, allowing updates to title, category, theme, description, and location.
*   **Action Deletion:** Users can delete their own actions with proper confirmation dialog. Admins can delete any action.

### 3. Complete CRUD Functionality for Resources
*   **Resource Creation:** Users can create new resources through the resource form tab. The backend API (`/my_work/api/create_resource.php`) processes resource data with proper validation.
*   **Resource Display:** Resources are fetched from the database and displayed alongside actions in both public and dashboard interfaces.
*   **Resource Update:** Users can edit their own resources using the "Edit" button in the dashboard.
*   **Resource Deletion:** Users can delete their own resources with confirmation. Admins can delete any resource.

### 4. Dashboard Interface
*   **User Dashboard:** Provides interface for users to manage their own actions and resources (full CRUD on own items).
*   **Admin Dashboard:** Provides interface for admins to manage all platform content with additional features like content approval.
*   **Separation of Concerns:** Public site allows viewing and creating (with authentication) but restricts edit/delete operations to dashboard interfaces only.

### 5. Ownership Verification
*   **Content Ownership:** Implemented logic to ensure that regular users can only edit or delete actions/resources they have personally created/published. This requires associating actions/resources with a `user_id` (from the authenticated user's session) and checking this ownership before allowing modification or deletion.
*   **Admin Privileges:** Admin users have elevated permissions to manage (create, read, update, delete) all actions and resources, regardless of who created them.

### 6. Approval Workflow
*   **Pending Content:** All user-created actions and resources start with 'pending' status and require admin approval before becoming visible to the general public.
*   **Admin Oversight:** Admin dashboard includes functionality to approve or reject pending content submissions.





### 9. Image Upload Functionality ✅ NOW IMPLEMENTED
*   **File Upload System:** Complete server-side file upload handling for both actions and resources.
*   **Image Validation:** Proper file type and size validation with security checks (only images, max 5MB).
*   **Database Integration:** Image paths properly stored in `image_url` column and mapped correctly in frontend displays.
*   **Frontend Integration:** Both public site and dashboard forms now support file uploads with conditional submission (FormData vs JSON).
*   **Image Display:** Uploaded images now properly display in all views (cards, modals, dashboards).
*   **File Management:** Automatically created `/uploads/actions` and `/uploads/resources` directories with unique file naming.
*   **Cleanup:** Automatic deletion of old images when content is updated or deleted to prevent orphaned files.

## Missing Features & Future Enhancements

### 1. User Profile Management
*   **Current State:** Basic user creation is implemented, but there's no dedicated profile management system.
*   **Future Idea:** Implement a user profile section where users can update their information, avatar, and preferences.

### 2. Enhanced Data Models and Relationships
*   **Current State:** The `actions` and `resources` tables are the primary data models. Creator/publisher information, tags, participants, and comments are currently placeholders in the frontend.
*   **Future Idea:**
    *   **User Profiles:** Expand the `users` table (`id`, `name`, `email`, `password_hash`, `avatar_url`, `badge`, `is_admin`, `created_at`, `updated_at`) to store complete user details.
    *   **Tags System:** Implement a proper tagging system with `tags` table (`id`, `name`) and many-to-many relationship tables like `action_tags` and `resource_tags`.
    *   **Participants/Response Tracking:** Create a `participants` or `responses` table to track which users have responded to specific resources or joined specific actions.
    *   **Comments System:** Implement a `comments` table to store user feedback and comments on both actions and resources.

### 3. Notification System
*   **Current State:** Limited notification functionality exists.
*   **Future Idea:** Develop a comprehensive notification system to alert users about new responses to their resources, updates to actions they've joined, or admin decisions on their content.

### 4. Enhanced Search and Filter Options
*   **Current State:** Basic filtering by type, category, and status is available.
*   **Future Idea:** Implement advanced search features like geographic proximity, date range, keyword search, and more granular filters.

### 5. Additional Image Enhancements
*   **Current State:** Basic image upload functionality is now implemented with file validation, unique naming, and proper storage.
*   **Future Idea:**
    *   Add image preview before upload in the frontend
    *   Implement image resizing/cropping to standardize dimensions
    *   Add support for image galleries (multiple images per action/resource)
    *   Improve security with image format validation and virus scanning

## Setup Instructions
1.  **XAMPP Setup:** Ensure XAMPP (or any Apache/MySQL server environment) is installed and running. Apache and MySQL services must be active.
2.  **Project Placement:** Place the `my_work` folder inside your XAMPP's `htdocs` directory (e.g., `D:\xampp\htdocs\my_work\`).
3.  **Database Creation:**
    *   Open phpMyAdmin (usually accessible via `http://localhost/phpmyadmin/`).
    *   Create a new database named `connect_for_peace`.
    *   Import the `model/schema.sql` file into this new database to create the `users`, `actions`, and `resources` tables and populate them with sample data.
4.  **Access the Application:** Open your web browser and navigate to `http://localhost/my_work/vue/index.html`.
5.  **Test Functionality:**
    *   Register a new user account via the registration page
    *   Login with your new account
    *   Create new actions and resources
    *   Visit the dashboard to manage your content
    *   Confirm that you can only edit/delete your own items
    *   Check the browser console (F12) for any JavaScript errors and the Apache error log (`D:\xampp\apache\logs\error.log`) for PHP errors if issues arise.
    *   Verify that new content has "pending" status and requires admin approval.

## Technologies Used
*   **Frontend:** HTML, CSS, JavaScript, SweetAlert2, Leaflet.js for maps
*   **Backend:** PHP pdo with file upload handling and validation
*   **Database:** MySQL
*   **Server:** Apache (via XAMPP)
*   **API:** RESTful endpoints for data communication

## New Authentication System & UI/UX Enhancements

### Authentication Features
- **Role-based Access**: Admin and Community Member roles
- **Session Management**: PHP sessions with localStorage fallback
- **Modern Login Interface**: Role selection with particle effects
- **Persistent Authentication**: Maintained across page navigation
- **Secure Logout**: Proper session cleanup

### Advanced UI/UX Features
- **Modern Animations**: Liquid morphing, particle effects, and smooth transitions
- **Enhanced Map**: Location search, marker clustering, and filtering
- **Interactive Elements**: Ripple effects, hover animations, and micro-interactions
- **Responsive Design**: Works on all device sizes
- **Accessibility**: Keyboard navigation and reduced motion support

### Admin Capabilities
- **Content Moderation**: Approve/reject actions and resources
- **User Management**: View and manage community content
- **Analytics Dashboard**: Monitor platform usage and engagement

## API Endpoints Added
- `/api/set_session.php` - Set user session
- `/api/logout.php` - Logout user
- `/auth/login.html` - Modern login interface

### Security Improvements
- Proper session management
- Role-based access control
- Input validation and sanitization
- Authorization checks for all sensitive operations

### Performance Enhancements
- Improved map marker clustering
- Optimized data loading
- Efficient animations with requestAnimationFrame
- Reduced motion support for accessibility





 ### How authentication works in your system:

   1. Session-based authentication - Uses PHP sessions to track user state
   2. Mock user system - Instead of a real database, the system uses two predefined users:
      - User ID 1: Admin user with admin privileges
      - User ID 2: Regular user with standard privileges

   3. Login flow:
      - User visits auth/login.html
      - Selects either "Admin" or "Community Member" role
      - JavaScript in auth/login.js calls api/set_session.php with the selected user ID
      - Session is stored in PHP server-side
      - User is redirected to dashboard

   4. Session management:
      - api/check_auth.php - Checks if user is authenticated by looking at session data
      - api/set_session.php - Sets session for user after login
      - api/logout.php - Destroys the user session
      - utils/AuthHelper.php - Provides centralized authentication functions

   5. Role-based access:
      - Admin users (ID=1) can approve/reject content, see all content
      - Regular users (ID=2) can only manage their own content
      - Unauthenticated users are redirected to login pages

   6. Frontend integration:
      - Vue frontend checks auth status via api/check_auth.php
      - Dashboard checks auth on page load
      - Auth state is stored in localStorage as fallback

## Global Explore Functionality

### Overview
The Global Explore feature provides an interactive 3D globe experience that allows users to discover actions and resources worldwide. The system includes country-level data visualization, filtering capabilities, and seamless integration with the main map interface.

### Key Features

#### 1. Interactive 3D Globe (globale_explore/index.html)
* **Three.js Integration**: Utilizes Three.js for a visually appealing rotating 3D globe
* **Country Hover Detection**: Displays country names when users hover over them
* **Country Click Functionality**: Clicking a country loads associated actions and resources
* **Auto-rotation Control**: Globe rotation automatically stops when user interacts or opens the country panel
* **Responsive Design**: Adapts to different screen sizes and touch devices

#### 2. Country Data Panel (Right-side)
* **Dynamic Content Loading**: Shows all approved actions and resources for selected country
* **Filtering & Sorting**: Users can filter by type (actions/resources), search by keywords, and sort by date, name, or category
* **Detailed Statistics**: Shows counts of actions and resources per country
* **Visual Indicators**: Enhanced UI with badges, date displays, and participant counts

#### 3. Enhanced API Endpoints
* **Country-based Queries**: `/api/get_actions_by_country.php` and `/api/get_resources_by_country.php`
* **Comprehensive Country Data**: `/api/get_country_statistics.php` for detailed statistics
* **All Countries List**: `/api/get_countries_with_data.php` to get countries with available content
* **Location-based Queries**: `/api/get_country_locations.php`, `/api/search_by_location.php`
* **Nearby Location Search**: `/api/get_nearby_locations.php` for proximity-based discovery
* **Unified Location Endpoint**: `/api/get_location_data.php` combining country and nearby features

#### 4. Database Integration
* **Country Field Support**: Actions and resources tables include country fields for location-based queries
* **Indexing**: Database indexes on country fields for optimized query performance
* **Case-insensitive Matching**: Proper handling of various capitalization formats (France, france, FRANCE)

#### 5. Map Integration
* **Seamless Navigation**: Clicking items in the country panel navigates to precise locations on the main map
* **Marker Customization**: Different markers for actions (green) and resources (blue) with appropriate labels
* **Popup Information**: Detailed information displayed when clicking map markers

#### 6. User Experience Enhancements
* **Loading States**: Visual feedback during data loading operations
* **Error Handling**: Clear error messages when data is unavailable
* **Keyboard Navigation**: Support for keyboard controls when possible
* **Accessibility**: Proper contrast and screen reader support

### How It Works

1. **Globe Interaction**: Users interact with the rotating 3D globe using mouse/trackpad or touch
2. **Country Selection**: Clicking a country triggers an API call to fetch related data
3. **Data Display**: Results are shown in the right-side panel with filtering options
4. **Navigation**: Clicking items navigates to the main map interface at the exact location
5. **Integration**: Works seamlessly with existing authentication and data management systems

### API Design
* **RESTful Endpoints**: Consistent API structure with proper error handling
* **JSON Response Format**: Standardized response format with success/error indicators
* **Security**: Proper authentication checks where required
* **Performance**: Optimized queries with indexing and caching where applicable

### File Structure
```
vue/globale_explore/
├── index.html          # Main globe interface
├── script.js           # Globe interaction logic, API calls, panel management
├── style.css           # Enhanced UI styling, animations, responsive design
├── # Globe uses Three.js for 3D rendering
```

### Integration Points
* **Database**: Uses existing actions/resources tables with country fields
* **Authentication**: Works with the existing auth system
* **Map System**: Connects to the main Leaflet.js map interface
* **User Dashboard**: Maintains consistency with existing UI/UX patterns

### Future Enhancements
* **Heat Map Visualization**: Color countries based on activity density
* **Comparison Tool**: Compare multiple countries side-by-side
* **Timeline View**: Show upcoming actions chronologically
* **Category Visualization**: Pie charts for resource/action categories
* **Real-time Updates**: WebSocket integration for live updates
* **Multi-language Support**: Country names in local languages
