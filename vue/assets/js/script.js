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
let currentCalendarView = 'calendar'; // Track current calendar view ('calendar' or 'agenda')

// Vue Create Modal Tabs
const createModal = document.getElementById('createModal');
const vueTabBtns = createModal?.querySelectorAll('.tab-btn');
const vueTabContents = createModal?.querySelectorAll('.tab-content');

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

        // Initialize calendar after data is loaded
        initializeCalendarAfterDataLoad();
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
    // Clear previous errors
    clearFieldError('actionTitle');
    clearFieldError('actionCategory');
    clearFieldError('actionTheme');
    clearFieldError('actionDescription');
    clearFieldError('actionDateTime');
    clearFieldError('actionCountry');
    clearFieldError('actionLocationDetails');

    // Validate required fields using the custom validation helper
    const isTitleValid = validateFormField('actionTitle', 'Title is required');
    const isCategoryValid = validateFormField('actionCategory', 'Category is required');
    const isThemeValid = validateFormField('actionTheme', 'Theme is required');
    const isDescriptionValid = validateFormField('actionDescription', 'Description is required');
    const isDateTimeValid = validateFormField('actionDateTime', 'Date & Time is required');
    const isCountryValid = validateFormField('actionCountry', 'Country is required');
    const isLocationDetailsValid = validateFormField('actionLocationDetails', 'Location details are required');

    if (!isTitleValid || !isCategoryValid || !isThemeValid || !isDescriptionValid ||
        !isDateTimeValid || !isCountryValid || !isLocationDetailsValid) {
        return;  // Validation failed, do not proceed
    }

    // Validate coordinates for the action
    const coordValidation = validateCoordinates('action');
    if (!coordValidation.valid) {
        showSwal('Location Issue', coordValidation.message, 'warning');
        // Decide to proceed or not - in this case we'll show the warning but allow submission
        // If you want to block submission, uncomment the following line:
        // return;
    } else {
        // Verify location matches country
        const lat = parseFloat(document.getElementById('actionLat').value);
        const lng = parseFloat(document.getElementById('actionLng').value);
        const country = document.getElementById('actionCountry').value;
        if (lat && lng && country) {
            verifyLocationCountryMatch(lat, lng, country);
        }
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
    const fileInput = document.getElementById('action-file-input'); // Using the correct ID as in the HTML

    // Construct location string from country and details
    const location = country && locationDetails ? `${country} - ${locationDetails}` : country || locationDetails || '';

    // Get coordinate and country hidden fields
    const latInput = document.getElementById('actionLat');
    const lngInput = document.getElementById('actionLng');
    const countryHiddenInput = document.getElementById('actionCountryHidden');

    // Check if there's a file to upload
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

    if (hasFile) {
        console.log('File selected:', fileInput.files[0].name, 'Size:', fileInput.files[0].size);
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

            if (result && result.success) {
                closeModal('createModal');
                const form = document.querySelector("#action-tab .create-form");
                form.reset();
                document.getElementById('action-num-of-files').textContent = "No Files Chosen";
                document.getElementById('action-files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                loadActions(); // Refresh from DB to show the new action
                showConfetti();
                showSwal('Success!', 'Action ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success', 'top-end');
            } else {
                const errorMessage = result && result.message ? result.message : 'Unknown error occurred';
                showSwal('Error', 'Failed to ' + (isEditMode ? 'update' : 'create') + ' action: ' + errorMessage, 'error', 'top-end');
            }
        } catch (error) {
            console.error("File upload error:", error);

            // Check if this is actually a successful request with a non-JSON response
            if ((error.status === 200 || error.status === undefined) &&
                (error.statusText === 'OK' || error.statusText === undefined)) {
                // This may be a successful creation with a non-JSON response
                // Close modal, reset form, and show success
                closeModal('createModal');
                const form = document.querySelector("#action-tab .create-form");
                form.reset();
                document.getElementById('action-num-of-files').textContent = "No Files Chosen";
                document.getElementById('action-files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                loadActions(); // Refresh from DB to show the new action
                showConfetti();
                showSwal('Success!', 'Action ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success', 'top-end');
            } else if (error.responseJSON && error.responseJSON.message) {
                // Server returned error in JSON format
                showSwal('Error', `File upload failed: ${error.responseJSON.message}`, 'error');
            } else if (error.responseText) {
                // Try to parse error response
                try {
                    const response = JSON.parse(error.responseText);
                    if (response.message) {
                        showSwal('Error', `File upload failed: ${response.message}`, 'error');
                    } else {
                        showSwal('Error', 'File upload failed. Please try again.', 'error');
                    }
                } catch (parseError) {
                    // Just because JSON parsing failed doesn't mean we should assume success.
                    // If the response is not valid JSON, it's an error
                    showSwal('Error', 'File upload failed. Please try again.', 'error');
                }
            } else if (error.status && error.status !== 200) {
                // Actual HTTP error (4xx, 5xx)
                showSwal('Error', `File upload failed: ${error.status} ${error.statusText}`, 'error');
            } else {
                // Generic error with no specific details
                showSwal('Error', 'File upload failed. Please try again.', 'error');
            }
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
                    const form = document.querySelector("#action-tab .create-form");
                    form.reset();
                    document.getElementById('action-num-of-files').textContent = "No Files Chosen";
                    document.getElementById('action-files-list').innerHTML = "";
                    document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                    document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                    loadActions(); // Refresh from DB to show the new action
                    showConfetti();
                    showSwal('Success!', 'Action created successfully!', 'success');
                } else {
                    showSwal('Error', 'Failed to create action: ' + result.message, 'error', 'top-end');
                }
            } catch (error) {
                console.error("Create error:", error);

                // First, check if there's an error.responseJSON with proper error details
                if (error.responseJSON && error.responseJSON.message) {
                    // Server returned error in JSON format
                    showSwal('Error', `Creation failed: ${error.responseJSON.message}`, 'error');
                } else if (error.responseText) {
                    // Try to parse error response
                    try {
                        const response = JSON.parse(error.responseText);
                        if (response.message) {
                            showSwal('Error', `Creation failed: ${response.message}`, 'error');
                        } else {
                            showSwal('Error', 'Network error. Please try again.', 'error');
                        }
                    } catch (parseError) {
                        // Just because JSON parsing failed doesn't mean we should assume success.
                        // If the response is not valid JSON, it's an error
                        showSwal('Error', 'Network error. Please try again.', 'error');
                    }
                } else if (error.status && error.status !== 200) {
                    // Actual HTTP error (4xx, 5xx)
                    showSwal('Error', `Creation failed: ${error.status} ${error.statusText}`, 'error');
                } else {
                    // Generic error with no specific details
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
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
            showSwal('Success!', 'Action updated successfully!', 'success', 'top-end');
        } else {
            showSwal('Error', 'Failed to update action: ' + result.message, 'error', 'top-end');
        }
    } catch (error) {
        console.error("Update error:", error);
        // Check if this is an HTTP error or a parsing error
        if (error.status !== 200 && error.statusText) {
            // Actual HTTP error
            showSwal('Error', `Update failed: ${error.status} ${error.statusText}`, 'error');
        } else if (error.responseJSON && error.responseJSON.message) {
            // Server returned error in JSON format
            showSwal('Error', `Update failed: ${error.responseJSON.message}`, 'error');
        } else if (error.responseText) {
            // There's a response body, parse it to see if it's valid JSON
            try {
                const response = JSON.parse(error.responseText);
                if (response.message) {
                    showSwal('Error', `Update failed: ${response.message}`, 'error');
                } else {
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
            } catch (e) {
                // Response is not JSON, show generic error
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        } else {
            // Generic error
            showSwal('Error', 'Network error. Please try again.', 'error');
        }
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
            showSwal('Success!', 'Action deleted successfully!', 'success', 'top-end');
        } else {
            showSwal('Error', 'Failed to delete action: ' + result.message, 'error', 'top-end');
        }
    } catch (error) {
        console.error("Delete error:", error);
        // Check if this is an HTTP error or a parsing error
        if (error.status !== 200 && error.statusText) {
            // Actual HTTP error
            showSwal('Error', `Delete failed: ${error.status} ${error.statusText}`, 'error');
        } else if (error.responseJSON && error.responseJSON.message) {
            // Server returned error in JSON format
            showSwal('Error', `Delete failed: ${error.responseJSON.message}`, 'error');
        } else if (error.responseText) {
            // There's a response body, parse it to see if it's valid JSON
            try {
                const response = JSON.parse(error.responseText);
                if (response.message) {
                    showSwal('Error', `Delete failed: ${response.message}`, 'error');
                } else {
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
            } catch (e) {
                // Response is not JSON, show generic error
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        } else {
            // Generic error
            showSwal('Error', 'Network error. Please try again.', 'error');
        }
    }
}

// ===================================================================
// 2. SUBMIT NEW RESOURCE / UPDATE RESOURCE / DELETE RESOURCE
// ===================================================================

async function submitResourceForm() {
    // Clear previous errors
    clearFieldError('resourceName');
    clearFieldError('resourceCategory');
    clearFieldError('resourceDescription');
    clearFieldError('resourceCountry');
    clearFieldError('resourceLocationDetails');

    // Validate required fields using the custom validation helper
    const isNameValid = validateFormField('resourceName', 'Resource name is required');
    const isCategoryValid = validateFormField('resourceCategory', 'Category is required');
    const isDescriptionValid = validateFormField('resourceDescription', 'Description is required');
    const isCountryValid = validateFormField('resourceCountry', 'Country is required');
    const isLocationDetailsValid = validateFormField('resourceLocationDetails', 'Location details are required');

    // Validate resource type (radio buttons)
    const resourceType = document.querySelector('#resource-tab input[name="type"]:checked');
    let isTypeValid = true;
    if (!resourceType) {
        isTypeValid = false;
        addFieldError('resourceType', 'Resource type (Offer/Request) is required');
    } else {
        clearFieldError('resourceType');
    }

    if (!isNameValid || !isCategoryValid || !isDescriptionValid ||
        !isCountryValid || !isLocationDetailsValid || !isTypeValid) {
        return;  // Validation failed, do not proceed
    }

    // Validate coordinates for the resource
    const coordValidation = validateCoordinates('resource');
    if (!coordValidation.valid) {
        showSwal('Location Issue', coordValidation.message, 'warning');
        // Decide to proceed or not - in this case we'll show the warning but allow submission
        // If you want to block submission, uncomment the following line:
        // return;
    } else {
        // Verify location matches country
        const lat = parseFloat(document.getElementById('resourceLat').value);
        const lng = parseFloat(document.getElementById('resourceLng').value);
        const country = document.getElementById('resourceCountry').value;
        if (lat && lng && country) {
            verifyLocationCountryMatch(lat, lng, country);
        }
    }

    const createModalElement = document.getElementById('createModal');
    const isEditMode = createModalElement.dataset.editMode === 'true';
    const resourceId = createModalElement.dataset.resourceId;

    const resourceName = document.getElementById('resourceName').value.trim();
    const typeRadio = document.querySelector('#resource-tab input[name="type"]:checked');
    const type = typeRadio ? typeRadio.value : '';
    const category = document.getElementById('resourceCategory').value;
    const description = document.getElementById('resourceDescription').value.trim();
    const country = document.getElementById('resourceCountry').value;
    const locationDetails = document.getElementById('resourceLocationDetails').value.trim();
    const fileInput = document.getElementById('resource-file-input'); // Using the correct ID as in the HTML

    // Construct location string from country and details
    const location = country && locationDetails ? `${country} - ${locationDetails}` : country || locationDetails || '';

    // Get coordinate and country hidden fields
    const latInput = document.getElementById('resourceLat');
    const lngInput = document.getElementById('resourceLng');
    const countryHiddenInput = document.getElementById('resourceCountryHidden');

    // Check if there's a file to upload
    const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

    if (hasFile) {
        console.log('File selected:', fileInput.files[0].name, 'Size:', fileInput.files[0].size);
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
                const form = document.querySelector("#resource-tab .create-form");
                form.reset();
                document.getElementById('resource-num-of-files').textContent = "No Files Chosen";
                document.getElementById('resource-files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
                loadAllData(); // Refresh from DB to show the new resource
                showConfetti();
                showSwal('Success!', 'Resource ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success', 'top-end');
            } else {
                showSwal('Error', 'Failed to ' + (isEditMode ? 'update' : 'create') + ' resource: ' + result.message, 'error', 'top-end');
            }
        } catch (error) {
            console.error("File upload error:", error);

            // Check if this is actually a successful request with a non-JSON response
            if ((error.status === 200 || error.status === undefined) &&
                (error.statusText === 'OK' || error.statusText === undefined)) {
                // This may be a successful creation with a non-JSON response
                // Close modal, reset form, and show success
                closeModal('createModal');
                const form = document.querySelector("#action-tab .create-form");
                form.reset();
                document.getElementById('action-num-of-files').textContent = "No Files Chosen";
                document.getElementById('action-files-list').innerHTML = "";
                document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                document.getElementById('createModal').dataset.actionId = ''; // Clear action ID
                loadActions(); // Refresh from DB to show the new action
                showConfetti();
                showSwal('Success!', 'Action ' + (isEditMode ? 'updated' : 'created') + ' successfully!', 'success', 'top-end');
            } else if (error.responseJSON && error.responseJSON.message) {
                // Server returned error in JSON format
                showSwal('Error', `File upload failed: ${error.responseJSON.message}`, 'error');
            } else if (error.responseText) {
                // Try to parse error response
                try {
                    const response = JSON.parse(error.responseText);
                    if (response.message) {
                        showSwal('Error', `File upload failed: ${response.message}`, 'error');
                    } else {
                        showSwal('Error', 'File upload failed. Please try again.', 'error');
                    }
                } catch (parseError) {
                    // Just because JSON parsing failed doesn't mean we should assume success.
                    // If the response is not valid JSON, it's an error
                    showSwal('Error', 'File upload failed. Please try again.', 'error');
                }
            } else if (error.status && error.status !== 200) {
                // Actual HTTP error (4xx, 5xx)
                showSwal('Error', `File upload failed: ${error.status} ${error.statusText}`, 'error');
            } else {
                // Generic error with no specific details
                showSwal('Error', 'File upload failed. Please try again.', 'error');
            }
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
                    const form = document.querySelector("#resource-tab .create-form");
                    form.reset();
                    document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
                    document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
                    loadAllData(); // Refresh from DB to show the new resource
                    showConfetti();
                    showSwal('Success!', 'Resource created successfully!', 'success', 'top-end');
                } else {
                    showSwal('Error', 'Failed to create resource: ' + result.message, 'error', 'top-end');
                }
            } catch (error) {
                console.error("Create error:", error);

                // First, check if there's an error.responseJSON with proper error details
                if (error.responseJSON && error.responseJSON.message) {
                    // Server returned error in JSON format
                    showSwal('Error', `Creation failed: ${error.responseJSON.message}`, 'error');
                } else if (error.responseText) {
                    // Try to parse error response
                    try {
                        const response = JSON.parse(error.responseText);
                        if (response.message) {
                            showSwal('Error', `Creation failed: ${response.message}`, 'error');
                        } else {
                            showSwal('Error', 'Network error. Please try again.', 'error');
                        }
                    } catch (parseError) {
                        // Just because JSON parsing failed doesn't mean we should assume success.
                        // If the response is not valid JSON, it's an error
                        showSwal('Error', 'Network error. Please try again.', 'error');
                    }
                } else if (error.status && error.status !== 200) {
                    // Actual HTTP error (4xx, 5xx)
                    showSwal('Error', `Creation failed: ${error.status} ${error.statusText}`, 'error');
                } else {
                    // Generic error with no specific details
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
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
            showSwal('Success!', 'Resource updated successfully!', 'success', 'top-end');
        } else {
            showSwal('Error', 'Failed to update resource: ' + result.message, 'error', 'top-end');
        }
    } catch (error) {
        console.error("Update error:", error);

        // Check if this is actually a successful request with a non-JSON response
        if ((error.status === 200 || error.status === undefined) &&
            (error.statusText === 'OK' || error.statusText === undefined)) {
            // This may be a successful update with a non-JSON response
            // Close modal, reset form, and show success
            closeModal('createModal');
            document.querySelector("#resource-tab .create-form").reset();
            document.getElementById('createModal').dataset.editMode = 'false'; // Reset edit mode
            document.getElementById('createModal').dataset.resourceId = ''; // Clear resource ID
            loadAllData(); // Refresh from DB to show updated resource
            showSwal('Success!', 'Resource updated successfully!', 'success', 'top-end');
        } else if (error.status !== 200 && error.statusText) {
            // Actual HTTP error (4xx, 5xx)
            showSwal('Error', `Update failed: ${error.status} ${error.statusText}`, 'error');
        } else if (error.responseJSON && error.responseJSON.message) {
            // Server returned error in JSON format
            showSwal('Error', `Update failed: ${error.responseJSON.message}`, 'error');
        } else if (error.responseText) {
            // There's a response body, parse it to see if it's valid JSON
            try {
                const response = JSON.parse(error.responseText);
                if (response.message) {
                    showSwal('Error', `Update failed: ${response.message}`, 'error');
                } else {
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
            } catch (e) {
                // Response is not JSON, show generic error
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        } else {
            // Generic error
            showSwal('Error', 'Network error. Please try again.', 'error');
        }
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
            showSwal('Success!', 'Resource deleted successfully!', 'success', 'top-end');
        } else {
            showSwal('Error', 'Failed to delete resource: ' + result.message, 'error', 'top-end');
        }
    } catch (error) {
        console.error("Delete error:", error);

        // Check if this is actually a successful request with a non-JSON response
        if ((error.status === 200 || error.status === undefined) &&
            (error.statusText === 'OK' || error.statusText === undefined)) {
            // This may be a successful deletion with a non-JSON response
            // Close modal and refresh data
            closeModal('detailsModal'); // Close details modal after deletion
            loadAllData(); // Refresh from DB
            showSwal('Success!', 'Resource deleted successfully!', 'success', 'top-end');
        } else if (error.status !== 200 && error.statusText) {
            // Actual HTTP error (4xx, 5xx)
            showSwal('Error', `Delete failed: ${error.status} ${error.statusText}`, 'error');
        } else if (error.responseJSON && error.responseJSON.message) {
            // Server returned error in JSON format
            showSwal('Error', `Delete failed: ${error.responseJSON.message}`, 'error');
        } else if (error.responseText) {
            // There's a response body, parse it to see if it's valid JSON
            try {
                const response = JSON.parse(error.responseText);
                if (response.message) {
                    showSwal('Error', `Delete failed: ${response.message}`, 'error');
                } else {
                    showSwal('Error', 'Network error. Please try again.', 'error');
                }
            } catch (e) {
                // Response is not JSON, show generic error
                showSwal('Error', 'Network error. Please try again.', 'error');
            }
        } else {
            // Generic error
            showSwal('Error', 'Network error. Please try again.', 'error');
        }
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

    // Initialize Lucide icons for the newly added cards
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    toggleSeeMoreLessButtons(); // Call the new function to manage buttons
}

// =============================================
// 4. CREATE CARD HTML
// =============================================
function createCard(item) {
    const card = document.createElement('div');
    // Use Tailwind classes matching template styling
    card.className = 'group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-xl hover:-translate-y-1 cursor-pointer';
    card.onclick = () => openDetailsModal(item);

    const statusClass = item.status || 'active';
    const badge = item.type === 'action' ? 'Action' : 'Resource';
    const badgeColor = item.type === 'action' ? 'bg-accent-green/20 text-green-800' : 'bg-primary/20 text-blue-800'; // Using primary instead of accent-blue

    // Use reasonable defaults for resources that may not have all action fields
    const participantsCount = item.participants || 0;
    const status = item.status || (item.type === 'resource' ? 'Available' : 'Active');
    const tags = item.tags || [];

    card.innerHTML = `
        <div class="relative h-48 w-full overflow-hidden">
            <img src="${item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlOiB7e3sgYmFkZ2UgfX19PC90ZXh0Pjwvc3ZnPg=='}" alt="${item.title}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlOiB7e3sgYmFkZ2UgfX19PC90ZXh0Pjwvc3ZnPg=='">
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-60"></div>
            <span class="absolute top-3 left-3 rounded-full ${badgeColor} px-3 py-1 text-xs font-semibold backdrop-blur-sm">${badge}</span>
        </div>
        <div class="flex flex-1 flex-col p-5">
            <div class="mb-3 flex items-center gap-2">
                <img src="${item.creator?.avatar || 'https://api.placeholder.com/40/40?text=User'}" alt="${item.creator?.name || 'User'}" class="h-6 w-6 rounded-full border border-zinc-100">
                <span class="text-xs font-medium text-zinc-500">${item.creator?.name || 'Anonymous User'}</span>
            </div>
            <h3 class="mb-2 text-lg font-semibold tracking-tight text-zinc-900 line-clamp-1">${item.title}</h3>
            <p class="mb-4 text-sm text-zinc-500 line-clamp-2 flex-1">${item.description.substring(0, 80)}${item.description.length > 80 ? '...' : ''}</p>

            <div class="flex flex-wrap gap-1 mb-3">
                ${tags.map(tag => `<span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600">${tag}</span>`).join('')}
            </div>

            <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-4">
                <div class="flex items-center gap-1 text-xs font-medium text-zinc-400">
                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                    <span>${participantsCount} ${item.type === 'action' ? 'Joined' : (item.type === 'resource' ? 'Responses' : 'Participants')}</span>
                </div>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 capitalize">${status}</span>
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
    const modalImage = document.getElementById('modalImage');
    modalImage.src = item.image || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    modalImage.onerror = function() {
        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
    };
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

    // Show/hide Add to Calendar button based on whether it's an action with a start_time


    // Only show Edit and Delete buttons for authenticated users in the dashboard (not in public view)
    // For public view, hide edit/delete buttons
    const modalActions = document.getElementById('modalActions');
    modalActions.innerHTML = ''; // Clear previous buttons

    // Note: Edit/Delete operations should only be available in the dashboard interface
    // In the public view (vue/index.html), we only show join/respond buttons
    // The edit/delete functionality belongs in the dashboard

    // Show/hide Create Reminder button based on whether it's an action with a start_time
    const createReminderBtn = document.getElementById('createReminderBtn');
    if (item.type === 'action' && item.start_time) {
        createReminderBtn.style.display = 'inline-flex'; // Show button for actions with start time
    } else {
        createReminderBtn.style.display = 'none'; // Hide button for resources or actions without start time
    }

    // Set currentModalData for use in other functions like reporting and calendar
    currentModalData = {
        id: item.id,
        type: item.type,
        title: item.title || item.resource_name,
        description: item.description,
        location: item.location,
        start_time: item.start_time,
        creator_id: item.creator?.id
    };

    openModal('detailsModal');

    // Load comments and participants after modal is open
    setTimeout(async () => {
        if (item.type === 'action') {
            await loadComments(item.id, 'action');
            await loadParticipants(item.id);

            // Update join button state based on whether user has joined
            const actionButton = document.getElementById('actionButton');
            if (actionButton && joinedActions) {
                if (joinedActions.has(item.id)) {
                    actionButton.textContent = 'Leave Action';
                    actionButton.classList.remove('btn-primary');
                    actionButton.classList.add('btn-secondary');
                } else {
                    actionButton.textContent = 'Join Action';
                    actionButton.classList.remove('btn-secondary');
                    actionButton.classList.add('btn-primary');
                }
            }
        } else if (item.type === 'resource') {
            await loadComments(item.id, 'resource');
        }

        // Populate comment section with current user info if logged in
        if (currentUser && document.getElementById('commentUserAvatar') && document.getElementById('commentUserName')) {
            document.getElementById('commentUserAvatar').src = currentUser.avatar_url || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + currentUser.name;
            document.getElementById('commentUserName').textContent = currentUser.name;
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
    if (!createModal) return;

    // Hide all tabs: remove active, add hidden
    vueTabContents.forEach(tab => {
        tab.classList.remove('active');
        tab.classList.add('hidden');
    });

    // Reset all buttons
    vueTabBtns.forEach(btn => {
        btn.classList.remove('active', 'border-zinc-900', 'text-zinc-900');
        btn.classList.add('border-transparent', 'text-zinc-500');
    });

    // Activate selected tab
    const selectedTab = createModal.querySelector(`#${tabId}`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
        selectedTab.classList.add('active');
    }

    // Activate corresponding button (assumes first=action, second=resource)
    const actionBtn = vueTabBtns[0];
    const resourceBtn = vueTabBtns[1];
    if (tabId === 'action-tab' && actionBtn) {
        actionBtn.classList.add('active', 'border-zinc-900', 'text-zinc-900');
        actionBtn.classList.remove('border-transparent', 'text-zinc-500');
    } else if (tabId === 'resource-tab' && resourceBtn) {
        resourceBtn.classList.add('active', 'border-zinc-900', 'text-zinc-900');
        resourceBtn.classList.remove('border-transparent', 'text-zinc-500');
    }
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

        marker.on('click', () => {
            openDetailsModal(item);
        });
    });
}

function findAction(id) {
    return [...actionsData, ...resourcesData].find(a => a.id === id);
}

// =============================================
// 11. FILE UPLOAD PREVIEW
// =============================================


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

    // Add event listeners for action form file upload preview
    const actionFileInput = document.getElementById("action-file-input");
    const actionFileList = document.getElementById("action-files-list");
    const actionNumOfFiles = document.getElementById("action-num-of-files");

    if (actionFileInput && actionFileList && actionNumOfFiles) {
        actionFileInput.addEventListener("change", () => {
            actionFileList.innerHTML = "";
            actionNumOfFiles.textContent = `${actionFileInput.files.length} File${actionFileInput.files.length > 1 ? 's' : ''} Selected`;

            for (const file of actionFileInput.files) {
                const li = document.createElement("li");
                const sizeKB = (file.size / 1024).toFixed(1);
                const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
                li.innerHTML = `<p>${file.name}</p><p>${sizeKB >= 1024 ? sizeMB + ' MB' : sizeKB + ' KB'}</p>`;
                actionFileList.appendChild(li);
            }
        });
    }

    // Add event listeners for resource form file upload preview
    const resourceFileInput = document.getElementById("resource-file-input");
    const resourceFileList = document.getElementById("resource-files-list");
    const resourceNumOfFiles = document.getElementById("resource-num-of-files");

    if (resourceFileInput && resourceFileList && resourceNumOfFiles) {
        resourceFileInput.addEventListener("change", () => {
            resourceFileList.innerHTML = "";
            resourceNumOfFiles.textContent = `${resourceFileInput.files.length} File${resourceFileInput.files.length > 1 ? 's' : ''} Selected`;

            for (const file of resourceFileInput.files) {
                const li = document.createElement("li");
                const sizeKB = (file.size / 1024).toFixed(1);
                const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
                li.innerHTML = `<p>${file.name}</p><p>${sizeKB >= 1024 ? sizeMB + ' MB' : sizeKB + ' KB'}</p>`;
                resourceFileList.appendChild(li);
            }
        });
    }
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
            popup.style.zIndex = '9999999';
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
    // Note: Calendar is initialized after data is loaded in loadAllData()

    if (document.getElementById('map')) {
        console.log('Map container found, initializing map...');
        initMap();
    } else {
        console.warn('Map container not found, skipping map initialization');
    }

    setupEventListeners();

    // Add event listeners for form submission
    document.getElementById('createActionBtn').addEventListener('click', async function (e) {
        e.preventDefault();
        await submitActionForm();
    });
    document.getElementById('createResourceBtn').addEventListener('click', async function (e) {
        e.preventDefault();
        await submitResourceForm();
    });

    // Vue Create Modal Tabs (scoped)
    const vueTabButtons = document.querySelectorAll('#createModal .tab-btn');
    vueTabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tabId = btn.dataset.tab || btn.getAttribute('data-tab');
            if (tabId) switchTab(tabId);
        });
    });

    // Populate country dropdowns after page loads
    populateCountryDropdowns();
});

