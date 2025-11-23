// Check if jQuery is available
if (typeof jQuery === 'undefined') {
    console.error('CRITICAL: jQuery is not loaded! All AJAX calls will fail.');
    Swal.fire({
        icon: 'error',
        title: 'Critical Error',
        text: 'jQuery library failed to load. Please refresh the page.',
        confirmButtonText: 'Refresh',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.reload();
        }
    });
}

// =============================================
// ==================================================
// GLOBAL STATE
// ==================================================
let actionsData = [];
let resourcesData = [];
let filteredData = [];
let map = null;
let joinedActions = new Set();
let joinedResources = new Set();
let currentModalData = null;
let displayedItemCount = 6; // Number of items to show per page
let isUserLoggedIn = false; // Track if user is authenticated
let currentUser = null;     // Store current user data

// Function to populate country dropdowns
function populateCountryDropdowns() {
    // Check if COUNTRIES array is available
    if (typeof window.COUNTRIES === 'undefined') {
        console.error('COUNTRIES array not available');
        return;
    }

    // Populate action country dropdown
    const actionCountrySelect = document.getElementById('actionCountry');
    if (actionCountrySelect) {
        // Clear existing options except possible default option
        actionCountrySelect.innerHTML = '<option value="">Select Country</option>';

        // Add countries sorted alphabetically by name
        const sortedCountries = [...window.COUNTRIES].sort((a, b) => a.name.localeCompare(b.name));
        sortedCountries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.name;
            option.textContent = country.name;
            actionCountrySelect.appendChild(option);
        });
    }

    // Populate resource country dropdown
    const resourceCountrySelect = document.getElementById('resourceCountry');
    if (resourceCountrySelect) {
        // Clear existing options except possible default option
        resourceCountrySelect.innerHTML = '<option value="">Select Country</option>';

        // Add countries sorted alphabetically by name
        const sortedCountries = [...window.COUNTRIES].sort((a, b) => a.name.localeCompare(b.name));
        sortedCountries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.name;
            option.textContent = country.name;
            resourceCountrySelect.appendChild(option);
        });
    }
}

// ==================================================
// 1. AUTHENTICATION CHECK
// ==================================================
async function checkAuthStatus() {
    try {
        const response = await fetch("../api/users/check_auth.php");
        const result = await response.json();

        if (result.authenticated) {
            isUserLoggedIn = true;
            currentUser = result.user;

            // Update UI for authenticated user
            updateAuthUI();
        } else {
            isUserLoggedIn = false;
            currentUser = null;
        }
    } catch (error) {
        console.error("Failed to check authentication status:", error);
        isUserLoggedIn = false;
        currentUser = null;
    }
}

// Update UI elements based on authentication status
function updateAuthUI() {
    // Update profile menu
    const profileMenu = document.querySelector('.profile-menu');
    if (profileMenu && currentUser) {
        const profileLink = profileMenu.querySelector('a[href*="profile"]');
        if (profileLink) {
            profileLink.innerHTML = `
                <span class="material-symbols-outlined">account_circle</span>
                ${currentUser.name}
            `;
        }
    }

    // Update dashboard link visibility
    const dashboardLink = document.querySelector('a[href="../dashboard/index.html"]');
    if (dashboardLink) {
        dashboardLink.style.display = isUserLoggedIn ? 'block' : 'none';
    }

    // Update create button behavior
    const createActionBtn = document.querySelector('.create-action-btn');
    const createResourceBtn = document.querySelector('.create-resource-btn');

    if (createActionBtn) {
        createActionBtn.onclick = () => {
            if (isUserLoggedIn) {
                openCreateModal('action');
            } else {
                // For unauthenticated users, show login prompt
                Swal.fire({
                    icon: 'info',
                    title: 'Login Required',
                    text: 'You need to be logged in to create actions or resources. Would you like to login?',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, login',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../auth/login.html';
                    }
                });
            }
        };
    }

    if (createResourceBtn) {
        createResourceBtn.onclick = () => {
            if (isUserLoggedIn) {
                openCreateModal('resource');
            } else {
                // For unauthenticated users, show login prompt
                Swal.fire({
                    icon: 'info',
                    title: 'Login Required',
                    text: 'You need to be logged in to create actions or resources. Would you like to login?',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, login',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../auth/login.html';
                    }
                });
            }
        };
    }

    // Update explore nearby button behavior
    const exploreNearbyBtn = document.querySelector('.explore-nearby-btn');

    if (exploreNearbyBtn) {
        exploreNearbyBtn.onclick = () => {
            // Navigate to the globe exploration page
            window.location.href = 'globale_explore/index.html';
        };
    }
}

// ==================================================
// 2. LOAD ACTIONS FROM BACKEND
// ==================================================
const itemsPerPage = 6; // Number of items to show per page

async function loadActions() {
    console.log('Loading actions from API...');
    console.log('API URL:', '../api/actions/get_actions.php');
    try {
        const response = await fetch("../api/actions/get_actions.php");
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        console.log("API result:", result);
        console.log("API result:", result);

        if (result.success) {
            // Only include actions with 'approved' status for public view
            // For authenticated users, include pending actions too
            const actions = result.actions.filter(action => {
                return action.status === 'approved' ||
                       (isUserLoggedIn && (currentUser.role === 'admin' || action.creator_id === currentUser.id));
            }).map(item => ({ ...item, type: 'action' }));

            console.log(`Loaded ${actions.length} actions`);

            actionsData = actions;

            // Load resources as well
            await loadResources();

            // Combine actions and resources for filtering
            filteredData = [...actionsData, ...resourcesData];

            // Initialize displayedItemCount and render cards
            displayedItemCount = Math.min(6, filteredData.length);
            renderCards();
            updateMap();
        } else {
            console.error("API returned error:", result.message);
            throw new Error(result.message || "Unknown error from API");
        }
    } catch (error) {
        console.error("Failed to load actions:", error);
        document.getElementById('cardsGrid').innerHTML =
            `<p style="text-align:center;color:var(--color-danger);margin:2rem 0;">Failed to load actions: ${error.message}</p>`;
    }
}

// Load resources from backend
async function loadResources() {
    console.log('Loading resources from API...');
    console.log('API URL:', '../api/resources/get_resources.php');
    try {
        const response = await fetch("../api/resources/get_resources.php");
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        console.log("Resources API result:", result);

        if (result.success) {
            // Only include resources with 'approved' status for public view
            // For authenticated users, include pending resources too
            const resources = result.resources.filter(resource => {
                return resource.status === 'approved' ||
                       (isUserLoggedIn && (currentUser.role === 'admin' || resource.publisher_id === currentUser.id));
            }).map(item => ({ ...item, type: 'resource' }));

            console.log(`Loaded ${resources.length} resources`);

            resourcesData = resources;
        } else {
            console.error("Resources API returned error:", result.message);
        }
    } catch (error) {
        console.error("Failed to load resources:", error);
    }
}

