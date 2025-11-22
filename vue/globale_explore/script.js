import * as THREE from "three";
import {OrbitControls} from "three/addons/controls/OrbitControls.js";
import {GUI} from "https://cdn.skypack.dev/lil-gui@0.17.0";

const containerEl = document.querySelector(".globe-wrapper");
const canvasEl = containerEl.querySelector("#globe-3d");
const svgMapDomEl = document.querySelector("#map");
const svgCountries = Array.from(svgMapDomEl.querySelectorAll("path"));
const svgCountryDomEl = document.querySelector("#country");
const countryNameEl = document.querySelector(".info span");

let renderer, scene, camera, rayCaster, pointer, controls;
let globeGroup, globeColorMesh, globeStrokesMesh, globeSelectionOuterMesh;

const svgViewBox = [2000, 1000];
const offsetY = -.1;

const params = {
    strokeColor: "#111111",
    defaultColor: "#9a9591",
    hoverColor: "#00C9A2",
    fogColor: "#e4e5e6",
    fogDistance: 2.6,
    strokeWidth: 2,
    hiResScalingFactor: 2,
    lowResScalingFactor: .7
}


let hoveredCountryIdx = 6;
let isTouchScreen = false;
let isHoverable = true;

const textureLoader = new THREE.TextureLoader();
let staticMapUri;
const bBoxes = [];
const dataUris = [];


initScene();
createControls();

window.addEventListener("resize", updateSize);


containerEl.addEventListener("touchstart", (e) => {
    isTouchScreen = true;
});
containerEl.addEventListener("mousemove", (e) => {
    updateMousePosition(e.clientX, e.clientY);
});
containerEl.addEventListener("click", (e) => {
    updateMousePosition(e.clientX, e.clientY);
    handleCountryClick();
});

// Add Missing updateMousePosition Function - account for container offset
function updateMousePosition(x, y) {
    const rect = containerEl.getBoundingClientRect();
    pointer.x = ((x - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((y - rect.top) / rect.height) * 2 + 1;
}



// Handle country click to show panel with actions/resources
function handleCountryClick() {
    // This function is safe to run after the existing functionality
    if (typeof isHoverable !== 'undefined' && typeof hoveredCountryIdx !== 'undefined' &&
        isHoverable && hoveredCountryIdx !== -1) {
        const countryName = svgCountries[hoveredCountryIdx].getAttribute("data-name");
        if (countryName) {
            loadCountryData(countryName);
        }
    }
}

// Load data for the selected country
async function loadCountryData(country) {
    try {
        // Show loading state
        showCountryPanel();
        showLoadingState();

        // Fetch data from both APIs
        const [actionsResponse, resourcesResponse] = await Promise.all([
            fetch(`../../api/get_actions_by_country.php?country=${encodeURIComponent(country)}`),
            fetch(`../../api/get_resources_by_country.php?country=${encodeURIComponent(country)}`)
        ]);

        const actionsData = await actionsResponse.json();
        const resourcesData = await resourcesResponse.json();

        // Render the data
        renderCountryData(country,
            actionsData.success ? actionsData.actions : [],
            resourcesData.success ? resourcesData.resources : []
        );
    } catch (error) {
        console.error('Error loading country data:', error);
        showErrorMessage('Failed to load data for ' + country);
    }
}

// Show the right-side panel
function showCountryPanel() {
    // Check if our panel already exists
    let panel = document.getElementById('countryPanel');
    if (panel) {
        panel.classList.remove('hidden');
    } else {
        // Create the panel if it doesn't exist
        createCountryPanel();
        // Now that the panel is created, retrieve it and remove the hidden class
        panel = document.getElementById('countryPanel');
        if (panel) {
            panel.classList.remove('hidden');
        }
    }
}

// Create the country panel dynamically
function createCountryPanel() {
    const panel = document.createElement('div');
    panel.id = 'countryPanel';
    panel.className = 'country-panel hidden';
    panel.innerHTML = `
        <div class="panel-header">
            <h2 id="panelCountryName">Country Name</h2>
            <button class="panel-close-btn" onclick="closeCountryPanel()">×</button>
        </div>
        <div class="panel-controls">
            <input type="text" id="searchInput" placeholder="Search actions/resources..." class="search-input">
            <select id="filterSelect" class="filter-select">
                <option value="all">All Types</option>
                <option value="action">Actions Only</option>
                <option value="resource">Resources Only</option>
            </select>
            <select id="sortBy" class="sort-select">
                <option value="date_desc">Date (Newest)</option>
                <option value="date_asc">Date (Oldest)</option>
                <option value="name">Name (A-Z)</option>
                <option value="category">Category</option>
            </select>
        </div>
        <div class="panel-stats">
            <div class="stat-item">
                <span class="stat-number" id="actionsCount">0</span>
                <span class="stat-label">Actions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="resourcesCount">0</span>
                <span class="stat-label">Resources</span>
            </div>
        </div>
        <div class="panel-content">
            <div class="section-header">Actions</div>
            <div id="actionsList" class="items-list"></div>
            <div class="section-header">Resources</div>
            <div id="resourcesList" class="items-list"></div>
        </div>
    `;
    document.body.appendChild(panel);

    // Add event listeners for search and filter
    setupPanelControls();
}

// Setup search and filter controls
function setupPanelControls() {
    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');
    const sortBy = document.getElementById('sortBy');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterAndSortItems, 300));
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', filterAndSortItems);
    }

    if (sortBy) {
        sortBy.addEventListener('change', filterAndSortItems);
    }
}