// Function to clear error styles used by validation functions
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



// Legacy submitResourceForm function was removed to avoid conflicts with the improved version
// The newer submitResourceForm function is located earlier in the file with proper validation integration

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
            showSwal('Error', 'Failed to add comment: ' + result.message, 'error', 'top-end');
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
                // Handle the response structure: with ApiResponse::success, comments are in result.data.comments
                const comments = result.data?.comments || [];
                console.log(`Found comments container, rendering ${comments.length} comments`);
                renderComments(comments, commentsList);
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
        container.innerHTML = '<p class="no-comments text-sm text-zinc-500">No comments yet. Be the first to comment!</p>';
        return;
    }

    // Create comments list
    const commentsList = document.createElement('div');
    commentsList.className = 'space-y-4';

    comments.forEach(comment => {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment bg-zinc-50 rounded-lg p-3 border border-zinc-100';
        commentElement.innerHTML = `
            <div class="flex gap-3">
                <img src="${comment.user_avatar || '/assets/images/avatar.png'}" alt="${comment.user_name}" class="w-8 h-8 rounded-full border border-zinc-200">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-medium text-sm text-zinc-800">${comment.user_name}</span>
                        <span class="text-xs text-zinc-500">${comment.created_at_formatted || formatDateTime(comment.created_at)}</span>
                    </div>
                    <p class="text-sm text-zinc-700">${comment.content}</p>
                </div>
            </div>
        `;
        commentsList.appendChild(commentElement);
    });

    container.appendChild(commentsList);
}

