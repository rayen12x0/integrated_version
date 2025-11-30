// --- 1. Theme Logic ---
        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }

        // Init Theme
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }

        // --- 2. Main Dashboard Logic (Integrated from main app) ---

        // Dashboard script with full functionality integration
        console.log('Dashboard script loading with full functionality...');

        // DOM Elements
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
        const tabBtns = document.querySelectorAll(".tab-btn");
        const tabContents = document.querySelectorAll(".tab-content");
        const actionForm = document.getElementById('actionForm');
        const resourceForm = document.getElementById('resourceForm');

        // Global State (integrated from main app)
        let isAuthenticated = false;
        let currentUser = null;
        let actionsData = [];
        let resourcesData = [];
        let reportsData = [];

        // Init
        document.addEventListener('DOMContentLoaded', async () => {
            // Initialize icons after DOM is loaded
            lucide.createIcons();

            // Check auth status
            await checkAuthStatus();

            initializeApp();

            // Set up form submissions
            actionForm?.addEventListener('submit', submitActionForm);
            resourceForm?.addEventListener('submit', submitResourceForm);

            // Location picker buttons
            document.querySelectorAll('.pick-location-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const formType = this.getAttribute('data-form');
                    openLocationPicker(formType);
                });
            });

            // Modal controls
            document.getElementById('confirmLocationBtn')?.addEventListener('click', confirmLocationSelection);
            document.getElementById('cancelLocationPicker')?.addEventListener('click', () => {
                document.getElementById('locationPickerModal').classList.add('hidden');
            });
            document.getElementById('closeLocationPicker')?.addEventListener('click', () => {
                document.getElementById('locationPickerModal').classList.add('hidden');
            });

            // Search inputs
            document.getElementById('my-actions-search')?.addEventListener('input', filterActionsTable);
            document.getElementById('my-resources-search')?.addEventListener('input', filterResourcesTable);

            // Status filters
            document.getElementById('my-actions-status-filter')?.addEventListener('change', filterActionsTable);
            document.getElementById('my-resources-status-filter')?.addEventListener('change', filterResourcesTable);

            // Reports filters
            document.getElementById('searchReports')?.addEventListener('input', filterReportsTable);
            document.getElementById('statusFilter')?.addEventListener('change', filterReportsTable);
            document.getElementById('categoryFilter')?.addEventListener('change', filterReportsTable);
        });

        // Additional initialization to ensure icons are properly loaded
        if (document.readyState === 'loading') {
            // If the document is still loading, wait for it to be complete
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    lucide.createIcons();
                }, 100);
            });
        } else {
            // If the document is already loaded, run immediately
            setTimeout(() => {
                lucide.createIcons();
            }, 100);
        }

        // Utility function to refresh icons after dynamic content changes
        function refreshIcons() {
            lucide.createIcons();
        }

        async function checkAuthStatus() {
            try {
                const response = await fetch("./../api/users/check_auth.php");

                if (!response.ok) {
                    // Handle non-2xx HTTP responses (like 401)
                    if (response.status === 401) {
                        // Unauthorized - redirect to login
                        window.location.href = './auth/login.html';
                        return;
                    }
                    throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
                }

                let result;
                try {
                    // Read response as text first to check for HTML content
                    const responseText = await response.text();

                    // Check if response looks like HTML (starts with <!DOCTYPE or <html)
                    if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html') ||
                        responseText.trim().startsWith('<b>') || responseText.trim().startsWith('<br')) {
                        console.error("Auth API returned HTML instead of JSON:", responseText.substring(0, 200) + "...");
                        throw new Error("Server returned HTML instead of JSON. Check for PHP errors.");
                    }

                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error("Error parsing auth response:", parseError);
                    throw new Error("Invalid JSON response from server");
                }

                if (result.authenticated) {
                    isAuthenticated = true;
                    currentUser = result.user;
                    window.currentUser = currentUser;
                } else {
                    isAuthenticated = false;
                    currentUser = null;
                    // Redirect to login if not authenticated
                    window.location.href = './auth/login.html';
                }
            } catch (error) {
                console.error("Failed to check authentication status:", error);
                // Redirect to login if there's any error
                window.location.href = './auth/login.html';
                isAuthenticated = false;
                currentUser = null;
            }
        }

        async function initializeApp() {
            await populateCountryDropdowns();
            setupEventListeners();

            // Load all data after checking auth
            if (isAuthenticated) {
                await loadAllData();
                // Update user info in the UI
                updateUserInfo();
            }
        }

        async function loadAllData() {
            await loadUserActions();
            await loadUserResources();
            await loadUserStats();
            await loadRecentActivity();
            await loadReports(); // If user is admin
        }

        function populateCountryDropdowns() {
            // Verify COUNTRIES data is loaded before attempting to populate
            if (!window.COUNTRIES || !Array.isArray(window.COUNTRIES)) {
                console.error('CRITICAL: COUNTRIES data not loaded! Expected window.COUNTRIES array.');
                // Fallback to a basic country list if COUNTRIES is not available
                const basicCountries = [
                    { name: "United States", code: "US" },
                    { name: "United Kingdom", code: "GB" },
                    { name: "Canada", code: "CA" },
                    { name: "Australia", code: "AU" },
                    { name: "India", code: "IN" },
                    { name: "Country not available", code: "N/A" }
                ];
                window.COUNTRIES = basicCountries;
            }

            const actionSelect = document.getElementById('actionCountrySelect');
            const resourceSelect = document.getElementById('resourceCountrySelect');

            const populate = (select) => {
                if(!select) return;
                select.innerHTML = '<option value="">Select Country</option>';
                // Double check that COUNTRIES exists before using it
                if (!window.COUNTRIES || !Array.isArray(window.COUNTRIES)) {
                    console.error('COUNTRIES data still not available after fallback');
                    return;
                }
                try {
                    window.COUNTRIES.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.name;
                        opt.textContent = c.name;
                        select.appendChild(opt);
                    });
                } catch (error) {
                    console.error('Error populating country dropdown:', error);
                }
            };
            populate(actionSelect);
            populate(resourceSelect);
        }

        function setupEventListeners() {
            // Nav
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
                        // Update active state in nav
                        document.querySelectorAll('.nav-item[data-page]').forEach(n => {
                           n.classList.remove('active', 'text-zinc-900', 'dark:text-zinc-100', 'bg-zinc-100', 'dark:bg-white/5');
                           n.classList.add('text-zinc-600', 'dark:text-zinc-400');
                        });
                        item.classList.add('active', 'text-zinc-900', 'dark:text-zinc-100', 'bg-zinc-100', 'dark:bg-white/5');
                        // Close mobile sidebar after navigation
                        if (window.innerWidth < 1024) { // lg breakpoint
                            sidebar.classList.add("-translate-x-full");
                        }
                    }
                });
            });

            // Toggles
            headerToggle?.addEventListener("click", () => {
                 sidebar.classList.toggle("-translate-x-full"); // Simple toggle for mobile logic
            });

            // Notifications
            notificationBtn?.addEventListener("click", (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle("hidden");
                userDropdown.classList.add("hidden");

                // Load notifications when dropdown is opened
                if (!notificationDropdown.classList.contains("hidden")) {
                    loadNotifications();
                }
            });

            // User Menu
            userMenu?.addEventListener("click", (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle("hidden");
                notificationDropdown.classList.add("hidden");

                // Update user info when menu is opened
                if (!userDropdown.classList.contains("hidden")) {
                    updateUserInfo();
                }
            });

            document.addEventListener("click", () => {
                notificationDropdown?.classList.add("hidden");
                userDropdown?.classList.add("hidden");
            });

            // Create Modal
            createActionBtn?.addEventListener("click", () => openCreateModal('action'));
            closeModal?.addEventListener("click", () => {
                createModal.classList.add("hidden");
                resetFormErrors();
            });

            // Tab Switching
            tabBtns.forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    e.preventDefault();  // Prevent default anchor behavior
                    const tabId = btn.getAttribute("data-tab");
                    if(tabId) switchTab(tabId);
                });
            });

            // Add logout functionality to all logout buttons
            document.querySelectorAll('.logout').forEach(logoutBtn => {
                logoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    handleLogout();
                });
            });

            // Add Profile Settings functionality
            document.querySelectorAll('#userDropdown button:not(.logout)').forEach(btn => {
                if (btn.textContent.trim().includes('Profile Settings')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Open profile settings modal or redirect to profile page
                        if (currentUser && currentUser.id) {
                            window.location.href = `../profile/index.html?user_id=${currentUser.id}`;
                        } else {
                            // Fallback: show a message or redirect to login
                            showErrorMessage('Please log in to access profile settings');
                        }
                    });
                } else if (btn.textContent.trim().includes('Preferences')) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        // Open preferences modal or redirect to settings page
                        if (currentUser && currentUser.id) {
                            window.location.href = `../profile/settings.html?user_id=${currentUser.id}`;
                        } else {
                            // Fallback: show a message or redirect to login
                            showErrorMessage('Please log in to access preferences');
                        }
                    });
                }
            });
        }

        function switchTab(tabId) {
             document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('active', 'border-indigo-500', 'text-zinc-900', 'dark:text-zinc-100');
                b.classList.add('border-transparent', 'text-zinc-500');
            });
            const activeBtn = document.querySelector(`[data-tab="${tabId}"]`);
            if(activeBtn) {
                activeBtn.classList.add('active', 'border-indigo-500', 'text-zinc-900', 'dark:text-zinc-100');
                activeBtn.classList.remove('border-transparent', 'text-zinc-500');
            }

            // Hide all tab content and remove active classes
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.remove('active', 'block');
                c.classList.add('hidden');
            });

            // Show selected tab content
            const activeTab = document.getElementById(tabId);
            if(activeTab) {
                activeTab.classList.remove('hidden', 'active');
                activeTab.classList.add('active', 'block');
            }
        }

        function showPage(pageName) {
            // Hide all pages
            pages.forEach((page) => {
                page.classList.remove("active", "block");
                page.classList.add("hidden");
            });

            // Show selected page
            const page = document.getElementById(pageName);
            if (page) {
                page.classList.remove("hidden", "active");
                page.classList.add("active", "block");
            }

            if(pageName === 'reports') {
                loadReports();
            }
        }


        function openCreateModal(type) {
            resetFormErrors(); // Clear previous errors
            createModal.classList.remove('hidden');
            if(type === 'action') {
                switchTab('action-tab');
                document.getElementById('editActionId').value = ""; // Clear edit mode
            } else {
                switchTab('resource-tab');
                document.getElementById('editResourceId').value = ""; // Clear edit mode
            }
        }

        // Data loading functions
        async function loadUserActions() {
            try {
                const response = await fetch(`./../api/actions/get_my_actions.php?user_id=${currentUser.id}`, {
                    method: "GET",
                    headers: { "Content-Type": "application/json" }
                });

                // Check if the response is ok before trying to parse JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if(result.success) {
                    actionsData = result.actions || [];
                    renderUserActions(actionsData);
                } else {
                    console.error("Failed to load actions:", result.message);
                }
            } catch (error) {
                console.error("Error loading actions:", error);
                // Additional check if the error is due to unexpected HTML response
                if (error.message && error.message.includes('JSON')) {
                    console.error("API returned HTML instead of JSON. Check for PHP errors.");
                }
            }
        }

        async function loadUserResources() {
            try {
                const response = await fetch(`./../api/resources/get_my_resources.php?user_id=${currentUser.id}`, {
                    method: "GET",
                    headers: { "Content-Type": "application/json" }
                });

                // Check if the response is ok before trying to parse JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if(result.success) {
                    resourcesData = result.resources || [];
                    renderUserResources(resourcesData);
                } else {
                    console.error("Failed to load resources:", result.message);
                }
            } catch (error) {
                console.error("Error loading resources:", error);
                // Additional check if the error is due to unexpected HTML response
                if (error.message && error.message.includes('JSON')) {
                    console.error("API returned HTML instead of JSON. Check for PHP errors.");
                }
            }
        }

        async function loadUserStats() {
            try {
                // Fetch all stats in parallel
                const [actionsRes, participationRes] = await Promise.allSettled([
                    fetch(`./../api/other/dashboard_stats.php?user_id=${currentUser.id}`, {
                        method: "GET",
                        headers: { "Content-Type": "application/json" }
                    }),
                    fetch(`./../api/other/dashboard_stats.php?user_id=${currentUser.id}&get_participation=true`, { // Using same file for participation count
                        method: "GET",
                        headers: { "Content-Type": "application/json" }
                    })
                ]);

                // Handle actions and resources stats
                if(actionsRes.status === 'fulfilled' && actionsRes.value.ok) {
                    let actionsStats;
                    try {
                        const responseText = await actionsRes.value.text();
                        actionsStats = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error("Error parsing actions stats response:", parseError);
                        throw new Error("Invalid JSON response from server");
                    }

                    if(actionsStats.success) {
                        document.getElementById('myActionsCount').textContent = actionsStats.total_actions || 0;
                        document.getElementById('myResourcesCount').textContent = actionsStats.total_resources || 0;
                    } else {
                        console.error("Actions stats API error:", actionsStats.message);
                        document.getElementById('myActionsCount').textContent = "0";
                        document.getElementById('myResourcesCount').textContent = "0";
                    }
                } else {
                    console.error("Failed to load action stats:", actionsRes.reason || "Network error");
                    document.getElementById('myActionsCount').textContent = "0";
                    document.getElementById('myResourcesCount').textContent = "0";
                }

                // Handle participation stats
                if(participationRes.status === 'fulfilled' && participationRes.value.ok) {
                    let participationStats;
                    try {
                        const responseText = await participationRes.value.text();
                        participationStats = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error("Error parsing participation stats response:", parseError);
                        throw new Error("Invalid JSON response from server");
                    }

                    if(participationStats.success) {
                        document.getElementById('participatedCount').textContent = participationStats.count || 0;
                    } else {
                        console.error("Participation stats API error:", participationStats.message);
                        document.getElementById('participatedCount').textContent = "0";
                    }
                } else {
                    console.error("Failed to load participation stats:", participationRes.reason || "Network error");
                    document.getElementById('participatedCount').textContent = "0";
                }

                // Comments count - fetch separately
                try {
                    const commentsRes = await fetch(`./../api/other/dashboard_stats.php?user_id=${currentUser.id}&type=comments`, {
                        method: "GET",
                        headers: { "Content-Type": "application/json" }
                    });

                    if (commentsRes.ok) {
                        const commentsStats = await commentsRes.json();
                        document.getElementById('commentsCount').textContent = commentsStats.comments_count || 0;
                    } else {
                        document.getElementById('commentsCount').textContent = "0";
                    }
                } catch (commentsError) {
                    console.error("Error loading comments count:", commentsError);
                    document.getElementById('commentsCount').textContent = "0";
                }
            } catch (error) {
                console.error("Error loading stats:", error);
                // Set defaults in case of error
                document.getElementById('myActionsCount').textContent = "0";
                document.getElementById('myResourcesCount').textContent = "0";
                document.getElementById('participatedCount').textContent = "0";
                document.getElementById('commentsCount').textContent = "0";
            }
        }

        async function loadRecentActivity() {
            try {
                const response = await fetch(`./../api/other/recent_activity.php?user_id=${currentUser.id}&limit=5&role=${currentUser.role}`, {
                    method: "GET",
                    headers: { "Content-Type": "application/json" }
                });

                // Check if the response is ok before trying to parse JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                let result;
                try {
                    // Read response as text first to check for HTML content
                    const responseText = await response.text();

                    // Check if response looks like HTML (starts with <!DOCTYPE or <html)
                    if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html') ||
                        responseText.trim().startsWith('<b>') || responseText.trim().startsWith('<br')) {
                        console.error("API returned HTML instead of JSON:", responseText.substring(0, 200) + "...");
                        throw new Error("Server returned HTML instead of JSON. Check for PHP errors.");
                    }

                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error("Error parsing recent activity response:", parseError);
                    throw new Error("Invalid JSON response from server");
                }

                if(result.success) {
                    renderRecentActivity(result.activity || []);
                } else {
                    console.error("Failed to load activity:", result.message);
                    // Fallback to empty state
                    const activityList = document.getElementById('recentActivityList');
                    activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No recent activity</p>';
                }
            } catch (error) {
                console.error("Error loading recent activity:", error);
                // Additional check if the error is due to unexpected HTML response
                if (error.message && error.message.includes('JSON')) {
                    console.error("API returned HTML instead of JSON. Check for PHP errors.");
                } else if (error.message && error.message.includes('HTML instead of JSON')) {
                    console.error("Server error detected - likely PHP error in API");
                }
                const activityList = document.getElementById('recentActivityList');
                activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">Error loading activity</p>';
            }
        }

        async function loadReports() {
            // Only load reports if user is admin
            if(currentUser.role !== 'admin') return;

            try {
                const response = await fetch("./../api/reports/get_reports.php");

                // Check if the response is ok before trying to parse JSON
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if(result.success) {
                    reportsData = result.reports || [];
                    renderReports(reportsData);

                    // Update stats
                    const total = reportsData.length;
                    const pending = reportsData.filter(r => r.status === 'pending').length;
                    const resolved = reportsData.filter(r => r.status === 'resolved').length;

                    document.getElementById('totalReportsCount').textContent = total;
                    document.getElementById('pendingReportsCount').textContent = pending;
                    document.getElementById('resolvedReportsCount').textContent = resolved;
                } else {
                    console.error("Failed to load reports:", result.message);
                }
            } catch (error) {
                console.error("Error loading reports:", error);
                // Additional check if the error is due to unexpected HTML response
                if (error.message && error.message.includes('JSON')) {
                    console.error("API returned HTML instead of JSON. Check for PHP errors.");
                }
            }
        }

        // Notification functions - Facebook-style
        async function loadNotifications() {
            try {
                const response = await fetch(`./../api/notifications/get_notifications.php?user_id=${currentUser.id}&limit=10`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                let result;
                try {
                    // Read response as text first to check for HTML content
                    const responseText = await response.text();

                    // Check if response looks like HTML (starts with <!DOCTYPE or <html)
                    if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html') ||
                        responseText.trim().startsWith('<b>') || responseText.trim().startsWith('<br')) {
                        console.error("API returned HTML instead of JSON:", responseText.substring(0, 200) + "...");
                        throw new Error("Server returned HTML instead of JSON. Check for PHP errors.");
                    }

                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error("Error parsing notifications response:", parseError);
                    throw new Error("Invalid JSON response from server");
                }

                if (result.success) {
                    renderNotifications(result.notifications);
                    updateNotificationBadge(result.unread_count);
                } else {
                    console.error("Failed to load notifications:", result.message);
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        function renderNotifications(notifications) {
            const container = document.getElementById('notificationDropdown');
            if (!notifications || notifications.length === 0) {
                container.innerHTML = '<p class="text-sm text-zinc-500 text-center py-8">No notifications yet</p>';
                return;
            }

            let notificationsHTML = `
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50 flex items-center justify-between">
                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Notifications</h3>
                    <div class="flex gap-2">
                        <button onclick="markAllAsRead()" class="text-xs text-indigo-600 hover:text-indigo-500">Mark all as read</button>
                        <button onclick="clearAllNotifications()" class="text-xs text-zinc-500 hover:text-zinc-700">Clear all</button>
                    </div>
                </div>
                <div class="max-h-96 overflow-y-auto" id="notificationsList">
            `;

            notifications.forEach(notif => {
                const isUnread = !notif.is_read;
                notificationsHTML += `
                    <div class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-800 cursor-pointer ${isUnread ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''}"
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
                            ${isUnread ? '<div class="w-2 h-2 bg-indigo-600 rounded-full"></div>' : ''}
                        </div>
                    </div>
                `;
            });

            notificationsHTML += `
                </div>
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                    <button onclick="loadMoreNotifications()" class="text-xs text-center w-full text-indigo-600 hover:text-indigo-500 font-medium">
                        See more
                    </button>
                </div>
            `;

            container.innerHTML = notificationsHTML;
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
            if (seconds < 86400) return Math.floor(seconds / 60) + 'h ago';
            if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
            return then.toLocaleDateString();
        }

        async function markAsRead(notificationId) {
            try {
                const response = await fetch(`./../api/notifications/mark_notification_read.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: notificationId })
                });

                if (response.ok) {
                    loadNotifications(); // Reload to update UI
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        async function markAllAsRead() {
            try {
                const response = await fetch(`./../api/notifications/mark_all_notifications_read.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: currentUser.id })
                });

                if (response.ok) {
                    loadNotifications();
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }

        async function clearAllNotifications() {
            const result = await Swal.fire({
                title: 'Clear all notifications?',
                text: 'This action cannot be undone',
                icon: 'warning',
                background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e4e4e7' : '#18181b',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, clear all'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch(`./../api/notifications/clear_all_notifications.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: currentUser.id })
                    });

                    if (response.ok) {
                        loadNotifications();
                    }
                } catch (error) {
                    console.error('Error clearing notifications:', error);
                }
            }
        }

        function loadMoreNotifications() {
            // Implementation for loading more notifications
            console.log('Loading more notifications...');
        }

        function updateNotificationBadge(count) {
            const badge = document.querySelector('#notificationBtn .badge');
            if (badge) {
                if (count > 0) {
                    badge.style.display = 'block';
                    badge.textContent = count > 99 ? '99+' : count;
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        function renderUserActions(actions) {
            const tbody = document.getElementById('my-actions-table-body');
            tbody.innerHTML = actions.map(a => {
                const statusColor = a.status === 'approved' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50' :
                                   a.status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50' :
                                   'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700';

                return `
                <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
                    <td class="py-3 px-5 text-zinc-500">#${a.id}</td>
                    <td class="py-3 px-5 font-medium text-zinc-900 dark:text-zinc-200">${a.title}</td>
                    <td class="py-3 px-5">${a.category}</td>
                    <td class="py-3 px-5">
                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusColor}">${a.status}</span>
                    </td>
                    <td class="py-3 px-5">${a.participants || 0}</td>
                    <td class="py-3 px-5 text-zinc-500">${new Date(a.created_at).toLocaleDateString()}</td>
                    <td class="py-3 px-5">
                        <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 text-xs font-medium mr-2" onclick="openEditAction(${a.id})">Edit</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium" onclick="confirmDeleteAction(${a.id})">Delete</button>
                        ${currentUser && currentUser.role === 'admin' && a.status === 'pending' ? `
                            <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium ml-2" onclick="approveAction(${a.id})">Approve</button>
                            <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium ml-1" onclick="rejectAction(${a.id})">Reject</button>
                        ` : ''}
                    </td>
                </tr>
                `;
            }).join('');
        }

        function renderUserResources(resources) {
            const tbody = document.getElementById('my-resources-table-body');
            tbody.innerHTML = resources.map(r => {
                const statusColor = r.status === 'approved' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50' :
                                   r.status === 'pending' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50' :
                                   'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700';

                return `
                <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
                    <td class="py-3 px-5 text-zinc-500">#${r.id}</td>
                    <td class="py-3 px-5 font-medium text-zinc-900 dark:text-zinc-200">${r.resource_name || r.title}</td>
                    <td class="py-3 px-5">${r.category}</td>
                    <td class="py-3 px-5 uppercase text-[10px] font-bold text-zinc-500">${r.type || ''}</td>
                    <td class="py-3 px-5">
                        <span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusColor}">${r.status}</span>
                    </td>
                    <td class="py-3 px-5 text-zinc-500">${r.location || ''}</td>
                    <td class="py-3 px-5">
                        <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium mr-2" onclick="openEditResource(${r.id})">Edit</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium" onclick="confirmDeleteResource(${r.id})">Delete</button>
                        ${currentUser && currentUser.role === 'admin' && r.status === 'pending' ? `
                            <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium ml-2" onclick="approveResource(${r.id})">Approve</button>
                            <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium ml-1" onclick="rejectResource(${r.id})">Reject</button>
                        ` : ''}
                    </td>
                </tr>
                `;
            }).join('');
        }

        function renderRecentActivity(activity) {
            const activityList = document.getElementById('recentActivityList');

            if(activity.length === 0) {
                activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No recent activity</p>';
                return;
            }

            activityList.innerHTML = activity.map(item => {
                let icon = "circle";  // Default
                let bgClass = "bg-zinc-500/10";
                let textClass = "text-zinc-500";

                if(item.type.includes('action')) {
                    icon = "zap";
                    bgClass = "bg-indigo-500/10";
                    textClass = "text-indigo-600";
                } else if(item.type.includes('resource')) {
                    icon = "package";
                    bgClass = "bg-emerald-500/10";
                    textClass = "text-emerald-600";
                } else if(item.type.includes('comment')) {
                    icon = "message-circle";
                    bgClass = "bg-amber-500/10";
                    textClass = "text-amber-600";
                }

                return `
                <div class="flex gap-4 group">
                    <div class="w-8 h-8 rounded-full ${bgClass} border flex items-center justify-center ${textClass} shrink-0">
                        <i data-lucide="${icon}" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-900 dark:text-zinc-200 font-medium">${item.message}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">${item.details || ''}</p>
                        <p class="text-[10px] text-zinc-500 mt-1">${new Date(item.timestamp).toLocaleString()}</p>
                    </div>
                </div>
                `;
            }).join('');

            lucide.createIcons();
        }

        function renderReports(reports) {
            const tbody = document.getElementById('reports-table-body');
            tbody.innerHTML = reports.map(r => `
                <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
                    <td class="py-3 px-5">${r.id}</td>
                    <td class="py-3 px-5">${r.reporter_name || 'Unknown'}</td>
                    <td class="py-3 px-5">${r.item_title || 'Item'}</td>
                    <td class="py-3 px-5">${r.item_type}</td>
                    <td class="py-3 px-5"><span class="px-2 py-0.5 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 text-[10px] rounded">${r.category}</span></td>
                    <td class="py-3 px-5 truncate max-w-[150px]">${r.reason || 'No reason provided'}</td>
                    <td class="py-3 px-5">${r.status}</td>
                    <td class="py-3 px-5">${new Date(r.created_at).toLocaleDateString()}</td>
                    <td class="py-3 px-5"><button class="text-indigo-500 text-xs font-medium hover:underline" onclick="viewReportDetails(${r.id})">View</button></td>
                </tr>
            `).join('');
        }

        // Filtering functions
        function filterActionsTable() {
            const searchTerm = document.getElementById('my-actions-search').value.toLowerCase();
            const statusFilter = document.getElementById('my-actions-status-filter').value;

            const filtered = actionsData.filter(action => {
                const matchesSearch = action.title.toLowerCase().includes(searchTerm) ||
                                     action.category.toLowerCase().includes(searchTerm);
                const matchesStatus = !statusFilter || action.status === statusFilter;

                return matchesSearch && matchesStatus;
            });

            renderUserActions(filtered);
        }

        function filterResourcesTable() {
            const searchTerm = document.getElementById('my-resources-search').value.toLowerCase();
            const statusFilter = document.getElementById('my-resources-status-filter').value;

            const filtered = resourcesData.filter(resource => {
                const resourceName = resource.resource_name || resource.title;
                const matchesSearch = resourceName.toLowerCase().includes(searchTerm) ||
                                     resource.category.toLowerCase().includes(searchTerm);
                const matchesStatus = !statusFilter || resource.status === statusFilter;

                return matchesSearch && matchesStatus;
            });

            renderUserResources(filtered);
        }

        function filterReportsTable() {
            if (!reportsData || reportsData.length === 0) return;

            const searchTerm = document.getElementById('searchReports').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;

            const filtered = reportsData.filter(report => {
                const matchesSearch = report.reason.toLowerCase().includes(searchTerm) ||
                                     (report.item_title && report.item_title.toLowerCase().includes(searchTerm));
                const matchesStatus = !statusFilter || report.status === statusFilter;
                const matchesCategory = !categoryFilter || report.category === categoryFilter;

                return matchesSearch && matchesStatus && matchesCategory;
            });

            renderReports(filtered);
        }

        // Edit functionality
        function openEditAction(id) {
            const action = actionsData.find(a => a.id == id);
            if(action) {
                document.getElementById('editActionId').value = action.id;
                document.getElementById('actionTitle').value = action.title || '';
                document.getElementById('actionCategory').value = action.category || '';
                document.getElementById('actionTheme').value = action.theme || action.category || '';
                document.getElementById('actionDateTime').value = action.start_time || '';
                document.getElementById('actionDescription').value = action.description || '';

                // Location fields
                document.getElementById('actionCountrySelect').value = action.country || '';
                document.getElementById('actionLocationDetails').value = action.location_details || '';
                document.getElementById('actionLatitude').value = action.latitude || '';
                document.getElementById('actionLongitude').value = action.longitude || '';

                openCreateModal('action');
            }
        }

        function openEditResource(id) {
            const resource = resourcesData.find(r => r.id == id);
            if(resource) {
                document.getElementById('editResourceId').value = resource.id;
                document.getElementById('resourceName').value = resource.resource_name || '';
                document.getElementById('resourceCategory').value = resource.category || '';
                document.getElementById('resourceType').querySelector(`input[value="${resource.type}"]`)?.click();
                document.getElementById('resourceDescription').value = resource.description || '';

                // Location fields
                document.getElementById('resourceCountrySelect').value = resource.country || '';
                document.getElementById('resourceLocationDetails').value = resource.location_details || '';
                document.getElementById('resourceLatitude').value = resource.latitude || '';
                document.getElementById('resourceLongitude').value = resource.longitude || '';

                openCreateModal('resource');
            }
        }

        // Form validation and submission
        function resetFormErrors() {
            // Remove error classes from inputs
            document.querySelectorAll('.input-error').forEach(el => {
                el.classList.remove('input-error');
            });

            // Hide error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.style.display = 'none';
            });
        }

        function addFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorEl = document.getElementById(fieldId + '-error');

            if(field) field.classList.add('input-error');
            if(errorEl) {
                errorEl.textContent = message;
                errorEl.classList.add('show');
                errorEl.style.display = 'block';
            }
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            const errorEl = document.getElementById(fieldId + '-error');

            if(field) field.classList.remove('input-error');
            if(errorEl) {
                errorEl.classList.remove('show');
                errorEl.style.display = 'none';
            }
        }

        async function submitActionForm(e) {
            e.preventDefault();

            resetFormErrors();

            const form = e.target;
            const formData = new FormData(form);
            const editId = document.getElementById('editActionId').value;

            // Get field values
            const title = document.getElementById('actionTitle').value.trim();
            const category = document.getElementById('actionCategory').value;
            const theme = document.getElementById('actionTheme').value;
            const start_time = document.getElementById('actionDateTime').value;
            const description = document.getElementById('actionDescription').value.trim();
            const country = document.getElementById('actionCountrySelect').value;
            const location_details = document.getElementById('actionLocationDetails').value.trim();
            const latitude = document.getElementById('actionLatitude').value;
            const longitude = document.getElementById('actionLongitude').value;

            // Validation
            let isValid = true;

            if(!title) {
                addFieldError('actionTitle', 'Title is required');
                isValid = false;
            }

            if(!category) {
                addFieldError('actionCategory', 'Category is required');
                isValid = false;
            }

            if(!start_time) {
                addFieldError('actionDateTime', 'Date & Time is required');
                isValid = false;
            }

            if(!country) {
                addFieldError('actionCountrySelect', 'Country is required');
                isValid = false;
            }

            if(!isValid) return;

            // Prepare payload
            const payload = {
                id: editId ? parseInt(editId) : undefined,
                title,
                category,
                theme,
                description,
                start_time,
                country,
                location_details,
                latitude: latitude ? parseFloat(latitude) : null,
                longitude: longitude ? parseFloat(longitude) : null,
                creator_id: currentUser.id
            };

            try {
                const url = editId ? "./../api/actions/update_action.php" : "./../api/actions/create_action.php";
                const response = await fetch(url, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if(result.success) {
                    showSuccessMessage(`Action ${(editId ? 'updated' : 'created')} successfully!`);
                    createModal.classList.add('hidden');

                    // Reload data
                    await loadAllData();
                } else {
                    showErrorMessage(`Failed to ${(editId ? 'update' : 'create')} action: ${result.message}`);
                }
            } catch (error) {
                console.error("Error submitting action:", error);
                showErrorMessage("Network error. Please try again.");
            }
        }

        async function submitResourceForm(e) {
            e.preventDefault();

            resetFormErrors();

            const form = e.target;
            const formData = new FormData(form);
            const editId = document.getElementById('editResourceId').value;

            // Get field values
            const resource_name = document.getElementById('resourceName').value.trim();
            const category = document.getElementById('resourceCategory').value;
            const type = document.querySelector('input[name="type"]:checked')?.value;
            const description = document.getElementById('resourceDescription').value.trim();
            const country = document.getElementById('resourceCountrySelect').value;
            const location_details = document.getElementById('resourceLocationDetails').value.trim();
            const latitude = document.getElementById('resourceLatitude').value;
            const longitude = document.getElementById('resourceLongitude').value;

            // Validation
            let isValid = true;

            if(!resource_name) {
                addFieldError('resourceName', 'Resource name is required');
                isValid = false;
            }

            if(!category) {
                addFieldError('resourceCategory', 'Category is required');
                isValid = false;
            }

            if(!type) {
                addFieldError('resourceType', 'Type (Offer/Request) is required');
                isValid = false;
            }

            if(!country) {
                addFieldError('resourceCountrySelect', 'Country is required');
                isValid = false;
            }

            if(!isValid) return;

            // Prepare payload
            const payload = {
                id: editId ? parseInt(editId) : undefined,
                resource_name,
                category,
                type,
                description,
                country,
                location_details,
                latitude: latitude ? parseFloat(latitude) : null,
                longitude: longitude ? parseFloat(longitude) : null,
                publisher_id: currentUser.id
            };

            try {
                const url = editId ? "./../api/resources/update_resource.php" : "./../api/resources/create_resource.php";
                const response = await fetch(url, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if(result.success) {
                    showSuccessMessage(`Resource ${(editId ? 'updated' : 'created')} successfully!`);
                    createModal.classList.add('hidden');

                    // Reload data
                    await loadAllData();
                } else {
                    showErrorMessage(`Failed to ${(editId ? 'update' : 'create')} resource: ${result.message}`);
                }
            } catch (error) {
                console.error("Error submitting resource:", error);
                showErrorMessage("Network error. Please try again.");
            }
        }

        async function confirmDeleteAction(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e4e4e7' : '#18181b',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch("./../api/actions/delete_action.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: id })
                    });

                    const result = await response.json();

                    if(result.success) {
                        showSuccessMessage('Action deleted successfully!');
                        await loadAllData(); // Reload to reflect changes
                    } else {
                        showErrorMessage(`Failed to delete action: ${result.message}`);
                    }
                } catch (error) {
                    console.error("Error deleting action:", error);
                    showErrorMessage("Network error. Please try again.");
                }
            }
        }

        async function confirmDeleteResource(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e4e4e7' : '#18181b',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch("./../api/resources/delete_resource.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: id })
                    });

                    const result = await response.json();

                    if(result.success) {
                        showSuccessMessage('Resource deleted successfully!');
                        await loadAllData(); // Reload to reflect changes
                    } else {
                        showErrorMessage(`Failed to delete resource: ${result.message}`);
                    }
                } catch (error) {
                    console.error("Error deleting resource:", error);
                    showErrorMessage("Network error. Please try again.");
                }
            }
        }

        // Add approve/reject functions for actions
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
                console.error("Error approving action:", error);
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
                console.error("Error rejecting action:", error);
                showErrorMessage('Error rejecting action');
            }
        }

        // Add approve/reject functions for resources
        async function approveResource(id) {
            try {
                const response = await fetch("./../api/resources/approve_resource.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: id, action: 'approve' })
                });
                const result = await response.json();
                if(result.success) {
                    showSuccessMessage('Resource approved successfully!');
                    await loadAllData();
                } else {
                    showErrorMessage(result.message);
                }
            } catch (error) {
                console.error("Error approving resource:", error);
                showErrorMessage('Error approving resource');
            }
        }

        async function rejectResource(id) {
            try {
                const response = await fetch("./../api/resources/approve_resource.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: id, action: 'reject' })
                });
                const result = await response.json();
                if(result.success) {
                    showSuccessMessage('Resource rejected successfully!');
                    await loadAllData();
                } else {
                    showErrorMessage(result.message);
                }
            } catch (error) {
                console.error("Error rejecting resource:", error);
                showErrorMessage('Error rejecting resource');
            }
        }

        async function handleLogout() {
            try {
                await fetch("./../api/users/logout.php", { method: "POST" });
            } catch (e) {
                // Even if logout fails, redirect to login
            } finally {
                window.location.href = './auth/login.html';
            }
        }

        function updateUserInfo() {
            if (currentUser) {
                // Update user dropdown information
                const userNameElement = document.querySelector('#userDropdown .text-zinc-900') ||
                                      document.querySelector('#userDropdown p.text-zinc-900');
                const userEmailElement = document.querySelector('#userDropdown .text-zinc-500.truncate') ||
                                        document.querySelector('#userDropdown p.text-zinc-500');

                if (userNameElement) {
                    userNameElement.textContent = currentUser.name || 'User';
                }

                if (userEmailElement) {
                    userEmailElement.textContent = currentUser.email || 'user@example.com';
                }

                // Update header user info in the user menu button
                const headerUserNameElement = document.querySelector('#userMenu .text-zinc-700') ||
                                          document.querySelector('#userMenu .text-zinc-300');
                if (headerUserNameElement) {
                    const text = headerUserNameElement.textContent.trim();
                    // Only update if it's the placeholder text, not a real name
                    if (text === 'Alex J.' || text === 'User' || text.includes('...')) {
                        headerUserNameElement.textContent = currentUser.name || 'User';
                    }
                }

                // Update avatar in user menu
                const headerAvatarElement = document.querySelector('#userMenu img');
                if (headerAvatarElement) {
                    const avatarUrl = currentUser.avatar_url || `https://api.dicebear.com/7.x/avataaars/svg?seed=${currentUser.name || 'user'}`;
                    headerAvatarElement.src = avatarUrl;
                }

                // Update avatar in dropdown (if it exists)
                const dropdownAvatarElement = document.querySelector('#userDropdown img');
                if (dropdownAvatarElement) {
                    const avatarUrl = currentUser.avatar_url || `https://api.dicebear.com/7.x/avataaars/svg?seed=${currentUser.name || 'user'}`;
                    dropdownAvatarElement.src = avatarUrl;
                }
            }
        }

        function showSuccessMessage(msg) {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: msg,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                background: isDark ? '#18181b' : '#fff',
                color: isDark ? '#e4e4e7' : '#18181b',
                customClass: { popup: 'swal2-popup-custom shadow-xl border border-zinc-200 dark:border-zinc-800' }
            });
        }

        function showErrorMessage(msg) {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                background: isDark ? '#18181b' : '#fff',
                color: isDark ? '#e4e4e7' : '#18181b',
                customClass: { popup: 'swal2-popup-custom shadow-xl border border-zinc-200 dark:border-zinc-800' }
            });
        }

        // Report functionality
        function viewReportDetails(id) {
            const report = reportsData.find(r => r.id == id);
            if(report) {
                document.getElementById('reportIdDetail').textContent = "#" + report.id;
                document.getElementById('reporterNameDetail').textContent = report.reporter_name || 'Unknown';
                document.getElementById('reporterEmailDetail').textContent = report.reporter_email || 'No email';
                document.getElementById('reportedItemTypeDetail').textContent = report.item_type || 'Unknown';
                document.getElementById('reportedItemTitleDetail').textContent = report.item_title || 'Untitled';
                document.getElementById('reportReasonDetail').textContent = report.reason || 'No reason provided';
                document.getElementById('reportCategoryDetail').textContent = report.category || 'Uncategorized';
                document.getElementById('reportStatusDetail').textContent = report.status || 'Unknown';
                document.getElementById('reportDateDetail').textContent = new Date(report.created_at).toLocaleString();

                // Set the ID for later actions
                document.getElementById('reportDetailsModal').dataset.reportId = report.id;

                const modal = document.getElementById('reportDetailsModal');
                modal.classList.remove('hidden');
            }
        }

        function closeReportDetailsModal() {
            document.getElementById('reportDetailsModal').classList.add('hidden');
        }

        async function updateReportStatus(status) {
            const reportId = document.getElementById('reportDetailsModal').dataset.reportId;
            if(!reportId) return;

            try {
                const response = await fetch("./../api/reports/update_report_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ id: reportId, status })
                });

                const result = await response.json();

                if(result.success) {
                    closeReportDetailsModal();
                    showSuccessMessage(`Report marked as ${status}`);
                    loadReports(); // Reload reports list
                } else {
                    showErrorMessage(`Failed to update report: ${result.message}`);
                }
            } catch (error) {
                console.error("Error updating report:", error);
                showErrorMessage("Network error. Please try again.");
            }
        }

        async function deleteReport() {
            const reportId = document.getElementById('reportDetailsModal').dataset.reportId;
            if(!reportId) return;

            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the report!",
                icon: 'warning',
                background: document.documentElement.classList.contains('dark') ? '#18181b' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#e4e4e7' : '#18181b',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch("./../api/reports/delete_report.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ id: reportId })
                    });

                    const result = await response.json();

                    if(result.success) {
                        closeReportDetailsModal();
                        showSuccessMessage('Report deleted successfully!');
                        loadReports(); // Reload reports list
                    } else {
                        showErrorMessage(`Failed to delete report: ${result.message}`);
                    }
                } catch (error) {
                    console.error("Error deleting report:", error);
                    showErrorMessage("Network error. Please try again.");
                }
            }
        }

        // --- Location Map Logic ---
        function initLocationPickerMap(lat = 48.8566, lng = 2.3522) {
            if (window.locationPickerMap) window.locationPickerMap.remove();

            const mapEl = document.getElementById('locationPickerMap');
            if(!mapEl) return;

            window.locationPickerMap = L.map('locationPickerMap').setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(window.locationPickerMap);

            window.selectedLocationMarker = L.marker([lat, lng]).addTo(window.locationPickerMap);

            window.locationPickerMap.on('click', function(e) {
                const {lat, lng} = e.latlng;
                if(window.selectedLocationMarker) window.selectedLocationMarker.setLatLng(e.latlng);
                else window.selectedLocationMarker = L.marker(e.latlng).addTo(window.locationPickerMap);

                document.getElementById('selectedCoords').textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                window.selectedLocation = {lat, lng};
            });
        }

        function openLocationPicker(formType) {
            const modal = document.getElementById('locationPickerModal');
            modal.classList.remove('hidden');
            // Slight delay to render map correctly
            setTimeout(() => {
                // Use current coordinates if available, otherwise default
                let lat = 48.8566, lng = 2.3522;

                // Try to get current location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        lat = position.coords.latitude;
                        lng = position.coords.longitude;
                        initLocationPickerMap(lat, lng);
                        window.locationPickerMap.invalidateSize();
                    }, function() {
                        // If geolocation fails, use default coords
                        initLocationPickerMap(lat, lng);
                        window.locationPickerMap.invalidateSize();
                    });
                } else {
                    initLocationPickerMap(lat, lng);
                    window.locationPickerMap.invalidateSize();
                }
            }, 100);
        }

        function confirmLocationSelection() {
            const modal = document.getElementById('locationPickerModal');
            modal.classList.add('hidden');
            if(window.selectedLocation) {
                // Update inputs based on active tab
                const activeTab = document.querySelector('.tab-content.active').id;
                const prefix = activeTab === 'action-tab' ? 'action' : 'resource';

                document.getElementById(`${prefix}Latitude`).value = window.selectedLocation.lat;
                document.getElementById(`${prefix}Longitude`).value = window.selectedLocation.lng;

                // Update country field based on coordinates using reverse geocoding
                updateCountryFromCoordinates(window.selectedLocation.lat, window.selectedLocation.lng, `${prefix}Country`);
            }
        }

        async function updateCountryFromCoordinates(lat, lng, countryFieldId) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();

                if(data.address && data.address.country) {
                    // Find the country in our list and set it
                    const countrySelect = document.getElementById(countryFieldId + 'Select');
                    if(countrySelect) {
                        for(let option of countrySelect.options) {
                            if(option.text === data.address.country) {
                                option.selected = true;
                                document.getElementById(countryFieldId).value = option.value; // Hidden field
                                break;
                            }
                        }
                    }
                }
            } catch (error) {
                console.error("Error getting country from coordinates:", error);
            }
        }