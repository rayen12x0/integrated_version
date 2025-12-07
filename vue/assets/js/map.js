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
    const hasCoordinatesParam = params.get('hasCoordinates');
    const hasCoordinates = hasCoordinatesParam === 'true';

    // Check if we have at least some identifying parameters (coordinates, id, or title)
    if (!isNaN(lat) && !isNaN(lng)) {
        // We have coordinates
        return {
            lat,
            lng,
            title: title || 'Location from Globe',
            type: type || 'globe-location',
            id: id || null,
            country: country || '',
            hasCoordinates: true
        };
    } else if (id || title) {
        // We have at least an id or title, even without coordinates
        return {
            lat: lat && !isNaN(lat) ? lat : null,
            lng: lng && !isNaN(lng) ? lng : null,
            title: title || 'Location from Globe',
            type: type || 'globe-location',
            id: id || null,
            country: country || '',
            hasCoordinates: hasCoordinates
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

    const marker = L.marker([lat, lng], { icon: customIcon, zIndexOffset: 2000 }).addTo(window.map);

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

    if (modalImage) {
        // First try item.image, then item.image_url, then fallback to default SVG
        const imageUrl = item.image || item.image_url || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
        modalImage.src = imageUrl;

        // Add error handling for image loading
        modalImage.onerror = function() {
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
        };
    }
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

        // Add tile layer with grayscale style for template consistency
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Store map instance globally
        window.map = map;

        // Check for URL parameters to show a specific location
        const params = getURLParameters();
        if (params) {
            if (params.hasCoordinates && params.lat && params.lng) {
                // Show the specific location marker only if coordinates are available
                createCustomMarker(params.lat, params.lng, params.type, params.title, params.id, params.country);

                // Center the map on the specific location with higher zoom
                map.setView([params.lat, params.lng], 15);

                // Show SweetAlert confirmation that pin has been created
                const alertKey = `mapAlert_${params.id}_${params.type}`;
                const alertShown = sessionStorage.getItem(alertKey);

                if (!alertShown && typeof Swal !== 'undefined') {
                    setTimeout(() => {
                        Swal.fire({
                            title: 'Location Found!',
                            text: `A pin has been placed on the map for the ${params.type}: "${params.title}"`,
                            icon: 'success',
                            position: 'top-end',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false,
                            toast: true
                        });
                        sessionStorage.setItem(alertKey, 'true');
                    }, 1000); // Small delay to ensure map is loaded
                }
            } else {
                // If no coordinates but we have an ID or title, show the details modal instead
                // Create a minimal object to pass to the details modal
                const itemForModal = {
                    id: params.id,
                    title: params.title,
                    resource_name: params.title,
                    type: params.type,
                    country: params.country,
                    description: params.hasCoordinates ? 'Location details with coordinates' : 'Location details only (coordinates not available)',
                    location: params.country || 'Location details only',
                    latitude: params.lat,
                    longitude: params.lng
                };

                // Attempt to open the details modal
                setTimeout(() => {
                    if (typeof window.openDetailsModalFromGlobe === 'function') {
                        window.openDetailsModalFromGlobe(itemForModal);
                    } else if (typeof window.openDetailsModal === 'function') {
                        window.openDetailsModal(itemForModal);
                    } else {
                        console.warn('Could not open details modal - function not available');
                    }
                }, 500); // Small delay to ensure everything is loaded
            }
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

// Store markers in a global array for easy management
window.customMarkers = [];

// Function to add a marker to the existing map from external call
function addMarkerToMap(item, type) {
    // If map is not initialized, log an error and return
    if (!window.map) {
        console.error('Map not initialized, cannot add marker');
        return;
    }

    // Check if the item has coordinates before creating a marker
    if (!item.latitude || !item.longitude) {
        console.warn('Item has no coordinates, cannot create marker');
        return;
    }

    // Clear existing markers before adding a new one
    clearAllMarkers();

    // Create a custom marker for the item
    const marker = createCustomMarker(
        item.latitude,
        item.longitude,
        type || item.type || 'globe-location',
        item.title || item.resource_name || 'Untitled',
        item.id,
        item.country || ''
    );

    // Add marker to global array for future reference
    window.customMarkers.push(marker);

    // Center map on the new marker with zoom level 15
    window.map.setView([item.latitude, item.longitude], 15);

    // Open popup automatically
    setTimeout(() => {
        if (marker && marker.openPopup) {
            marker.openPopup();
        }
    }, 500);

    return marker;
}

// Function to clear all custom markers from the map
function clearAllMarkers() {
    if (!window.map) {
        console.error('Map not initialized, cannot clear markers');
        return;
    }

    // Remove all markers in the customMarkers array
    window.customMarkers.forEach(marker => {
        if (marker && window.map.hasLayer(marker)) {
            window.map.removeLayer(marker);
        }
    });

    // Clear the array
    window.customMarkers = [];
}

// Function to remove a specific marker by ID
function removeMarker(markerId) {
    if (!window.map) {
        console.error('Map not initialized, cannot remove marker');
        return;
    }

    // Find the marker in the array by matching associated data
    const markerIndex = window.customMarkers.findIndex(marker => {
        // This is a simple implementation - you may need to store ID in marker options for a more robust approach
        return marker.options && marker.options.id === markerId;
    });

    if (markerIndex !== -1) {
        const marker = window.customMarkers[markerIndex];

        // Remove marker from map if it exists and is still on the map
        if (window.map.hasLayer(marker)) {
            window.map.removeLayer(marker);
        }

        // Remove from the array
        window.customMarkers.splice(markerIndex, 1);
    }
}

// Update map with markers for actions and resources
function updateMap() {
    if (!window.map) {
        console.warn('Map not initialized, skipping update');
        return;
    }

    // Remove any existing markers except the custom ones (if any)
    window.map.eachLayer(layer => {
        if (layer instanceof L.Marker || layer instanceof L.CircleMarker) {
            window.map.removeLayer(layer);
        }
    });

    // Clear our custom markers array as we're updating the whole map
    window.customMarkers = [];

    const colors = { action: '#C3E6CB', resource: '#AEE1F9' };

    // Use filteredData if it exists and is not empty; otherwise fallback to all data
    const displayData = (window.filteredData && window.filteredData.length > 0)
        ? window.filteredData
        : [...(window.actionsData || []), ...(window.resourcesData || [])];

    // Add markers for each item
    displayData.forEach(item => {
        if (!item.latitude || !item.longitude) return;

        const marker = L.marker([item.latitude, item.longitude], {
            zIndexOffset: 2000  // Ensure markers appear above other map elements
        })
            .addTo(window.map);

        // Store marker reference in our array
        window.customMarkers.push(marker);

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

        marker.on('click', () => {
            if (typeof window.openDetailsModal === 'function') {
                window.openDetailsModal(item);
            } else {
                console.warn('openDetailsModal function not available');
            }
        });
    });
}

// Expose marker functions globally
window.addMarkerToMap = addMarkerToMap;
window.clearAllMarkers = clearAllMarkers;
window.removeMarker = removeMarker;