async function loadAllData() {
    console.log('Starting loadAllData...');
    console.log('Fetching from:', '../api/actions/get_actions.php', '../api/resources/get_resources.php');
    try {
        // Load both actions and resources concurrently using fetch
        const [actionsResponse, resourcesResponse] = await Promise.all([
            fetch("../api/actions/get_actions.php"),
            fetch("../api/resources/get_resources.php")
        ]);

        if (!actionsResponse.ok) {
            throw new Error(`HTTP error! status: ${actionsResponse.status}`);
        }

        if (!resourcesResponse.ok) {
            throw new Error(`HTTP error! status: ${resourcesResponse.status}`);
        }

        const actionsResult = await actionsResponse.json();
        const resourcesResult = await resourcesResponse.json();

        if (!actionsResult.success) {
            throw new Error(actionsResult.message || "Unknown error from actions API");
        }

        if (!resourcesResult.success) {
            throw new Error(resourcesResult.message || "Unknown error from resources API");
        }

        // Process actions data
        const actionsWithTypes = actionsResult.actions.map(item => ({ ...item, type: 'action' }));
        console.log(`Loaded ${actionsWithTypes.length} actions`);

        // Process resources data - map resource_name to title for consistency with actions
        const resourcesWithTypes = resourcesResult.resources.map(item => ({
            ...item,
            type: 'resource',
            title: item.resource_name || item.title || 'Untitled Resource'  // Map resource_name to title
        }));
        console.log(`Loaded ${resourcesWithTypes.length} resources`);

        // Update global data
        actionsData = actionsWithTypes;
        resourcesData = resourcesWithTypes;

        // Merge actions and resources into one array for filteredData
        filteredData = [...actionsWithTypes, ...resourcesWithTypes];

        // Initialize displayedItemCount and render cards
        displayedItemCount = Math.min(itemsPerPage, filteredData.length);
        renderCards();
        updateMap();
    } catch (error) {
        console.error("Failed to load all data:", error);
        document.getElementById('cardsGrid').innerHTML =
            `<p style="text-align:center;color:var(--color-danger);margin:2rem 0;">Failed to load all data: ${error.message}</p>`;
    }
}



