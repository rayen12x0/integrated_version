// Dashboard script with fixed functionality for all CRUD operations

console.log('Dashboard script loading...');
console.log('Leaflet available:', typeof L !== 'undefined');

// DOM Elements for dashboard
const sidebar = document.getElementById("sidebar");
const sidebarToggle = document.getElementById("sidebarToggle");
const headerToggle = document.getElementById("headerToggle");
const navItems = document.querySelectorAll(".nav-item");
const pages = document.querySelectorAll(".page");
const notificationBtn = document.getElementById("notificationBtn");
const notificationDropdown = document.getElementById("notificationDropdown");
const userMenu = document.getElementById("userMenu");
const userDropdown = document.getElementById("userDropdown");
const createActionBtn = document.getElementById("createActionBtn");
const createModal = document.getElementById("createModal");
const closeModal = document.getElementById("closeModal");
const contactForm = document.getElementById("contactForm");
const faqQuestions = document.querySelectorAll(".faq-question");

// Updated to use the new tab structure
const tabBtns = document.querySelectorAll(".tab-btn:not(.nav-item)");
const tabContents = document.querySelectorAll(".tab-content:not(.page)");

// Authentication state
let isAuthenticated = false;
let currentUser = null;

// Check authentication status on page load
document.addEventListener('DOMContentLoaded', async () => {
    console.log('Dashboard DOM loaded');
    console.log('Checking dependencies...');
    console.log('- Leaflet:', typeof L !== 'undefined' ? 'OK' : 'MISSING');
    console.log('- SweetAlert2:', typeof Swal !== 'undefined' ? 'OK' : 'MISSING');

    await checkAuthStatus();
    initializeApp();

    // Set up form submissions
    document.getElementById('actionForm')?.addEventListener('submit', submitActionForm);
    document.getElementById('resourceForm')?.addEventListener('submit', submitResourceForm);

    // Add event listeners for location picker buttons
    document.querySelectorAll('.pick-location-btn').forEach(button => {
        button.addEventListener('click', function() {
            const formType = this.getAttribute('data-form');
            openLocationPicker(formType);
        });
    });

    // Event listeners for location input blur are not needed as geocoding is handled in the form submission
    // The map picker functionality will handle location selection instead

    // Add event listeners for modal controls
    document.getElementById('confirmLocationBtn')?.addEventListener('click', confirmLocationSelection);
    document.getElementById('cancelLocationBtn')?.addEventListener('click', function() {
        document.getElementById('locationPickerModal').classList.remove('active');
        document.body.style.overflow = '';
    });
    document.getElementById('closeLocationPicker')?.addEventListener('click', function() {
        document.getElementById('locationPickerModal').classList.remove('active');
        document.body.style.overflow = '';
    });
});

// Check authentication status
async function checkAuthStatus() {
    console.log('Checking authentication status...');
    console.log('API URL:', '../api/users/check_auth.php');
    try {
        const response = await fetch('../api/users/check_auth.php');
        const result = await response.json();

        if (result.success && result.authenticated) {
            isAuthenticated = true;
            currentUser = result.user;
            window.currentUser = currentUser; // Make global for debugging

            // Store in localStorage for persistence and fallback
            localStorage.setItem('currentUser', JSON.stringify(result.user));
            localStorage.setItem('userId', currentUser.id);
            localStorage.setItem('userRole', currentUser.role);
        } else {
            isAuthenticated = false;
            currentUser = null;
            window.currentUser = null; // Clear global

            // Redirect unauthenticated users to login
            window.location.href = '../auth/login.html';
            return; // Exit early to avoid initializing app
        }
    } catch (error) {
        console.error('Error checking auth status:', error);

        // Fallback: try to load user data from localStorage to hydrate UI
        try {
            const storedUser = localStorage.getItem('currentUser');
            if (storedUser) {
                currentUser = JSON.parse(storedUser);
                isAuthenticated = currentUser.id !== null;
                window.currentUser = currentUser;

                // Store in localStorage for persistence (for backward compatibility)
                localStorage.setItem('userId', currentUser.id);
                localStorage.setItem('userRole', currentUser.role);
            } else {
                isAuthenticated = false;
                currentUser = null;
                window.currentUser = null; // Clear global
            }
        } catch (localStorageError) {
            console.error("Error reading from localStorage:", localStorageError);
            isAuthenticated = false;
            currentUser = null;
            window.currentUser = null; // Clear global
        }

        // If still not authenticated after fallback, redirect
        if (!isAuthenticated) {
            window.location.href = '../auth/login.html';
            return; // Exit early to avoid initializing app
        }
    }

    // After all attempts, check authentication status
    if (!isAuthenticated) {
        // Redirect unauthenticated users to login
        window.location.href = '../auth/login.html';
        return; // Exit early to avoid initializing app
    }
}

// Initialize the app after auth check
function initializeApp() {
    loadUserData();

    // Setup event listeners after auth check
    setupEventListeners();

    // Populate country dropdowns after DOM is loaded
    populateCountryDropdowns();
}

// Populate country dropdowns with country options from COUNTRIES array
function populateCountryDropdowns() {
    // Check if COUNTRIES array is available
    if (typeof window.COUNTRIES === 'undefined') {
        console.error('COUNTRIES array not available');
        return;
    }

    const actionCountrySelect = document.getElementById('actionCountrySelect');
    const resourceCountrySelect = document.getElementById('resourceCountrySelect');

    if (actionCountrySelect) {
        // Clear existing options except the first placeholder
        actionCountrySelect.innerHTML = '<option value="">Select Country</option>';

        // Add countries to the dropdown
        window.COUNTRIES.forEach(country => {
            const option = document.createElement('option');
            option.value = country.name;
            option.textContent = country.name;
            actionCountrySelect.appendChild(option);
        });
    }

    if (resourceCountrySelect) {
        // Clear existing options except the first placeholder
        resourceCountrySelect.innerHTML = '<option value="">Select Country</option>';

        // Add countries to the dropdown
        window.COUNTRIES.forEach(country => {
            const option = document.createElement('option');
            option.value = country.name;
            option.textContent = country.name;
            resourceCountrySelect.appendChild(option);
        });
    }
}

