<?php
session_start();
require_once __DIR__ . '/../../utils/AuthHelper.php';

// Require admin access
if (!AuthHelper::isAdmin()) {
    header("Location: ../index.html?error=Access denied");
    exit();
}

// Get current user
$currentUser = AuthHelper::getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories - Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Dependencies -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Functional overrides */
        .page { display: none; }
        .page.active { display: block; animation: fadeIn 0.3s ease-out; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-pending { background-color: #fbbf24; color: #92400e; }
        .status-approved { background-color: #6ee7b7; color: #166534; }
        .status-rejected { background-color: #fca5a5; color: #991b1b; }
        .status-draft { background-color: #d1d5db; color: #374151; }
        .status-published { background-color: #60a5fa; color: #1e40af; }
    </style>
</head>

<body class="bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-400 font-sans antialiased selection:bg-indigo-500/30 selection:text-indigo-600 dark:selection:text-indigo-200 overflow-hidden transition-colors duration-300">
    <div class="flex h-screen w-full">
        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative transition-colors duration-300" id="mainContent">

            <!-- Stories Page Content -->
            <div id="stories" class="page active space-y-8 p-6 lg:p-8">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">Stories</h2>
                        <p class="text-sm text-zinc-500 mt-1">Manage user stories and content</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="createStoryBtn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> New Story
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-zinc-900/20 backdrop-blur-sm border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 hover:border-indigo-500/30 transition-colors shadow-sm dark:shadow-none">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wider">Total Stories</p>
                                <h3 class="text-2xl font-medium text-zinc-900 dark:text-zinc-100 mt-2 tracking-tight" id="totalStoriesCount">0</h3>
                            </div>
                            <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg border border-indigo-500/20">
                                <i data-lucide="file-text" class="stroke-[1.5] w-5 h-5"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900/20 backdrop-blur-sm border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 hover:border-emerald-500/30 transition-colors shadow-sm dark:shadow-none">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wider">Pending</p>
                                <h3 class="text-2xl font-medium text-zinc-900 dark:text-zinc-100 mt-2 tracking-tight" id="pendingStoriesCount">0</h3>
                            </div>
                            <div class="p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg border border-amber-500/20">
                                <i data-lucide="clock" class="stroke-[1.5] w-5 h-5"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900/20 backdrop-blur-sm border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-5 hover:border-purple-500/30 transition-colors shadow-sm dark:shadow-none">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wider">Approved</p>
                                <h3 class="text-2xl font-medium text-zinc-900 dark:text-zinc-100 mt-2 tracking-tight" id="approvedStoriesCount">0</h3>
                            </div>
                            <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg border border-emerald-500/20">
                                <i data-lucide="check-circle" class="stroke-[1.5] w-5 h-5"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stories Table -->
                <div class="bg-white dark:bg-zinc-900/20 backdrop-blur-sm border border-zinc-200 dark:border-zinc-800/60 rounded-xl p-6 shadow-sm dark:shadow-none">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-200">All Stories</h3>
                        <div class="flex gap-3">
                            <select id="statusFilter" class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <input type="text" id="searchInput" placeholder="Search stories..." class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Title</th>
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Author</th>
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Theme</th>
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Date</th>
                                    <th class="pb-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="storiesTableBody">
                                <!-- Stories will be loaded here by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="loadingSpinner" class="flex justify-center py-8 hidden">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>
                    
                    <div id="noStoriesMessage" class="text-center py-8 text-zinc-500 dark:text-zinc-400 hidden">
                        No stories found
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Create/Edit Story Modal -->
    <div id="storyModal" class="fixed inset-0 z-[2000] flex items-center justify-center bg-zinc-900/40 backdrop-blur-sm p-4 hidden">
        <div class="modal-content relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-zinc-900" id="modalTitle">Create New Story</h2>
                <button class="text-zinc-500 hover:text-zinc-900" id="closeStoryModal">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="storyForm" class="space-y-4">
                    <input type="hidden" id="storyId">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Title</label>
                        <input type="text" id="storyTitle" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500" placeholder="Enter story title...">
                        <small class="error-message text-red-500 text-xs mt-1 hidden" id="storyTitle-error"></small>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Author Name</label>
                        <input type="text" id="authorName" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500" placeholder="Enter author name...">
                        <small class="error-message text-red-500 text-xs mt-1 hidden" id="authorName-error"></small>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Theme</label>
                        <select id="storyTheme" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm bg-white">
                            <option value="">Select Theme</option>
                            <option value="Personal">Personal</option>
                            <option value="Community">Community</option>
                            <option value="Inspirational">Inspirational</option>
                            <option value="Educational">Educational</option>
                            <option value="Social Help">Social Help</option>
                            <option value="Environment">Environment</option>
                            <option value="Art & Culture">Art & Culture</option>
                        </select>
                        <small class="error-message text-red-500 text-xs mt-1 hidden" id="storyTheme-error"></small>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Status</label>
                        <select id="storyStatus" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm bg-white">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Content</label>
                        <textarea id="storyContent" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500" placeholder="Write your story content..." rows="6"></textarea>
                        <small class="error-message text-red-500 text-xs mt-1 hidden" id="storyContent-error"></small>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-500">Upload Image</label>
                        <input type="file" id="storyImage" class="w-full text-xs text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200" name="image" accept="image/*">
                        <div id="story-num-of-files" class="text-xs mt-1 text-zinc-400">No Files Chosen</div>
                        <ul id="story-files-list"></ul>
                    </div>
                    <div class="flex gap-2 justify-end pt-4">
                        <button type="button" class="btn btn-outline px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 rounded-lg" id="cancelStoryBtn">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 py-2 text-sm font-medium text-white bg-zinc-900 hover:bg-zinc-800 rounded-lg">Save Story</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize icons
        lucide.createIcons();

        // DOM elements
        const storiesTableBody = document.getElementById('storiesTableBody');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const noStoriesMessage = document.getElementById('noStoriesMessage');
        const createStoryBtn = document.getElementById('createStoryBtn');
        const storyModal = document.getElementById('storyModal');
        const closeStoryModal = document.getElementById('closeStoryModal');
        const cancelStoryBtn = document.getElementById('cancelStoryBtn');
        const storyForm = document.getElementById('storyForm');
        const modalTitle = document.getElementById('modalTitle');
        const statusFilter = document.getElementById('statusFilter');
        const searchInput = document.getElementById('searchInput');

        // Event listeners
        createStoryBtn.addEventListener('click', () => {
            document.getElementById('storyId').value = '';
            document.getElementById('storyTitle').value = '';
            document.getElementById('authorName').value = '';
            document.getElementById('storyTheme').value = '';
            document.getElementById('storyStatus').value = 'pending';
            document.getElementById('storyContent').value = '';
            document.getElementById('storyImage').value = '';
            modalTitle.textContent = 'Create New Story';
            storyModal.classList.remove('hidden');
        });

        closeStoryModal.addEventListener('click', closeModal);
        cancelStoryBtn.addEventListener('click', closeModal);

        statusFilter.addEventListener('change', loadStories);
        searchInput.addEventListener('input', loadStories);

        storyForm.addEventListener('submit', handleStoryFormSubmit);

        // Close modal when clicking outside
        storyModal.addEventListener('click', (e) => {
            if (e.target === storyModal) {
                closeModal();
            }
        });

        // Load stories on page load
        document.addEventListener('DOMContentLoaded', loadStories);

        // Function to close modal
        function closeModal() {
            storyModal.classList.add('hidden');
            clearFormErrors();
        }

        // Function to clear form errors
        function clearFormErrors() {
            const errorElements = document.querySelectorAll('.error-message');
            errorElements.forEach(el => {
                el.classList.add('hidden');
                el.textContent = '';
            });
        }

        // Function to load stories
        async function loadStories() {
            try {
                showLoading(true);
                
                const status = statusFilter.value;
                const search = searchInput.value.trim();
                
                let url = '../../api/stories/get_stories.php';
                const params = new URLSearchParams();
                
                if (status) params.append('status', status);
                if (search) params.append('search', search);
                
                if (params.toString()) {
                    url += '?' + params.toString();
                }
                
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    displayStories(result.data || []);
                    updateStats(result.data || []);
                } else {
                    console.error('Error loading stories:', result.message);
                    displayStories([]);
                }
            } catch (error) {
                console.error('Error loading stories:', error);
                displayStories([]);
            } finally {
                showLoading(false);
            }
        }

        // Function to show/hide loading spinner
        function showLoading(show) {
            if (show) {
                loadingSpinner.classList.remove('hidden');
                storiesTableBody.innerHTML = '';
                noStoriesMessage.classList.add('hidden');
            } else {
                loadingSpinner.classList.add('hidden');
            }
        }

        // Function to display stories in table
        function displayStories(stories) {
            storiesTableBody.innerHTML = '';
            
            if (stories.length === 0) {
                noStoriesMessage.classList.remove('hidden');
                return;
            }
            
            noStoriesMessage.classList.add('hidden');
            
            stories.forEach(story => {
                const row = document.createElement('tr');
                row.className = 'border-b border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50';
                
                const statusClass = `status-${story.status.toLowerCase()}`;
                const statusText = story.status.charAt(0).toUpperCase() + story.status.slice(1);
                
                // Format date
                const date = new Date(story.created_at);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                
                row.innerHTML = `
                    <td class="py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100 max-w-xs truncate" title="${story.title}">
                        ${story.title}
                    </td>
                    <td class="py-4 text-sm text-zinc-600 dark:text-zinc-300">
                        ${story.author_name || story.creator?.name || 'N/A'}
                    </td>
                    <td class="py-4 text-sm text-zinc-600 dark:text-zinc-300">
                        ${story.theme || 'N/A'}
                    </td>
                    <td class="py-4">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td class="py-4 text-sm text-zinc-600 dark:text-zinc-300">
                        ${formattedDate}
                    </td>
                    <td class="py-4 text-sm font-medium">
                        <button onclick="editStory(${story.id})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                        <button onclick="deleteStory(${story.id}, '${story.title}')" class="text-rose-600 hover:text-rose-900">Delete</button>
                        ${story.status === 'pending' ? `<button onclick="approveStory(${story.id}, '${story.title}', 'approve')" class="text-emerald-600 hover:text-emerald-900 ml-3">Approve</button>
                        <button onclick="approveStory(${story.id}, '${story.title}', 'reject')" class="text-amber-600 hover:text-amber-900 ml-3">Reject</button>` : ''}
                    </td>
                `;
                
                storiesTableBody.appendChild(row);
            });
        }

        // Function to update stats
        function updateStats(stories) {
            document.getElementById('totalStoriesCount').textContent = stories.length;
            
            const pendingCount = stories.filter(s => s.status === 'pending').length;
            const approvedCount = stories.filter(s => s.status === 'approved').length;
            
            document.getElementById('pendingStoriesCount').textContent = pendingCount;
            document.getElementById('approvedStoriesCount').textContent = approvedCount;
        }

        // Function to handle story form submission
        async function handleStoryFormSubmit(e) {
            e.preventDefault();
            
            const storyId = document.getElementById('storyId').value;
            const isEdit = !!storyId;
            
            const formData = new FormData();
            formData.append('title', document.getElementById('storyTitle').value);
            formData.append('author_name', document.getElementById('authorName').value);
            formData.append('theme', document.getElementById('storyTheme').value);
            formData.append('status', document.getElementById('storyStatus').value);
            formData.append('content', document.getElementById('storyContent').value);
            
            const imageFile = document.getElementById('storyImage').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }
            
            try {
                const url = isEdit 
                    ? '../../api/stories/update_story.php' 
                    : '../../api/stories/create_story.php';
                
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    loadStories();
                    alert(isEdit ? 'Story updated successfully!' : 'Story created successfully!');
                } else {
                    console.error('Error:', result.message);
                    alert('Error: ' + result.message);
                    displayFormErrors(result.errors || {});
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                alert('Error submitting form: ' + error.message);
            }
        }

        // Function to display form errors
        function displayFormErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorElement = document.getElementById(field + '-error');
                if (errorElement) {
                    errorElement.textContent = errors[field];
                    errorElement.classList.remove('hidden');
                }
            });
        }

        // Function to edit a story
        async function editStory(id) {
            try {
                const response = await fetch(`../../api/stories/get_story.php?id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const story = result.data;
                    
                    document.getElementById('storyId').value = story.id;
                    document.getElementById('storyTitle').value = story.title;
                    document.getElementById('authorName').value = story.author_name;
                    document.getElementById('storyTheme').value = story.theme || '';
                    document.getElementById('storyStatus').value = story.status;
                    document.getElementById('storyContent').value = story.content;
                    
                    modalTitle.textContent = 'Edit Story';
                    storyModal.classList.remove('hidden');
                } else {
                    alert('Error: ' + (result.message || 'Story not found'));
                }
            } catch (error) {
                console.error('Error fetching story:', error);
                alert('Error fetching story: ' + error.message);
            }
        }

        // Function to delete a story
        async function deleteStory(id, title) {
            if (!confirm(`Are you sure you want to delete the story "${title}"?`)) {
                return;
            }
            
            try {
                const response = await fetch('../../api/stories/delete_story.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadStories();
                    alert('Story deleted successfully!');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error deleting story:', error);
                alert('Error deleting story: ' + error.message);
            }
        }

        // Function to approve/reject a story
        async function approveStory(id, title, action) {
            if (!confirm(`Are you sure you want to ${action} the story "${title}"?`)) {
                return;
            }
            
            try {
                const response = await fetch('../../api/stories/approve_story.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        id: id,
                        action: action
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadStories();
                    alert(`Story ${action}ed successfully!`);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error(`Error ${action}ing story:`, error);
                alert(`Error ${action}ing story: ` + error.message);
            }
        }

        // Make functions available globally for inline onclick handlers
        window.editStory = editStory;
        window.deleteStory = deleteStory;
        window.approveStory = approveStory;
    </script>
</body>
</html>