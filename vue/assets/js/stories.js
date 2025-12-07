// stories.js - Handles story logic and AI features

// Initialize variables
let storiesData = [];
let filteredStories = [];
let displayedStoryCount = 6;
let currentUser = null;
let isUserLoggedIn = false;
let currentStoryId = null;

// DOM Elements
let storiesContainer;
let createStoryBtn;
let storyDetailsModal;
let createStoryModal;
let storyForm;
let storiesThemeFilter;
let storiesLanguageFilter;
let storiesSearchInput;
let loadMoreStoriesBtn;
let addCommentBtn;
let reportModal;
let reportForm;

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    console.log('Stories page initializing...');

    // Initialize DOM Elements
    storiesContainer = document.getElementById('storiesContainer');
    createStoryBtn = document.getElementById('createStoryBtn');
    storyDetailsModal = document.getElementById('storyDetailsModal');
    createStoryModal = document.getElementById('createStoryModal');
    storyForm = document.getElementById('storyForm');
    storiesThemeFilter = document.getElementById('storiesThemeFilter');
    storiesLanguageFilter = document.getElementById('storiesLanguageFilter');
    storiesSearchInput = document.getElementById('storiesSearchInput');
    loadMoreStoriesBtn = document.getElementById('loadMoreStoriesBtn');
    addCommentBtn = document.getElementById('addStoryCommentBtn');

    // Event Listeners
    if (createStoryBtn) {
        createStoryBtn.addEventListener('click', openCreateStoryModal);
        console.log('✓ Create story button listener added');
    }
    if (storyForm) storyForm.addEventListener('submit', handleStoryFormSubmit);
    if (storiesThemeFilter) storiesThemeFilter.addEventListener('change', filterStories);
    if (storiesLanguageFilter) storiesLanguageFilter.addEventListener('change', filterStories);
    if (storiesSearchInput) storiesSearchInput.addEventListener('input', filterStories);
    if (loadMoreStoriesBtn) loadMoreStoriesBtn.addEventListener('click', loadMoreStories);
    if (addCommentBtn) addCommentBtn.addEventListener('click', handlePostComment);

    checkAuthStatus();
    loadStories();

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Setup reaction buttons
    setupModalReactionButtons();

    // Initialize character counters
    initializeCharacterCounters();

    // Initialize image preview
    initializeImagePreview();
});

// Check authentication
async function checkAuthStatus() {
    try {
        const response = await fetch("../api/users/check_auth.php");
        const result = await response.json();
        if (result.authenticated) {
            isUserLoggedIn = true;
            currentUser = result.user;
            console.log('User authenticated:', currentUser);
        } else {
            isUserLoggedIn = false;
            currentUser = null;
        }
    } catch (error) {
        console.error("Failed to check auth:", error);
        isUserLoggedIn = false;
        currentUser = null;
    }
}

// Load stories
async function loadStories() {
    console.log('Loading stories...');
    try {
        const response = await fetch("../api/stories/get_stories.php");
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const result = await response.json();
        console.log('Stories API response:', result);

        if (result.success) {
            storiesData = result.stories.map(story => ({
                ...story,
                type: 'story',
                title: story.title || 'Untitled Story',
                content: story.content || 'No content',
                author_name: story.author_name || 'Anonymous',
                theme: story.theme || 'Story',
                created_at: story.created_at || new Date().toISOString()
            }));

            console.log(`Loaded ${storiesData.length} stories`);
            filterStories();
        } else {
            console.error('API error:', result.message);
            if (storiesContainer) {
                storiesContainer.innerHTML = `<p class="text-center text-red-600 py-8">Failed to load stories: ${result.message}</p>`;
            }
        }
    } catch (error) {
        console.error("Failed to load stories:", error);
        if (storiesContainer) {
            storiesContainer.innerHTML = `<p class="text-center text-red-600 py-8">Failed to load stories: ${error.message}<br><small>Make sure XAMPP is running</small></p>`;
        }
    }
}

