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

            // Handle URL hash navigation - check if a specific page should be shown
            const hash = window.location.hash.replace('#', '');
            if (hash) {
                // Map hash values to page names (they should match the section IDs and data-page values)
                const pageMap = {
                    'overview': 'overview',
                    'actions': 'actions',
                    'stories': 'stories',
                    'challenges': 'challenges',
                    'reminders': 'reminders',
                    'contact': 'contact'
                };

                const targetPage = pageMap[hash];
                if (targetPage) {
                    // Show the requested page after a brief delay to ensure initialization is complete
                    setTimeout(() => {
                        showPage(targetPage);

                        // Update the active state in the navigation
                        document.querySelectorAll('.nav-item[data-page]').forEach(navItem => {
                            navItem.classList.remove('active', 'text-zinc-900', 'dark:text-zinc-100', 'bg-zinc-100', 'dark:bg-white/5');
                            navItem.classList.add('text-zinc-600', 'dark:text-zinc-400');
                        });

                        const activeNavItem = document.querySelector(`.nav-item[data-page="${targetPage}"]`);
                        if (activeNavItem) {
                            activeNavItem.classList.remove('text-zinc-600', 'dark:text-zinc-400');
                            activeNavItem.classList.add('active', 'text-zinc-900', 'dark:text-zinc-100', 'bg-zinc-100', 'dark:bg-white/5');
                        }
                    }, 100);
                }
            }
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
            await loadUserReminders(); // Load user reminders as well
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
                    // Only prevent default if it's an internal page navigation (has data-page attribute)
                    const pageName = item.getAttribute("data-page");
                    if (pageName) {
                        e.preventDefault();
                        if (item.classList.contains("logout")) {
                            handleLogout();
                            return;
                        }
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
                    } else if (item.classList.contains("logout")) {
                        // Handle logout without preventing default
                        e.preventDefault();
                        handleLogout();
                    }
                    // For links without data-page (external pages), let the default behavior occur
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

            if(pageName === 'reminders') {
                if (isUserLoggedIn) {
                    loadUserReminders(); // Load reminders when navigating to reminders page
                } else {
                    showSwal('Login Required', 'Please log in to view your reminders.', 'info');
                }
            }

            // Update URL hash based on current page (except for overview/dashboard)
            if (pageName === 'overview') {
                history.replaceState(null, null, window.location.pathname + window.location.search);
            } else {
                history.replaceState(null, null, window.location.pathname + window.location.search + '#' + pageName);
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
                        // Safely update elements only if they exist
                        const myActionsCountEl = document.getElementById('myActionsCount');
                        if(myActionsCountEl) myActionsCountEl.textContent = actionsStats.total_actions || 0;

                        const myResourcesCountEl = document.getElementById('myResourcesCount');
                        if(myResourcesCountEl) myResourcesCountEl.textContent = actionsStats.total_resources || 0;
                    } else {
                        console.error("Actions stats API error:", actionsStats.message);
                        const myActionsCountEl = document.getElementById('myActionsCount');
                        if(myActionsCountEl) myActionsCountEl.textContent = "0";
                        const myResourcesCountEl = document.getElementById('myResourcesCount');
                        if(myResourcesCountEl) myResourcesCountEl.textContent = "0";
                    }
                } else {
                    console.error("Failed to load action stats:", actionsRes.reason || "Network error");
                    const myActionsCountEl = document.getElementById('myActionsCount');
                    if(myActionsCountEl) myActionsCountEl.textContent = "0";
                    const myResourcesCountEl = document.getElementById('myResourcesCount');
                    if(myResourcesCountEl) myResourcesCountEl.textContent = "0";
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
                        const participatedCountEl = document.getElementById('participatedCount');
                        if(participatedCountEl) participatedCountEl.textContent = participationStats.count || 0;
                    } else {
                        console.error("Participation stats API error:", participationStats.message);
                        const participatedCountEl = document.getElementById('participatedCount');
                        if(participatedCountEl) participatedCountEl.textContent = "0";
                    }
                } else {
                    console.error("Failed to load participation stats:", participationRes.reason || "Network error");
                    const participatedCountEl = document.getElementById('participatedCount');
                    if(participatedCountEl) participatedCountEl.textContent = "0";
                }

                // Comments/Engagement count - fetch separately (only update if element exists)
                const engagementCountEl = document.getElementById('engagementCount');
                if(engagementCountEl) {  // Only fetch and update if the element exists
                    try {
                        const commentsRes = await fetch(`./../api/other/dashboard_stats.php?user_id=${currentUser.id}&type=comments`, {
                            method: "GET",
                            headers: { "Content-Type": "application/json" }
                        });

                        if (commentsRes.ok) {
                            const commentsStats = await commentsRes.json();
                            engagementCountEl.textContent = commentsStats.comments_count || 0;
                        } else {
                            if(engagementCountEl) engagementCountEl.textContent = "0";
                        }
                    } catch (commentsError) {
                        console.error("Error loading comments count:", commentsError);
                        if(engagementCountEl) engagementCountEl.textContent = "0";
                    }
                }
            } catch (error) {
                console.error("Error loading stats:", error);
                // Safely set defaults only if elements exist
                const myActionsCountEl = document.getElementById('myActionsCount');
                if(myActionsCountEl) myActionsCountEl.textContent = "0";

                const myResourcesCountEl = document.getElementById('myResourcesCount');
                if(myResourcesCountEl) myResourcesCountEl.textContent = "0";

                const participatedCountEl = document.getElementById('participatedCount');
                if(participatedCountEl) participatedCountEl.textContent = "0";

                const engagementCountEl = document.getElementById('engagementCount');
                if(engagementCountEl) engagementCountEl.textContent = "0";
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
                    if(activityList) activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No recent activity</p>';
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
                if(activityList) activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">Error loading activity</p>';
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
            if (!container) return; // Safety check
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
                const isUnread = !notif.is_read || notif.isRead === 0;

                // Get the user name instead of the item title to match main project behavior
                const userName = notif.user_name || notif.userName || notif.name || 'User';

                // The message should be what the user did
                const message = notif.message || notif.title || notif.notification_message || 'No message';

                const timestamp = notif.created_at || notif.date || notif.timestamp || notif.createdAt;

                notificationsHTML += `
                    <div class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-800 cursor-pointer ${isUnread ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''}"
                         onclick="markAsRead(${notif.id || notif.ID || 0})">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <i data-lucide="${getNotificationIcon(notif.type || 'default')}" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">${userName}</p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">${message}</p>
                                <p class="text-xs text-zinc-400 mt-1">${formatTimeAgo(timestamp)}</p>
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
            // Show a simple confirmation without SweetAlert
            if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`./../api/notifications/clear_all_notifications.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: currentUser.id })
                });

                if (response.ok) {
                    loadNotifications();
                } else {
                    console.error('Failed to clear notifications:', response.status);
                }
            } catch (error) {
                console.error('Error clearing notifications:', error);
            }
        }

        async function loadMoreNotifications() {
            try {
                // Get current notification count to use as offset for pagination
                const currentNotificationsCount = document.querySelectorAll('#notificationsList > div').length;
                const limit = 10; // Load 10 more notifications

                const response = await fetch(`./../api/notifications/get_notifications.php?user_id=${currentUser.id}&limit=10&offset=${currentNotificationsCount}`);

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

                if (result.success && result.notifications && result.notifications.length > 0) {
                    // Append new notifications to the existing list
                    const container = document.getElementById('notificationsList');
                    if (container) {
                        // Add new notifications to the list (after existing ones)
                        result.notifications.forEach(notif => {
                            const isUnread = !notif.is_read;

                            // Get the user name instead of the item title to match main project behavior
                            const userName = notif.user_name || notif.userName || notif.name || 'User';

                            // The message should be what the user did
                            const message = notif.message || notif.title || notif.notification_message || 'No message';
                            const timestamp = notif.created_at || notif.date || notif.timestamp || notif.createdAt;

                            const notificationHTML = `
                                <div class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-900/50 border-b border-zinc-100 dark:border-zinc-800 cursor-pointer ${isUnread ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''}"
                                     onclick="markAsRead(${notif.id || notif.ID || 0})">
                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                                <i data-lucide="${getNotificationIcon(notif.type || 'default')}" class="w-4 h-4 text-indigo-600 dark:text-indigo-400"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">${userName}</p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">${message}</p>
                                            <p class="text-xs text-zinc-400 mt-1">${formatTimeAgo(timestamp)}</p>
                                        </div>
                                        ${isUnread ? '<div class="w-2 h-2 bg-indigo-600 rounded-full"></div>' : ''}
                                    </div>
                                </div>
                            `;
                            container.insertAdjacentHTML('beforeend', notificationHTML);
                        });
                        lucide.createIcons();
                    }
                } else {
                    alert('No more notifications to load');
                }
            } catch (error) {
                console.error('Error loading more notifications:', error);
            }
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
            if(!tbody) return; // Safety check
            tbody.innerHTML = actions.map(a => {
                let statusClass = 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700';

                if (a.status.toLowerCase() === 'pending') {
                    statusClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50';
                } else if (a.status.toLowerCase() === 'approved') {
                    statusClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50';
                } else if (a.status.toLowerCase() === 'rejected') {
                    statusClass = 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50';
                }

                const isAdmin = currentUser && currentUser.role === 'admin';
                let actionButtons = '';

                if (isAdmin) {
                    actionButtons = `
                        <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium mr-2" onclick="approveAction(${a.id})">Approve</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium mr-2" onclick="rejectAction(${a.id})">Reject</button>
                        <button class="text-amber-600 dark:text-amber-400 hover:text-amber-500 text-xs font-medium" onclick="openEditAction(${a.id})">Edit</button>
                    `;
                } else {
                    actionButtons = `
                        <button class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 text-xs font-medium mr-2" onclick="openEditAction(${a.id})">Edit</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium" onclick="confirmDeleteAction(${a.id})">Delete</button>
                    `;
                }

                return `
                    <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
                        <td class="py-3 px-5 text-zinc-500">#${a.id}</td>
                        <td class="py-3 px-5">
                            <img src="${a.image_url || a.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEFjdGlvbiBJbWFnZTwvdGV4dD48L3N2Zz4='}" alt="${a.title}" class="w-12 h-12 object-cover rounded" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEFjdGlvbiBJbWFnZTwvdGV4dD48L3N2Zz4='">
                        </td>
                        <td class="py-3 px-5 font-medium text-zinc-900 dark:text-zinc-200">${a.title}</td>
                        <td class="py-3 px-5">${a.category}</td>
                        <td class="py-3 px-5"><span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass}">${a.status}</span></td>
                        <td class="py-3 px-5">${a.participants || 0}</td>
                        <td class="py-3 px-5 text-zinc-500">${new Date(a.created_at).toLocaleDateString()}</td>
                        <td class="py-3 px-5">
                            ${actionButtons}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderUserResources(resources) {
            const tbody = document.getElementById('my-resources-table-body');
            if(!tbody) return; // Safety check
            tbody.innerHTML = resources.map(r => {
                let statusClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50';

                if (r.status.toLowerCase() === 'pending') {
                    statusClass = 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50';
                } else if (r.status.toLowerCase() === 'approved') {
                    statusClass = 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/50';
                } else if (r.status.toLowerCase() === 'rejected') {
                    statusClass = 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900/50';
                }

                const isAdmin = currentUser && currentUser.role === 'admin';
                let resourceButtons = '';

                if (isAdmin) {
                    resourceButtons = `
                        <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium mr-2" onclick="approveResource(${r.id})">Approve</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium mr-2" onclick="rejectResource(${r.id})">Reject</button>
                        <button class="text-amber-600 dark:text-amber-400 hover:text-amber-500 text-xs font-medium" onclick="openEditResource(${r.id})">Edit</button>
                    `;
                } else {
                    resourceButtons = `
                        <button class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 text-xs font-medium mr-2" onclick="openEditResource(${r.id})">Edit</button>
                        <button class="text-rose-600 dark:text-rose-400 hover:text-rose-500 text-xs font-medium" onclick="confirmDeleteResource(${r.id})">Delete</button>
                    `;
                }

                return `
                    <tr class="hover:bg-zinc-50 dark:hover:bg-white/5 transition-colors border-b border-zinc-100 dark:border-zinc-800/50">
                        <td class="py-3 px-5 text-zinc-500">#${r.id}</td>
                        <td class="py-3 px-5">
                            <img src="${r.image_url || r.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFJlc291cmNlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='}}" alt="${r.resource_name || r.title}" class="w-12 h-12 object-cover rounded" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIFJlc291cmNlIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                        </td>
                        <td class="py-3 px-5 font-medium text-zinc-900 dark:text-zinc-200">${r.resource_name || r.title}</td>
                        <td class="py-3 px-5">${r.category}</td>
                        <td class="py-3 px-5 uppercase text-[10px] font-bold text-zinc-500">${r.type || ''}</td>
                        <td class="py-3 px-5"><span class="px-2 py-1 rounded-full text-[10px] font-semibold ${statusClass}">${r.status}</span></td>
                        <td class="py-3 px-5 text-zinc-500">${r.location || ''}</td>
                        <td class="py-3 px-5">
                            ${resourceButtons}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderRecentActivity(activity) {
            const activityList = document.getElementById('recentActivityList');
            if(!activityList) return; // Safety check

            if(activity.length === 0) {
                if(activityList) activityList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No recent activity</p>';
                return;
            }

            if(activityList) activityList.innerHTML = activity.map(item => {
                let icon = "circle";  // Default
                let bgClass = "bg-zinc-500/10";
                let textClass = "text-zinc-500";

                // Support multiple field name variations
                const itemType = item.type || item.itemType || item.activityType || 'unknown';
                const message = item.message || item.title || item.activity_message || 'No activity';
                const details = item.details || item.description || item.content || '';
                const timestamp = item.timestamp || item.date || item.created_at || item.createdAt;

                if(itemType.includes('action')) {
                    icon = "zap";
                    bgClass = "bg-indigo-500/10";
                    textClass = "text-indigo-600";
                } else if(itemType.includes('resource')) {
                    icon = "package";
                    bgClass = "bg-emerald-500/10";
                    textClass = "text-emerald-600";
                } else if(itemType.includes('comment')) {
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
                        <p class="text-sm text-zinc-900 dark:text-zinc-200 font-medium">${message}</p>
                        <p class="text-xs text-zinc-500 mt-0.5">${details}</p>
                        <p class="text-[10px] text-zinc-500 mt-1">${timestamp ? new Date(timestamp).toLocaleString() : ''}</p>
                    </div>
                </div>
                `;
            }).join('');

            lucide.createIcons();
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

                // Show image preview if available
                if (action.image_url || action.image) {
                    document.getElementById('actionImagePreview').src = action.image_url || action.image;
                    document.getElementById('actionImagePreviewContainer').classList.remove('hidden');
                } else {
                    document.getElementById('actionImagePreviewContainer').classList.add('hidden');
                }

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

                // Show image preview if available
                if (resource.image_url || resource.image) {
                    document.getElementById('resourceImagePreview').src = resource.image_url || resource.image;
                    document.getElementById('resourceImagePreviewContainer').classList.remove('hidden');
                } else {
                    document.getElementById('resourceImagePreviewContainer').classList.add('hidden');
                }

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

            // Check if we have a file input in the form and if there's a file selected
            const fileInput = document.getElementById('action-file-input');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

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

            try {
                if (hasFile) {
                    // If there's a file, submit via FormData
                    const actionFormData = new FormData();
                    actionFormData.append('title', title);
                    actionFormData.append('category', category);
                    actionFormData.append('theme', theme);
                    actionFormData.append('description', description);
                    actionFormData.append('start_time', start_time);
                    actionFormData.append('country', country);
                    actionFormData.append('location_details', location_details);
                    actionFormData.append('latitude', latitude);
                    actionFormData.append('longitude', longitude);
                    actionFormData.append('creator_id', currentUser.id);

                    if (editId) {
                        actionFormData.append('id', editId);
                    }

                    actionFormData.append('image', fileInput.files[0]);

                    const url = editId ? "./../api/actions/update_action.php" : "./../api/actions/create_action.php";
                    const response = await fetch(url, {
                        method: "POST",
                        body: actionFormData
                    });

                    const result = await response.json();

                    if(result.success) {
                        showSuccessMessage(`Action ${(editId ? 'updated' : 'created')} successfully!`);
                        createModal.classList.add('hidden');
                        // Reset form and hide image preview
                        document.getElementById('actionImagePreviewContainer').classList.add('hidden');

                        // Reload data
                        await loadAllData();
                    } else {
                        showErrorMessage(`Failed to ${(editId ? 'update' : 'create')} action: ${result.message}`);
                    }
                } else {
                    // Prepare payload for JSON submission (when no image is uploaded)
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

            // Check if we have a file input in the form and if there's a file selected
            const fileInput = document.getElementById('resource-file-input');
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

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

            try {
                if (hasFile) {
                    // If there's a file, submit via FormData
                    const resourceFormData = new FormData();
                    resourceFormData.append('resource_name', resource_name);
                    resourceFormData.append('category', category);
                    resourceFormData.append('type', type);
                    resourceFormData.append('description', description);
                    resourceFormData.append('country', country);
                    resourceFormData.append('location_details', location_details);
                    resourceFormData.append('latitude', latitude);
                    resourceFormData.append('longitude', longitude);
                    resourceFormData.append('publisher_id', currentUser.id);

                    if (editId) {
                        resourceFormData.append('id', editId);
                    }

                    resourceFormData.append('image', fileInput.files[0]);

                    const url = editId ? "./../api/resources/update_resource.php" : "./../api/resources/create_resource.php";
                    const response = await fetch(url, {
                        method: "POST",
                        body: resourceFormData
                    });

                    const result = await response.json();

                    if(result.success) {
                        showSuccessMessage(`Resource ${(editId ? 'updated' : 'created')} successfully!`);
                        createModal.classList.add('hidden');
                        // Reset form and hide image preview
                        document.getElementById('resourceImagePreviewContainer').classList.add('hidden');

                        // Reload data
                        await loadAllData();
                    } else {
                        showErrorMessage(`Failed to ${(editId ? 'update' : 'create')} resource: ${result.message}`);
                    }
                } else {
                    // Prepare payload for JSON submission (when no image is uploaded)
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

         async function rejectAction(actionId) {
            if (!confirm('Are you sure you want to reject this action?')) return;

            try {
                const response = await fetch('../api/actions/reject_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: actionId,
                        action: 'reject'  // Use 'action' field instead of 'status'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showSwal('Success', 'Action rejected successfully', 'success');
                    loadUserActions(); // Reload the actions list
                } else {
                    showSwal('Error', `Failed to reject action: ${result.message}`, 'error');
                }
            } catch (error) {
                console.error('Error rejecting action:', error);
                showSwal('Error', 'Failed to reject action. Please try again.', 'error');
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

        async function rejectResource(resourceId) {
            if (!confirm('Are you sure you want to reject this resource?')) return;

            try {
                const response = await fetch('../api/resources/reject_resource.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: resourceId,
                        action: 'reject'  // Use 'action' field instead of 'status'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showSwal('Success', 'Resource rejected successfully', 'success');
                    loadUserResources(); // Reload the resources list
                } else {
                    showSwal('Error', `Failed to reject resource: ${result.message}`, 'error');
                }
            } catch (error) {
                console.error('Error rejecting resource:', error);
                showSwal('Error', 'Failed to reject resource. Please try again.', 'error');
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

        // --- 8. REMINDER MANAGEMENT FUNCTIONS ---

        // Load user reminders
        async function loadUserReminders() {
            try {
                const response = await fetch("../api/reminders/get_reminders.php");
                const result = await response.json();

                if (result.success) {
                    const reminders = result.data || [];
                    const totalRemindersCountEl = document.getElementById('totalRemindersCount');
                    if(totalRemindersCountEl) totalRemindersCountEl.textContent = reminders.length;

                    // Calculate upcoming and overdue reminders
                    const now = new Date();
                    const upcoming = reminders.filter(reminder => new Date(reminder.reminder_time) > now).length;
                    const overdue = reminders.filter(reminder => new Date(reminder.reminder_time) <= now && !reminder.sent).length;

                    const upcomingRemindersCountEl = document.getElementById('upcomingRemindersCount');
                    if(upcomingRemindersCountEl) upcomingRemindersCountEl.textContent = upcoming;

                    const pastRemindersCountEl = document.getElementById('pastRemindersCount');
                    if(pastRemindersCountEl) pastRemindersCountEl.textContent = overdue;

                    renderRemindersTable(reminders);
                } else {
                    console.error("Failed to load reminders:", result.message);
                    showErrorMessage(result.message || "Failed to load reminders");
                }
            } catch (error) {
                console.error("Error loading reminders:", error);
                showErrorMessage("Network error. Failed to load reminders.");
            }
        }

        // Render reminders in the table
        function renderRemindersTable(reminders) {
            const tableBody = document.getElementById('remindersTableBody');
            if (!tableBody) return;

            if (reminders.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-8 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <i data-lucide="bell-off" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-3"></i>
                                <p class="text-sm">No reminders set yet</p>
                                <p class="text-xs text-zinc-400 mt-1">Create reminders from the calendar view</p>
                            </div>
                        </td>
                    </tr>
                `;
                // Initialize Lucide icons
                lucide.createIcons();
                return;
            }

            tableBody.innerHTML = reminders.map(reminder => {
                const reminderTime = new Date(reminder.reminder_time);
                const statusClass = reminder.sent ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' :
                                  new Date(reminder.reminder_time) <= new Date() ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' :
                                  'bg-blue-500/10 text-blue-600 dark:text-blue-400';
                const statusText = reminder.sent ? 'Sent' :
                                 new Date(reminder.reminder_time) <= new Date() ? 'Overdue' :
                                 'Upcoming';

                return `
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/20">
                        <td class="py-3 px-5 text-sm text-zinc-700 dark:text-zinc-300">${reminder.id}</td>
                        <td class="py-3 px-5 text-sm">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">${reminder.item_title || 'Untitled'}</div>
                            <div class="text-xs text-zinc-500">${reminder.item_type || 'N/A'}</div>
                        </td>
                        <td class="py-3 px-5 text-sm text-zinc-700 dark:text-zinc-300 capitalize">${reminder.item_type || 'N/A'}</td>
                        <td class="py-3 px-5 text-sm">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">${new Date(reminder.reminder_time).toLocaleDateString()}</div>
                            <div class="text-xs text-zinc-500">${new Date(reminder.reminder_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                        </td>
                        <td class="py-3 px-5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                ${statusText}
                            </span>
                        </td>
                        <td class="py-3 px-5 text-sm text-zinc-500">${new Date(reminder.created_at).toLocaleDateString()}</td>
                        <td class="py-3 px-5">
                            <div class="flex gap-2">
                                <button onclick="editReminder(${reminder.id})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Edit</button>
                                <button onclick="deleteReminder(${reminder.id})" class="text-xs text-rose-600 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 ml-2">Delete</button>
                                <button onclick="downloadICSFromReminder(${reminder.id})" class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 ml-2 flex items-center gap-1">
                                    <i data-lucide="download" class="w-3 h-3"></i> Download
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Initialize Lucide icons
            lucide.createIcons();
        }

        // Filter reminders table
        function filterRemindersTable() {
            const searchTerm = document.getElementById('remindersSearch').value.toLowerCase();
            const statusFilter = document.getElementById('reminderStatusFilter').value;

            // For this implementation, we'll reload and filter all reminders
            loadUserReminders();
        }

        // Edit reminder function
        async function editReminder(id) {
            try {
                const response = await fetch(`../api/reminders/get_reminder.php?id=${id}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const reminder = result.data;

                    // Show a modal or form to edit the reminder
                    Swal.fire({
                        title: 'Edit Reminder',
                        html: `
                            <div class="text-left mx-auto w-full">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Event: ${reminder.item_title || 'N/A'}</label>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Type: ${reminder.item_type || 'N/A'}</label>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Current Time: ${new Date(reminder.reminder_time).toLocaleString()}</label>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">New Reminder Time</label>
                                    <input type="datetime-local" id="editReminderTime" class="swal2-input w-full mt-1" value="${reminder.reminder_time.slice(0, 16)}">
                                </div>
                            </div>
                        `,
                        focusConfirm: false,
                        preConfirm: () => {
                            const newTime = document.getElementById('editReminderTime').value;
                            if (!newTime) {
                                Swal.showValidationMessage('Please select a reminder time');
                                return false;
                            }
                            return { newTime };
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Update',
                        cancelButtonText: 'Cancel'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            // Validate that the new reminder time is in the future
                            const selectedTime = new Date(result.value.newTime);
                            const now = new Date();

                            if (selectedTime <= now) {
                                showErrorMessage('Reminder time is in the past. Please select a future time.');
                                return;
                            }

                            try {
                                const updateResponse = await fetch('../api/reminders/update_reminder.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        id: id,
                                        reminder_time: result.value.newTime
                                    })
                                });

                                const updateResult = await updateResponse.json();

                                if (updateResult.success) {
                                    showSuccessMessage(updateResult.message || 'Reminder updated successfully');
                                    loadUserReminders(); // Reload the reminders table
                                } else {
                                    showErrorMessage(updateResult.message || 'Failed to update reminder');
                                }
                            } catch (error) {
                                console.error('Error updating reminder:', error);
                                showErrorMessage('Network error. Please try again.');
                            }
                        }
                    });
                } else {
                    showErrorMessage(result.message || 'Failed to get reminder details');
                }
            } catch (error) {
                console.error('Error fetching reminder details:', error);
                showErrorMessage('Network error. Please try again.');
            }
        }

        // Delete reminder function
        async function deleteReminder(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('../api/reminders/delete_reminder.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });

                        const result = await response.json();

                        if (result.success) {
                            showSuccessMessage(result.message || 'Reminder deleted successfully');
                            loadUserReminders(); // Reload the reminders table
                        } else {
                            showErrorMessage(result.message || 'Failed to delete reminder');
                        }
                    } catch (error) {
                        console.error('Error deleting reminder:', error);
                        showErrorMessage('Network error. Please try again.');
                    }
                }
            });
        }

        // Download ICS for a specific reminder
        async function downloadICSFromReminder(reminderId) {
            try {
                // Fetch the reminder details by ID
                const response = await fetch(`../api/reminders/get_reminder.php?id=${reminderId}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const reminder = result.data;

                    // Check if the reminder has an associated action (for resources, there may not be a start_time for a calendar event)
                    if (!reminder.item_title) {
                        showSwal('Error', 'Cannot create calendar entry for this item.', 'error');
                        return;
                    }

                    // For actions, we can create a calendar event using the action's start time, not the reminder time
                    // But we need to get the full action details to do this properly
                    const fullItem = reminder.item_type === 'action' ?
                        actionsData.find(action => action.id == reminder.item_id) :
                        resourcesData.find(resource => resource.id == reminder.item_id);

                    if (!fullItem) {
                        // If the full item isn't in our cached data, we need to fetch it
                        fetch(`../api/${reminder.item_type}s/get_${reminder.item_type}.php?id=${reminder.item_id}`)
                            .then(response => response.json())
                            .then(fetchResult => {
                                if (fetchResult.success) {
                                    const item = fetchResult.data;
                                    generateAndDownloadICS(item, reminder.item_title);
                                } else {
                                    showSwal('Error', 'Failed to get item details for calendar export.', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching item details for ICS:', error);
                                showSwal('Error', 'Failed to get item details for calendar export.', 'error');
                            });
                    } else {
                        generateAndDownloadICS(fullItem, reminder.item_title);
                    }
                } else {
                    showSwal('Error', result.message || 'Failed to get reminder details.', 'error');
                }
            } catch (error) {
                console.error('Error downloading ICS for reminder:', error);
                showSwal('Error', 'Network error. Failed to download calendar file.', 'error');
            }
        }

        // Helper function to generate and download ICS file
        function generateAndDownloadICS(item, titleOverride = null) {
            try {
                // Use start_time from the action/resource rather than reminder time
                // This creates a calendar event for the actual action/resource event, not the reminder
                const startTime = new Date(item.start_time || item.created_at);
                if (isNaN(startTime.getTime())) {
                    showSwal('Error', 'Invalid date for calendar export.', 'error');
                    return;
                }

                // Calculate end time (assuming 2 hours duration)
                const endTime = new Date(startTime.getTime() + (2 * 60 * 60 * 1000));

                // Create unique UID for the event
                const uid = `${item.id}-${item.creator_id || item.publisher_id}@connectforpeace.com`;

                // Build ICS content
                const icsContent = [
                    'BEGIN:VCALENDAR',
                    'VERSION:2.0',
                    'PRODID:-//Connect for Peace//Calendar Export//EN',
                    'BEGIN:VEVENT',
                    `UID:${uid}`,
                    `DTSTAMP:${toISOStringForICS(new Date())}`,
                    `DTSTART:${toISOStringForICS(startTime)}`,
                    `DTEND:${toISOStringForICS(endTime)}`,
                    `SUMMARY:${escapeICSText(titleOverride || item.title || item.resource_name)}`,
                    `DESCRIPTION:${escapeICSText(item.description || 'No description provided')}`,
                    `LOCATION:${escapeICSText(item.location || 'Location not specified')}`,
                    'END:VEVENT',
                    'END:VCALENDAR'
                ].join('\\r\\n');

                // Create blob and download
                const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
                const url = URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.href = url;
                link.download = `${(titleOverride || item.title || item.resource_name || 'event').replace(/[^a-z0-9]/gi, '_')}.ics`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                showSwal('Success', 'Calendar file downloaded successfully!', 'success');
            } catch (error) {
                console.error('Error generating ICS:', error);
                showSwal('Error', 'Failed to generate calendar file.', 'error');
            }
        }

        // Helper function to format date for ICS
        function toISOStringForICS(date) {
            // Format date as YYYYMMDDTHHMMSSZ for ICS
            return date.getFullYear() +
                String(date.getMonth() + 1).padStart(2, '0') +
                String(date.getDate()).padStart(2, '0') + 'T' +
                String(date.getHours()).padStart(2, '0') +
                String(date.getMinutes()).padStart(2, '0') +
                String(date.getSeconds()).padStart(2, '0') + 'Z';
        }

        // Helper function to escape ICS text
        function escapeICSText(text) {
            if (!text) return '';
            return text.toString()
                .replace(/\\/g, '\\\\')
                .replace(/;/g, '\\;')
                .replace(/,/g, '\\,')
                .replace(/\n/g, '\\n');
        }

        // Add event listeners for reminders page
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for reminders filters if on reminders page
            const reminderStatusFilter = document.getElementById('reminderStatusFilter');
            const remindersSearch = document.getElementById('remindersSearch');

            if (reminderStatusFilter) {
                reminderStatusFilter.addEventListener('change', filterRemindersTable);
            }

            if (remindersSearch) {
                remindersSearch.addEventListener('input', filterRemindersTable);
            }
        });