// =============================================
// 2. SUBMIT NEW ACTION / UPDATE ACTION
// =============================================
async function submitActionForm() {
    const form = document.querySelector("#action-tab .create-form");
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const createModalElement = document.getElementById('createModal');
    const isEditMode = createModalElement.dataset.editMode === 'true';
    const actionId = createModalElement.dataset.actionId;

    const title = document.getElementById('actionTitle').value.trim();
    const category = document.getElementById('actionCategory').value;
    const theme = document.getElementById('actionTheme').value;
    const description = document.getElementById('actionDescription').value.trim();
    const start_time = document.getElementById('actionDateTime').value;
    const country = document.getElementById('actionCountry').value;
    const locationDetails = document.getElementById('actionLocationDetails').value.trim();
    const duration = document.getElementById('actionDuration').value;
    const fileInput = document.getElementById('file-input'); // Using the correct ID as in the HTML

    // Construct location string from country and details
    const location = country && locationDetails ? `${country} - ${locationDetails}` : country || locationDetails || '';

    // Get coordinate and country hidden fields
    const latInput = document.getElementById('actionLat');
    const lngInput = document.getElementById('actionLng');
    const countryHiddenInput = document.getElementById('actionCountryHidden');

    // Check if there's a file to upload
    const hasFile = fileInput && fileInput.files.length > 0;

    if (hasFile && fileInput.files[0].size > 0) {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('title', title);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('theme', theme);
        formData.append('location', location);
        formData.append('start_time', start_time);
        formData.append('creator_id', currentUser ? currentUser.id : 1); // Add creator ID

        // Add country, latitude, and longitude to form data
        formData.append('country', country);
        if (latInput && latInput.value) {
            formData.append('latitude', latInput.value);
        }
        if (lngInput && lngInput.value) {
            formData.append('longitude', lngInput.value);
        }

        if (start_time && duration) {
            const start = new Date(start_time);
            start.setHours(start.getHours() + parseInt(duration));
            formData.append('end_time', start.toISOString().slice(0, 16).replace('T', ' '));
        }
        formData.append('image', fileInput.files[0]);

        if (isEditMode) {
            formData.append('id', actionId); // Add ID for update
        }

        try {
            if (typeof $ === 'undefined') {
                console.error('jQuery not available for AJAX call in submitActionForm');
                showSwal('Error', 'System error: jQuery not loaded', 'error');
                return;
            }
            const result = await $.ajax({
                url: isEditMode ? "../api/actions/update_action.php" : "../api/actions/create_action.php",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json"
            });

            if (result.success) {
                closeModal('createModal');
                form.reset();
                document.getElementById('num-of-files').textContent = "No Files Chosen";
                document.getElementById('files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                loadActions(); // Refresh from DB to show the new action
                showConfetti();
                showSwal('Success!', 'Action ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success');
            } else {
                showSwal('Error', 'Failed to ' + (isEditMode ? 'update' : 'create') + ' action: ' + result.message, 'error');
            }
        } catch (error) {
            console.error("File upload error:", error);
            showSwal('Error', 'File upload error. Please try again.', 'error');
        }
    } else {
        // Use JSON submission for cases without image
        const payload = {
            creator_id: currentUser ? currentUser.id : 1, // Use current user's ID
            title,
            description,
            category,
            theme,
            location,
            start_time,
            country, // Add country field
            latitude: latInput && latInput.value ? parseFloat(latInput.value) : null,
            longitude: lngInput && lngInput.value ? parseFloat(lngInput.value) : null,
            image_url: null // No image provided
        };

        // Optional: calculate end_time
        if (start_time && duration) {
            const start = new Date(start_time);
            start.setHours(start.getHours() + parseInt(duration));
            payload.end_time = start.toISOString().slice(0, 16).replace('T', ' ');
        }

        if (isEditMode) {
            payload.id = actionId; // Add ID for update
            await updateAction(payload);
        } else {
            try {
                if (typeof $ === 'undefined') {
                    console.error('jQuery not available for AJAX call in submitActionForm (JSON)');
                    showSwal('Error', 'System error: jQuery not loaded', 'error');
                    return;
                }
                const result = await $.ajax({
                    url: "../api/actions/create_action.php",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify(payload),
                    dataType: "json"
                });

                if (result.success) {
                    closeModal('createModal');
                    form.reset();
                    document.getElementById('num-of-files').textContent = "No Files Chosen";
                    document.getElementById('files-list').innerHTML = "";
                    document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                    document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                    loadActions(); // Refresh from DB to show the new action
                    showConfetti();
                    showSwal('Success!', 'Action created successfully!', 'success');
                } else {
                    showSwal('Error', 'Failed to create action: ' + result.message, 'error');
                }
            } catch (error) {
                console.error("Create error:", error);
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        }
    }
}

async function updateAction(payload) {
    if (typeof $ === 'undefined') {
        console.error('jQuery not available for AJAX call in updateAction');
        showSwal('Error', 'System error: jQuery not loaded', 'error');
        return;
    }
    try {
        const result = await $.ajax({
            url: "../api/actions/update_action.php",
            method: "POST", // Or PUT, depending on your API design
            contentType: "application/json",
            data: JSON.stringify(payload),
            dataType: "json"
        });

        if (result.success) {
            closeModal('createModal');
            document.querySelector("#action-tab .create-form").reset();
            document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
            document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
            loadActions(); // Refresh from DB to show updated action
            showSwal('Success!', 'Action updated successfully!', 'success');
        } else {
            showSwal('Error', 'Failed to update action: ' + result.message, 'error');
        }
    } catch (error) {
        console.error("Update error:", error);
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}

async function deleteAction(id) {
    if (typeof $ === 'undefined') {
        console.error('jQuery not available for AJAX call in deleteAction');
        showSwal('Error', 'System error: jQuery not loaded', 'error');
        return;
    }
    try {
        const result = await $.ajax({
            url: "../api/actions/delete_action.php",
            method: "POST", // Or DELETE, depending on your API design
            contentType: "application/json",
            data: JSON.stringify({ id: id }),
            dataType: "json"
        });

        if (result.success) {
            closeModal('detailsModal'); // Close details modal after deletion
            loadActions(); // Refresh from DB
            showSwal('Success!', 'Action deleted successfully!', 'success');
        } else {
            showSwal('Error', 'Failed to delete action: ' + result.message, 'error');
        }
    } catch (error) {
        console.error("Delete error:", error);
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}

// ===================================================================
// 2. SUBMIT NEW RESOURCE / UPDATE RESOURCE / DELETE RESOURCE
// ===================================================================

async function submitResourceForm() {
    const form = document.querySelector("#resource-tab .create-form");
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const createModalElement = document.getElementById('createModal');
    const isEditMode = createModalElement.dataset.editMode === 'true';
    const resourceId = createModalElement.dataset.resourceId;

    const resourceName = document.getElementById('resourceName').value.trim();
    const type = document.querySelector('#resource-tab input[name="type"]:checked').value;
    const category = document.getElementById('resourceCategory').value;
    const description = document.getElementById('resourceDescription').value.trim();
    const country = document.getElementById('resourceCountry').value;
    const locationDetails = document.getElementById('resourceLocationDetails').value.trim();
    const fileInput = document.getElementById('file-input'); // Using the correct ID as in the HTML

    // Construct location string from country and details
    const location = country && locationDetails ? `${country} - ${locationDetails}` : country || locationDetails || '';

    // Get coordinate and country hidden fields
    const latInput = document.getElementById('resourceLat');
    const lngInput = document.getElementById('resourceLng');
    const countryHiddenInput = document.getElementById('resourceCountryHidden');

    // Check if there's a file to upload
    const hasFile = fileInput && fileInput.files.length > 0;

    if (hasFile && fileInput.files[0].size > 0) {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('resource_name', resourceName);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('type', type);
        formData.append('location', location);
        formData.append('country', country); // Add country field
        if (latInput && latInput.value) {
            formData.append('latitude', latInput.value);
        }
        if (lngInput && lngInput.value) {
            formData.append('longitude', lngInput.value);
        }
        formData.append('image', fileInput.files[0]);

        if (isEditMode) {
            formData.append('id', resourceId); // Add ID for update
        }

        try {
            if (typeof $ === 'undefined') {
                console.error('jQuery not available for AJAX call in submitResourceForm');
                showSwal('Error', 'System error: jQuery not loaded', 'error');
                return;
            }
            const result = await $.ajax({
                url: isEditMode ? "../api/resources/update_resource.php" : "../api/resources/create_resource.php",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json"
            });

            if (result.success) {
                closeModal('createModal');
                form.reset();
                document.getElementById('num-of-files').textContent = "No Files Chosen";
                document.getElementById('files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
                loadAllData(); // Refresh from DB to show the new resource
                showConfetti();
                showSwal('Success!', 'Resource ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success');
            } else {
                showSwal('Error', 'Failed to ' + (isEditMode ? 'update' : 'create') + ' resource: ' + result.message, 'error');
            }
        } catch (error) {
            console.error("File upload error:", error);
            showSwal('Error', 'File upload error. Please try again.', 'error');
        }
    } else {
        // Use JSON submission for cases without image
        const payload = {
            publisher_id: currentUser ? currentUser.id : 1, // Use current user's ID
            resource_name: resourceName,
            description: description,
            category: category,
            type: type,
            location: location,
            country: country, // Add country field
            latitude: latInput && latInput.value ? parseFloat(latInput.value) : null,
            longitude: lngInput && lngInput.value ? parseFloat(lngInput.value) : null,
            image_url: null // No image provided
        };

        if (isEditMode) {
            payload.id = resourceId; // Add ID for update
            await updateResource(payload);
        } else {
            try {
                if (typeof $ === 'undefined') {
                    console.error('jQuery not available for AJAX call in submitResourceForm (JSON)');
                    showSwal('Error', 'System error: jQuery not loaded', 'error');
                    return;
                }
                const result = await $.ajax({
                    url: "../api/resources/create_resource.php",
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify(payload),
                    dataType: "json"
                });

                if (result.success) {
                    closeModal('createModal');
                    form.reset();
                    document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                    document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
                    loadAllData(); // Refresh from DB to show the new resource
                    showConfetti();
                    showSwal('Success!', 'Resource created successfully!', 'success');
                } else {
                    showSwal('Error', 'Failed to create resource: ' + result.message, 'error');
                }
            } catch (error) {
                console.error("Create error:", error);
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        }
    }
}

async function updateResource(payload) {
    try {
        const result = await $.ajax({
            url: "../api/resources/update_resource.php",
            method: "POST", // Or PUT, depending on your API design
            contentType: "application/json",
            data: JSON.stringify(payload),
            dataType: "json"
        });

        if (result.success) {
            closeModal('createModal');
            document.querySelector("#resource-tab .create-form").reset();
            document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
            document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
            loadAllData(); // Refresh from DB to show updated resource
            showSwal('Success!', 'Resource updated successfully!', 'success');
        } else {
            showSwal('Error', 'Failed to update resource: ' + result.message, 'error');
        }
    } catch (error) {
        console.error("Update error:", error);
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}

async function deleteResource(id) {
    try {
        const result = await $.ajax({
            url: "../api/resources/delete_resource.php",
            method: "POST", // Or DELETE, depending on your API design
            contentType: "application/json",
            data: JSON.stringify({ id: id }),
            dataType: "json"
        });

        if (result.success) {
            closeModal('detailsModal'); // Close details modal after deletion
            loadAllData(); // Refresh from DB
            showSwal('Success!', 'Resource deleted successfully!', 'success');
        } else {
            showSwal('Error', 'Failed to delete resource: ' + result.message, 'error');
        }
    } catch (error) {
        console.error("Delete error:", error);
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}

// =============================================
// 3. RENDER CARDS
// =============================================
function renderCards() {
    const grid = document.getElementById('cardsGrid');
    grid.innerHTML = '';

    const itemsToRender = filteredData.slice(0, displayedItemCount);
    itemsToRender.forEach(item => grid.appendChild(createCard(item)));

    toggleSeeMoreLessButtons(); // Call the new function to manage buttons
}

// =============================================
// 4. CREATE CARD HTML
// =============================================
function createCard(item) {
    const card = document.createElement('div');
    card.className = 'card';
    card.onclick = () => openDetailsModal(item);

    const statusClass = `status-${item.status || 'active'}`;
    const badge = item.type === 'action' ? 'Action' : 'Resource';

    // Use reasonable defaults for resources that may not have all action fields
    const participantsCount = item.participants || 0;
    const status = item.status || (item.type === 'resource' ? 'available' : 'active');
    const tags = item.tags || [];

    card.innerHTML = `
        <div class="card-image">
            <img src="${item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlOiB7e3sgYmFkZ2UgfX19PC90ZXh0Pjwvc3ZnPg=='}" alt="${item.title}">
            <span class="card-badge">${badge}</span>
        </div>
        <div class="card-content">
            <div class="card-creator">
                <img src="${item.creator?.avatar || 'https://api.placeholder.com/40/40?text=User'}" alt="${item.creator?.name || 'User'}" class="small-avatar">
                <div class="creator-info">
                    <p class="creator-name">${item.creator?.name || 'Anonymous User'}</p>
                    <p class="creator-badge">${item.creator?.badge || 'Community Member'}</p>
                </div>
            </div>
            <h3 class="card-title">${item.title}</h3>
            <p class="card-description">${item.description.substring(0, 80)}${item.description.length > 80 ? '...' : ''}</p>
            <div class="card-tags">
                ${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
            </div>
            <div class="card-footer">
                <div class="card-stats">
                    <span>${item.type === 'action' ? 'Joined ' : ''}${participantsCount} ${item.type === 'action' ? 'people' : (item.type === 'resource' ? 'responses' : 'participants')}</span>
                </div>
                <span class="card-status ${statusClass}">${status}</span>
            </div>
        </div>
    `;
    return card;
}

// =============================================
// 4. PAGINATION CONTROLS (New functions)
// =============================================
function showMore() {
    displayedItemCount = Math.min(displayedItemCount + itemsPerPage, filteredData.length);
    renderCards();
}

function showLess() {
    displayedItemCount = Math.max(itemsPerPage, displayedItemCount - itemsPerPage);
    renderCards();
}

function toggleSeeMoreLessButtons() {
    let seeMoreContainer = document.getElementById('seeMoreContainer');
    if (!seeMoreContainer) {
        seeMoreContainer = document.createElement('div');
        seeMoreContainer.id = 'seeMoreContainer';
        seeMoreContainer.style.textAlign = 'center';
        seeMoreContainer.style.margin = '20px 0';
        document.querySelector('.grid-section .container').appendChild(seeMoreContainer);
    }
    seeMoreContainer.innerHTML = ''; // Clear previous buttons

    // Show "See More" button if there are more items to display
    if (displayedItemCount < filteredData.length) {
        const seeMoreBtn = document.createElement('button');
        seeMoreBtn.id = 'seeMoreBtn';
        seeMoreBtn.className = 'btn btn-primary';
        seeMoreBtn.style.padding = '10px 20px';
        seeMoreBtn.textContent = 'See More';
        seeMoreBtn.addEventListener('click', showMore);
        seeMoreContainer.appendChild(seeMoreBtn);
    }

    // Show "See Less" button if more than initial items are displayed
    if (displayedItemCount > itemsPerPage) {
        const seeLessBtn = document.createElement('button');
        seeLessBtn.id = 'seeLessBtn';
        seeLessBtn.className = 'btn btn-outline';
        seeLessBtn.style.padding = '10px 20px';
        seeLessBtn.style.marginLeft = '10px';
        seeLessBtn.textContent = 'See Less';
        seeLessBtn.addEventListener('click', showLess);
        seeMoreContainer.appendChild(seeLessBtn);
    }

    // If all items are displayed and there are more than initial items, show "See Less" only
    if (displayedItemCount >= filteredData.length && filteredData.length > itemsPerPage) {
        // Ensure only "See Less" is shown if all are displayed and it's not the initial load
        if (seeMoreContainer.querySelector('#seeMoreBtn')) {
            seeMoreContainer.querySelector('#seeMoreBtn').remove();
        }
    }
}

// =============================================
// 5. DETAILS MODAL
// =============================================
function openDetailsModal(item) {
    currentModalData = item;
    document.getElementById('modalBadge').textContent = item.type === 'action' ? 'Action' : 'Resource';
    document.getElementById('modalTitle').textContent = item.title || item.resource_name || 'Untitled';
    document.getElementById('modalImage').src = item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    document.getElementById('modalCreatorName').textContent = item.creator?.name || 'Unknown Creator';
    document.getElementById('modalCreatorBadge').textContent = item.creator?.badge || 'User';
    document.getElementById('modalCreatorAvatar').src = item.creator?.avatar || 'https://api.placeholder.com/40/40?text=?';
    document.getElementById('modalDescription').textContent = item.description;
    document.getElementById('modalLocation').textContent = item.location || 'Location not specified';
    document.getElementById('modalDate').textContent = item.date || 'Date not specified';
    document.getElementById('modalDuration').textContent = item.duration || (item.type === 'resource' ? 'N/A' : 'Not specified');
    document.getElementById('modalParticipants').textContent = `${item.participants || 0} ${item.participants === 1 ? 'person' : 'people'}`;

    // Tags
    const tagsContainer = document.getElementById('modalTags');
    tagsContainer.innerHTML = item.tags ? item.tags.map(tag => `<span class="tag">${tag}</span>`).join('') : '';

    // Join button
    const actionButton = document.getElementById('actionButton');
    if (item.type === 'action') {
        if (joinedActions.has(item.id)) {
            actionButton.textContent = 'Leave Action';
            actionButton.classList.remove('btn-primary');
            actionButton.classList.add('btn-secondary');
        } else {
            actionButton.textContent = 'Join Action';
            actionButton.classList.remove('btn-secondary');
            actionButton.classList.add('btn-primary');
        }
        actionButton.style.display = 'block'; // Show button for actions
    } else {
        actionButton.style.display = 'none'; // Hide button for resources
    }

    // Only show Edit and Delete buttons for authenticated users in the dashboard (not in public view)
    // For public view, hide edit/delete buttons
    const modalActions = document.getElementById('modalActions');
    modalActions.innerHTML = ''; // Clear previous buttons

    // Note: Edit/Delete operations should only be available in the dashboard interface
    // In the public view (vue/index.html), we only show join/respond buttons
    // The edit/delete functionality belongs in the dashboard

    openModal('detailsModal');

    // Load comments and participants after modal is open
    setTimeout(async () => {
        if (item.type === 'action') {
            await loadComments(item.id, 'action');
            await loadParticipants(item.id);
        } else if (item.type === 'resource') {
            await loadComments(item.id, 'resource');
        }
    }, 100); // Small delay to ensure modal is fully loaded
}

// Function to open the edit modal and populate it with action data
function openEditModal(action) {
    // Set a flag or data attribute to indicate edit mode
    const createModalElement = document.getElementById('createModal');
    createModalElement.dataset.editMode = 'true';
    createModalElement.dataset.actionId = action.id;
    createModalElement.dataset.resourceId = ''; // Clear resource ID

    // Switch to the action tab
    switchTab('action-tab');

    // Populate the form fields with existing action data
    document.getElementById('actionTitle').value = action.title;
    document.getElementById('actionCategory').value = action.category;
    document.getElementById('actionTheme').value = action.theme;
    document.getElementById('actionDescription').value = action.description;
    document.getElementById('actionDateTime').value = action.start_time ? action.start_time.slice(0, 16) : '';
    document.getElementById('actionLocation').value = action.location;
    document.getElementById('actionDuration').value = action.duration || '';

    openModal('createModal');
}

// Function to open the edit modal and populate it with resource data
function openEditResourceModal(resource) {
    // Set a flag or data attribute to indicate edit mode
    const createModalElement = document.getElementById('createModal');
    createModalElement.dataset.editMode = 'true';
    createModalElement.dataset.actionId = ''; // Clear action ID
    createModalElement.dataset.resourceId = resource.id;

    // Switch to the resource tab
    switchTab('resource-tab');

    // Populate the form fields with existing resource data
    document.querySelector('#resource-tab input[placeholder="e.g., Books, Furniture..."]').value = resource.title || resource.resource_name;
    if (resource.type) {
        const typeRadio = document.querySelector(`input[name="type"][value="${resource.type}"]`);
        if (typeRadio) {
            typeRadio.checked = true;
        }
    }
    document.querySelector('#resource-tab select').value = resource.category;
    document.querySelector('#resource-tab textarea[placeholder="Describe the resource..."]').value = resource.description;
    document.querySelector('#resource-tab input[placeholder="Enter location..."]').value = resource.location;

    openModal('createModal');
}

// Function to confirm deletion
function confirmDeleteAction(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteAction(id);
        }
    });
}

// Function to confirm deletion for resources
function confirmDeleteResource(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteResource(id);
        }
    });
}

// =============================================
// 6. JOIN / LEAVE
// =============================================
function toggleJoin() {
    if (!currentModalData || currentModalData.type !== 'action') return;

    toggleJoinAction(currentModalData.id);
}

// =============================================
// 7. CREATE MODAL TABS
// =============================================
function openCreateModal(type) {
    const createModalElement = document.getElementById('createModal');
    createModalElement.dataset.editMode = 'false'; // Reset to create mode
    createModalElement.dataset.actionId = ''; // Clear any previous action ID

    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

    if (type === 'action') {
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
        document.getElementById('action-tab').classList.add('active');
    } else {
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
        document.getElementById('resource-tab').classList.add('active');
    }
    openModal('createModal');
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
}

// =============================================
// 8. MODAL UTILS
// =============================================
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = 'auto';
}

// =============================================
// 9. FILTERS
// =============================================
function filterCards() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const type = document.getElementById('typeFilter').value;
    const theme = document.getElementById('themeFilter').value;
    const status = document.getElementById('statusFilter').value;

    // Filter from the combined data array
    filteredData = [...actionsData, ...resourcesData].filter(item => {
        // Search filter - checks title, description, tags, and location
        const matchesSearch = item.title.toLowerCase().includes(search) ||
            item.description.toLowerCase().includes(search) ||
            (item.tags && item.tags.some(t => t.toLowerCase().includes(search))) ||
            (item.location && item.location.toLowerCase().includes(search));

        // Type filter
        const matchesType = !type || item.type === type;

        // Theme filter - only applies to actions since resource categories don't match theme options
        // Theme filter options are environment, education, social, culture which match action categories
        // Resource categories are Books, Furniture, etc. which don't correspond to theme filter options
        const matchesTheme = !theme ||
            (item.type === 'action' && item.category && item.category.toLowerCase().includes(theme)) ||
            (item.type === 'resource'); // Allow all resources regardless of theme filter

        // Status filter - handle both action and resource statuses
        const matchesStatus = !status || item.status === status;

        return matchesSearch && matchesType && matchesTheme && matchesStatus;
    });

    renderCards();
    updateMap();
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('themeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filteredData = [...actionsData, ...resourcesData];
    renderCards();
    updateMap();
}

// =============================================
// 10. MAP
// =============================================
function initMap() {
    // Check if map.js functions are available and if URL params exist
    if (window.getURLParameters && window.initMapWithParams) {
        const params = window.getURLParameters();
        if (params) {
            // Use enhanced map initialization from map.js
            window.initMapWithParams();
            // Set local map variable to match the global window.map
            map = window.map;
            return;
        }
    }
    // Otherwise, use basic map initialization
    const L = window.L;
    if (!L) {
        console.error('Leaflet library not loaded');
        return;
    }
    map = L.map('map').setView([48.8566, 2.3522], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
}

function updateMap() {
    if (!map) return;
    map.eachLayer(layer => {
        if (layer instanceof L.CircleMarker) map.removeLayer(layer);
    });

    const colors = { action: '#C3E6CB', resource: '#AEE1F9' };

    // Use filteredData if it exists and is not empty; otherwise fallback to all data
    const displayData = filteredData && filteredData.length > 0 ? filteredData : [...actionsData, ...resourcesData];

    // Display both actions and resources on the map
    displayData.forEach(item => {
        if (!item.latitude || !item.longitude) return;

        const marker = L.circleMarker([item.latitude, item.longitude], {
            radius: 8,
            fillColor: colors[item.type],
            color: '#333',
            weight: 2,
            fillOpacity: 0.8
        }).addTo(map);

        marker.bindPopup(`
            <strong>${item.title}</strong><br>
            ${item.location}<br>
            <button onclick="openDetailsModal(${JSON.stringify(item).replace(/"/g, '&quot;')})" style="margin-top:8px;padding:4px 8px;">
                View Details
            </button>
        `);
    });
}

function findAction(id) {
    return [...actionsData, ...resourcesData].find(a => a.id === id);
}

// =============================================
// 11. FILE UPLOAD PREVIEW
// =============================================
const fileInput = document.getElementById("file-input");
const fileList = document.getElementById("files-list");
const numOfFiles = document.getElementById("num-of-files");

fileInput.addEventListener("change", () => {
    fileList.innerHTML = "";
    numOfFiles.textContent = `${fileInput.files.length} File${fileInput.files.length > 1 ? 's' : ''} Selected`;

    for (const file of fileInput.files) {
        const li = document.createElement("li");
        const sizeKB = (file.size / 1024).toFixed(1);
        const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
        li.innerHTML = `<p>${file.name}</p><p>${sizeKB >= 1024 ? sizeMB + ' MB' : sizeKB + ' KB'}</p>`;
        fileList.appendChild(li);
    }
});

// =============================================
// 12. EVENT LISTENERS
// =============================================
function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', filterCards);
    document.getElementById('typeFilter').addEventListener('change', filterCards);
    document.getElementById('themeFilter').addEventListener('change', filterCards);
    document.getElementById('statusFilter').addEventListener('change', filterCards);

    // Navbar scroll
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('navbar');
        navbar.classList.toggle('scrolled', window.scrollY > 100);
    });

    // Profile dropdown
    document.querySelector('.profile-avatar').addEventListener('click', e => {
        e.stopPropagation();
        document.querySelector('.dropdown').classList.toggle('active');
    });
    document.addEventListener('click', () => {
        document.querySelector('.dropdown').classList.remove('active');
    });

    // ESC to close modals
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            document.body.style.overflow = 'auto';
        }
    });

}