// Add comment function called from the UI
async function addComment() {
    // Check if user is logged in
    if (!isUserLoggedIn || !currentUser) {
        showSwal('Login Required', 'Please log in to add comments.', 'info');
        return;
    }

    // Get the input field
    const commentInput = document.getElementById('commentInput');
    if (!commentInput) {
        console.error("Comment input field not found!");
        showSwal('Error', 'Comment input field not found', 'error');
        return;
    }

    const commentText = commentInput.value.trim();
    if (!commentText) {
        showSwal('Error', 'Please enter a comment', 'error');
        return;
    }

    // Determine if we're commenting on an action or resource
    const modal = document.getElementById('detailsModal');
    if (!modal) {
        showSwal('Error', 'Details modal not open', 'error');
        return;
    }

    // Get the current item from the modal data
    if (!currentModalData) {
        showSwal('Error', 'No item selected for comment', 'error');
        return;
    }

    let actionId = null;
    let resourceId = null;

    if (currentModalData.type === 'action') {
        actionId = currentModalData.id;
    } else if (currentModalData.type === 'resource') {
        resourceId = currentModalData.id;
    } else {
        showSwal('Error', 'Unknown item type for comment', 'error');
        return;
    }

    // Call the existing submitComment function
    await submitComment(actionId, resourceId);

    // Clear the input field
    commentInput.value = '';
}