// Debounce function to limit search calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Apply filtering and sorting to the items
function filterAndSortItems() {
    const searchTerm = (document.getElementById('searchInput')?.value || '').toLowerCase();
    const filterValue = document.getElementById('filterSelect')?.value || 'all';
    const sortValue = document.getElementById('sortBy')?.value || 'date_desc';

    // Get the current data and filter/sort it
    const country = document.getElementById('panelCountryName').textContent;

    // For simplicity, we'll reload and immediately filter/sort
    // In a production environment, you might want to cache the data locally
    loadCountryData(country).then(() => {
        // Actually apply filtering after data is loaded
        applyFiltersAndSorting(searchTerm, filterValue, sortValue);
    });
}

// Apply filtering and sorting to existing data
function applyFiltersAndSorting(searchTerm, filterValue, sortValue) {
    // This would be called with actual data after it's loaded
    // Implementation would depend on how data is cached in the UI
}

// Show loading state in the panel
function showLoadingState() {
    document.getElementById('actionsList').innerHTML = '<div class="loading">Loading actions...</div>';
    document.getElementById('resourcesList').innerHTML = '<div class="loading">Loading resources...</div>';
}

// Show error message in the panel
function showErrorMessage(message) {
    document.getElementById('actionsList').innerHTML = `<div class="error">${message}</div>`;
    document.getElementById('resourcesList').innerHTML = `<div class="error">${message}</div>`;
}

// Render country data in the panel
function renderCountryData(country, actions, resources) {
    // Update panel header
    document.getElementById('panelCountryName').textContent = country;

    // Update statistics
    document.getElementById('actionsCount').textContent = actions.length;
    document.getElementById('resourcesCount').textContent = resources.length;

    // Render actions list
    const actionsList = document.getElementById('actionsList');
    if (actions.length > 0) {
        actionsList.innerHTML = '';
        actions.forEach(action => {
            const itemCard = document.createElement('div');
            itemCard.className = 'item-card';
            itemCard.innerHTML = `
                <h3>${action.title}</h3>
                <div class="location">📍 ${action.location || 'Location not specified'}</div>
                <div class="category">${action.category || 'Uncategorized'}</div>
                <div class="type-badge action-badge">Action</div>
            `;
            itemCard.setAttribute('data-type', 'action');
            itemCard.setAttribute('data-latitude', action.latitude || '');
            itemCard.setAttribute('data-longitude', action.longitude || '');
            itemCard.addEventListener('click', () => navigateToMap(action, 'action'));
            actionsList.appendChild(itemCard);
        });
    } else {
        actionsList.innerHTML = '<div class="no-items">No actions found</div>';
    }

    // Render resources list
    const resourcesList = document.getElementById('resourcesList');
    if (resources.length > 0) {
        resourcesList.innerHTML = '';
        resources.forEach(resource => {
            const itemCard = document.createElement('div');
            itemCard.className = 'item-card';
            itemCard.innerHTML = `
                <h3>${resource.resource_name}</h3>
                <div class="location">📍 ${resource.location || 'Location not specified'}</div>
                <div class="category">${resource.type} - ${resource.category}</div>
                <div class="type-badge resource-badge">Resource</div>
            `;
            itemCard.setAttribute('data-type', 'resource');
            itemCard.setAttribute('data-latitude', resource.latitude || '');
            itemCard.setAttribute('data-longitude', resource.longitude || '');
            itemCard.addEventListener('click', () => navigateToMap(resource, 'resource'));
            resourcesList.appendChild(itemCard);
        });
    } else {
        resourcesList.innerHTML = '<div class="no-items">No resources found</div>';
    }

    // Set up controls after data is loaded
    setupPanelControls();
}