// =============================================
// 13. RATING & FEEDBACK
// =============================================
function setRating(stars) {
    document.querySelectorAll('#ratingStars .star').forEach((star, i) => {
        star.style.opacity = i < stars ? '1' : '0.5';
        star.style.transform = i < stars ? 'scale(1.1)' : 'scale(1)';
    });
}

function submitFeedback() {
    // Submit feedback as a comment using the new submitComment function
    if (!currentModalData) {
        showSwal('Error', 'No item selected.', 'error');
        return;
    }

    if (currentModalData.type === 'action') {
        submitComment(currentModalData.id, null); // action ID, no resource ID
    } else {
        submitComment(null, currentModalData.id); // no action ID, resource ID
    }
}

// =============================================
// 14. SHOW SWEET ALERT
// =============================================
function showSwal(title, text, icon, position = 'top-end') {
    // Temporarily ensure the SweetAlert appears above all modals
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
        position: position,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        toast: true,
        background: '#ffffff',
        color: '#333333',
        width: '350px',
        padding: '1rem',
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            content: 'swal2-content-custom',
            closeButton: 'swal2-close-button-custom'
        },
        // Ensure it's above other modals
        willOpen: (popup) => {
            popup.style.zIndex = '999999';
        }
    });
}

// =============================================
// 15. CONFETTI
// =============================================
function showConfetti() {
    const colors = ['#AEE1F9', '#C3E6CB', '#FFB6A0'];
    for (let i = 0; i < 30; i++) {
        const div = document.createElement('div');
        div.style.position = 'fixed';
        div.style.left = Math.random() * window.innerWidth + 'px';
        div.style.top = '-10px';
        div.style.width = '10px';
        div.style.height = '10px';
        div.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        div.style.borderRadius = '50%';
        div.style.pointerEvents = 'none';
        div.style.zIndex = '9999';
        document.body.appendChild(div);

        let y = 0, x = Math.random() - 0.5, vx = (Math.random() - 0.5) * 2, vy = Math.random() * 3 + 2;
        const animate = () => {
            y += vy; x += vx; vy += 0.1;
            div.style.transform = `translate(${x}px, ${y}px)`;
            div.style.opacity = 1 - y / 500;
            if (y < 500) requestAnimationFrame(animate);
            else div.remove();
        };
        animate();
    }
}