// Format date time for display
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Enhanced toggleJoinAction function with proper state management
async function toggleJoinAction(actionId) {
    // Add authentication check
    if (!isUserLoggedIn || !currentUser) {
        showSwal('Login Required', 'Please log in to join actions.', 'info');
        return;
    }

    // Get button reference and store original text
    const button = document.getElementById('actionButton');
    if (button) {
        button.disabled = true;
        // Determine the current state from the button text to show appropriate loading text
        const isCurrentlyJoined = button.textContent.includes('Leave');
        button.textContent = isCurrentlyJoined ? 'Leaving...' : 'Joining...';
    }

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
                action_id: actionId
            }),
            dataType: "json"
        });

        if (result.success) {
            // Handle the response structure: joined status is in result.data.joined
            const joined = result.data && result.data.joined ? result.data.joined : false;

            // Update the global state for joined actions based on the result
            if (joined) {
                joinedActions.add(actionId);
            } else {
                joinedActions.delete(actionId);
            }

            // Update the button text and style
            if (button) {
                if (joined) {
                    button.textContent = 'Leave Action';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-secondary');
                } else {
                    button.textContent = 'Join Action';
                    button.classList.remove('btn-secondary');
                    button.classList.add('btn-primary');
                }
                button.disabled = false;
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
    } finally {
        // Re-enable button in all cases
        if (button) {
            button.disabled = false;
            // Update button state based on actual joined status (fallback)
            const isNowJoined = joinedActions.has(parseInt(actionId));
            if (isNowJoined) {
                button.textContent = 'Leave Action';
                button.classList.remove('btn-primary');
                button.classList.add('btn-secondary');
            } else {
                button.textContent = 'Join Action';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');
            }
        }
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
                // Handle the response structure: participants are in result.data.participants
                const participants = result.data?.participants || [];
                renderParticipants(participants, participantsList);

                // Update the participants count in the details section
                const participantsCount = document.getElementById('modalParticipants');
                if (participantsCount) {
                    // Access count from result.data.count
                    const count = result.data?.count || 0;
                    participantsCount.textContent = `${count} ${count === 1 ? 'person' : 'people'}`;
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

    // Check if participants is undefined or not an array
    if (!participants || !Array.isArray(participants)) {
        container.innerHTML = '<p class="no-participants">No participants yet. Be the first to join!</p>';
        return;
    }

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
document.addEventListener('input', function (e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        if (e.target.value.trim() !== '') {
            e.target.style.border = '';
        }
    }
});

// Add change event listener for radio buttons
document.addEventListener('change', function (e) {
    if (e.target.name === 'type') {
        document.querySelector('.radio-group').style.border = '';
    }
});

// Custom validation functions for form validation
function validateFormField(fieldId, errorMessage) {
    const field = document.getElementById(fieldId);
    if (!field) {
        console.error(`Field with ID ${fieldId} not found`);
        return false;
    }

    let isValid = true;

    // Check if field is empty
    if (!field.value.trim()) {
        addFieldError(fieldId, errorMessage);
        isValid = false;
    } else {
        clearFieldError(fieldId);
    }

    return isValid;
}

function addFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    // Special handling for radio group containers
    if (fieldId === 'resourceType') {
        // For the resource type radio group, apply error class to the actual radio-group inside
        const actualRadioGroup = field.querySelector('.radio-group');
        if (actualRadioGroup) {
            actualRadioGroup.classList.add('error-border');
        }
    } else {
        // Add error class to field
        field.classList.add('input-error');
    }

    // Create or update error message element
    let errorElement = document.getElementById(`${fieldId}-error`);
    if (!errorElement) {
        errorElement = document.createElement('small');
        errorElement.id = `${fieldId}-error`;
        errorElement.className = 'error-message';

        // Insert after the field
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    errorElement.textContent = message;
    errorElement.classList.add('show');
}

function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    // Special handling for radio group containers
    if (fieldId === 'resourceType') {
        // For the resource type radio group, remove error class from the actual radio-group inside
        const actualRadioGroup = field.querySelector('.radio-group');
        if (actualRadioGroup) {
            actualRadioGroup.classList.remove('error-border');
        }
    } else {
        // Remove error class from field
        field.classList.remove('input-error');
    }

    // Hide error message
    const errorElement = document.getElementById(`${fieldId}-error`);
    if (errorElement) {
        errorElement.classList.remove('show');
    }
}

// Add input event listeners to clear error messages when user starts typing
document.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('input', function () {
        clearFieldError(this.id);
    });

    // Also add change event for select elements to catch selection changes
    if (field.tagName.toLowerCase() === 'select') {
        field.addEventListener('change', function () {
            clearFieldError(this.id);
        });
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
document.addEventListener('DOMContentLoaded', function () {
    // Set up location picker event listeners after DOM is loaded
    setTimeout(setupLocationPickerEventListeners, 1000); // Delay to ensure DOM is fully loaded
});

function setupLocationPickerEventListeners() {
    // Add event listeners to all "Pick on Map" buttons
    const pickLocationButtons = document.querySelectorAll('.pick-location-btn');
    pickLocationButtons.forEach(button => {
        button.addEventListener('click', function () {
            const formType = this.getAttribute('data-form');
            openLocationPicker(formType);
        });
    });

    // Add event listeners for location picker modal buttons
    document.getElementById('closeLocationPickerBtn').addEventListener('click', closeLocationPicker);
    document.getElementById('cancelLocationPicker').addEventListener('click', closeLocationPicker);
    document.getElementById('confirmLocationBtn').addEventListener('click', function () {
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

    // Guard against multiple initializations: if locationPickerMap already exists, remove it first
    if (locationPickerMap) {
        try {
            locationPickerMap.remove();
        } catch (e) {
            console.warn('Could not remove existing map:', e);
        }
        locationPickerMap = null;
    }

    // Clear any existing Leaflet content in the container
    mapContainer.innerHTML = '';

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
            function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                locationPickerMap.setView([lat, lng], 13);
            },
            function (error) {
                console.log("Could not get user's location, using default view");
                // Use default view if geolocation fails
                locationPickerMap.setView([20, 0], 2);
            }
        );
    }

    // Add click event to map for location selection
    locationPickerMap.on('click', function (e) {
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

        // Show pin placement feedback
        showSwal('Pin Placed', 'Location marker added. Click Confirm to save.', 'success', 'bottom-end');

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
    // Use Nominatim API for reverse geocoding with proper headers
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=en`;

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'User-Agent': 'ConnectForPeace/1.0 (Contact: contact@example.com)'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Geocoding API error: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            let address = 'Address not found';
            if (data && data.display_name) {
                address = data.display_name;
            } else if (data && data.address) {
                const addr = data.address;
                // Try to build a meaningful address from the available parts
                const addressParts = [
                    addr.road || addr.pedestrian || addr.path || addr.cycleway || addr.footway || addr.street || addr.residential || '',
                    addr.house_number || addr.building || '',
                    addr.city || addr.town || addr.village || addr.hamlet || addr.suburb || addr.neighbourhood || addr.municipality || addr.county || addr.state || '',
                    addr.country || '',
                    addr.postcode || ''
                ].filter(part => part !== '').join(', ');

                // If we have an address, use it, otherwise fall back to coordinates
                address = addressParts || `(${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            } else {
                // If no address data is available, use coordinates as fallback
                address = `(${lat.toFixed(4)}, ${lng.toFixed(4)})`;
            }

            document.getElementById('selectedAddress').textContent = address;
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
            // Fallback to coordinates if API fails
            document.getElementById('selectedAddress').textContent = `(${lat.toFixed(4)}, ${lng.toFixed(4)})`;
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

// Enhanced action form validation - DEPRECATED: Use submitActionForm() which has integrated validation
// function validateActionForm(e) {
//     e.preventDefault();
//
//     // Get form fields
//     const title = document.getElementById('actionTitle');
//     const category = document.getElementById('actionCategory');
//     const description = document.getElementById('actionDescription');
//     const duration = document.getElementById('actionDuration');
//     const locationDetails = document.getElementById('actionLocationDetails');
//     const country = document.getElementById('actionCountry');
//
//     let isValid = true;
//     const errors = [];
//
//     // Clear previous error styles
//     clearErrorStyles();
//
//     // Validate title
//     if (!title.value.trim()) {
//         title.classList.add('error-border');
//         errors.push('Title is required');
//         isValid = false;
//     }
//
//     // Validate category
//     if (!category.value) {
//         category.classList.add('error-border');
//         errors.push('Category is required');
//         isValid = false;
//     }
//
//     // Validate description
//     if (!description.value.trim()) {
//         description.classList.add('error-border');
//         errors.push('Description is required');
//         isValid = false;
//     }
//
//     // Validate duration if provided (should be positive number)
//     if (duration.value && (isNaN(duration.value) || parseInt(duration.value) <= 0)) {
//         duration.classList.add('error-border');
//         errors.push('Duration must be a positive number');
//         isValid = false;
//     }
//
//     // Validate location details
//     if (!locationDetails.value.trim()) {
//         locationDetails.classList.add('error-border');
//         errors.push('Location details are required');
//         isValid = false;
//     }
//
//     // Validate country
//     if (!country.value) {
//         country.classList.add('error-border');
//         errors.push('Country is required');
//         isValid = false;
//     }
//
//     if (!isValid) {
//         // Show error message with all issues
//         showSwal('Validation Error', errors.join('<br>'), 'error');
//
//         // Add shake animation to form to draw attention
//         const form = document.querySelector('#action-tab .create-form');
//         form.classList.add('shake');
//         setTimeout(() => {
//             form.classList.remove('shake');
//         }, 500);
//
//         return false;
//     }
//
//     // Check coordinates validation after basic validation passes
//     const coordValidation = validateCoordinates('action');
//     if (!coordValidation.valid) {
//         showSwal('Location Validation', coordValidation.message, 'warning');
//         return false;
//     }
//
//     // Submit the action form using the main submission function
//     submitActionForm();
// }

// Enhanced resource form validation - DEPRECATED: Use submitResourceForm() which has integrated validation
// function validateResourceForm(e) {
//     e.preventDefault();
//
//     // Get resource form fields
//     const resourceName = document.getElementById('resourceName');
//     const resourceType = document.querySelector('#resource-tab input[name="type"]:checked');
//     const resourceCategory = document.getElementById('resourceCategory');
//     const resourceDescription = document.getElementById('resourceDescription'); // Fixed to use proper ID
//     const resourceLocationDetails = document.getElementById('resourceLocationDetails');
//     const resourceCountry = document.getElementById('resourceCountry');
//
//     let isValid = true;
//     const errors = [];
//
//     // Clear previous error styles
//     clearErrorStyles();
//
//     // Validate resource name
//     if (!resourceName.value.trim()) {
//         resourceName.classList.add('error-border');
//         errors.push('Resource name is required');
//         isValid = false;
//     }
//
//     // Validate resource type
//     if (!resourceType) {
//         const radioGroup = document.querySelector('.radio-group');
//         radioGroup.classList.add('error-border');
//         errors.push('Resource type (Offer/Request/Knowledge) is required');
//         isValid = false;
//     }
//
//     // Validate resource category
//     if (!resourceCategory.value) {
//         resourceCategory.classList.add('error-border');
//         errors.push('Category is required');
//         isValid = false;
//     }
//
//     // Validate resource description
//     if (!resourceDescription.value.trim()) {
//         resourceDescription.classList.add('error-border');
//         errors.push('Description is required');
//         isValid = false;
//     }
//
//     // Validate resource location details
//     if (!resourceLocationDetails.value.trim()) {
//         resourceLocationDetails.classList.add('error-border');
//         errors.push('Location details are required');
//         isValid = false;
//     }
//
//     // Validate resource country
//     if (!resourceCountry.value) {
//         resourceCountry.classList.add('error-border');
//         errors.push('Country is required');
//         isValid = false;
//     }
//
//     if (!isValid) {
//         // Show error message with all issues
//         showSwal('Validation Error', errors.join('<br>'), 'error');
//
//         // Add shake animation to form to draw attention
//         const form = document.querySelector('#resource-tab .create-form');
//         form.classList.add('shake');
//         setTimeout(() => {
//             form.classList.remove('shake');
//         }, 500);
//
//         return false;
//     }
//
//     // Check coordinates validation after basic validation passes
//     const coordValidation = validateCoordinates('resource');
//     if (!coordValidation.valid) {
//         showSwal('Location Validation', coordValidation.message, 'warning');
//         return false;
//     }
//
//     // Submit the resource form
//     submitResourceForm();
// }

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

// =============================================
// ==================================================
// Calendar & Scheduling Functions
// ==================================================

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

// Loading and empty state functions
function showCalendarLoading() {
    const calendarContainer = document.getElementById('calendar-container');
    if (calendarContainer) {
        calendarContainer.innerHTML = '<div class="calendar-loading">Loading calendar...</div>';
    }

    const agendaContainer = document.getElementById('agenda-container');
    if (agendaContainer) {
        agendaContainer.innerHTML = '<div class="calendar-loading">Loading events...</div>';
    }
}

function hideCalendarLoading() {
    const calendarLoading = document.querySelector('.calendar-loading');
    if (calendarLoading) {
        calendarLoading.remove();
    }
}

function showEmptyCalendarState(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="calendar-empty-state">
                <div class="empty-state-icon">📅</div>
                <h3>No Events Scheduled</h3>
                <p>There are no events for this date range.</p>
            </div>
        `;
    }
}

// Calendar rendering functions
function initializeCalendar() {
    showCalendarLoading(); // Show loading indicator
    setTimeout(() => {
        renderCalendar(currentMonth, currentYear);
        hideCalendarLoading(); // Hide loading indicator

        // Update active state for view toggle buttons (set calendar as active by default)
        const viewToggleButtons = document.querySelectorAll('.view-toggle .btn');
        viewToggleButtons.forEach(button => {
            if (button.textContent.includes('Calendar')) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }, 300); // Small delay to show loading state

    // Add event listeners for calendar navigation
    document.addEventListener('click', function (e) {
        const prevBtn = e.target.closest('.calendar-nav-prev');
        const nextBtn = e.target.closest('.calendar-nav-next');

        if (prevBtn) {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        } else if (nextBtn) {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        }
    });
}

function renderCalendar(month, year) {
    const calendarContainer = document.getElementById('calendar-container');
    if (!calendarContainer) return;

    // Clear previous calendar
    calendarContainer.innerHTML = '';

    // Create calendar header with navigation and Today button
    const calendarHeader = document.createElement('div');
    calendarHeader.className = 'calendar-header-inner';
    calendarHeader.innerHTML = `
        <div class="calendar-controls flex items-center justify-between w-full">
            <button class="btn btn-outline calendar-nav-prev rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition-all hover:text-zinc-900 hover:bg-zinc-50 border border-zinc-200 flex items-center gap-1">
                <i data-lucide="chevron-left" class="h-4 w-4"></i> Prev
            </button>
            <h3 class="text-base font-semibold text-zinc-900">${new Date(year, month).toLocaleString('default', { month: 'long', year: 'numeric' })}</h3>
            <button class="btn btn-outline calendar-nav-next rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition-all hover:text-zinc-900 hover:bg-zinc-50 border border-zinc-200 flex items-center gap-1">
                Next <i data-lucide="chevron-right" class="h-4 w-4"></i>
            </button>
        </div>
        <div class="calendar-month-year-selector">
            <select id="monthSelector" onchange="changeMonth(this.value)" class="month-selector">
                ${Array.from({ length: 12 }, (_, i) =>
        `<option value="${i}" ${i === month ? 'selected' : ''}>${new Date(0, i).toLocaleString('default', { month: 'short' })}</option>`
    ).join('')}
            </select>
            <select id="yearSelector" onchange="changeYear(this.value)" class="year-selector">
                ${Array.from({ length: 5 }, (_, i) => {
        const yearOption = new Date().getFullYear() - 2 + i;
        return `<option value="${yearOption}" ${yearOption === year ? 'selected' : ''}>${yearOption}</option>`;
    }).join('')}
            </select>
        </div>
    `;
    calendarContainer.appendChild(calendarHeader);

    // Initialize Lucide icons for the new chevron icons
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Create calendar grid
    const calendarGrid = document.createElement('div');
    calendarGrid.className = 'calendar-grid';

    // Add day headers
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });

    // Get first day of month and number of days in month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Add empty cells for days before the first day
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'calendar-day empty';
        calendarGrid.appendChild(emptyCell);
    }

    // Add cells for each day of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day';
        dayCell.innerHTML = `<span class="day-number">${day}</span>`;

        // Check if this day has any events
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayEvents = getEventsForDate(dateStr);

        if (dayEvents.length > 0) {
            dayCell.classList.add('has-event');

            // Limit events to show (e.g., max 3 events per day)
            const maxEventsToShow = 3;
            const eventsToShow = dayEvents.slice(0, maxEventsToShow);
            const moreEventsCount = dayEvents.length - maxEventsToShow;

            eventsToShow.forEach(event => {
                const eventElement = document.createElement('div');
                eventElement.className = `calendar-event calendar-event-${event.type}`;
                eventElement.innerHTML = `<span class="event-icon">${event.type === 'action' ? '📅' : '📦'}</span> ${event.title.substring(0, 20) + (event.title.length > 20 ? '...' : '')}`;
                eventElement.title = `${event.title} (${event.type})`;
                eventElement.dataset.id = event.id;
                eventElement.dataset.type = event.type;
                eventElement.style.cursor = 'pointer';
                eventElement.setAttribute('tabindex', '0');

                // Add click handler to open details modal
                eventElement.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Look up the full item from actionsData or resourcesData based on type
                    let fullItem = null;
                    if (event.type === 'action') {
                        fullItem = actionsData.find(action => action.id == event.id);
                    } else if (event.type === 'resource') {
                        fullItem = resourcesData.find(resource => resource.id == event.id);
                    }
                    if (fullItem) {
                        openDetailsModal(fullItem);
                    } else {
                        console.error('Could not find item with id:', event.id, 'and type:', event.type);
                    }
                });

                dayCell.appendChild(eventElement);
            });

            // Add "+X more" indicator if there are more events
            if (moreEventsCount > 0) {
                const moreEventsElement = document.createElement('div');
                moreEventsElement.className = 'calendar-more-events';
                moreEventsElement.textContent = `+${moreEventsCount} more`;
                moreEventsElement.title = `Click to see all ${dayEvents.length} events on this day`;
                moreEventsElement.style.cursor = 'pointer';
                moreEventsElement.addEventListener('click', function (e) {
                    e.stopPropagation();
                    // Show tooltip with all events for this day
                    showEventsTooltip(dayCell, dayEvents);
                });
                dayCell.appendChild(moreEventsElement);
            }
        }

        // Highlight today
        const today = new Date();
        if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
            dayCell.classList.add('today');
        }

        calendarGrid.appendChild(dayCell);
    }

    calendarContainer.appendChild(calendarGrid);
}

function renderAgenda() {
    filterAgenda('all'); // Call the enhanced agenda filter with 'all' as default
}

function getEventsForDate(dateStr) {
    const events = [];

    // Find actions for this date
    actionsData.forEach(action => {
        if (action.start_time && action.start_time.startsWith(dateStr)) {
            events.push({
                id: action.id,
                title: action.title,
                type: 'action',
                description: action.description,
                location: action.location,
                date: action.start_time
            });
        }
    });

    // Find resources created on this date
    resourcesData.forEach(resource => {
        if (resource.created_at && resource.created_at.startsWith(dateStr)) {
            events.push({
                id: resource.id,
                title: resource.resource_name,
                type: 'resource',
                description: resource.description,
                location: resource.location,
                date: resource.created_at
            });
        }
    });

    return events;
}

function toggleCalendarView(view) {
    const calendarContainer = document.getElementById('calendar-container');
    const agendaContainer = document.getElementById('agenda-container');

    if (view === 'calendar') {
        calendarContainer.style.display = 'block';
        agendaContainer.style.display = 'none';
        renderCalendar(currentMonth, currentYear);
    } else if (view === 'agenda') {
        calendarContainer.style.display = 'none';
        agendaContainer.style.display = 'block';
        renderAgenda();
    }

    // Update active state for view toggle buttons
    const viewToggleButtons = document.querySelectorAll('.view-toggle .btn');
    viewToggleButtons.forEach(button => {
        if (button.dataset.view === view) {
            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');
        } else {
            button.classList.remove('active');
            button.setAttribute('aria-pressed', 'false');
        }
    });

    // Update global view state
    currentCalendarView = view;
}

// =============================================
// ==================================================
// ICS File Generation Functions
// ==================================================

function generateICS(action) {
    const start = new Date(action.start_time);
    const end = new Date(start.getTime() + (2 * 60 * 60 * 1000)); // Assuming 2 hours duration

    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Connect for Peace//Calendar//EN',
        'BEGIN:VEVENT',
        `UID:${action.id}-${action.creator_id}@connectforpeace.com`,
        `DTSTAMP:${toISOStringForICS(new Date())}`,
        `DTSTART:${toISOStringForICS(start)}`,
        `DTEND:${toISOStringForICS(end)}`,
        `SUMMARY:${escapeICSText(action.title)}`,
        `DESCRIPTION:${escapeICSText(action.description)}`,
        `LOCATION:${escapeICSText(action.location || '')}`,
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\\r\\n');

    return icsContent;
}

function toISOStringForICS(date) {
    // Format date as YYYYMMDDTHHMMSSZ for ICS
    return date.getFullYear() +
        String(date.getMonth() + 1).padStart(2, '0') +
        String(date.getDate()).padStart(2, '0') + 'T' +
        String(date.getHours()).padStart(2, '0') +
        String(date.getMinutes()).padStart(2, '0') +
        String(date.getSeconds()).padStart(2, '0') + 'Z';
}

function escapeICSText(text) {
    // Escape special characters for ICS format
    if (!text) return '';
    return text.toString()
        .replace(/\\/g, '\\\\')
        .replace(/;/g, '\\;')
        .replace(/,/g, '\\,')
        .replace(/\\n/g, '\\n');
}

function downloadICS() {
    if (!currentModalData || currentModalData.type !== 'action') {
        showSwal('Error', 'Calendar export is only available for actions with a date.', 'error');
        return;
    }

    const icsContent = generateICS(currentModalData);
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;
    link.download = `${currentModalData.title.replace(/[^a-z0-9]/gi, '_')}.ics`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showSwal('Success', 'Calendar file downloaded successfully!', 'success');
}

// =============================================
// ==================================================
// Reminder Functions
// ==================================================

function createReminder(itemId, itemType, reminderTime, reminderType = 'both') {
    if (!isUserLoggedIn) {
        showSwal('Login Required', 'Please log in to set reminders.', 'info');
        return;
    }

    const reminderData = {
        item_id: itemId,
        item_type: itemType,
        reminder_time: reminderTime,
        reminder_type: reminderType
    };

    $.ajax({
        url: '../api/reminders/create_reminder.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(reminderData),
        success: function (response) {
            if (response.success) {
                showSwal('Success', response.message, 'success');
            } else {
                showSwal('Error', response.message, 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error creating reminder:', error);
            showSwal('Error', 'Failed to create reminder. Please try again.', 'error', 'top-end');
        }
    });
}

function loadUserReminders() {
    if (!isUserLoggedIn) return;

    $.ajax({
        url: '../api/reminders/get_reminders.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                // Process reminders here if needed
                console.log('User reminders loaded:', response.data);
            } else {
                console.error('Error loading reminders:', response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading reminders:', error);
        }
    });
}

// =============================================
// ==================================================
// Reminder Modal Functions
// ==================================================

function openReminderModal() {
    if (!isUserLoggedIn) {
        showSwal('Login Required', 'Please log in to set reminders.', 'info');
        return;
    }

    if (!currentModalData || !currentModalData.start_time) {
        showSwal('Error', 'This item does not have a date/time for setting reminders.', 'error');
        return;
    }

    // Set the item details in hidden fields
    document.getElementById('reminderItemId').value = currentModalData.id;
    document.getElementById('reminderItemType').value = currentModalData.type;

    // Show the modal
    const reminderModal = document.getElementById('reminderModal');
    reminderModal.classList.add('active');
}

function closeReminderModal() {
    const reminderModal = document.getElementById('reminderModal');
    reminderModal.classList.remove('active');

    // Reset form
    document.getElementById('reminderForm').reset();
    // Clear any error messages
    clearFieldError('reminderTime');
}

function submitReminder() {
    // Clear previous errors
    clearFieldError('reminderTime');

    // Validate required fields
    const selectedTime = document.querySelector('input[name="reminderTime"]:checked');
    if (!selectedTime) {
        addFieldError('reminderTime', 'Please select when you want to be reminded');
        return;
    }

    // Get item details from hidden fields
    const itemId = document.getElementById('reminderItemId').value;
    const itemType = document.getElementById('reminderItemType').value;
    const timeOption = selectedTime.value;

    // Calculate reminder time based on selected option and action time
    const actionTime = new Date(currentModalData.start_time);
    let reminderTime;

    switch (timeOption) {
        case '1h':
            reminderTime = new Date(actionTime.getTime() - 1 * 60 * 60 * 1000); // 1 hour before
            break;
        case '1d':
            reminderTime = new Date(actionTime.getTime() - 24 * 60 * 60 * 1000); // 1 day before
            break;
        case '1w':
            reminderTime = new Date(actionTime.getTime() - 7 * 24 * 60 * 60 * 1000); // 1 week before
            break;
        default:
            showSwal('Error', 'Invalid reminder time option.', 'error');
            return;
    }

    // Validate that reminder time is before action time and in the future
    const now = new Date();
    if (reminderTime >= actionTime) {
        showSwal('Error', 'Reminder time must be before the event time.', 'error');
        return;
    }

    if (reminderTime <= now) {
        showSwal('Error', 'Reminder time would be in the past. Please select an event in the future.', 'error');
        return;
    }

    // Create the reminder via API
    createReminder(itemId, itemType, reminderTime.toISOString(), 'both');

    // Close the modal after submission
    closeReminderModal();
}

// Add event listener for the reminder form submission
document.addEventListener('DOMContentLoaded', function() {
    const reminderForm = document.getElementById('reminderForm');
    if (reminderForm) {
        reminderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitReminder();
        });
    }
});

// =============================================
// ==================================================
// Reporting Functions
// ==================================================

function openReportModal() {
    if (!isUserLoggedIn) {
        showSwal('Login Required', 'Please log in to report content.', 'info');
        return;
    }

    // Set the item details in hidden fields
    document.getElementById('reportItemId').value = currentModalData.id;
    document.getElementById('reportItemType').value = currentModalData.type;

    // Show the modal
    const reportModal = document.getElementById('reportModal');
    reportModal.classList.add('active');
}

function closeReportModal() {
    const reportModal = document.getElementById('reportModal');
    reportModal.classList.remove('active');

    // Reset form
    document.getElementById('reportForm').reset();
}


// Calendar navigation functions
function goToToday() {
    const today = new Date();
    currentMonth = today.getMonth();
    currentYear = today.getFullYear();
    renderCalendar(currentMonth, currentYear);
}

function changeMonth(month) {
    currentMonth = parseInt(month);
    renderCalendar(currentMonth, currentYear);
}

function changeYear(year) {
    currentYear = parseInt(year);
    renderCalendar(currentMonth, currentYear);
}

// Enhanced agenda view with filters
function filterAgenda(range) {
    // This function will filter the agenda based on the selected range
    const agendaContainer = document.getElementById('agenda-container');
    if (!agendaContainer) return;

    // Combine and sort all actions and resources by date
    let allItems = [];

    // Add actions with start_time
    actionsData.forEach(action => {
        if (action.start_time) {
            allItems.push({
                id: action.id,
                title: action.title,
                type: 'action',
                date: new Date(action.start_time),
                description: action.description,
                location: action.location
            });
        }
    });

    // Add resources (using created_at as their date for now)
    resourcesData.forEach(resource => {
        allItems.push({
            id: resource.id,
            title: resource.resource_name,
            type: 'resource',
            date: new Date(resource.created_at),
            description: resource.description,
            location: resource.location
        });
    });

    // Filter based on range
    const now = new Date();
    let filteredItems = [];

    switch (range) {
        case 'week':
            const oneWeekFromNow = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
            filteredItems = allItems.filter(item => item.date >= now && item.date <= oneWeekFromNow);
            break;
        case 'month':
            const oneMonthFromNow = new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000);
            filteredItems = allItems.filter(item => item.date >= now && item.date <= oneMonthFromNow);
            break;
        case 'all':
        default:
            filteredItems = allItems;
            break;
    }

    // Sort by date
    filteredItems.sort((a, b) => a.date - b.date);

    // Create agenda HTML with filter controls
    let agendaHTML = `
        <div class="agenda-filters">
            <button class="agenda-filter-btn ${range === 'week' ? 'active' : ''}" onclick="filterAgenda('week')">Upcoming Week</button>
            <button class="agenda-filter-btn ${range === 'month' ? 'active' : ''}" onclick="filterAgenda('month')">This Month</button>
            <button class="agenda-filter-btn ${range === 'all' ? 'active' : ''}" onclick="filterAgenda('all')">All Events</button>
            <span class="agenda-count">${filteredItems.length} events</span>
        </div>
        <div class="agenda-list">
    `;

    if (filteredItems.length === 0) {
        agendaHTML += `
            <div class="agenda-empty-state">
                <div class="empty-state-icon">📅</div>
                <h3>No Events Scheduled</h3>
                <p>No events match your current filter.</p>
            </div>
        `;
    } else {
        filteredItems.forEach(item => {
            const dateStr = item.date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const timeStr = item.date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            agendaHTML += `
                <div class="agenda-item ${item.type === 'resource' ? 'agenda-item-resource' : ''}" data-id="${item.id}" data-type="${item.type}" onclick="openAgendaItemDetails(this)">
                    <div class="agenda-date">
                        <div class="date-day">${item.date.getDate()}</div>
                        <div class="date-month">${item.date.toLocaleString('default', { month: 'short' })}</div>
                    </div>
                    <div class="agenda-content">
                        <h4>${item.title}</h4>
                        <p class="agenda-type ${item.type === 'action' ? 'action-type' : 'resource-type'}">${item.type.charAt(0).toUpperCase() + item.type.slice(1)}</p>
                        <p class="agenda-time">${item.type === 'action' ? timeStr : dateStr}</p>
                        ${item.location ? `<p class="agenda-location">📍 ${item.location}</p>` : ''}
                    </div>
                </div>
            `;
        });
    }

    agendaHTML += '</div>';
    agendaContainer.innerHTML = agendaHTML;
}

// Event tooltip function
function showEventsTooltip(dayCell, events) {
    // Remove any existing tooltips
    document.querySelectorAll('.calendar-event-tooltip').forEach(el => el.remove());

    let tooltipHTML = '<div class="calendar-event-tooltip" style="display: block; position: absolute; z-index: 1000;">';
    tooltipHTML += '<div class="tooltip-content">';
    tooltipHTML += `<h4>Events for ${dayCell.querySelector('.day-number').textContent}</h4>`;

    events.forEach(event => {
        tooltipHTML += `<div class="tooltip-event" data-id="${event.id}" data-type="${event.type}" onclick="openTooltipItemDetails(this)">
            <strong class="event-type-icon">${event.type === 'action' ? '📅' : '📦'}</strong>
            <span>${event.title}</span>
        </div>`;
    });

    tooltipHTML += '</div><div class="tooltip-arrow"></div></div>';

    const tooltip = document.createElement('div');
    tooltip.innerHTML = tooltipHTML;
    tooltip.className = 'calendar-event-tooltip';

    // Position tooltip near the day cell
    const rect = dayCell.getBoundingClientRect();
    tooltip.style.top = rect.bottom + window.scrollY + 5 + 'px';
    tooltip.style.left = rect.left + window.scrollX + 'px';

    document.body.appendChild(tooltip);

    // Close tooltip when clicking elsewhere
    setTimeout(() => {
        document.addEventListener('click', function closeTooltip(e) {
            if (!tooltip.contains(e.target) && !dayCell.contains(e.target)) {
                tooltip.remove();
                document.removeEventListener('click', closeTooltip);
            }
        });
    }, 100);
}

// Helper function for agenda item clicks
function openAgendaItemDetails(element) {
    const id = element.dataset.id;
    const type = element.dataset.type;

    // Look up the full item from actionsData or resourcesData based on type
    let fullItem = null;
    if (type === 'action') {
        fullItem = actionsData.find(action => action.id == id);
    } else if (type === 'resource') {
        fullItem = resourcesData.find(resource => resource.id == id);
    }
    if (fullItem) {
        openDetailsModal(fullItem);
    } else {
        console.error('Could not find item with id:', id, 'and type:', type);
    }
}

// Helper function for tooltip event clicks
function openTooltipItemDetails(element) {
    const id = element.dataset.id;
    const type = element.dataset.type;

    // Look up the full item from actionsData or resourcesData based on type
    let fullItem = null;
    if (type === 'action') {
        fullItem = actionsData.find(action => action.id == id);
    } else if (type === 'resource') {
        fullItem = resourcesData.find(resource => resource.id == id);
    }
    if (fullItem) {
        openDetailsModal(fullItem);
    } else {
        console.error('Could not find item with id:', id, 'and type:', type);
    }
}

// Initialize calendar after data load
function initializeCalendarAfterDataLoad() {
    if (actionsData.length > 0 || resourcesData.length > 0) {
        showCalendarLoading(); // Show loading indicator
        setTimeout(() => {
            renderCalendar(currentMonth, currentYear);
            hideCalendarLoading(); // Hide loading indicator

            // Update active state for view toggle buttons (set calendar as active by default)
            const viewToggleButtons = document.querySelectorAll('.view-toggle .btn');
            viewToggleButtons.forEach(button => {
                if (button.textContent.includes('Calendar')) {
                    button.classList.add('active');
                    button.setAttribute('aria-pressed', 'true');
                } else {
                    button.classList.remove('active');
                    button.setAttribute('aria-pressed', 'false');
                }
            });
        }, 300); // Small delay to show loading state
    } else {
        // Show empty state when no events exist
        showEmptyCalendarState('calendar-container');
        showEmptyCalendarState('agenda-container');
    }
}

// Add keyboard navigation
// Handle keyboard navigation and accessibility
document.addEventListener('keydown', function (e) {
    if (currentCalendarView === 'calendar') {
        // Handle arrow key navigation for calendar view
        switch (e.key) {
            case 'ArrowLeft':
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentMonth, currentYear);
                break;
            case 'ArrowRight':
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentMonth, currentYear);
                break;
            case 'Enter':
                // If there's a focused calendar event, trigger the click behavior
                const focusedElement = document.activeElement;
                if (focusedElement && focusedElement.classList.contains('calendar-event')) {
                    const id = focusedElement.dataset.id;
                    const type = focusedElement.dataset.type;

                    // Look up the full item from actionsData or resourcesData based on type
                    let fullItem = null;
                    if (type === 'action') {
                        fullItem = actionsData.find(action => action.id == id);
                    } else if (type === 'resource') {
                        fullItem = resourcesData.find(resource => resource.id == id);
                    }
                    if (fullItem) {
                        openDetailsModal(fullItem);
                    }
                }
                break;
            case 'Escape':
                // Close any visible calendar event tooltips
                document.querySelectorAll('.calendar-event-tooltip').forEach(tooltip => {
                    tooltip.remove();
                });
                break;
        }
    }
});

/**
 * Setup real-time validation for forms
 */
function setupRealTimeValidation() {
    // Fields to validate
    const fields = {
        'actionTitle': { min: 3, message: 'Title must be at least 3 characters' },
        'actionDescription': { min: 20, message: 'Description must be at least 20 characters' },
        'resourceName': { min: 3, message: 'Name must be at least 3 characters' },
        'resourceDescription': { min: 20, message: 'Description must be at least 20 characters' }
    };

    Object.keys(fields).forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field) return;

        const config = fields[fieldId];

        // Validate on blur
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                addFieldError(fieldId, 'This field is required');
            } else if (this.value.trim().length < config.min) {
                addFieldError(fieldId, config.message);
            } else {
                clearFieldError(fieldId);
            }
        });

        // Clear error while typing
        field.addEventListener('input', function() {
            if (this.value.trim().length >= config.min) {
                clearFieldError(fieldId);
            }
        });
    });

    // Validate country dropdowns
    ['actionCountry', 'resourceCountry'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                if (this.value) {
                    clearFieldError(fieldId);
                }
            });
        }
    });
}

/**
 * Verify location coordinates match selected country
 */
async function verifyLocationCountryMatch(lat, lng, selectedCountry) {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`,
            { headers: { 'User-Agent': 'ConnectForPeace/1.0' } }
        );
        const data = await response.json();

        if (data.address && data.address.country) {
            const detectedCountry = data.address.country;
            if (detectedCountry.toLowerCase() !== selectedCountry.toLowerCase()) {
                showSwal(
                    'Location Mismatch',
                    `Coordinates appear to be in ${detectedCountry}, but you selected ${selectedCountry}.`,
                    'warning'
                );
            }
        }
    } catch (error) {
        console.log('Could not verify location:', error);
    }
}

// =============================================
// 15. DROPDOWN TOGGLE FUNCTIONALITY
// =============================================
// Add click functionality to profile dropdown
document.addEventListener('DOMContentLoaded', function () {
    const profileAvatar = document.querySelector('.profile-avatar');
    const dropdown = document.querySelector('.dropdown');

    if (profileAvatar && dropdown) {
        // Toggle dropdown visibility on avatar click
        profileAvatar.addEventListener('click', function (e) {
            e.stopPropagation(); // Prevent event bubbling
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            const isClickInsideDropdown = dropdown.contains(e.target);
            const isClickOnAvatar = profileAvatar.contains(e.target);

            if (!isClickInsideDropdown && !isClickOnAvatar) {
                dropdown.classList.add('hidden');
            }
        });
    }

    // ADD NEW FUNCTION CALLS HERE:
    setupRealTimeValidation(); // Add real-time validation

    // Initialize fixes from script_fixes.js
    initializeCreateButtons();
    initializeCommentSection(); // Add initialization for comment section

    // Add event listener for report form submission
    const reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.removeEventListener('submit', submitReport); // Remove any existing listener
        reportForm.addEventListener('submit', submitReport);
    }
});


// Functions from script_fixes.js merged here:

// Fix 1: Sweet Alert error on file upload - already handled in main script
function handleFileUploadSuccess(result) {
    if (result.success) {
        closeModal('createModal');
        const form = document.querySelector('#action-tab .create-form');
        form.reset();
        document.getElementById('action-num-of-files').textContent = "No Files Chosen";
        document.getElementById('action-files-list').innerHTML = "";
        document.getElementById('createModal').dataset.editMode = 'false';
        document.getElementById('createModal').dataset.actionId = '';
        loadActions();
        showConfetti();
        showSwal('Success!', 'Action created successfully!', 'success');
    } else {
        showSwal('Error', 'Failed to create action: ' + result.message, 'error', 'top-end');
    }
}

function handleFileUploadError(error) {
    let errorMessage = 'File upload failed. Please try again.';

    // Check various types of errors
    if (error.responseJSON && error.responseJSON.message) {
        errorMessage = error.responseJSON.message;
    } else if (error.responseText) {
        try {
            const response = JSON.parse(error.responseText);
            if (response.message) {
                errorMessage = response.message;
            }
        } catch (e) {
            // If response is not JSON, it's likely an HTML error page
            if (error.responseText && error.responseText.includes('Fatal error')) {
                errorMessage = 'Server error occurred. Please contact support.';
            }
        }
    } else if (error.status && error.status !== 200) {
        errorMessage = `File upload failed: ${error.status} ${error.statusText}`;
    }

    showSwal('Error', errorMessage, 'error');
}

// Fix 2: Offer Help button should open resource form instead of action form
function initializeCreateButtons() {
    const createActionBtn = document.querySelector('.create-action-btn');
    const createResourceBtn = document.querySelector('.create-resource-btn');

    if (createActionBtn) {
        createActionBtn.onclick = () => {
            if (isUserLoggedIn) {
                openCreateModal('action');
            } else {
                showLoginPrompt();
            }
        };
    }

    if (createResourceBtn) {
        createResourceBtn.onclick = () => {
            if (isUserLoggedIn) {
                openCreateModal('resource');
            } else {
                showLoginPrompt();
            }
        };
    }
}

function showLoginPrompt() {
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

// Fix 3: Change comment button style and add comment section
function enhanceCommentSection() {
    // Update the post comment button style to look like a proper button
    const commentBtn = document.getElementById('addCommentBtn');
    if (commentBtn) {
        commentBtn.className = 'btn btn-primary px-4 py-2 text-sm font-medium rounded-lg hover:bg-zinc-800 transition-colors';
    }

    // Check both isUserLoggedIn and currentUser to determine if user is logged in
    if ((typeof isUserLoggedIn !== 'undefined' && isUserLoggedIn) &&
        (typeof currentUser !== 'undefined' && currentUser)) {
        // User is logged in, enable comment section
        const commentInput = document.getElementById('commentInput');
        const commentBtn = document.getElementById('addCommentBtn');

        if (commentInput) {
            commentInput.placeholder = 'Add a comment...';
            commentInput.disabled = false;
            commentInput.style.opacity = '1';
        }

        if (commentBtn) {
            commentBtn.textContent = 'Post Comment';
            commentBtn.onclick = function() {
                addComment(); // Use the addComment function instead of default
            };
        }

        const commentUserAvatar = document.getElementById('commentUserAvatar');
        const commentUserName = document.getElementById('commentUserName');

        if (commentUserAvatar) {
            commentUserAvatar.src = currentUser.avatar_url || `https://api.dicebear.com/7.x/avataaars/svg?seed=${currentUser.name || 'user'}`;
        }

        if (commentUserName) {
            commentUserName.textContent = currentUser.name || 'User';
        }
    } else {
        // If user is not logged in, disable comments or show login prompt
        const commentInput = document.getElementById('commentInput');
        const commentBtn = document.getElementById('addCommentBtn');

        if (commentInput) {
            commentInput.placeholder = 'Please login to add a comment';
            commentInput.disabled = true;
            commentInput.style.opacity = '0.6';
        }

        if (commentBtn) {
            commentBtn.textContent = 'Login to Comment';
            commentBtn.onclick = function() {
                showLoginPrompt();
            };
        }
    }
}

// Add comment functionality
async function addComment() {
    // Check if user is logged in
    if (!isUserLoggedIn || !currentUser) {
        showLoginPrompt();
        return;
    }

    const commentInput = document.getElementById('commentInput');
    if (!commentInput) {
        console.error('Comment input not found');
        return;
    }

    const commentText = commentInput.value.trim();
    if (!commentText) {
        showSwal('Error', 'Please enter a comment', 'error');
        return;
    }

    // Get the current item ID from the modal context
    // This will depend on whether it's an action or resource
    if (!currentModalData) {
        console.error('No current modal data available');
        showSwal('Error', 'Unable to add comment to this item', 'error');
        return;
    }

    // Show loading state
    const commentBtn = document.getElementById('addCommentBtn');
    if (commentBtn) {
        commentBtn.disabled = true;
        commentBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i> Posting...';
        lucide.createIcons(); // Refresh icons
    }

    try {
        // Prepare comment data
        const commentData = {
            user_id: currentUser.id,
            content: commentText
        };

        // Set the appropriate item ID based on the type
        if (currentModalData.type === 'action') {
            commentData.action_id = currentModalData.id;
        } else if (currentModalData.type === 'resource') {
            commentData.resource_id = currentModalData.id;
        } else {
            throw new Error('Unknown item type');
        }

        const response = await fetch('../api/comments/add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(commentData)
        });

        const result = await response.json();

        if (result.success) {
            // Clear the input field
            commentInput.value = '';

            // Show success message
            showSwal('Success', 'Comment added successfully', 'success');

            // Reload comments to show the new one
            // This can be done by re-opening the modal or refreshing comments section
            if (typeof loadCommentsForItem === 'function') {
                loadCommentsForItem(currentModalData.id, currentModalData.type);
            } else {
                // If we don't have a specific function, just refresh the modal
                if (typeof openDetailsModal === 'function') {
                    setTimeout(() => {
                        openDetailsModal(currentModalData); // Re-open to refresh comments
                    }, 1000);
                }
            }
        } else {
            showSwal('Error', result.message || 'Failed to add comment', 'error');
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        showSwal('Error', 'Failed to add comment. Please try again.', 'error');
    } finally {
        // Reset button state
        if (commentBtn) {
            commentBtn.disabled = false;
            commentBtn.textContent = 'Post Comment';
        }
    }
}

// Fix 4: Fix report submission in detailed modal
function submitReport(event) {
    event.preventDefault();

    // Clear previous errors
    clearFieldError('reportCategory');
    clearFieldError('reportReason');

    // Validate required fields
    const isCategoryValid = validateFormField('reportCategory', 'Please select a category');
    const isReasonValid = validateFormField('reportReason', 'Please provide a reason');

    if (!isCategoryValid || !isReasonValid) {
        return;
    }

    const itemId = document.getElementById('reportItemId').value;
    const itemType = document.getElementById('reportItemType').value;
    const category = document.getElementById('reportCategory').value;
    const reason = document.getElementById('reportReason').value;

    const reportData = {
        reported_item_id: itemId,
        reported_item_type: itemType,
        report_category: category,
        report_reason: reason
    };

    // Show loading state
    const submitBtn = document.querySelector('#reportForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin mr-2"></i> Submitting...';
        lucide.createIcons(); // Refresh icons
    }

    fetch('../api/reports/create_report.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(reportData)
    })
    .then(async response => {
        // Check if response is ok first
        if (!response.ok) {
            // Even if not ok, try to get the response text to see if it's a JSON error
            const errorText = await response.text();
            try {
                const errorJson = JSON.parse(errorText);
                // If it's JSON, throw the error message from the JSON
                throw new Error(errorJson.message || `HTTP error! status: ${response.status}`);
            } catch (e) {
                // If it's not JSON, throw the text as error
                throw new Error(`Server error! status: ${response.status}, response: ${errorText.substring(0, 200)}`);
            }
        }

        // Try to parse JSON response
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Even if content type isn't JSON, try to parse the response
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Response is not valid JSON:', text);
                // Check if response looks like HTML error
                if (text.trim().startsWith('<!DOCTYPE') || text.trim().startsWith('<html')) {
                    throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
                }
                throw new Error('Server response error. Please try again.');
            }
        }
    })
    .then(data => {
        if (data.success) {
            showSwal('Success', data.message, 'success');
            closeReportModal();
            // Reset form after successful submission
            document.getElementById('reportForm').reset();
        } else {
            showSwal('Error', data.message || 'Failed to submit report', 'error');
        }
    })
    .catch(error => {
        console.error('Report submission error:', error);
        let errorMessage = 'Failed to submit report. Please try again.';

        if (error.message && error.message.includes('JSON')) {
            errorMessage = 'Server response error. Please try again.';
        } else if (error.message && error.message.includes('HTML instead of JSON')) {
            errorMessage = 'Server configuration error. Please contact support.';
        } else if (error.message) {
            errorMessage = error.message;
        } else if (error.toString().includes('HTTP error')) {
            errorMessage = `Server error: ${error}`;
        }

        showSwal('Error', errorMessage, 'error');
    })
    .finally(() => {
        // Reset button state
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Report';
        }
    });
}

// Fix 5: Change join action button to leave after joining - already handled in main script

// Enhanced toggleJoinAction function with proper state management - already exists in main script

// Function to update join/leave button state - already exists in main script

// Fix 6: Calendar functionality verification is handled in existing code

// Fix 7: Apply Tailwind theme to map - already implemented in map.js

// Function to be called when modal is opened to enhance comment section
function initializeCommentSection() {
    // Use a small delay to ensure currentUser is properly available
    setTimeout(() => {
        enhanceCommentSection();
    }, 100);
}