// Filter stories
function filterStories() {
    const theme = storiesThemeFilter ? storiesThemeFilter.value : '';
    const language = storiesLanguageFilter ? storiesLanguageFilter.value : '';
    const search = storiesSearchInput ? storiesSearchInput.value.toLowerCase() : '';

    filteredStories = storiesData.filter(story => {
        const matchesTheme = !theme || story.theme === theme;
        const matchesLanguage = !language || story.language === language;
        const matchesSearch = !search ||
            story.title.toLowerCase().includes(search) ||
            story.content.toLowerCase().includes(search) ||
            story.author_name.toLowerCase().includes(search);
        return matchesTheme && matchesLanguage && matchesSearch;
    });

    displayedStoryCount = 6;
    renderStories();
}

// Render stories
function renderStories() {
    if (!storiesContainer) return;

    storiesContainer.innerHTML = '';
    const storiesToDisplay = filteredStories.slice(0, displayedStoryCount);

    if (storiesToDisplay.length === 0) {
        storiesContainer.innerHTML = '<p class="text-center col-span-full py-8 text-zinc-500">No stories found</p>';
        if (loadMoreStoriesBtn) loadMoreStoriesBtn.classList.add('hidden');
        return;
    }

    storiesToDisplay.forEach(story => {
        const card = createStoryCard(story);
        storiesContainer.appendChild(card);
    });

    updateLoadMoreButton();

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Create story card
function createStoryCard(story) {
    const card = document.createElement('div');
    card.className = 'story-card group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-xl hover:-translate-y-1 cursor-pointer';
    card.onclick = () => openStoryDetails(story);

    const date = new Date(story.created_at);
    const formattedDate = date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });

    const reactions = story.reaction_counts || { heart: 0, support: 0, inspiration: 0, solidarity: 0 };

    card.innerHTML = `
        <div class="relative">
            <img src="${story.image_url || 'https://placehold.co/400x200?text=Story+Image'}" alt="${story.title}" class="h-48 w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            <div class="absolute bottom-0 left-0 w-full p-4">
                <span class="inline-block rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white backdrop-blur-md mb-2">
                    ${story.theme || 'Story'}
                </span>
                <h3 class="text-lg font-semibold text-white">${story.title}</h3>
                <p class="text-sm text-zinc-200 mt-1">${story.author_name || 'Anonymous'}</p>
            </div>
        </div>
        <div class="p-4 flex-1 flex flex-col">
            <p class="text-sm text-zinc-600 line-clamp-3 mb-4">${story.excerpt || (story.content && story.content.length > 100 ? story.content.substring(0, 100) + '...' : story.content) || 'No content'}</p>
            <div class="mt-auto pt-4 flex justify-between items-center">
                <span class="text-sm text-zinc-500">${formattedDate}</span>
                <div class="flex items-center gap-2">
                    <button class="reaction-btn" data-story-id="${story.id}" data-reaction="heart" title="Love">
                        <span class="text-red-500">❤️</span> <span class="reaction-count">${reactions.heart}</span>
                    </button>
                    <button class="reaction-btn" data-story-id="${story.id}" data-reaction="support" title="Support">
                        <span class="text-blue-500">👍</span> <span class="reaction-count">${reactions.support}</span>
                    </button>
                    <button class="reaction-btn" data-story-id="${story.id}" data-reaction="inspiration" title="Inspiration">
                        <span class="text-yellow-500">💡</span> <span class="reaction-count">${reactions.inspiration}</span>
                    </button>
                    <button class="reaction-btn" data-story-id="${story.id}" data-reaction="solidarity" title="Solidarity">
                        <span class="text-green-500">🤝</span> <span class="reaction-count">${reactions.solidarity}</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    // Add reaction button listeners
    const reactionButtons = card.querySelectorAll('.reaction-btn');
    reactionButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            handleStoryReaction(this);
        });
    });

    return card;
}

// Open story details
function openStoryDetails(story) {
    console.log('Opening story details for:', story);
    currentStoryId = story.id;

    document.getElementById('storyModalTitle').textContent = story.title;
    document.getElementById('storyModalContent').textContent = story.content;
    document.getElementById('storyModalAuthor').textContent = story.author_name || 'Anonymous';
    document.getElementById('storyModalTheme').textContent = story.theme || 'Story';

    const avatarElement = document.getElementById('storyModalAvatar');
    const authorName = story.author_name || 'Anonymous';
    avatarElement.textContent = (authorName.charAt(0) || 'A').toUpperCase();

    const date = new Date(story.created_at);
    const formattedDate = date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('storyModalDate').textContent = formattedDate;

    document.getElementById('storyModalImage').src = story.image_url || 'https://placehold.co/400x200?text=Story+Image';

    const reactions = story.reaction_counts || { heart: 0, support: 0, inspiration: 0, solidarity: 0 };
    document.getElementById('heartCount').textContent = reactions.heart;
    document.getElementById('supportCount').textContent = reactions.support;
    document.getElementById('inspirationCount').textContent = reactions.inspiration;
    document.getElementById('solidarityCount').textContent = reactions.solidarity;

    // Set data attributes and active states for reaction buttons in modal
    const reactionBtns = document.querySelectorAll('.reaction-btn-small');
    const userReactions = story.user_reactions || [];
    reactionBtns.forEach(btn => {
        btn.setAttribute('data-story-id', story.id);
        const reactionType = btn.getAttribute('data-reaction');
        if (userReactions.includes(reactionType)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Add edit/delete buttons if user is the creator of the story
    const actionsDiv = document.getElementById('storyModalActions');
    if (currentUser && story.creator_id == currentUser.id) {
        actionsDiv.innerHTML = `
            <button onclick="editStory(${story.id})" class="btn btn-outline">
                <i data-lucide="edit"></i> Edit
            </button>
            <button onclick="deleteStory(${story.id})" class="btn btn-outline text-red-600">
                <i data-lucide="trash"></i> Delete
            </button>
        `;
    } else {
        actionsDiv.innerHTML = ''; // Clear any existing buttons
    }

    if (storyDetailsModal) {
        storyDetailsModal.classList.remove('hidden');
        storyDetailsModal.classList.add('active');
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Update comment user avatar and name
    if (currentUser) {
        const commentUserAvatar = document.getElementById('commentUserAvatar');
        const commentUserName = document.getElementById('commentUserName');
        if (commentUserAvatar) {
            const userInitial = (currentUser.name || 'U').charAt(0).toUpperCase();
            commentUserAvatar.textContent = userInitial;
        }
        if (commentUserName) {
            commentUserName.textContent = currentUser.name || 'User';
        }
    } else {
        // Set to default values if not logged in
        const commentUserAvatar = document.getElementById('commentUserAvatar');
        const commentUserName = document.getElementById('commentUserName');
        if (commentUserAvatar) commentUserAvatar.textContent = 'U';
        if (commentUserName) commentUserName.textContent = 'User';
    }

    loadStoryComments(story.id);
}

// Close story details modal
function closeStoryDetailsModal() {
    if (storyDetailsModal) {
        storyDetailsModal.classList.remove('active');
        storyDetailsModal.classList.add('hidden');
        currentStoryId = null;
    }
}

// Setup modal reaction buttons
function setupModalReactionButtons() {
    const modalReactionBtns = document.querySelectorAll('.reaction-btn-small');
    modalReactionBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            handleStoryReaction(this);
        });
    });
}

// Handle story reaction with toggle behavior
async function handleStoryReaction(button) {
    const storyId = button.getAttribute('data-story-id');
    const reactionType = button.getAttribute('data-reaction');

    if (!isUserLoggedIn) {
        alert('Please log in to react to stories');
        return;
    }

    // Disable button to prevent double-clicks
    button.disabled = true;

    // Get current state
    const countSpan = button.querySelector('.reaction-count');
    const originalCount = parseInt(countSpan.textContent) || 0;
    const wasActive = button.classList.contains('active');

    // Optimistic UI update - toggle
    const isAdding = !wasActive;
    countSpan.textContent = isAdding ? originalCount + 1 : Math.max(0, originalCount - 1);
    button.classList.toggle('active');

    try {
        const response = await fetch('../api/reactions/add_story_reaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                story_id: storyId,
                reaction_type: reactionType
            })
        });

        const result = await response.json();

        if (result.success) {
            // Update all buttons with server data
            if (result.reaction_counts && result.user_reactions) {
                updateAllReactionButtons(storyId, result.reaction_counts, result.user_reactions);
            }
            showReactionFeedback(button, result.action === 'added');
        } else {
            // Rollback on error
            countSpan.textContent = originalCount;
            if (wasActive) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
            alert('Error: ' + (result.message || 'Failed to react'));
        }
    } catch (error) {
        console.error('Error handling reaction:', error);
        // Rollback on error
        countSpan.textContent = originalCount;
        if (wasActive) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
        alert('Failed to react. Please try again.');
    } finally {
        button.disabled = false;
    }
}

// Update all reaction buttons for a story
function updateAllReactionButtons(storyId, reactionCounts, userReactions = []) {
    const allButtons = document.querySelectorAll(`[data-story-id="${storyId}"]`);
    allButtons.forEach(btn => {
        const reactionType = btn.getAttribute('data-reaction');
        if (!reactionType) return;

        const countSpan = btn.querySelector('.reaction-count');
        if (countSpan && reactionCounts[reactionType] !== undefined) {
            countSpan.textContent = reactionCounts[reactionType];
        }

        // Update active state based on user's reactions
        if (userReactions.includes(reactionType)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// Show reaction feedback
function showReactionFeedback(button, added) {
    const emoji = button.textContent.trim().split(' ')[0];
    const feedback = document.createElement('span');
    feedback.textContent = emoji;
    feedback.style.cssText = 'position:absolute;font-size:24px;animation:floatUp 1s ease-out forwards;pointer-events:none;';
    feedback.className = 'reaction-feedback';

    const rect = button.getBoundingClientRect();
    feedback.style.left = rect.left + 'px';
    feedback.style.top = rect.top + 'px';

    document.body.appendChild(feedback);
    setTimeout(() => feedback.remove(), 1000);
}

// Load story comments
// Render comments with enhanced styling (matching Actions pattern)
function renderComments(comments, container) {
    if (!container) return;

    container.innerHTML = '';

    if (comments.length === 0) {
        container.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No comments yet. Be the first to comment!</p>';
        return;
    }

    const commentsList = document.createElement('div');
    commentsList.className = 'space-y-4';

    comments.forEach(comment => {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment bg-zinc-50 dark:bg-zinc-800/30 rounded-lg p-3 border border-zinc-100 dark:border-zinc-700/50';
        commentElement.id = `comment-${comment.id}`;

        // Generate avatar with first letter of username
        const avatarLetter = (comment.user_name || 'A').charAt(0).toUpperCase();
        const avatarColors = ['from-indigo-500 to-purple-500', 'from-emerald-500 to-teal-500', 'from-rose-500 to-pink-500', 'from-amber-500 to-orange-500'];
        const avatarColor = avatarColors[comment.id % avatarColors.length];

        // Generate avatar with first letter of username
        const commentAvatarLetter = (comment.user_name || 'A').charAt(0).toUpperCase();
        commentElement.innerHTML = `
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-r ${avatarColor} flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    ${commentAvatarLetter}
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100">${comment.user_name || 'Anonymous'}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">${formatDateTime(comment.created_at)}</span>
                        </div>
                        ${currentUser && comment.user_id == currentUser.id ? `
                            <div class="flex gap-2">
                                <button onclick="editComment(${comment.id})" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">Edit</button>
                                <button onclick="deleteComment(${comment.id})" class="text-xs text-red-600 hover:text-red-800 hover:underline">Delete</button>
                            </div>
                        ` : ''}
                    </div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 comment-content" id="comment-content-${comment.id}">${comment.content}</p>
                </div>
            </div>
        `;
        commentsList.appendChild(commentElement);
    });

    container.appendChild(commentsList);
}

// Format date time for display
function formatDateTime(dateString) {
    if (!dateString) return 'Just now';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Load comments with enhanced renderer
async function loadStoryComments(storyId) {
    try {
        const response = await fetch(`../api/comments/get_story_comments.php?story_id=${storyId}`);
        const result = await response.json();

        const commentsList = document.getElementById('storyCommentsList');
        if (!commentsList) return;

        if (result.success && result.comments && result.comments.length > 0) {
            renderComments(result.comments, commentsList);
        } else {
            commentsList.innerHTML = '<p class="text-sm text-zinc-500 text-center py-4">No comments yet. Be the first to comment!</p>';
        }
    } catch (error) {
        console.error('Error loading comments:', error);
        const commentsList = document.getElementById('storyCommentsList');
        if (commentsList) {
            commentsList.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error loading comments</p>';
        }
    }
}

// Handle post comment
async function handlePostComment() {
    const commentInput = document.getElementById('storyCommentInput');
    const content = commentInput.value.trim();

    if (!content) {
        alert('Please enter a comment');
        return;
    }

    if (!isUserLoggedIn) {
        alert('Please log in to comment');
        return;
    }

    try {
        const response = await fetch('../api/comments/add_story_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                story_id: currentStoryId,
                content: content
            })
        });

        const result = await response.json();

        if (result.success) {
            commentInput.value = '';
            loadStoryComments(currentStoryId);
        } else {
            alert('Error posting comment: ' + result.message);
        }
    } catch (error) {
        console.error('Error posting comment:', error);
        alert('Failed to post comment');
    }
}

// Open create story modal
function openCreateStoryModal() {
    if (!isUserLoggedIn) {
        alert('Please log in to share your story');
        return;
    }

    document.getElementById('storyId').value = '';
    document.getElementById('storyTitle').value = '';
    document.getElementById('storyAuthorName').value = currentUser ? currentUser.name : 'Anonymous';
    document.getElementById('storyTheme').value = '';
    document.getElementById('storyContent').value = '';
    document.getElementById('storyImage').value = '';
    document.getElementById('createStoryModalTitle').textContent = 'Share Your Story';

    if (createStoryModal) {
        createStoryModal.classList.remove('hidden');
        createStoryModal.classList.add('active');
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Close create story modal
function closeCreateStoryModal() {
    if (createStoryModal) {
        createStoryModal.classList.remove('active');
        createStoryModal.classList.add('hidden');
    }
}


// Reset form errors
function resetFormErrors() {
    // Remove error classes from inputs
    document.querySelectorAll('.error-border').forEach(el => {
        el.classList.remove('error-border');
    });

    // Hide error messages
    document.querySelectorAll('.error-message').forEach(el => {
        el.classList.remove('show');
        el.style.display = 'none';
    });
}

// Add field error
function addFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(fieldId + '-error');

    if (field) {
        field.classList.add('error-border');
    }
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
        errorEl.style.display = 'block';
    }
}

// Clear field error
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorEl = document.getElementById(fieldId + '-error');

    if (field) {
        field.classList.remove('error-border');
    }
    if (errorEl) {
        errorEl.classList.remove('show');
        errorEl.style.display = 'none';
        errorEl.textContent = '';
    }
}

// Validate field function
function validateField(fieldId, value, rules) {
    const errors = [];

    if (rules.required && !value.trim()) {
        errors.push(`${rules.label} is required`);
    }

    if (rules.minLength && value.trim().length < rules.minLength) {
        errors.push(`${rules.label} must be at least ${rules.minLength} characters`);
    }

    if (rules.maxLength && value.length > rules.maxLength) {
        errors.push(`${rules.label} must not exceed ${rules.maxLength} characters`);
    }

    if (rules.maxWords) {
        const words = value.trim().split(/\s+/).filter(w => w.length > 0);
        if (words.length > rules.maxWords) {
            errors.push(`${rules.label} must not exceed ${rules.maxWords} words`);
        }
    }

    return errors;
}

// Validate story form
function validateStoryForm() {
    resetFormErrors();
    let isValid = true;

    // Validate title
    const title = document.getElementById('storyTitle').value;
    const titleErrors = validateField('storyTitle', title, {
        required: true,
        minLength: 5,
        maxLength: 255,
        label: 'Title'
    });
    if (titleErrors.length > 0) {
        addFieldError('storyTitle', titleErrors[0]);
        isValid = false;
    }

    // Validate author name
    const authorName = document.getElementById('storyAuthorName').value;
    const authorErrors = validateField('storyAuthorName', authorName, {
        required: true,
        maxLength: 100,
        label: 'Author name'
    });
    if (authorErrors.length > 0) {
        addFieldError('storyAuthorName', authorErrors[0]);
        isValid = false;
    }

    // Validate content
    const content = document.getElementById('storyContent').value;
    const contentErrors = validateField('storyContent', content, {
        required: true,
        minLength: 50,
        maxLength: 5000,
        maxWords: 500,
        label: 'Content'
    });
    if (contentErrors.length > 0) {
        addFieldError('storyContent', contentErrors[0]);
        isValid = false;
    }

    // Validate theme
    const theme = document.getElementById('storyTheme').value;
    if (!theme) {
        addFieldError('storyTheme', 'Please select a theme');
        isValid = false;
    }

    // Validate image (if provided)
    const imageInput = document.getElementById('storyImage');
    if (imageInput.files.length > 0) {
        const file = imageInput.files[0];
        if (!file.type.startsWith('image/')) {
            addFieldError('storyImage', 'Please select a valid image file');
            isValid = false;
        } else if (file.size > 5 * 1024 * 1024) {  // 5MB
            addFieldError('storyImage', 'Image must be less than 5MB');
            isValid = false;
        }
    }

    return isValid;
}

// Enhanced form submit handler with validation
async function handleStoryFormSubmit(e) {
    e.preventDefault();

    // Validate form
    if (!validateStoryForm()) {
        return;
    }

    const storyId = document.getElementById('storyId').value;
    const formData = new FormData();
    formData.append('title', document.getElementById('storyTitle').value);
    formData.append('author_name', document.getElementById('storyAuthorName').value);
    formData.append('theme', document.getElementById('storyTheme').value);
    formData.append('language', document.getElementById('storyLanguage').value);
    formData.append('privacy', document.getElementById('storyPrivacy').value);
    formData.append('content', document.getElementById('storyContent').value);

    // Add the story ID if we're editing an existing story
    if (storyId) {
        formData.append('id', storyId);
    }

    const imageFile = document.getElementById('storyImage').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    try {
        // Determine the appropriate API endpoint
        const apiUrl = storyId
            ? '../api/stories/update_story.php'  // Update if story has an ID
            : '../api/stories/create_story.php'; // Create if no ID

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const actionText = storyId ? 'updated' : 'created';
            alert(`Story ${actionText} successfully!`);
            closeCreateStoryModal();
            loadStories();
        } else {
            alert(`Error ${storyId ? 'updating' : 'creating'} story: ` + result.message);
        }
    } catch (error) {
        console.error(`Error ${storyId ? 'updating' : 'creating'} story:`, error);
        alert(`Failed to ${storyId ? 'update' : 'create'} story`);
    }
}

// Open report modal
function openReportModal() {
    if (!reportModal) {
        reportModal = document.getElementById('reportModal');
    }
    if (!reportForm) {
        reportForm = document.getElementById('reportForm');
        if (reportForm) {
            reportForm.addEventListener('submit', handleReportSubmit);
        }
    }

    if (reportModal && currentStoryId) {
        document.getElementById('reportStoryId').value = currentStoryId;
        document.getElementById('reportReason').value = '';
        document.getElementById('reportDetails').value = '';
        reportModal.classList.remove('hidden');
        reportModal.classList.add('active');
    }
}

// Close report modal
function closeReportModal() {
    if (reportModal) {
        reportModal.classList.remove('active');
        reportModal.classList.add('hidden');
    }
}

// Handle report submit
async function handleReportSubmit(e) {
    e.preventDefault();

    const storyId = document.getElementById('reportStoryId').value;
    const reason = document.getElementById('reportReason').value;
    const details = document.getElementById('reportDetails').value;

    if (!reason) {
        alert('Please select a reason');
        return;
    }

    try {
        const response = await fetch('../api/reports/create_report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                reported_item_id: storyId,
                reported_item_type: 'story',
                report_category: reason,  // Changed from 'reason' to match controller
                report_reason: details    // Changed from 'details' to match controller
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Thank you for your report');
            closeReportModal();
        } else {
            alert('Error: ' + (result.message || 'Failed to submit report'));
        }
    } catch (error) {
        console.error('Error submitting report:', error);
        alert('Failed to submit report');
    }
}

// Load more stories
function loadMoreStories() {
    displayedStoryCount += 6;
    renderStories();
}

// Update load more button
function updateLoadMoreButton() {
    if (!loadMoreStoriesBtn) return;

    if (displayedStoryCount >= filteredStories.length) {
        loadMoreStoriesBtn.classList.add('hidden');
    } else {
        loadMoreStoriesBtn.classList.remove('hidden');
    }
}

// Clear stories filters
function clearStoriesFilters() {
    if (storiesThemeFilter) storiesThemeFilter.value = '';
    if (storiesLanguageFilter) storiesLanguageFilter.value = '';
    if (storiesSearchInput) storiesSearchInput.value = '';
    filterStories();
}

// Initialize character counters
function initializeCharacterCounters() {
    const titleInput = document.getElementById('storyTitle');
    const titleCounter = document.getElementById('titleCounter');
    if (titleInput && titleCounter) {
        titleInput.addEventListener('input', function () {
            titleCounter.textContent = this.value.length;
        });
    }

    const contentInput = document.getElementById('storyContent');
    const contentCounter = document.getElementById('contentCounter');
    const wordCounter = document.getElementById('wordCounter');
    if (contentInput && contentCounter && wordCounter) {
        contentInput.addEventListener('input', function () {
            const text = this.value;
            contentCounter.textContent = text.length;

            const words = text.trim().split(/\s+/).filter(word => word.length > 0);
            wordCounter.textContent = words.length;

            if (words.length > 500) {
                wordCounter.style.color = '#ef4444';
            } else if (words.length > 450) {
                wordCounter.style.color = '#f59e0b';
            } else {
                wordCounter.style.color = '';
            }
        });
    }
}

// Initialize image preview
function initializeImagePreview() {
    const imageInput = document.getElementById('storyImage');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const previewImage = document.getElementById('imagePreview');

    if (imageInput && previewContainer && previewImage) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    this.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    alert('Image must be less than 5MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImage.src = event.target.result;
                    previewContainer.classList.remove('hidden');

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Remove image preview
function removeImagePreview() {
    const imageInput = document.getElementById('storyImage');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const previewImage = document.getElementById('imagePreview');

    if (imageInput) imageInput.value = '';
    if (previewContainer) previewContainer.classList.add('hidden');
    if (previewImage) previewImage.src = '';
}

// Edit comment function
async function editComment(commentId) {
    const commentElement = document.getElementById(`comment-${commentId}`);
    const contentElement = document.getElementById(`comment-content-${commentId}`);
    const originalContent = contentElement.textContent;

    // Create edit form
    const editForm = document.createElement('div');
    editForm.className = 'edit-comment-form mt-2';
    editForm.innerHTML = `
        <textarea id="edit-comment-textarea-${commentId}" class="w-full p-2 border rounded text-sm" rows="3">${originalContent}</textarea>
        <div class="flex gap-2 mt-2">
            <button onclick="saveComment(${commentId})" class="text-xs bg-blue-600 text-white px-3 py-1 rounded">Save</button>
            <button onclick="cancelEdit(${commentId})" class="text-xs bg-gray-300 px-3 py-1 rounded">Cancel</button>
        </div>
    `;

    // Replace content with edit form
    contentElement.innerHTML = '';
    contentElement.appendChild(editForm);

    // Focus on the textarea
    document.getElementById(`edit-comment-textarea-${commentId}`).focus();
}

// Save comment after editing
async function saveComment(commentId) {
    const textarea = document.getElementById(`edit-comment-textarea-${commentId}`);
    const newContent = textarea.value.trim();

    if (!newContent) {
        alert('Comment content cannot be empty');
        return;
    }

    try {
        const response = await fetch('../api/comments/update_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: commentId,
                content: newContent
            })
        });

        const result = await response.json();

        if (result.success) {
            // Update the comment display
            const contentElement = document.getElementById(`comment-content-${commentId}`);
            contentElement.textContent = newContent;
            contentElement.className = 'text-sm text-zinc-700 comment-content';
        } else {
            alert('Error updating comment: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving comment:', error);
        alert('Failed to update comment');
    }
}

// Cancel comment edit
function cancelEdit(commentId) {
    // Reload the comments to restore the original state
    loadStoryComments(currentStoryId);
}

// Delete comment function
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    try {
        const response = await fetch('../api/comments/delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: commentId
            })
        });

        const result = await response.json();

        if (result.success) {
            // Remove the comment from the UI
            const commentElement = document.getElementById(`comment-${commentId}`);
            if (commentElement) {
                commentElement.remove();
            }
        } else {
            alert('Error deleting comment: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        alert('Failed to delete comment');
    }
}

// Edit story function
function editStory(storyId) {
    // Close the story details modal
    closeStoryDetailsModal();

    // Load the story data to fill the form
    const story = storiesData.find(s => s.id == storyId);
    if (!story) {
        alert('Story not found');
        return;
    }

    // Fill the form with existing data
    document.getElementById('storyId').value = story.id;
    document.getElementById('storyTitle').value = story.title;
    document.getElementById('storyAuthorName').value = story.author_name || currentUser.name;
    document.getElementById('storyTheme').value = story.theme;
    document.getElementById('storyLanguage').value = story.language || 'en';
    document.getElementById('storyPrivacy').value = story.privacy || 'public';
    document.getElementById('storyContent').value = story.content;

    // Clear and set image
    document.getElementById('storyImage').value = '';
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');

    // Update modal title
    document.getElementById('createStoryModalTitle').textContent = 'Edit Story';

    // Show the create story modal
    if (createStoryModal) {
        createStoryModal.classList.remove('hidden');
        createStoryModal.classList.add('active');
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

// Delete story function
async function deleteStory(storyId) {
    if (!confirm('Are you sure you want to delete this story? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('../api/stories/delete_story.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: storyId
            })
        });

        const result = await response.json();

        if (result.success) {
            // Close the modal and reload stories
            closeStoryDetailsModal();
            loadStories();
            alert('Story deleted successfully');
        } else {
            alert('Error deleting story: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting story:', error);
        alert('Failed to delete story');
    }
}