// =============================================
// 16. INIT
// =============================================
document.addEventListener('DOMContentLoaded', async () => {
    console.log('DOM Content Loaded - Initializing application...');
    console.log('jQuery version:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'NOT LOADED');
    console.log('Leaflet available:', typeof L !== 'undefined');

    await checkAuthStatus(); // Check authentication status first
    loadAllData();

    if (document.getElementById('map')) {
        console.log('Map container found, initializing map...');
        initMap();
    } else {
        console.warn('Map container not found, skipping map initialization');
    }

    setupEventListeners();

    // Add event listeners for form validation
    document.querySelector('#action-tab .btn-large').addEventListener('click', validateActionForm);
    document.querySelector('#resource-tab .btn-large').addEventListener('click', validateResourceForm);

    // Populate country dropdowns after page loads
    populateCountryDropdowns();
});

// =============================================
// 17. FORM VALIDATION
// =============================================
function validateActionForm(e) {
    e.preventDefault();

    // Get form fields
    const title = document.getElementById('actionTitle');
    const category = document.getElementById('actionCategory');
    const description = document.getElementById('actionDescription');
    const start_time = document.getElementById('actionDateTime');
    const location = document.getElementById('actionLocation');

    let isValid = true;

    // Clear previous error styles
    clearErrorStyles();

    // Validate title
    if (!title.value.trim()) {
        title.style.border = '2px solid red';
        isValid = false;
    }

    // Validate category
    if (!category.value) {
        category.style.border = '2px solid red';
        isValid = false;
    }

    // Validate description
    if (!description.value.trim()) {
        description.style.border = '2px solid red';
        isValid = false;
    }

    // Validate start time
    if (!start_time.value) {
        start_time.style.border = '2px solid red';
        isValid = false;
    }

    // Validate location
    if (!location.value.trim()) {
        location.style.border = '2px solid red';
        isValid = false;
    }

    if (!isValid) {
        showSwal('Validation Error', 'Please fill in all required fields.', 'error');
        return false;
    }

    // Submit the action form using the main submission function
    submitActionForm();
}

function validateResourceForm(e) {
    e.preventDefault();

    // Get resource form fields
    const resourceName = document.querySelector('#resource-tab input[placeholder="e.g., Books, Furniture..."]');
    const resourceType = document.querySelector('#resource-tab input[name="type"]:checked');
    const resourceCategory = document.querySelector('#resource-tab select');
    const resourceDescription = document.querySelector('#resource-tab textarea[placeholder="Describe the resource..."]');
    const resourceLocation = document.querySelector('#resource-tab input[placeholder="Enter location..."]');

    let isValid = true;

    // Clear previous error styles
    clearErrorStyles();

    // Validate resource name
    if (!resourceName.value.trim()) {
        resourceName.style.border = '2px solid red';
        isValid = false;
    }

    // Validate resource type
    if (!resourceType) {
        const radioGroup = document.querySelector('.radio-group');
        radioGroup.style.border = '2px solid red';
        isValid = false;
    }

    // Validate resource category
    if (!resourceCategory.value) {
        resourceCategory.style.border = '2px solid red';
        isValid = false;
    }

    // Validate resource description
    if (!resourceDescription.value.trim()) {
        resourceDescription.style.border = '2px solid red';
        isValid = false;
    }

    // Validate resource location
    if (!resourceLocation.value.trim()) {
        resourceLocation.style.border = '2px solid red';
        isValid = false;
    }

    if (!isValid) {
        showSwal('Validation Error', 'Please fill in all required fields.', 'error');
        return false;
    }

    // Submit the resource form
    submitResourceForm();
}

function clearErrorStyles() {
    // Remove red borders from all inputs
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.style.border = '';
    });

    // Remove red border from radio group if present
    const radioGroups = document.querySelectorAll('.radio-group');
    radioGroups.forEach(group => {
        group.style.border = '';
    });
}


