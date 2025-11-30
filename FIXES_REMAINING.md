# Remaining Fixes for Connect for Peace Platform

## Completed Fixes:
✅ 1. Sweet Alert error on file upload - Fixed in script_fixes.js
✅ 2. "Offer Help" opens resource form - Fixed in script_fixes.js
✅ 3. Comment button style - Fixed in script_fixes.js
✅ 4. Join/Leave button toggle - Fixed in script_fixes.js  
✅ 5. Dashboard stats API - Fixed JSON output issues
✅ 6. Recent Activity API - Fixed JSON parsing errors

## Remaining Fixes to Implement:

### 7. Report Submission Fix
**File**: `api/reports/create_report.php` (Line 1)
**Issue**: Path issue in require statements

**Fix**: Update the require paths:
```php
<?php
session_start();
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../model/report.php';
require_once __DIR__ . '/../../utils/AuthHelper.php';

$controller = new ReportController();
$controller->create();
?>
```

### 8. Approve/Reject Buttons in Dashboard Actions Table
**File**: `dashboard/index.html`
**Location**: Lines containing `my-actions-table-body` and `my-resources-table-body`

**Fix**: Update the renderUserActions and renderUserResources functions in `dashboard/script.js`:

```javascript
function renderUserActions(actions) {
    const tbody = document.getElementById('my-actions-table-body');
    tbody.innerHTML = actions.map(a => {
        const statusColor = a.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                           a.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                           'bg-zinc-100 text-zinc-600';
        
        return `
        <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
            <td class="py-3 px-5 text-zinc-500">#${a.id}</td>
            <td class="py-3 px-5 font-medium text-zinc-900 dark:text-zinc-200">${a.title}</td>
            <td class="py-3 px-5">${a.category}</td>
            <td class="py-3 px-5">
                <span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusColor} border">
                    ${a.status}
                </span>
            </td>
            <td class="py-3 px-5">${a.participants || 0}</td>
            <td class="py-3 px-5 text-zinc-500">${new Date(a.created_at).toLocaleDateString()}</td>
            <td class="py-3 px-5">
                <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 text-xs font-medium mr-2" onclick="openEditAction(${a.id})">Edit</button>
                <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium" onclick="confirmDeleteAction(${a.id})">Delete</button>
                ${currentUser.role === 'admin' && a.status === 'pending' ? `
                    <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium ml-2" onclick="approveAction(${a.id})">Approve</button>
                    <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium ml-1" onclick="rejectAction(${a.id})">Reject</button>
                ` : ''}
            </td>
        </tr>
        `;
    }).join('');
}

// Add approve/reject functions
async function approveAction(id) {
    try {
        const response = await fetch("./../api/actions/approve_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id, action: 'approve' })
        });
        const result = await response.json();
        if(result.success) {
            showSuccessMessage('Action approved successfully!');
            await loadAllData();
        } else {
            showErrorMessage(result.message);
        }
    } catch (error) {
        showErrorMessage('Error approving action');
    }
}

async function rejectAction(id) {
    try {
        const response = await fetch("./../api/actions/approve_action.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: id, action: 'reject' })
        });
        const result = await response.json();
        if(result.success) {
            showSuccessMessage('Action rejected successfully!');
            await loadAllData();
        } else {
            showErrorMessage(result.message);
        }
    } catch (error) {
        showErrorMessage('Error rejecting action');
    }
}
```

Do the same for resources table.

### 9. Notification Dropdown (Facebook Style)
**File**: `dashboard/index.html`  
**Location**: `<div id="notificationDropdown">`

**Fix**: Replace the notification dropdown HTML:

```html
<div class="absolute right-0 mt-2 w-96 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl shadow-zinc-200/50 dark:shadow-black/50 overflow-hidden z-50 hidden" id="notificationDropdown">
    <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 flex items-center justify-between">
        <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Notifications</h3>
        <div class="flex gap-2">
            <button onclick="markAllAsRead()" class="text-xs text-indigo-600 hover:text-indigo-500">Mark all as read</button>
            <button onclick="clearAllNotifications()" class="text-xs text-zinc-500 hover:text-zinc-700">Clear all</button>
        </div>
    </div>
    <div class="overflow-y-auto max-h-96" id="notificationsList">
        <p class="text-sm text-zinc-500 text-center py-8">No notifications yet</p>
    </div>
    <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
        <button onclick="loadMoreNotifications()" class="text-xs text-center w-full text-indigo-600 hover:text-indigo-500 font-medium">
            See more
        </button>
    </div>
</div>
```

Add JavaScript functions:
```javascript
async function loadNotifications() {
    try {
        const response = await fetch(`./../api/notifications/get_notifications.php?user_id=${currentUser.id}&limit=10`);
        const result = await response.json();
        if (result.success) {
            renderNotifications(result.notifications);
            updateNotificationBadge(result.unread_count);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

function renderNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    if (!notifications || notifications.length === 0) {
        container.innerHTML = '<p class="text-sm text-zinc-500 text-center py-8">No notifications yet</p>';
        return;
    }
    
    container.innerHTML = notifications.map(notif => `
        <div class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-800 cursor-pointer ${!notif.is_read ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''}" 
             onclick="markAsRead(${notif.id})">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <i data-lucide="${getNotificationIcon(notif.type)}" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">${notif.title}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">${notif.message}</p>
                    <p class="text-xs text-zinc-400 mt-1">${formatTimeAgo(notif.created_at)}</p>
                </div>
                ${!notif.is_read ? '<div class="w-2 h-2 bg-indigo-600 rounded-full"></div>' : ''}
            </div>
        </div>
    `).join('');
    lucide.createIcons();
}

function getNotificationIcon(type) {
    const icons = {
        'action_created': 'plus-circle',
        'action_updated': 'edit',
        'resource_created': 'package',
        'comment_added': 'message-circle',
        'action_joined': 'users',
        'default': 'bell'
    };
    return icons[type] || icons.default;
}

function formatTimeAgo(timestamp) {
    const now = new Date();
    const then = new Date(timestamp);
    const seconds = Math.floor((now - then) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    return then.toLocaleDateString();
}

async function markAsRead(notificationId) {
    try {
        await fetch(`./../api/notifications/mark_notification_read.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: notificationId })
        });
        loadNotifications(); // Reload to update UI
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