// Close the country panel
function closeCountryPanel() {
    const panel = document.getElementById('countryPanel');
    if (panel) {
        panel.classList.add('hidden');
    }
}

// Navigate to the main map with the selected item's coordinates
function navigateToMap(item, type) {
    if (item.latitude && item.longitude) {
        // Set the panel state as open before navigating
        isPanelOpen = true;

        // Update auto-rotation based on panel state
        if (controls) {
            controls.autoRotate = autoRotation && !isPanelOpen;
        }

        // Construct URL with parameters - avoid double encoding by using raw values
        const params = new URLSearchParams({
            lat: item.latitude,
            lng: item.longitude,
            title: item.title || item.resource_name,
            type: type,
            id: item.id,
            country: item.country || ''
        }).toString();

        // Navigate to main page with parameters
        window.location.href = `../index.html?${params}`;
    } else {
        alert('This item does not have location coordinates available.');
    }
}

// Expose closeCountryPanel globally to make it accessible to the onclick handler in the HTML
window.closeCountryPanel = closeCountryPanel;



function initScene() {
    renderer = new THREE.WebGLRenderer({canvas: canvasEl, alpha: true});
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    scene = new THREE.Scene();
    scene.fog = new THREE.Fog(params.fogColor, 0, params.fogDistance);

    camera = new THREE.OrthographicCamera(-1.2, 1.2, 1.2, -1.2, 0, 3);
    camera.position.z = 1.3;

    globeGroup = new THREE.Group();
    scene.add(globeGroup);

    rayCaster = new THREE.Raycaster();
    rayCaster.far = 1.15;
    pointer = new THREE.Vector2(-1, -1);

    createOrbitControls();
    createGlobe();
    prepareHiResTextures();
    prepareLowResTextures();


    updateSize();

    gsap.ticker.add(render);
}


function createOrbitControls() {
    controls = new OrbitControls(camera, canvasEl);
    controls.enablePan = false;
    // controls.enableZoom = false;
    controls.enableDamping = true;
    controls.minPolarAngle = .46 * Math.PI;
    controls.maxPolarAngle = .46 * Math.PI;
    controls.autoRotate = true; // Default to auto-rotate enabled
    controls.autoRotateSpeed *= 1.2;

    controls.addEventListener("start", () => {
        isHoverable = false;
        // Disable auto-rotation when user starts interacting
        controls.autoRotate = false;
        pointer = new THREE.Vector2(-1, -1);
        gsap.to(globeGroup.scale, {
            duration: .3,
            x: .9,
            y: .9,
            z: .9,
            ease: "power1.inOut"
        })
    });
    controls.addEventListener("end", () => {
        // isHoverable = true;
        gsap.to(globeGroup.scale, {
            duration: .6,
            x: 1,
            y: 1,
            z: 1,
            ease: "back(1.7).out",
            onComplete: () => {
                isHoverable = true;
                // Re-enable auto-rotation when user stops interacting
                controls.autoRotate = true;
            }
        })
    });
}

function createGlobe() {
    const globeGeometry = new THREE.IcosahedronGeometry(1, 20);

    const globeColorMaterial = new THREE.MeshBasicMaterial({
        transparent: true,
        alphaTest: true,
        side: THREE.DoubleSide
    });
    const globeStrokeMaterial = new THREE.MeshBasicMaterial({
        transparent: true,
        depthTest: false,
    });
    const outerSelectionColorMaterial = new THREE.MeshBasicMaterial({
        transparent: true,
        side: THREE.DoubleSide
    });

    globeColorMesh = new THREE.Mesh(globeGeometry, globeColorMaterial);
    globeStrokesMesh = new THREE.Mesh(globeGeometry, globeStrokeMaterial);
    globeSelectionOuterMesh = new THREE.Mesh(globeGeometry, outerSelectionColorMaterial);

    globeStrokesMesh.renderOrder = 2;

    globeGroup.add(globeStrokesMesh, globeSelectionOuterMesh, globeColorMesh);
}

function setMapTexture(material, URI) {
    textureLoader.load(
        URI,
        (t) => {
            t.repeat.set(1, 1);
            material.map = t;
            material.needsUpdate = true;
        });
}