// Function to submit resource form
async function submitResourceForm() {
    const form = document.querySelector("#resource-tab .create-form");
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const createModalElement = document.getElementById('createModal');
    const isEditMode = createModalElement.dataset.editMode === 'true';
    const resourceId = createModalElement.dataset.resourceId;

    const resourceName = document.querySelector('#resource-tab input[placeholder="e.g., Books, Furniture..."]').value.trim();
    const resourceType = document.querySelector('#resource-tab input[name="type"]:checked');
    const resourceCategory = document.querySelector('#resource-tab select').value;
    const resourceDescription = document.querySelector('#resource-tab textarea[placeholder="Describe the resource..."]').value.trim();
    const resourceLocation = document.querySelector('#resource-tab input[placeholder="Enter location..."]').value.trim();

    // Validate resource type
    if (!resourceType) {
        showSwal('Validation Error', 'Please select a resource type (Offer or Request).', 'error');
        return;
    }

    const payload = {
        publisher_id: 1, // TODO: Replace with logged-in user ID
        resource_name: resourceName,
        description: resourceDescription,
        category: resourceCategory,
        type: resourceType.value,
        location: resourceLocation
    };

    if (isEditMode) {
        payload.id = resourceId; // Add ID for update
        await updateResource(payload);
    } else {
        try {
            if (typeof $ === 'undefined') {
                console.error('jQuery not available for AJAX call in submitResourceForm');
                showSwal('Error', 'System error: jQuery not loaded', 'error');
                return;
            }
            const result = await $.ajax({
                url: "../api/resources/create_resource.php",
                method: "POST",
                contentType: "application/json",
                data: JSON.stringify(payload),
                dataType: "json"
            });

            if (result.success) {
                closeModal('createModal');
                form.reset();
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
                loadAllData(); // Refresh from DB to show the new resource
                showConfetti();
                showSwal('Success!', 'Resource created successfully!', 'success');
            } else {
                showSwal('Error', 'Failed to create resource: ' + result.message, 'error');
            }
        } catch (error) {
            console.error("Create error:", error);
            showSwal('Error', 'Network error. Please try again.', 'error');
        }
    }
}

// Add comment functionality
async function submitComment(actionId, resourceId = null) {
    // Look for the comment textarea in the details modal using multiple possible selectors
    const commentText = document.querySelector('#detailsModal textarea') ||
                       document.querySelector('#detailsModal input[type="text"]') ||
                       document.querySelector('#commentInput') ||  // Most likely ID for comment input
                       document.querySelector('#comment-text') ||
                       document.querySelector('#commentContent') ||
                       document.querySelector('.comment-textarea') ||
                       document.querySelector('.feedback-textarea');

    if (!commentText) {
        console.error("Comment textarea not found!");
        showSwal('Error', 'Comment input field not found', 'error');
        return;
    }

    if (!commentText.value.trim()) {
        showSwal('Error', 'Please enter a comment', 'error');
        return;
    }

    const commentContent = commentText.value.trim();

    try {
        // Use current user's ID if available, otherwise default to 1 for testing
        const userId = currentUser ? currentUser.id : 1;
        console.log("Submitting comment:", { user_id: userId, content: commentContent, action_id: actionId, resource_id: resourceId });

        if (typeof $ === 'undefined') {
            console.error('jQuery not available for AJAX call in submitComment');
            showSwal('Error', 'System error: jQuery not loaded', 'error');
            return;
        }
        const result = await $.ajax({
            url: "../api/comments/add_comment.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                user_id: userId,
                content: commentContent,
                action_id: actionId,
                resource_id: resourceId
            }),
            dataType: "json"
        });
        console.log("API response:", result);

        if (result.success) {
            commentText.value = ''; // Clear the comment text area
            showSwal('Success', 'Comment added successfully!', 'success');
            // Reload the comments for the current item after a small delay
            setTimeout(async () => {
                if (currentModalData && currentModalData.type === 'action') {
                    await loadComments(currentModalData.id, 'action');
                } else if (currentModalData && currentModalData.type === 'resource') {
                    await loadComments(currentModalData.id, 'resource');
                }
            }, 500);
        } else {
            showSwal('Error', 'Failed to add comment: ' + result.message, 'error');
        }
    } catch (error) {
        console.error("Add comment error:", error);
        console.error("Error details:", { message: error.message, stack: error.stack });
        showSwal('Error', 'Network error. Please try again. Details: ' + error.message, 'error');
    }
}

// Load comments for an action or resource
async function loadComments(entityId, entityType) {
    try {
        console.log(`Loading comments for ${entityType} ID: ${entityId}`);
        if (typeof $ === 'undefined') {
            console.error('jQuery not available for AJAX call in loadComments');
            return;
        }
        const result = await $.ajax({
            url: `../api/comments/get_comments.php?${entityType}_id=${entityId}`,
            method: "GET",
            dataType: "json"
        });
        console.log("Comments API result:", result);

        if (result.success) {
            // Look for the comments container in the details modal using multiple possible selectors
            const commentsList = document.getElementById('commentsList') ||
                                document.querySelector('#detailsModal #commentsList') ||
                                document.querySelector('#detailsModal .comments-container') ||
                                document.querySelector('#detailsModal .comments-list') ||
                                document.querySelector('.comments-container') ||
                                document.querySelector('#comments-container');

            if (commentsList) {
                console.log(`Found comments container, rendering ${result.comments.length} comments`);
                renderComments(result.comments, commentsList);
            } else {
                console.error("Comments container element not found!");
            }
        } else {
            console.error("Failed to load comments:", result.message);
        }
    } catch (error) {
        console.error("Load comments error:", error);
    }
}

