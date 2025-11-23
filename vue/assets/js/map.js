// Check if Leaflet is available
if (typeof L === 'undefined') {
    console.error('CRITICAL: Leaflet library is not loaded! Map functionality will not work.');
}

// Lightweight map functionality for Connect for Peace platform (dashboard version)

// Check if we have location parameters in the URL
function getURLParameters() {
    const params = new URLSearchParams(window.location.search);
    const lat = parseFloat(params.get('lat'));
    const lng = parseFloat(params.get('lng'));
    const title = params.get('title');
    const type = params.get('type');
    const id = params.get('id');
    const country = params.get('country');

    // Check if we have at least coordinates
    if (!isNaN(lat) && !isNaN(lng)) {
        return {
            lat,
            lng,
            title: title || 'Location from Globe',
            type: type || 'globe-location',
            id: id || null,
            country: country || ''
        };
    }
    return null;
}

// Expose function globally to allow checking for URL parameters from script.js
window.getURLParameters = getURLParameters;

// Create custom marker based on type
function createCustomMarker(lat, lng, type, title, id, country) {
    // Different colors for actions vs resources vs general locations
    let markerColor, markerLabel;
    if (type === 'action') {
        markerColor = '#4CAF50'; // Green for actions
        markerLabel = 'A';
    } else if (type === 'resource') {
        markerColor = '#2196F3'; // Blue for resources
        markerLabel = 'R';
    } else {
        markerColor = '#FF9800'; // Orange for general globe locations
        markerLabel = '📍';
    }

    // Create a custom icon
    const customIcon = L.divIcon({
        className: 'custom-location-marker',
        html: `<div style="
            background-color: ${markerColor};
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            font-weight: bold;
            color: white;
            font-size: 16px;
            text-align: center;
        ">${markerLabel}</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });

    const marker = L.marker([lat, lng], { icon: customIcon }).addTo(window.map);

    // Create popup content
    const popupContent = `
        <div style="min-width: 200px;">
            <h4 style="margin-top: 0; color: ${markerColor};">${title}</h4>
            <p><strong>Type:</strong> ${type === 'action' ? 'Action' : type === 'resource' ? 'Resource' : 'Location'}</p>
            <p><strong>Country:</strong> ${country || 'N/A'}</p>
            <p><strong>Coordinates:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
            ${id ? `<button onclick="openDetailsModalFromGlobe(${JSON.stringify({id, title, type, country, latitude: lat, longitude: lng, location: `${lat.toFixed(6)}, ${lng.toFixed(6)}`, description: 'Location from globe navigation'}).replace(/"/g, '&quot;')})"
                    style="padding: 8px 12px; background: ${markerColor}; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                View Details
            </button>` : ''}
        </div>
    `;

    marker.bindPopup(popupContent);

    // Open the popup automatically
    setTimeout(() => {
        marker.openPopup();
    }, 500);

    return marker;
}

// Function to open details modal from the globe map marker
function openDetailsModalFromGlobe(item) {
    // First check if the item exists in our loaded data
    let foundItem = null;

    // Search in actions and resources
    if (window.actionsData) {
        foundItem = window.actionsData.find(x => x.id == item.id && x.type === item.type);
    }

    if (!foundItem && window.resourcesData) {
        foundItem = window.resourcesData.find(x => x.id == item.id && x.type === item.type);
    }

    // If found in local data, use that, otherwise use the item passed from the globe
    const modalItem = foundItem || item;

    // Set the current modal data to the item from the globe
    window.currentModalData = modalItem;

    // Delegate to the existing openDetailsModal function to ensure consistent behavior
    if (typeof window.openDetailsModal === 'function') {
        window.openDetailsModal(modalItem);
    } else {
        // Fallback to manual activation if the function is not available
        const modal = document.getElementById('detailsModal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Update modal content
            updateModalContent(modalItem);
        }
    }
}

// Expose function globally to make it accessible from marker popup onclick
window.openDetailsModalFromGlobe = openDetailsModalFromGlobe;

// Update modal content based on item
function updateModalContent(item) {
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');
    const modalLocation = document.getElementById('modalLocation');
    const modalBadge = document.getElementById('modalBadge');

    if (modalImage) modalImage.src = item.image || item.image_url || 'https://via.placeholder.com/400x200?text=Image+Not+Available';
    if (modalTitle) modalTitle.textContent = item.title || item.resource_name || 'Untitled';
    if (modalDescription) modalDescription.textContent = item.description || 'No description available';
    if (modalLocation) modalLocation.textContent = item.location || `${item.latitude}, ${item.longitude}`;
    if (modalBadge) modalBadge.textContent = item.type || 'Item';
}

// Expose function globally
window.updateModalContent = updateModalContent;

// Initialize map with basic functionality
function initMap() {
    if (!window.L) {
        console.error('Leaflet library not loaded');
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.innerHTML = '<p style="color: red; text-align: center; padding: 20px;">Map library failed to load. Please refresh the page.</p>';
        }
        return;
    }

    try {
        // Initialize the map
        const map = L.map('map').setView([48.8566, 2.3522], 12);

        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Store map instance globally
        window.map = map;

        // Check for URL parameters to show a specific location
        const params = getURLParameters();
        if (params) {
            // Show the specific location marker
            createCustomMarker(params.lat, params.lng, params.type, params.title, params.id, params.country);

            // Center the map on the specific location with higher zoom
            map.setView([params.lat, params.lng], 15);
        }

        return map;
    } catch (error) {
        console.error('Error initializing map:', error);
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.innerHTML = '<p style="color: red; text-align: center; padding: 20px;">Failed to initialize map: ' + error.message + '</p>';
        }
        return null;
    }
}

// Expose functions globally
window.createCustomMarker = createCustomMarker;
window.initMapWithParams = initMap;

// Update map with markers for actions and resources
function updateMap() {
    if (!window.map) {
        console.warn('Map not initialized, skipping update');
        return;
    }

    // Remove any existing markers
    window.map.eachLayer(layer => {
        if (layer instanceof L.Marker || layer instanceof L.CircleMarker) {
            window.map.removeLayer(layer);
        }
    });

    const colors = { action: '#C3E6CB', resource: '#AEE1F9' };

    // Use filteredData if it exists and is not empty; otherwise fallback to all data
    const displayData = (window.filteredData && window.filteredData.length > 0)
        ? window.filteredData
        : [...(window.actionsData || []), ...(window.resourcesData || [])];

    // Add markers for each item
    displayData.forEach(item => {
        if (!item.latitude || !item.longitude) return;

        const marker = L.marker([item.latitude, item.longitude])
            .addTo(window.map);

        // Construct location display based on new structure
        let locationDisplay = item.location || 'Location not specified';

        // If we have separate country and location details, combine them
        if (item.country && item.location_details) {
            locationDisplay = `${item.location_details}, ${item.country}`;
        } else if (item.country && !item.location_details) {
            locationDisplay = item.country;
        } else if (item.location_details && !item.country) {
            locationDisplay = item.location_details;
        }

        marker.bindPopup(`
            <div style="min-width: 200px;">
                <h4>${item.title || item.resource_name || 'Untitled'}</h4>
                <p>${locationDisplay}</p>
                <button onclick="openDetailsModal(${JSON.stringify(item).replace(/"/g, '&quot;')})"
                        style="padding: 6px 12px; margin-top: 8px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    View Details
                </button>
            </div>
        `);
    });
}