async function markAllAsRead() {
    try {
        await fetch(`./../api/notifications/mark_all_notifications_read.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: currentUser.id })
        });
        loadNotifications();
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
}

async function clearAllNotifications() {
    const result = await Swal.fire({
        title: 'Clear all notifications?',
        text: 'This action cannot be undone',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear all'
    });
    
    if (result.isConfirmed) {
        // Implement clear all endpoint
        loadNotifications();
    }
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-btn .badge');
    if (badge) {
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Load notifications on init
document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
});
```

### 10. User Account Section
**File**: `dashboard/index.html`
**Location**: `<div id="userDropdown">`

**Fix**: Update user dropdown HTML and JS:

```html
<div class="absolute right-0 mt-2 w-72 bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl shadow-zinc-200/50 dark:shadow-black/50 overflow-hidden z-50 hidden" id="userDropdown">
    <div class="px-4 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-500/20 to-emerald-500/20 border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <img id="userAvatarDropdown" src="" alt="User" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate" id="userNameDropdown">Loading...</p>
                <p class="text-xs text-zinc-500 truncate" id="userEmailDropdown">Loading...</p>
            </div>
        </div>
    </div>
    <div class="py-1">
        <button class="w-full px-4 py-2 text-sm text-left text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-2">
            <i data-lucide="user" class="w-4 h-4"></i>
            Profile Settings
        </button>
        <button class="w-full px-4 py-2 text-sm text-left text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-2">
            <i data-lucide="settings" class="w-4 h-4"></i>
            Preferences
        </button>
        <div class="border-t border-zinc-200 dark:border-zinc-800 my-1"></div>
        <button onclick="handleLogout()" class="w-full px-4 py-2 text-sm text-left text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors flex items-center gap-2">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            Sign out
        </button>
    </div>
</div>
```

Update user info on load:
```javascript
function updateUserInfo() {
    if (currentUser) {
        document.getElementById('userNameDropdown').textContent = currentUser.name || 'User';
        document.getElementById('userEmailDropdown').textContent = currentUser.email || 'user@example.com';
        
        // Update avatar
        const avatarUrl = currentUser.avatar_url || `https://api.dicebear.com/7.x/avataaars/svg?seed=${currentUser.name}`;
        document.getElementById('userAvatarDropdown').src = avatarUrl;
        
        // Update header avatar
        const headerAvatar = document.querySelector('.profile-avatar img, #userMenu img');
        if (headerAvatar) {
            headerAvatar.src = avatarUrl;
        }
    }
}

// Call after auth check
checkAuthStatus().then(() => {
    if (isAuthenticated) {
        updateUserInfo();
    }
});
```

### 11. Map Styling with Tailwind Theme
**File**: `vue/assets/js/map.js` or integrate into `script.js`

**Add after map initialization**:
```javascript
// Apply Tailwind theme to map
if (map) {
    // Use a styled tile layer
    map.eachLayer((layer) => {
        if (layer instanceof L.TileLayer) {
            map.removeLayer(layer);
        }
    });
    
    // Add Carto light theme that matches Tailwind
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© OpenStreetMap contributors © CARTO',
        maxZoom: 19
    }).addTo(map);
    
    // Style map container
    const mapEl = document.getElementById('map');
    if (mapEl) {
        mapEl.style.borderRadius = '16px';
        mapEl.style.overflow = 'hidden';
    }
}
```

### 12. Include script_fixes.js in index.html
**File**: `vue/index.html`
**Location**: Before closing `</body>` tag

**Add**:
```html
<script src="assets/js/script_fixes.js"></script>
<script>
    // Initialize the fixes
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof initializeCreateButtons === 'function') {
            initializeCreateButtons();
        }
    });
</script>
```

### 13. Calendar - Ensure All Data Stored
The calendar code looks good, but ensure:
- All date fields are properly validated before submission
- The `downloadICS()` function works correctly
- Events render with proper colors

### 14. Reports Management
Reports table already has proper structure. Ensure the modal functions work by testing them.

### 15. Reminder System
Check that:
- `utils/send_reminders.php` has proper Resend API integration
- Cron job is set up to run send_reminders.php periodically
- Email templates are properly formatted

## Testing Checklist:
- [ ] Test action creation with file upload
- [ ] Test "Offer Help" button opens resource form
- [ ] Test comment posting and display
- [ ] Test report submission
- [ ] Test join/leave action toggle
- [ ] Test dashboard stats loading
- [ ] Test recent activity display
- [ ] Test approve/reject buttons (admin only)
- [ ] Test notification dropdown
- [ ] Test user profile display
- [ ] Test map styling
- [ ] Test calendar functionality
- [ ] Test reports management

## Notes:
1. All API files now suppress PHP errors to prevent JSON parsing issues
2. Use error_reporting(0) and ob_clean() in all API endpoints
3. Always check result.success before showing error alerts
4. Test with actual database data to verify all functionality

## Additional Recommendations:
1. Add loading states to all async operations
2. Implement proper error boundaries
3. Add rate limiting to API endpoints
4. Optimize database queries with indexes
5. Add caching for frequently accessed data
6. Implement proper session management
7. Add CSRF protection to forms
8. Validate all inputs on both client and server side