// Render comments in the UI
function renderComments(comments, container) {
    if (!container) return;

    // Clear existing comments
    container.innerHTML = '';

    if (comments.length === 0) {
        container.innerHTML = '<p class="no-comments">No comments yet. Be the first to comment!</p>';
        return;
    }

    comments.forEach(comment => {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment';
        commentElement.innerHTML = `
            <div class="comment-header">
                <img src="${comment.user_avatar}" alt="${comment.user_name}" class="comment-avatar">
                <div class="comment-author-info">
                    <div class="comment-author">${comment.user_name}</div>
                    <div class="comment-date">${comment.created_at_formatted}</div>
                </div>
            </div>
            <div class="comment-text">${comment.content}</div>
        `;
        container.appendChild(commentElement);
    });
}

// Join or leave an action
async function toggleJoinAction(actionId) {
    try {
        if (typeof $ === 'undefined') {
            console.error('jQuery not available for AJAX call in toggleJoinAction');
            showSwal('Error', 'System error: jQuery not loaded', 'error');
            return;
        }
        const result = await $.ajax({
            url: "../api/actions/join_action.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                action_id: actionId,
                user_id: currentUser ? currentUser.id : 1  // Use current user ID or default
            }),
            dataType: "json"
        });

        if (result.success) {
            // Update the global state for joined actions
            if (result.joined) {
                joinedActions.add(actionId);
            } else {
                joinedActions.delete(actionId);
            }

            // Update the button text and style
            const button = document.getElementById('actionButton');
            if (button) {
                if (result.joined) {
                    button.textContent = 'Leave Action';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                } else {
                    button.textContent = 'Join Action';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                }
            }

            showSwal('Success', result.message, 'success');

            // Reload the action data to update participant count
            if (currentModalData) {
                // Update the modal to reflect the new participant count
                openDetailsModal(currentModalData);
            }
        } else {
            showSwal('Error', result.message, 'error');
        }
    } catch (error) {
        console.error("Join action error:", error);
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}

// Load participants for an action
async function loadParticipants(actionId) {
    try {
        if (typeof $ === 'undefined') {
            console.error('jQuery not available for AJAX call in loadParticipants');
            return;
        }
        const result = await $.ajax({
            url: `../api/actions/get_participants.php?action_id=${actionId}`,
            method: "GET",
            dataType: "json"
        });

        if (result.success) {
            const participantsList = document.getElementById('participantsList');
            if (participantsList) {
                renderParticipants(result.participants, participantsList);

                // Update the participants count in the details section
                const participantsCount = document.getElementById('modalParticipants');
                if (participantsCount) {
                    participantsCount.textContent = `${result.count} ${result.count === 1 ? 'person' : 'people'}`;
                }
            }
        } else {
            console.error("Failed to load participants:", result.message);
        }
    } catch (error) {
        console.error("Load participants error:", error);
    }
}

// Render participants in the UI
function renderParticipants(participants, container) {
    if (!container) return;

    // Clear existing participants
    container.innerHTML = '';

    if (participants.length === 0) {
        container.innerHTML = '<p class="no-participants">No participants yet. Be the first to join!</p>';
        return;
    }

    participants.forEach(participant => {
        const participantElement = document.createElement('div');
        participantElement.className = 'participant';
        participantElement.innerHTML = `
            <img src="${participant.avatar_url}" alt="${participant.name}" class="participant-avatar">
            <div class="participant-info">
                <div class="participant-name">${participant.name}</div>
                <div class="participant-badge">${participant.badge}</div>
            </div>
        `;
        container.appendChild(participantElement);
    });
}

// Add input event listeners to remove error styling when user types
document.addEventListener('input', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        if (e.target.value.trim() !== '') {
            e.target.style.border = '';
        }
    }
});

// Add change event listener for radio buttons
document.addEventListener('change', function(e) {
    if (e.target.name === 'type') {
        document.querySelector('.radio-group').style.border = '';
    }
});

// =============================================
// 18. LOCATION PICKER MODAL FUNCTIONALITY
// =============================================

// Initialize variables for location picker
let locationPickerMap = null;
let selectedMarker = null;
let selectedCoordinates = null;

// Add event listeners for "Pick Location" buttons
document.addEventListener('DOMContentLoaded', function() {
    // Set up location picker event listeners after DOM is loaded
    setTimeout(setupLocationPickerEventListeners, 1000); // Delay to ensure DOM is fully loaded
});

function setupLocationPickerEventListeners() {
    // Add event listeners to all "Pick on Map" buttons
    const pickLocationButtons = document.querySelectorAll('.pick-location-btn');
    pickLocationButtons.forEach(button => {
        button.addEventListener('click', function() {
            const formType = this.getAttribute('data-form');
            openLocationPicker(formType);
        });
    });

    // Add event listeners for location picker modal buttons
    document.getElementById('closeLocationPickerBtn').addEventListener('click', closeLocationPicker);
    document.getElementById('cancelLocationPicker').addEventListener('click', closeLocationPicker);
    document.getElementById('confirmLocationBtn').addEventListener('click', function() {
        const formType = this.getAttribute('data-form-type');
        if (formType) {
            confirmLocation(formType);
        }
    });
}

function openLocationPicker(formType) {
    // Show the location picker modal
    openModal('locationPickerModal');

    // Store form type in the confirm button for later use
    document.getElementById('confirmLocationBtn').setAttribute('data-form-type', formType);

    // Initialize the location picker map
    initLocationPickerMap(formType);
}

function initLocationPickerMap(formType) {
    // Create or get the map container
    const mapContainer = document.getElementById('locationPickerMap');

    // Initialize Leaflet map
    locationPickerMap = L.map('locationPickerMap').setView([20, 0], 2); // Default to world view

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(locationPickerMap);

    // Try to get user's location for better initial view
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                locationPickerMap.setView([lat, lng], 13);
            },
            function(error) {
                console.log("Could not get user's location, using default view");
                // Use default view if geolocation fails
                locationPickerMap.setView([20, 0], 2);
            }
        );
    }

    // Add click event to map for location selection
    locationPickerMap.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Remove existing marker if present
        if (selectedMarker) {
            locationPickerMap.removeLayer(selectedMarker);
        }

        // Add new marker at clicked location
        selectedMarker = L.marker([lat, lng]).addTo(locationPickerMap);
        selectedCoordinates = { lat, lng };

        // Update UI with selected coordinates
        document.getElementById('selectedCoordinates').textContent = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;

        // Enable confirm button
        document.getElementById('confirmLocationBtn').disabled = false;

        // Perform reverse geocoding to get address
        reverseGeocode(lat, lng);
    });

    // If coordinates are already set in the form, show them on the map
    let existingLat, existingLng;
    if (formType === 'action') {
        existingLat = document.getElementById('actionLat').value;
        existingLng = document.getElementById('actionLng').value;
    } else {
        existingLat = document.getElementById('resourceLat').value;
        existingLng = document.getElementById('resourceLng').value;
    }

    if (existingLat && existingLng) {
        // Show existing location on map
        const lat = parseFloat(existingLat);
        const lng = parseFloat(existingLng);

        if (!isNaN(lat) && !isNaN(lng)) {
            locationPickerMap.setView([lat, lng], 13);

            // Add marker for existing location
            selectedMarker = L.marker([lat, lng]).addTo(locationPickerMap);
            selectedCoordinates = { lat, lng };

            // Update UI with existing coordinates
            document.getElementById('selectedCoordinates').textContent = `Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}`;

            // Enable confirm button
            document.getElementById('confirmLocationBtn').disabled = false;

            // Perform reverse geocoding for existing location
            reverseGeocode(lat, lng);
        }
    }
}

function reverseGeocode(lat, lng) {
    // Use Nominatim API for reverse geocoding
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            let address = 'Address not found';
            if (data.display_name) {
                address = data.display_name;
            } else if (data.address) {
                const addr = data.address;
                address = [
                    addr.road || addr.pedestrian || '',
                    addr.city || addr.town || addr.village || '',
                    addr.country || ''
                ].filter(part => part !== '').join(', ');
            }

            document.getElementById('selectedAddress').textContent = address;
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
            document.getElementById('selectedAddress').textContent = 'Unable to retrieve address';
        });
}