// Set up event listeners
function setupEventListeners() {
    // Navigation
    navItems.forEach((item) => {
        item.addEventListener("click", (e) => {
            e.preventDefault();
            if (item.classList.contains("logout")) {
                handleLogout();
                return;
            }

            const pageName = item.getAttribute("data-page");
            if (pageName) {
                showPage(pageName);
                navItems.forEach((nav) => nav.classList.remove("active"));
                item.classList.add("active");
                sidebar.classList.remove("show");
            }
        });
    });

    // Sidebar Toggle
    sidebarToggle?.addEventListener("click", () => {
        sidebar.classList.toggle("show");
    });

    headerToggle?.addEventListener("click", () => {
        sidebar.classList.toggle("show");
    });

    // Close sidebar on outside click
    document.addEventListener("click", (e) => {
        if (!sidebar.contains(e.target) && !headerToggle?.contains(e.target)) {
            sidebar.classList.remove("show");
        }
    });

    // Notifications
    notificationBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        notificationDropdown.classList.toggle("show");
        userDropdown.classList.remove("show");

        // Load notifications when dropdown is opened
        if (notificationDropdown.classList.contains("show")) {
            loadNotifications();
        }
    });

    // User Menu
    userMenu?.addEventListener("click", (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle("show");
        notificationDropdown.classList.remove("show");
    });

    document.addEventListener("click", () => {
        notificationDropdown?.classList.remove("show");
        userDropdown?.classList.remove("show");
    });

    // Modal
    createActionBtn?.addEventListener("click", () => {
        createModal.classList.add("show");
    });

    closeModal?.addEventListener("click", () => {
        createModal.classList.remove("show");
    });

    createModal?.addEventListener("click", (e) => {
        if (e.target === createModal) {
            createModal.classList.remove("show");
        }
    });

    // Tab Switching
    tabBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            const tabName = btn.getAttribute("data-tab");

            tabBtns.forEach((b) => b.classList.remove("active"));
            tabContents.forEach((c) => c.classList.remove("active"));

            btn.classList.add("active");
            const tab = document.getElementById(tabName);
            if (tab) tab.classList.add("active");
        });
    });

    // FAQ Accordion
    faqQuestions.forEach((question) => {
        question.addEventListener("click", () => {
            const answer = question.nextElementSibling;
            const isOpen = answer.style.display === "block";

            document.querySelectorAll(".faq-answer").forEach((a) => (a.style.display = "none"));

            if (!isOpen) {
                answer.style.display = "block";
            }
        });
    });

    // Search and filter functionality
    // Actions search
    const actionsSearchInput = document.getElementById('my-actions-search');
    if (actionsSearchInput) {
        let actionsSearchTimeout;
        actionsSearchInput.addEventListener('input', function() {
            clearTimeout(actionsSearchTimeout);
            actionsSearchTimeout = setTimeout(() => {
                filterActionsTable();
            }, 300); // Debounce 300ms
        });
    }

    // Actions status filter
    const actionsStatusFilter = document.getElementById('my-actions-status-filter');
    if (actionsStatusFilter) {
        actionsStatusFilter.addEventListener('change', function() {
            filterActionsTable();
        });
    }

    // Resources search
    const resourcesSearchInput = document.getElementById('my-resources-search');
    if (resourcesSearchInput) {
        let resourcesSearchTimeout;
        resourcesSearchInput.addEventListener('input', function() {
            clearTimeout(resourcesSearchTimeout);
            resourcesSearchTimeout = setTimeout(() => {
                filterResourcesTable();
            }, 300); // Debounce 300ms
        });
    }

    // Resources status filter
    const resourcesStatusFilter = document.getElementById('my-resources-status-filter');
    if (resourcesStatusFilter) {
        resourcesStatusFilter.addEventListener('change', function() {
            filterResourcesTable();
        });
    }
}

// Debounced filtering functions
function filterActionsTable() {
    const searchInput = document.getElementById('my-actions-search');
    const statusFilter = document.getElementById('my-actions-status-filter');
    const tableBody = document.getElementById('my-actions-table-body');

    if (!tableBody) return;

    const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
    const statusValue = statusFilter ? statusFilter.value : '';

    const rows = tableBody.querySelectorAll('tr');
    let hasVisibleRows = false;

    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return; // Skip the "No actions found" row

        const title = row.cells[1]?.textContent.toLowerCase() || '';
        const category = row.cells[2]?.textContent.toLowerCase() || '';
        const status = row.cells[3]?.textContent.toLowerCase() || '';

        const matchesSearch = !searchValue ||
                              title.includes(searchValue) ||
                              category.includes(searchValue);

        const matchesStatus = !statusValue || status.includes(statusValue.toLowerCase());

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            hasVisibleRows = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "No results" message
    const noResultsRow = document.querySelector('#my-actions-table-body tr:has(td[colspan="8"])');
    if (noResultsRow) {
        noResultsRow.style.display = hasVisibleRows ? 'none' : '';
    }
}