function prepareHiResTextures() {
    let svgData;
    gsap.set(svgMapDomEl, {
        attr: {
            "viewBox": "0 " + (offsetY * svgViewBox[1]) + " " + svgViewBox[0] + " " + svgViewBox[1],
            "stroke-width": params.strokeWidth,
            "stroke": params.strokeColor,
            "fill": params.defaultColor,
            "width": svgViewBox[0] * params.hiResScalingFactor,
            "height": svgViewBox[1] * params.hiResScalingFactor,
        }
    })
    svgData = new XMLSerializer().serializeToString(svgMapDomEl);
    staticMapUri = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svgData);
    setMapTexture(globeColorMesh.material, staticMapUri);

    gsap.set(svgMapDomEl, {
        attr: {
            "fill": "none",
            "stroke": params.strokeColor,
        }
    })
    svgData = new XMLSerializer().serializeToString(svgMapDomEl);
    staticMapUri = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svgData);
    setMapTexture(globeStrokesMesh.material, staticMapUri);
    countryNameEl.innerHTML = svgCountries[hoveredCountryIdx].getAttribute("data-name");

}

function prepareLowResTextures() {
    gsap.set(svgCountryDomEl, {
        attr: {
            "viewBox": "0 " + (offsetY * svgViewBox[1]) + " " + svgViewBox[0] + " " + svgViewBox[1],
            "stroke-width": params.strokeWidth,
            "stroke": params.strokeColor,
            "fill": params.hoverColor,
            "width": svgViewBox[0] * params.lowResScalingFactor,
            "height": svgViewBox[1] * params.lowResScalingFactor,
        }
    })
    svgCountries.forEach((path, idx) => {
        bBoxes[idx] = path.getBBox();
    })
    svgCountries.forEach((path, idx) => {
        svgCountryDomEl.innerHTML = "";
        svgCountryDomEl.appendChild(svgCountries[idx].cloneNode(true));
        const svgData = new XMLSerializer().serializeToString(svgCountryDomEl);
        dataUris[idx] = "data:image/svg+xml;charset=utf-8," + encodeURIComponent(svgData);
    })
    setMapTexture(globeSelectionOuterMesh.material, dataUris[hoveredCountryIdx]);

}

function updateMap(uv = {x: 0, y: 0}) {
    const pointObj = svgMapDomEl.createSVGPoint();
    pointObj.x = uv.x * svgViewBox[0];
    pointObj.y = (1 + offsetY - uv.y) * svgViewBox[1];

    for (let i = 0; i < svgCountries.length; i++) {
        const boundingBox = bBoxes[i];
        if (
            pointObj.x > boundingBox.x ||
            pointObj.x < boundingBox.x + boundingBox.width ||
            pointObj.y > boundingBox.y ||
            pointObj.y < boundingBox.y + boundingBox.height
        ) {
            const isHovering = svgCountries[i].isPointInFill(pointObj);
            if (isHovering) {
                if (i !== hoveredCountryIdx) {
                    hoveredCountryIdx = i;
                    setMapTexture(globeSelectionOuterMesh.material, dataUris[hoveredCountryIdx]);
                    countryNameEl.innerHTML = svgCountries[hoveredCountryIdx].getAttribute("data-name");
                    break;
                }
            }
        }
    }
}

function render() {
    controls.update();

    if (isHoverable) {
        rayCaster.setFromCamera(pointer, camera);
        const intersects = rayCaster.intersectObject(globeStrokesMesh);
        if (intersects.length) {
            updateMap(intersects[0].uv);
        }
    }

    if (isTouchScreen && isHoverable) {
        isHoverable = false;
    }

    renderer.render(scene, camera);
}

function updateSize() {
    const side = Math.min(500, Math.min(window.innerWidth, window.innerHeight) - 50);
    containerEl.style.width = side + "px";
    containerEl.style.height = side + "px";
    renderer.setSize(side, side);
}


function createControls() {
    const gui = new GUI();
	
	 gui.close();
	
    gui.addColor(params, "strokeColor")
        .onChange(prepareHiResTextures)
        .name("stroke")
    gui.addColor(params, "defaultColor")
        .onChange(prepareHiResTextures)
        .name("color")
    gui.addColor(params, "hoverColor")
        .onChange(prepareLowResTextures)
        .name("highlight")
    gui.addColor(params, "fogColor")
        .onChange(() => {
            scene.fog = new THREE.Fog(params.fogColor, 0, params.fogDistance);
        })
        .name("fog");
    gui.add(params, "fogDistance", 1, 4)
        .onChange(() => {
            scene.fog = new THREE.Fog(params.fogColor, 0, params.fogDistance);
        })
        .name("fog distance");
}