function confirmLocation(formType) {
    if (!selectedCoordinates) {
        showSwal('Error', 'Please select a location on the map first.', 'error');
        return;
    }

    // Update the appropriate form fields based on form type
    if (formType === 'action') {
        document.getElementById('actionLat').value = selectedCoordinates.lat;
        document.getElementById('actionLng').value = selectedCoordinates.lng;

        // Update location details field if it's empty
        const locationDetailsField = document.getElementById('actionLocationDetails');
        if (!locationDetailsField.value.trim()) {
            locationDetailsField.value = document.getElementById('selectedAddress').textContent;
        }

        // Mark the pick location button as selected
        const pickBtn = document.querySelector('.pick-location-btn[data-form="action"]');
        if (pickBtn) {
            pickBtn.classList.add('selected');
        }
    } else {
        document.getElementById('resourceLat').value = selectedCoordinates.lat;
        document.getElementById('resourceLng').value = selectedCoordinates.lng;

        // Update location details field if it's empty
        const locationDetailsField = document.getElementById('resourceLocationDetails');
        if (!locationDetailsField.value.trim()) {
            locationDetailsField.value = document.getElementById('selectedAddress').textContent;
        }

        // Mark the pick location button as selected
        const pickBtn = document.querySelector('.pick-location-btn[data-form="resource"]');
        if (pickBtn) {
            pickBtn.classList.add('selected');
        }
    }

    // Close the location picker modal
    closeLocationPicker();

    // Show success message
    showSwal('Location Selected', 'Coordinates have been added to the form.', 'success');
}

function closeLocationPicker() {
    // Close the modal
    closeModal('locationPickerModal');

    // Clean up the map if it exists
    if (locationPickerMap) {
        locationPickerMap.remove();
        locationPickerMap = null;
    }

    // Reset selected marker and coordinates
    selectedMarker = null;
    selectedCoordinates = null;

    // Reset UI elements
    document.getElementById('selectedCoordinates').textContent = 'Click on map to select';
    document.getElementById('selectedAddress').textContent = '-';
    document.getElementById('confirmLocationBtn').disabled = true;
    document.getElementById('confirmLocationBtn').removeAttribute('data-form-type');
}

// =============================================
// 19. ENHANCED FORM VALIDATION
// =============================================

// Enhanced action form validation
function validateActionForm(e) {
    e.preventDefault();

    // Get form fields
    const title = document.getElementById('actionTitle');
    const category = document.getElementById('actionCategory');
    const description = document.getElementById('actionDescription');
    const duration = document.getElementById('actionDuration');
    const locationDetails = document.getElementById('actionLocationDetails');
    const country = document.getElementById('actionCountry');

    let isValid = true;
    const errors = [];

    // Clear previous error styles
    clearErrorStyles();

    // Validate title
    if (!title.value.trim()) {
        title.classList.add('error-border');
        errors.push('Title is required');
        isValid = false;
    }

    // Validate category
    if (!category.value) {
        category.classList.add('error-border');
        errors.push('Category is required');
        isValid = false;
    }

    // Validate description
    if (!description.value.trim()) {
        description.classList.add('error-border');
        errors.push('Description is required');
        isValid = false;
    }

    // Validate duration if provided (should be positive number)
    if (duration.value && (isNaN(duration.value) || parseInt(duration.value) <= 0)) {
        duration.classList.add('error-border');
        errors.push('Duration must be a positive number');
        isValid = false;
    }

    // Validate location details
    if (!locationDetails.value.trim()) {
        locationDetails.classList.add('error-border');
        errors.push('Location details are required');
        isValid = false;
    }

    // Validate country
    if (!country.value) {
        country.classList.add('error-border');
        errors.push('Country is required');
        isValid = false;
    }

    if (!isValid) {
        // Show error message with all issues
        showSwal('Validation Error', errors.join('<br>'), 'error');

        // Add shake animation to form to draw attention
        const form = document.querySelector('#action-tab .create-form');
        form.classList.add('shake');
        setTimeout(() => {
            form.classList.remove('shake');
        }, 500);

        return false;
    }

    // Check coordinates validation after basic validation passes
    const coordValidation = validateCoordinates('action');
    if (!coordValidation.valid) {
        showSwal('Location Validation', coordValidation.message, 'warning');
        return false;
    }

    // Submit the action form using the main submission function
    submitActionForm();
}

// Enhanced resource form validation
function validateResourceForm(e) {
    e.preventDefault();

    // Get resource form fields
    const resourceName = document.getElementById('resourceName');
    const resourceType = document.querySelector('#resource-tab input[name="type"]:checked');
    const resourceCategory = document.getElementById('resourceCategory');
    const resourceDescription = document.getElementById('resourceDescription'); // Fixed to use proper ID
    const resourceLocationDetails = document.getElementById('resourceLocationDetails');
    const resourceCountry = document.getElementById('resourceCountry');

    let isValid = true;
    const errors = [];

    // Clear previous error styles
    clearErrorStyles();

    // Validate resource name
    if (!resourceName.value.trim()) {
        resourceName.classList.add('error-border');
        errors.push('Resource name is required');
        isValid = false;
    }

    // Validate resource type
    if (!resourceType) {
        const radioGroup = document.querySelector('.radio-group');
        radioGroup.classList.add('error-border');
        errors.push('Resource type (Offer/Request/Knowledge) is required');
        isValid = false;
    }

    // Validate resource category
    if (!resourceCategory.value) {
        resourceCategory.classList.add('error-border');
        errors.push('Category is required');
        isValid = false;
    }

    // Validate resource description
    if (!resourceDescription.value.trim()) {
        resourceDescription.classList.add('error-border');
        errors.push('Description is required');
        isValid = false;
    }

    // Validate resource location details
    if (!resourceLocationDetails.value.trim()) {
        resourceLocationDetails.classList.add('error-border');
        errors.push('Location details are required');
        isValid = false;
    }

    // Validate resource country
    if (!resourceCountry.value) {
        resourceCountry.classList.add('error-border');
        errors.push('Country is required');
        isValid = false;
    }

    if (!isValid) {
        // Show error message with all issues
        showSwal('Validation Error', errors.join('<br>'), 'error');

        // Add shake animation to form to draw attention
        const form = document.querySelector('#resource-tab .create-form');
        form.classList.add('shake');
        setTimeout(() => {
            form.classList.remove('shake');
        }, 500);

        return false;
    }

    // Check coordinates validation after basic validation passes
    const coordValidation = validateCoordinates('resource');
    if (!coordValidation.valid) {
        showSwal('Location Validation', coordValidation.message, 'warning');
        return false;
    }

    // Submit the resource form
    submitResourceForm();
}

// Improved validation that checks coordinates when location is provided
function validateCoordinates(formType) {
    let hasLocation = false;
    let hasCoordinates = false;

    if (formType === 'action') {
        const locationDetails = document.getElementById('actionLocationDetails').value.trim();
        const lat = document.getElementById('actionLat').value.trim();
        const lng = document.getElementById('actionLng').value.trim();

        hasLocation = locationDetails !== '';
        hasCoordinates = lat !== '' && lng !== '';
    } else {
        const locationDetails = document.getElementById('resourceLocationDetails').value.trim();
        const lat = document.getElementById('resourceLat').value.trim();
        const lng = document.getElementById('resourceLng').value.trim();

        hasLocation = locationDetails !== '';
        hasCoordinates = lat !== '' && lng !== '';
    }

    // If location is provided but coordinates are missing, prompt user to use map picker
    if (hasLocation && !hasCoordinates) {
        return {
            valid: false,
            message: "Please use 'Pick on Map' to select exact location coordinates for better accuracy."
        };
    }

    return {
        valid: true,
        message: "Coordinates are valid."
    };
}