function filterResourcesTable() {
    const searchInput = document.getElementById('my-resources-search');
    const statusFilter = document.getElementById('my-resources-status-filter');
    const tableBody = document.getElementById('my-resources-table-body');

    if (!tableBody) return;

    const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
    const statusValue = statusFilter ? statusFilter.value : '';

    const rows = tableBody.querySelectorAll('tr');
    let hasVisibleRows = false;

    rows.forEach(row => {
        if (row.querySelector('td[colspan]')) return; // Skip the "No resources found" row

        const name = row.cells[1]?.textContent.toLowerCase() || '';
        const category = row.cells[2]?.textContent.toLowerCase() || '';
        const type = row.cells[3]?.textContent.toLowerCase() || '';
        const status = row.cells[4]?.textContent.toLowerCase() || '';

        const matchesSearch = !searchValue ||
                              name.includes(searchValue) ||
                              category.includes(searchValue) ||
                              type.includes(searchValue);

        const matchesStatus = !statusValue || status.includes(statusValue.toLowerCase());

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            hasVisibleRows = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "No results" message
    const noResultsRow = document.querySelector('#my-resources-table-body tr:has(td[colspan="7"])');
    if (noResultsRow) {
        noResultsRow.style.display = hasVisibleRows ? 'none' : '';
    }
}

// Load user's data (own actions and resources)
async function loadUserData() {
    console.log('Loading user data for user ID:', currentUser?.id);
    try {
        if (!currentUser) {
            console.error('User not authenticated, cannot load user data');
            return;
        }

        let actionsResponse, resourcesResponse;

        if (currentUser.role === 'admin') {
            // Admin users see all actions and resources
            actionsResponse = await fetch('../api/actions/get_all_actions.php');
            resourcesResponse = await fetch('../api/resources/get_all_resources.php');
        } else {
            // Regular users see only their own actions and resources
            actionsResponse = await fetch('../api/actions/get_my_actions.php?user_id=' + currentUser.id);
            resourcesResponse = await fetch('../api/resources/get_my_resources.php?user_id=' + currentUser.id);
        }

        const actionsResult = await actionsResponse.json();
        const resourcesResult = await resourcesResponse.json();

        if (actionsResult.success) {
            renderUserActions(actionsResult.actions);
        }

        if (resourcesResult.success) {
            renderUserResources(resourcesResult.resources);
        }

        // Update dashboard stats
        updateDashboardStats(
            actionsResult.actions || [],
            resourcesResult.resources || []
        );
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

// Update dashboard statistics
function updateDashboardStats(actions, resources) {
    // Update counts in the dashboard
    document.getElementById('myActionsCount').textContent = actions.length;
    document.getElementById('myResourcesCount').textContent = resources.length;

    // Load participated and comments counts with additional API calls
    loadParticipatedCount();
    loadCommentsCount();

    // Update recent activity list
    updateRecentActivity(actions, resources);
}

// Load participated actions count
async function loadParticipatedCount() {
    try {
        if (!currentUser) {
            console.error('User not authenticated, cannot load participated count');
            document.getElementById('participatedCount').textContent = '0';
            return;
        }

        const response = await fetch(`../api/actions/get_participated_actions.php?user_id=${currentUser.id}`);
        const result = await response.json();

        if (result.success) {
            const count = result.count || result.actions?.length || 0;
            document.getElementById('participatedCount').textContent = count;
        } else {
            document.getElementById('participatedCount').textContent = '0';
        }
    } catch (error) {
        console.error('Error loading participated count:', error);
        document.getElementById('participatedCount').textContent = '0';
    }
}

// Load comments count
async function loadCommentsCount() {
    try {
        if (!currentUser) {
            console.error('User not authenticated, cannot load comments count');
            document.getElementById('commentsCount').textContent = '0';
            return;
        }

        const response = await fetch(`../api/comments/get_my_comments.php?user_id=${currentUser.id}`);
        const result = await response.json();

        if (result.success) {
            const count = result.comments?.length || 0;
            document.getElementById('commentsCount').textContent = count;
        } else {
            document.getElementById('commentsCount').textContent = '0';
        }
    } catch (error) {
        console.error('Error loading comments count:', error);
        document.getElementById('commentsCount').textContent = '0';
    }
}

// Load notifications for the user
async function loadNotifications() {
    // Guard: return if not authenticated
    if (!currentUser || !isAuthenticated) {
        return;
    }

    try {
        const response = await fetch(`../api/notifications/get_notifications.php?user_id=${currentUser.id}`);
        const result = await response.json();

        if (result.success) {
            renderNotifications(result.notifications);

            // Update notification count
            const unreadCount = result.notifications.filter(n => !n.is_read).length;
            const countElement = document.querySelector('.badge');  // The HTML uses 'badge' class
            if (countElement) {
                if (unreadCount > 0) {
                    countElement.textContent = unreadCount;
                    countElement.style.display = 'inline-block';
                } else {
                    countElement.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
    }
}

// Render notifications in the UI
function renderNotifications(notifications) {
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (!notificationDropdown) return;

    if (!notifications || notifications.length === 0) {
        notificationDropdown.innerHTML = '<div class="notification-item">No notifications yet</div>';

        // Update badge count to show 0 and hide it
        const badgeElement = document.querySelector('.badge');
        if (badgeElement) {
            badgeElement.textContent = '0';
            badgeElement.style.display = 'none';
        }
        return;
    }

    // Show all notifications (not just the latest 5)
    const allNotifications = notifications;

    // Create the "Mark all as read" button if there are unread notifications
    let markAllButton = '';
    const hasUnread = notifications.some(n => !n.is_read);
    if (hasUnread) {
        markAllButton = '<div class="notification-item" id="markAllAsRead" style="text-align: center; cursor: pointer; background-color: #f8f9fa; font-weight: bold;">Mark all as read</div>';
    }

    notificationDropdown.innerHTML = markAllButton + allNotifications.map(notification => `
        <div class="notification-item ${!notification.is_read ? 'unread' : ''}" data-id="${notification.id}">
            <div class="notification-content">
                <div class="notification-message">${notification.message}</div>
                <div class="notification-date">${notification.created_at ? new Date(notification.created_at).toLocaleString() : 'Just now'}</div>
            </div>
            <button class="delete-notification-btn" data-id="${notification.id}" title="Delete notification">✕</button>
        </div>
    `).join('');

    // Add click event listeners to mark notifications as read
    document.querySelectorAll('.notification-item:not(#markAllAsRead)').forEach(item => {
        // Click on notification body (not delete button) to mark as read
        const notificationContent = item.querySelector('.notification-content');
        if (notificationContent) {
            notificationContent.addEventListener('click', async function(event) {
                event.stopPropagation(); // Prevent triggering parent click
                const notificationId = item.getAttribute('data-id');
                if (notificationId) {
                    await markNotificationAsRead(notificationId);
                    item.classList.remove('unread');

                    // Update the badge count
                    const badgeElement = document.querySelector('.badge');
                    if (badgeElement && !isNaN(badgeElement.textContent) && badgeElement.textContent > 0) {
                        const currentCount = parseInt(badgeElement.textContent);
                        const newCount = Math.max(0, currentCount - 1);
                        badgeElement.textContent = newCount;
                        if (newCount === 0) {
                            badgeElement.style.display = 'none';
                        }
                    }
                }
            });
        }
    });

    // Add click event listeners to delete notification buttons
    document.querySelectorAll('.delete-notification-btn').forEach(button => {
        button.addEventListener('click', async function(event) {
            event.stopPropagation(); // Prevent triggering notification click
            const notificationId = this.getAttribute('data-id');
            if (notificationId) {
                const result = await Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                });

                if (result.isConfirmed) {
                    await deleteNotification(notificationId);
                    // Reload notifications to update the UI
                    loadNotifications();
                }
            }
        });
    });

    // Add click event for "Mark all as read" button
    const markAllButtonElement = document.getElementById('markAllAsRead');
    if (markAllButtonElement) {
        markAllButtonElement.addEventListener('click', async function() {
            await markAllNotificationsAsRead();
        });
    }

    // Update badge count after rendering
    const unreadCount = notifications.filter(n => !n.is_read).length;
    const badgeElement = document.querySelector('.badge');
    if (badgeElement) {
        if (unreadCount > 0) {
            badgeElement.textContent = unreadCount;
            badgeElement.style.display = 'inline-block';
        } else {
            badgeElement.textContent = '0';
            badgeElement.style.display = 'none';
        }
    }
}

// Mark a specific notification as read
async function markNotificationAsRead(notificationId) {
    try {
        const response = await fetch('../api/notifications/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: notificationId })
        });

        const result = await response.json();
        if (!result.success) {
            console.error('Failed to mark notification as read:', result.message);
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

// Mark all notifications as read
async function markAllNotificationsAsRead() {
    // Guard: return if not authenticated
    if (!currentUser || !isAuthenticated) {
        return;
    }

    try {
        const response = await fetch('../api/notifications/mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: currentUser.id })
        });

        const result = await response.json();
        if (result.success) {
            // Reload notifications to update the UI with all read
            loadNotifications();
        } else {
            console.error('Failed to mark all notifications as read:', result.message);
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
}

// Delete a specific notification
async function deleteNotification(notificationId) {
    try {
        const response = await fetch('../api/notifications/delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: notificationId })
        });

        const result = await response.json();
        if (!result.success) {
            console.error('Failed to delete notification:', result.message);
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
    }
}

// Update recent activity list
function updateRecentActivity(actions, resources) {
    const activityList = document.getElementById('recentActivityList');
    if (!activityList) return;

    // Try to fetch recent activity from the dedicated API
    fetchRecentActivityFromAPI()
        .then(apiActivities => {
            if (apiActivities && apiActivities.length > 0) {
                // Use API activities if available
                renderRecentActivity(activityList, apiActivities);
            } else {
                // Fallback to synthesizing from actions/resources if API fails
                const allItems = [
                    ...actions.map(item => ({...item, type: 'action', timestamp: item.updated_at || item.created_at})),
                    ...resources.map(item => ({...item, type: 'resource', timestamp: item.updated_at || item.created_at}))
                ];

                // Sort by most recent
                allItems.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

                // Take the 5 most recent items
                const recentItems = allItems.slice(0, 5);
                renderRecentActivity(activityList, recentItems);
            }
        })
        .catch(error => {
            console.error('Error fetching API recent activity:', error);

            // Fallback to synthesizing from actions/resources
            const allItems = [
                ...actions.map(item => ({...item, type: 'action', timestamp: item.updated_at || item.created_at})),
                ...resources.map(item => ({...item, type: 'resource', timestamp: item.updated_at || item.created_at}))
            ];

            // Sort by most recent
            allItems.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

            // Take the 5 most recent items
            const recentItems = allItems.slice(0, 5);
            renderRecentActivity(activityList, recentItems);
        });
}

// Fetch recent activity from the dedicated API
async function fetchRecentActivityFromAPI() {
    // Guard: return if not authenticated
    if (!currentUser || !isAuthenticated) {
        return [];
    }

    try {
        const response = await fetch(`../api/other/recent_activity.php?user_id=${currentUser.id}&role=${currentUser.role}`);
        const result = await response.json();

        if (result.success && result.recentItems) {
            // Format API activities to match expected structure
            return result.recentItems.map(item => {
                return {
                    type: item.type,
                    title: item.title,
                    description: item.description || item.title,
                    timestamp: item.date,
                    status: item.status
                };
            });
        } else {
            return [];
        }
    } catch (error) {
        console.error('Error fetching recent activity API:', error);
        return [];
    }
}

// Render recent activity in the UI
function renderRecentActivity(activityList, items) {
    activityList.innerHTML = ''; // Clear existing activities

    if (items.length === 0) {
        activityList.innerHTML = '<p class="no-activity">No recent activity yet</p>';
        return;
    }

    items.forEach(item => {
        const activityItem = document.createElement('div');
        activityItem.className = 'activity-item';

        // Determine icon based on type
        const icon = item.type === 'action' ? '🤝' : '📦';

        activityItem.innerHTML = `
            <div class="activity-avatar">${icon}</div>
            <div class="activity-content">
                <strong>${item.title}</strong>
                <p>${item.description?.substring(0, 50)}${item.description?.length > 50 ? '...' : ''}</p>
                <time>${formatDateForActivity(item.timestamp)}</time>
            </div>
        `;

        activityList.appendChild(activityItem);
    });
}

// Format date for activity display
function formatDateForActivity(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        return date.toLocaleDateString();
    }
}

// Render user's actions in the dashboard
function renderUserActions(actions) {
    const tableBody = document.getElementById('my-actions-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (actions.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">No actions found</td>
            </tr>
        `;
        return;
    }

    actions.forEach(action => {
        const row = document.createElement('tr');

        let actionsHtml = '';
        if (currentUser && currentUser.role === 'admin') {
            // Admin view: show approve/reject buttons and owner column
            actionsHtml = `
                <span class="owner">Owner: ${action.creator?.name || action.creator_id || 'N/A'}</span><br>
                <button class="action-btn approve-btn" onclick="approveAction(${action.id})">Approve</button>
                <button class="action-btn reject-btn" onclick="rejectAction(${action.id})">Reject</button><br>
                <button class="action-btn edit-btn" onclick="openEditModal('action', ${action.id})">Edit</button>
                <button class="action-btn delete-btn" onclick="confirmDeleteAction(${action.id})">Delete</button>
            `;
        } else {
            // Regular user view: only show edit/delete for their own items
            actionsHtml = `
                <button class="action-btn edit-btn" onclick="openEditModal('action', ${action.id})">Edit</button>
                <button class="action-btn delete-btn" onclick="confirmDeleteAction(${action.id})">Delete</button>
            `;
        }

        row.innerHTML = `
            <td>${action.id}</td>
            <td>${action.title || 'N/A'}</td>
            <td>${action.category || 'N/A'}</td>
            <td><span class="status-badge status-${action.status || 'pending'}">${action.status || 'Pending'}</span></td>
            <td>${action.participants || 0}</td>
            <td>${formatDate(action.created_at)}</td>
            <td>${actionsHtml}</td>
        `;
        tableBody.appendChild(row);
    });
}

// Render user's resources in the dashboard
function renderUserResources(resources) {
    const tableBody = document.getElementById('my-resources-table-body');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (resources.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">No resources found</td>
            </tr>
        `;
        return;
    }

    resources.forEach(resource => {
        const row = document.createElement('tr');

        let actionsHtml = '';
        if (currentUser && currentUser.role === 'admin') {
            // Admin view: show approve/reject buttons and owner column
            actionsHtml = `
                <span class="owner">Owner: ${resource.publisher?.name || resource.publisher_id || 'N/A'}</span><br>
                <button class="action-btn approve-btn" onclick="approveResource(${resource.id})">Approve</button>
                <button class="action-btn reject-btn" onclick="rejectResource(${resource.id})">Reject</button><br>
                <button class="action-btn edit-btn" onclick="openEditModal('resource', ${resource.id})">Edit</button>
                <button class="action-btn delete-btn" onclick="confirmDeleteResource(${resource.id})">Delete</button>
            `;
        } else {
            // Regular user view: only show edit/delete for their own items
            actionsHtml = `
                <button class="action-btn edit-btn" onclick="openEditModal('resource', ${resource.id})">Edit</button>
                <button class="action-btn delete-btn" onclick="confirmDeleteResource(${resource.id})">Delete</button>
            `;
        }

        row.innerHTML = `
            <td>${resource.id}</td>
            <td>${resource.resource_name || 'N/A'}</td>
            <td>${resource.category || 'N/A'}</td>
            <td>${resource.type || 'N/A'}</td>
            <td><span class="status-badge status-${resource.status || 'pending'}">${resource.status || 'Pending'}</span></td>
            <td>${resource.location || 'N/A'}</td>
            <td>${actionsHtml}</td>
        `;
        tableBody.appendChild(row);
    });
}

// Format date for display
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

// Geocoding and Location Functions

// Geocode a location string to get coordinates and country
async function geocodeLocation(locationText) {
    if (!locationText) return null;

    try {
        // Using Nominatim API (free OpenStreetMap geocoder) - include address details
        const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(locationText)}&format=json&limit=1&addressdetails=1`);
        const data = await response.json();

        if (data && data.length > 0) {
            const result = data[0];

            // Extract country from address details if available
            let country = 'Unknown';
            if (result.address?.country) {
                country = result.address.country;
            } else if (result.address?.state) {
                country = result.address.state; // Fallback to state/province if country not available
            } else if (result.display_name) {
                // Fallback to extract country from display_name if address is missing
                // This is a simple heuristic - in practice you might want a more robust solution
                const parts = result.display_name.split(',');
                if (parts.length > 0) {
                    const lastPart = parts[parts.length - 1].trim();
                    // If last part seems to be a country (not a city/town)
                    if (['USA', 'UK', 'Canada', 'France', 'Germany', 'Italy', 'Spain', 'Japan', 'China', 'Brazil', 'Australia', 'India', 'Russia'].some(c =>
                        lastPart.toLowerCase().includes(c.toLowerCase()))) {
                        country = lastPart;
                    }
                }
            }

            return {
                latitude: parseFloat(result.lat),
                longitude: parseFloat(result.lon),
                country: country
            };
        }
        return null;
    } catch (error) {
        console.error('Geocoding error:', error);
        return null;
    }
}

// Reverse geocode to get country from coordinates
async function reverseGeocode(lat, lng) {
    try {
        // Using Nominatim reverse geocoding API
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();

        return {
            country: data.address?.country || 'Unknown',
            countryCode: data.address?.country_code || 'unknown'
        };
    } catch (error) {
        console.error('Reverse geocoding error:', error);
        return null;
    }
}

// Initialize the location picker map
function initLocationPickerMap(lat = 48.8566, lng = 2.3522) {
    if (!window.L) {
        console.error('Leaflet not loaded for location picker');
        return;
    }

    if (window.locationPickerMap) {
        window.locationPickerMap.remove();
    }

    const mapContainer = document.getElementById('locationPickerMap');
    if (!mapContainer) return;

    window.locationPickerMap = L.map('locationPickerMap').setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(window.locationPickerMap);

    // Add click event to select location
    window.locationPickerMap.on('click', async function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Update UI with selected coordinates
        document.getElementById('selectedCoords').textContent = `(${lat.toFixed(6)}, ${lng.toFixed(6)})`;

        // Get country from coordinates
        const countryData = await reverseGeocode(lat, lng);
        if (countryData) {
            document.getElementById('detectedCountry').textContent = countryData.country;
            window.selectedLocation = { lat, lng, country: countryData.country };
        }
    });

    // Add a marker at the initial location
    window.selectedLocationMarker = L.marker([lat, lng]).addTo(window.locationPickerMap);
    window.selectedLocation = { lat, lng, country: 'Unknown' };
}

// Open the location picker modal
function openLocationPicker(formType) {
    const modal = document.getElementById('locationPickerModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialize map with a default location or current location if available
        const locationInput = document.getElementById(`${formType}Location`);
        const latInput = document.getElementById(`${formType}Latitude`);
        const lngInput = document.getElementById(`${formType}Longitude`);

        let lat = 48.8566; // Default to Paris
        let lng = 2.3522;

        // If current location values exist, use them
        if (latInput.value && lngInput.value) {
            lat = parseFloat(latInput.value);
            lng = parseFloat(lngInput.value);
        } else if (locationInput.value.trim() !== '') {
            // If location text is provided but coordinates aren't, try to geocode
            geocodeLocation(locationInput.value).then(result => {
                if (result) {
                    lat = result.latitude;
                    lng = result.longitude;
                    initLocationPickerMap(lat, lng);
                } else {
                    initLocationPickerMap(); // Use default coordinates
                }
            });
            return; // Wait for geocoding before initializing
        }

        initLocationPickerMap(lat, lng);
    }
}

// Confirm location selection
function confirmLocationSelection() {
    if (window.selectedLocation) {
        // Get the form type from the active tab
        const activeTab = document.querySelector('.tab-btn.active');
        let formType = 'action';
        if (activeTab) {
            const tabId = activeTab.getAttribute('data-tab');
            formType = tabId.includes('action') ? 'action' : 'resource';
        } else {
            // Fallback: try to determine form type from context
            formType = window.currentlyEditingForm || 'action';
        }

        // Update form fields with selected location
        const countrySelect = document.getElementById(`${formType}CountrySelect`);
        const locationDetailsInput = document.getElementById(`${formType}LocationDetails`);
        const latInput = document.getElementById(`${formType}Latitude`);
        const lngInput = document.getElementById(`${formType}Longitude`);
        const countryInput = document.getElementById(`${formType}Country`);

        // Try to reverse geocode to get country and location details

        try {
            const reverseResult = reverseGeocode(window.selectedLocation.lat, window.selectedLocation.lng);
            if (reverseResult) {
                // Find matching country in our COUNTRIES array
                if (window.COUNTRIES) {
                    const matchedCountry = window.COUNTRIES.find(country =>
                        country.name.toLowerCase() === reverseResult.country.toLowerCase() ||
                        country.code.toLowerCase() === reverseResult.countryCode.toLowerCase() ||
                        (country.aliases && country.aliases.some(alias =>
                            alias.toLowerCase() === reverseResult.country.toLowerCase()))
                    );

                    if (matchedCountry) {
                        // Set the country in the dropdown
                        if (countrySelect) {
                            countrySelect.value = matchedCountry.name;
                        }

                        // Set the hidden country field
                        if (countryInput) {
                            countryInput.value = matchedCountry.name;
                        }
                    } else {
                        // If no match, use the country from reverse geocoding
                        if (countrySelect) {
                            countrySelect.value = reverseResult.country;
                        }
                        if (countryInput) {
                            countryInput.value = reverseResult.country;
                        }
                    }
                }

                // Set the location details input to the address
                if (locationDetailsInput) {
                    locationDetailsInput.value = window.selectedLocation.address || '';
                }
            }
        } catch (error) {
            console.error('Error reverse geocoding location:', error);
            // If reverse geocoding fails, at least set coordinates and a placeholder
            if (countryInput) {
                countryInput.value = 'Unknown';
            }
        }

        // Always set the lat/lng coordinates
        if (latInput) latInput.value = window.selectedLocation.lat;
        if (lngInput) lngInput.value = window.selectedLocation.lng;

        // Close the modal
        document.getElementById('locationPickerModal').classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Submit action form
async function submitActionForm(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const fileInput = document.getElementById('actionImage');
    const countrySelect = document.getElementById('actionCountrySelect');
    const locationDetails = document.getElementById('actionLocationDetails');
    const latInput = document.getElementById('actionLatitude');
    const lngInput = document.getElementById('actionLongitude');
    const countryInput = document.getElementById('actionCountry');

    console.log('Submitting action form...');
    console.log('Has file:', fileInput && fileInput.files.length > 0);
    console.log('Location data:', {
        country: countrySelect.value,
        details: locationDetails.value,
        lat: latInput.value,
        lng: lngInput.value,
        hiddenCountry: countryInput.value
    });

    // Update the hidden country field with the selected country
    if (countrySelect.value) {
        countryInput.value = countrySelect.value;
    }

    // Combine country and location details for the location text
    const countryValue = countrySelect.value;
    const detailsValue = locationDetails.value.trim();
    const fullLocationText = detailsValue ? `${countryValue} - ${detailsValue}` : countryValue;

    // Check if location has coordinates, if not, geocode the location
    if ((!latInput.value || !lngInput.value) && countryValue) {
        showLoadingMessage('Geocoding location...');
        const geoResult = await geocodeLocation(fullLocationText);
        if (geoResult) {
            // Update the hidden fields with geocoded data
            latInput.value = geoResult.latitude;
            lngInput.value = geoResult.longitude;

            // Only set country from geocoding if country hasn't been selected
            if (!countryInput.value && geoResult.country) {
                countryInput.value = geoResult.country;
            }

            // Also update the formData with the new values
            formData.set('latitude', geoResult.latitude);
            formData.set('longitude', geoResult.longitude);
            formData.set('country', countryInput.value);

            showSuccessMessage('Location geocoded successfully');
        } else {
            showErrorMessage('Could not geocode the provided location. Please try entering coordinates directly or use the map picker.');
            return; // Stop the submission if geocoding failed
        }
    } else if (!countryValue) {
        showErrorMessage('Please select a country before submitting');
        return;
    }

    // Check if there's a file to upload
    const hasFile = fileInput && fileInput.files.length > 0 && fileInput.files[0].size > 0;

    if (hasFile) {
        // Add creator_id to formData if not already present
        if (!formData.get('creator_id')) {
            formData.append('creator_id', currentUser?.id || 1);
        }
        // Add location coordinates and country
        if (!formData.get('latitude')) {
            formData.append('latitude', latInput.value);
        }
        if (!formData.get('longitude')) {
            formData.append('longitude', lngInput.value);
        }
        if (!formData.get('country')) {
            formData.append('country', countryInput.value);
        }

        // Check if we're in edit mode
        const editId = document.getElementById('editActionId')?.value;
        if (editId) {
            formData.append('id', editId); // Add ID for update
        }

        try {
            const response = await fetch(editId ? '../api/actions/update_action.php' : '../api/actions/create_action.php', {
                method: 'POST',
                body: formData // Send as FormData to handle file uploads
            });

            const result = await response.json();

            if (result.success) {
                e.target.reset(); // Reset form
                document.getElementById('editActionId').value = ''; // Clear edit ID
                createModal.classList.remove('show'); // Close modal
                loadUserData(); // Reload user's data
                showSuccessMessage(`${editId ? 'Updated' : 'Created'} action successfully!`);
            } else {
                showErrorMessage(result.message || `Failed to ${editId ? 'update' : 'create'} action`);
            }
        } catch (error) {
            console.error('Error submitting action:', error);
            showErrorMessage('Network error. Please try again.');
        }
    } else {
        // No file to upload, convert to JSON
        const data = Object.fromEntries(formData.entries());
        data.creator_id = currentUser?.id || 1; // Add creator ID

        // Ensure location coordinates and country are included
        if (!data.latitude) data.latitude = latInput.value;
        if (!data.longitude) data.longitude = lngInput.value;
        if (!data.country) data.country = countryInput.value;

        // Check if we're in edit mode
        const editId = document.getElementById('editActionId')?.value;
        const endpoint = editId ? '../api/actions/update_action.php' : '../api/actions/create_action.php';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                e.target.reset(); // Reset form
                document.getElementById('editActionId').value = ''; // Clear edit ID
                createModal.classList.remove('show'); // Close modal
                loadUserData(); // Reload user's data
                showSuccessMessage(`${editId ? 'Updated' : 'Created'} action successfully!`);
            } else {
                showErrorMessage(result.message || `Failed to ${editId ? 'update' : 'create'} action`);
            }
        } catch (error) {
            console.error('Error submitting action:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Submit resource form
async function submitResourceForm(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const resourceImage = document.getElementById('resourceImage');
    const countrySelect = document.getElementById('resourceCountrySelect');
    const locationDetails = document.getElementById('resourceLocationDetails');
    const latInput = document.getElementById('resourceLatitude');
    const lngInput = document.getElementById('resourceLongitude');
    const countryInput = document.getElementById('resourceCountry');

    console.log('Submitting resource form...');
    console.log('Has file:', resourceImage && resourceImage.files.length > 0);
    console.log('Location data:', {
        country: countrySelect.value,
        details: locationDetails.value,
        lat: latInput.value,
        lng: lngInput.value,
        hiddenCountry: countryInput.value
    });

    // Update the hidden country field with the selected country
    if (countrySelect.value) {
        countryInput.value = countrySelect.value;
    }

    // Combine country and location details for the location text
    const countryValue = countrySelect.value;
    const detailsValue = locationDetails.value.trim();
    const fullLocationText = detailsValue ? `${countryValue} - ${detailsValue}` : countryValue;

    // Check if location has coordinates, if not, geocode the location
    if ((!latInput.value || !lngInput.value) && countryValue) {
        showLoadingMessage('Geocoding location...');
        const geoResult = await geocodeLocation(fullLocationText);
        if (geoResult) {
            // Update the hidden fields with geocoded data
            latInput.value = geoResult.latitude;
            lngInput.value = geoResult.longitude;

            // Only set country from geocoding if country hasn't been selected
            if (!countryInput.value && geoResult.country) {
                countryInput.value = geoResult.country;
            }

            // Also update the formData with the new values
            formData.set('latitude', geoResult.latitude);
            formData.set('longitude', geoResult.longitude);
            formData.set('country', countryInput.value);

            showSuccessMessage('Location geocoded successfully');
        } else {
            showErrorMessage('Could not geocode the provided location. Please try entering coordinates directly or use the map picker.');
            return; // Stop the submission if geocoding failed
        }
    } else if (!countryValue) {
        showErrorMessage('Please select a country before submitting');
        return;
    }

    // Check if there's a file to upload
    const hasFile = resourceImage && resourceImage.files.length > 0 && resourceImage.files[0].size > 0;

    if (hasFile) {
        // Add publisher_id to formData if not already present
        if (!formData.get('publisher_id')) {
            formData.append('publisher_id', currentUser?.id || 1);
        }
        // Add location coordinates and country
        if (!formData.get('latitude')) {
            formData.append('latitude', latInput.value);
        }
        if (!formData.get('longitude')) {
            formData.append('longitude', lngInput.value);
        }
        if (!formData.get('country')) {
            formData.append('country', countryInput.value);
        }

        // Check if we're in edit mode
        const editId = document.getElementById('editResourceId')?.value;
        if (editId) {
            formData.append('id', editId); // Add ID for update
        }

        try {
            const response = await fetch(editId ? '../api/resources/update_resource.php' : '../api/resources/create_resource.php', {
                method: 'POST',
                body: formData // Send as FormData to handle file uploads
            });

            const result = await response.json();

            if (result.success) {
                e.target.reset(); // Reset form
                document.getElementById('editResourceId').value = ''; // Clear edit ID
                createModal.classList.remove('show'); // Close modal
                loadUserData(); // Reload user's data
                showSuccessMessage(`${editId ? 'Updated' : 'Created'} resource successfully!`);
            } else {
                showErrorMessage(result.message || `Failed to ${editId ? 'update' : 'create'} resource`);
            }
        } catch (error) {
            console.error('Error submitting resource:', error);
            showErrorMessage('Network error. Please try again.');
        }
    } else {
        // No file to upload, convert to JSON
        const data = Object.fromEntries(formData.entries());
        data.publisher_id = currentUser?.id || 1; // Add publisher ID

        // Ensure location coordinates and country are included
        if (!data.latitude) data.latitude = latInput.value;
        if (!data.longitude) data.longitude = lngInput.value;
        if (!data.country) data.country = countryInput.value;

        // Check if we're in edit mode
        const editId = document.getElementById('editResourceId')?.value;
        const endpoint = editId ? '../api/resources/update_resource.php' : '../api/resources/create_resource.php';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                e.target.reset(); // Reset form
                document.getElementById('editResourceId').value = ''; // Clear edit ID
                createModal.classList.remove('show'); // Close modal
                loadUserData(); // Reload user's data
                showSuccessMessage(`${editId ? 'Updated' : 'Created'} resource successfully!`);
            } else {
                showErrorMessage(result.message || `Failed to ${editId ? 'update' : 'create'} resource`);
            }
        } catch (error) {
            console.error('Error submitting resource:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Open edit modal for an action
async function openEditModal(type, id) {
    try {
        let response;
        let result;

        if (type === 'action') {
            response = await fetch(`../api/actions/get_action.php?id=${id}`);
            result = await response.json();

            if (result.success) {
                document.getElementById('editActionId').value = id;
                document.getElementById('actionTitle').value = result.action.title || '';
                document.getElementById('actionCategory').value = result.action.category || '';
                document.getElementById('actionTheme').value = result.action.theme || '';
                document.getElementById('actionDescription').value = result.action.description || '';

                if (result.action.start_time) {
                    // Format datetime for input field
                    const formattedDateTime = new Date(result.action.start_time).toISOString().slice(0, 16);
                    document.getElementById('actionDateTime').value = formattedDateTime;
                }

                document.getElementById('actionLocationDetails').value = result.action.location || '';
                document.getElementById('actionDuration').value = result.action.duration || '';

                // Switch to action tab
                switchTab('action-tab');
            }
        } else if (type === 'resource') {
            response = await fetch(`../api/resources/get_resource.php?id=${id}`);
            result = await response.json();

            if (result.success) {
                document.getElementById('editResourceId').value = id;
                document.getElementById('resourceName').value = result.resource.resource_name || '';

                // Select the correct type radio button
                const typeRadio = document.querySelector(`input[name="type"][value="${result.resource.type}"]`);
                if (typeRadio) {
                    typeRadio.checked = true;
                }

                document.getElementById('resourceCategory').value = result.resource.category || '';
                document.getElementById('resourceDescription').value = result.resource.description || '';
                document.getElementById('resourceLocationDetails').value = result.resource.location || '';

                // Switch to resource tab
                switchTab('resource-tab');
            }
        }

        // Update modal title and button text
        document.getElementById('modalTitle').textContent = `Edit ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        const submitBtn = type === 'action' ?
            document.querySelector('#action-tab .btn-large') :
            document.querySelector('#resource-tab .btn-large');

        if (submitBtn) {
            submitBtn.textContent = `Update ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        }

        // Show the modal
        createModal.classList.add('show');
    } catch (error) {
        console.error(`Error loading ${type} for edit:`, error);
        showErrorMessage(`Error loading ${type}: ${error.message}`);
    }
}

// Confirm delete for actions
async function confirmDeleteAction(id) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        deleteAction(id);
    }
}

// Confirm delete for resources
async function confirmDeleteResource(id) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        deleteResource(id);
    }
}

// Delete an action
async function deleteAction(id) {
    try {
        const response = await fetch('../api/actions/delete_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.success) {
            loadUserData(); // Reload user's data
            showSuccessMessage('Action deleted successfully!');
        } else {
            showErrorMessage(result.message || 'Failed to delete action');
        }
    } catch (error) {
        console.error('Error deleting action:', error);
        showErrorMessage('Network error. Please try again.');
    }
}

// Delete a resource
async function deleteResource(id) {
    try {
        const response = await fetch('../api/resources/delete_resource.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.success) {
            loadUserData(); // Reload user's data
            showSuccessMessage('Resource deleted successfully!');
        } else {
            showErrorMessage(result.message || 'Failed to delete resource');
        }
    } catch (error) {
        console.error('Error deleting resource:', error);
        showErrorMessage('Network error. Please try again.');
    }
}

// Switch between action and resource tabs
function switchTab(tabId) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
    });

    // Update tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.toggle('active', content.id === tabId);
    });
}

// Navigation
function showPage(pageName) {
    pages.forEach((page) => page.classList.remove("active"));
    const page = document.getElementById(pageName);
    if (page) page.classList.add("active");
}

// Handle logout
async function handleLogout() {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to logout?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch("../api/users/logout.php", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const result = await response.json();

            if (result.success) {
                // Clear localStorage
                localStorage.removeItem('userId');
                localStorage.removeItem('userRole');

                // Update UI
                isAuthenticated = false;
                currentUser = null;
                window.currentUser = null;

                // Show success message
                showSuccessMessage('You have been logged out successfully!');

                // Redirect to login page
                setTimeout(() => {
                    window.location.href = '../auth/login.html';
                }, 1000);
            } else {
                showErrorMessage('Failed to logout: ' + result.message);
            }
        } catch (error) {
            console.error('Logout error:', error);
            showErrorMessage('An error occurred during logout.');
        }
    }
}

// Show success message with SweetAlert
function showSuccessMessage(message) {
    Swal.fire({
        title: 'Success!',
        text: message,
        icon: 'success',
        position: 'top-end',
        toast: true,
        showConfirmButton: false,
        timer: 3000,
        customClass: {
            popup: 'swal2-popup-custom'
        }
    });
}

// Approve an action
async function approveAction(id) {
    const result = await Swal.fire({
        title: 'Approve Action?',
        text: "Are you sure you want to approve this action?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('../api/actions/approve_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id, action: 'approve' })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessMessage('Action approved successfully!');
                // Reload data to reflect changes
                loadUserData();
            } else {
                showErrorMessage(result.message || 'Failed to approve action');
            }
        } catch (error) {
            console.error('Error approving action:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Reject an action
async function rejectAction(id) {
    const result = await Swal.fire({
        title: 'Reject Action?',
        text: "Are you sure you want to reject this action?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('../api/actions/approve_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id, action: 'reject' })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessMessage('Action rejected successfully!');
                // Reload data to reflect changes
                loadUserData();
            } else {
                showErrorMessage(result.message || 'Failed to reject action');
            }
        } catch (error) {
            console.error('Error rejecting action:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Approve a resource
async function approveResource(id) {
    const result = await Swal.fire({
        title: 'Approve Resource?',
        text: "Are you sure you want to approve this resource?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('../api/resources/approve_resource.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id, action: 'approve' })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessMessage('Resource approved successfully!');
                // Reload data to reflect changes
                loadUserData();
            } else {
                showErrorMessage(result.message || 'Failed to approve resource');
            }
        } catch (error) {
            console.error('Error approving resource:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Reject a resource
async function rejectResource(id) {
    const result = await Swal.fire({
        title: 'Reject Resource?',
        text: "Are you sure you want to reject this resource?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reject it!',
        cancelButtonText: 'Cancel'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('../api/resources/approve_resource.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id, action: 'reject' })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessMessage('Resource rejected successfully!');
                // Reload data to reflect changes
                loadUserData();
            } else {
                showErrorMessage(result.message || 'Failed to reject resource');
            }
        } catch (error) {
            console.error('Error rejecting resource:', error);
            showErrorMessage('Network error. Please try again.');
        }
    }
}

// Show error message with SweetAlert
function showErrorMessage(message) {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        position: 'top-end',
        toast: true,
        showConfirmButton: false,
        timer: 3000,
        customClass: {
            popup: 'swal2-popup-custom'
        }
    });
}

// Open create modal for action or resource
function openCreateModal(type) {
    // Clear any form errors
    document.querySelectorAll('.error-message').forEach(el => el.remove());

    // Reset form to create mode
    const createModalElement = document.getElementById('createModal');
    createModalElement.dataset.editMode = 'false'; // Reset to create mode
    document.getElementById('editActionId').value = ''; // Clear action ID
    document.getElementById('editResourceId').value = ''; // Clear resource ID

    // Reset forms
    document.getElementById('actionForm')?.reset();
    document.getElementById('resourceForm')?.reset();

    // Update modal title
    document.getElementById('modalTitle').textContent = 'Create New';
    const submitBtnAction = document.querySelector('#action-tab .btn-large');
    const submitBtnResource = document.querySelector('#resource-tab .btn-large');

    if (submitBtnAction) {
        submitBtnAction.textContent = 'Create Action';
    }
    if (submitBtnResource) {
        submitBtnResource.textContent = 'Create Resource';
    }

    // Switch to appropriate tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    if (type === 'action') {
        document.querySelector('.tab-btn[data-tab="action-tab"]').classList.add('active');
        document.getElementById('action-tab').classList.add('active');
    } else {
        document.querySelector('.tab-btn[data-tab="resource-tab"]').classList.add('active');
        document.getElementById('resource-tab').classList.add('active');
    }

    // Show the modal
    createModal.classList.add('show');
}