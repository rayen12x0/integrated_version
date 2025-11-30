# Complete Fix Guide for All Issues

## Issues Found and Solutions

### 1. Report System Not Working (500 Error)

**Problem:** The report submission gives a server 500 error.

**Root Cause:** Missing error handling and potential issues with JSON response formatting.

**Fix for `api/reports/create_report.php`:**

```php
<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json; charset=utf-8');

// Clear any output buffers
if (ob_get_level()) {
    ob_clean();
}

try {
    require_once __DIR__ . '/../../controllers/ReportController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/report.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $controller = new ReportController();
    $controller->create();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
```

### 2. Participants Showing "undefined people" 

**Problem:** In the details modal, participants display shows "undefined people"

**Location:** `vue/index.html` line 344 and `vue/assets/js/script.js` line 1866

**Fix for `script.js` in `openDetailsModal()` function:**

```javascript
// Around line 1245-1250, replace:
document.getElementById('modalParticipants').textContent = `${item.participants || 0} ${item.participants === 1 ? 'person' : 'people'}`;

// With:
const participantCount = parseInt(item.participants) || 0;
document.getElementById('modalParticipants').textContent = `${participantCount} ${participantCount === 1 ? 'person' : 'people'}`;
```

**Fix for `loadParticipants()` function (around line 1875):**

```javascript
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
            // Check if participants is an array
            const participants = Array.isArray(result.participants) ? result.participants : [];
            const participantsList = document.getElementById('participantsList');
            
            if (participantsList) {
                renderParticipants(participants, participantsList);

                // Update the participants count in the details section
                const participantsCount = document.getElementById('modalParticipants');
                if (participantsCount) {
                    const count = result.count || participants.length || 0;
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
```

### 3. Comments Not Visible (Length Error)

**Problem:** Comments can be submitted but aren't visible, console error at line 1866

**Fix for `loadComments()` function:**

```javascript
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
            // Ensure comments is an array
            const comments = Array.isArray(result.comments) ? result.comments : [];
            
            const commentsList = document.getElementById('commentsList') ||
                document.querySelector('#detailsModal #commentsList') ||
                document.querySelector('#detailsModal .comments-container') ||
                document.querySelector('#detailsModal .comments-list') ||
                document.querySelector('.comments-container') ||
                document.querySelector('#comments-container');

            if (commentsList) {
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
```

**Fix for `api/comments/get_comments.php`:**

```php
<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Clear output buffers
if (ob_get_level()) {
    ob_clean();
}

try {
    require_once __DIR__ . '/../../controllers/CommentController.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../model/comment.php';
    require_once __DIR__ . '/../../utils/AuthHelper.php';

    $actionId = $_GET['action_id'] ?? null;
    $resourceId = $_GET['resource_id'] ?? null;

    $controller = new CommentController();

    if ($actionId || $resourceId) {
        $controller->getByEntity($actionId, $resourceId);
    } else {
        $controller->getAll();
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage(),
        "comments" => [] // Always return empty array on error
    ]);
}
?>
```

### 4. Join/Leave Toggle Not Working

**Problem:** Join button doesn't change to Leave after joining

**Fix for `toggleJoinAction()` in script.js:**

```javascript
async function toggleJoinAction(actionId) {
    if (!isUserLoggedIn || !currentUser) {
        showSwal('Login Required', 'Please log in to join actions.', 'info');
        return;
    }

    const button = document.getElementById('actionButton');
    if (button) {
        button.disabled = true;
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
                action_id: actionId,
                user_id: currentUser.id
            }),
            dataType: "json"
        });

        if (result.success) {
            // Update state based on response
            if (result.joined) {
                joinedActions.add(parseInt(actionId));
            } else {
                joinedActions.delete(parseInt(actionId));
            }

            // Update button
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
                button.disabled = false;
            }

            showSwal('Success', result.message, 'success');

            // Reload participants
            if (currentModalData) {
                await loadParticipants(actionId);
            }
        } else {
            if (button) button.disabled = false;
            showSwal('Error', result.message, 'error');
        }
    } catch (error) {
        console.error("Join action error:", error);
        if (button) button.disabled = false;
        showSwal('Error', 'Network error. Please try again.', 'error');
    }
}
```

### 5. Dashboard Recent Activity Undefined

**Problem:** Dashboard shows "undefined" for recent activity and comments

**Fix for `api/other/recent_activity.php`:**

Add better null checks and default values:

```php
<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once "../../config/config.php";

header("Content-Type: application/json");

if (ob_get_level()) {
    ob_clean();
}

try {
    $pdo = Config::getConnexion();
    $userId = $_GET['user_id'] ?? null;
    $role = $_GET['role'] ?? 'user';
    $activities = [];

    if ($role === 'admin') {
        $sql = "
            SELECT 'action' as type, id, title, description, created_at as date, status
            FROM actions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'resource' as type, id, COALESCE(resource_name, 'Unnamed Resource') as title, description, created_at as date, status
            FROM resources
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'comment' as type, c.id,
                COALESCE(
                    (SELECT title FROM actions WHERE id = c.action_id),
                    (SELECT resource_name FROM resources WHERE id = c.resource_id),
                    'Comment'
                ) as title,
                COALESCE(c.content, 'No content') as description,
                c.created_at as date,
                'comment' as status
            FROM comments c
            WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY date DESC
            LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allActivities as $activity) {
            $activities[] = [
                'type' => $activity['type'] ?? 'unknown',
                'message' => ($activity['title'] ?? 'Untitled') . ' - ' . ($activity['type'] ?? 'Activity'),
                'details' => $activity['description'] ?? 'No description',
                'timestamp' => $activity['date'] ?? date('Y-m-d H:i:s'),
                'status' => $activity['status'] ?? 'unknown'
            ];
        }
    } else {
        // Regular user activity query with better null handling
        $sql = "
            SELECT 'action' as type, id, title, description, created_at as date, status
            FROM actions
            WHERE creator_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'resource' as type, id, COALESCE(resource_name, 'Unnamed Resource') as title, description, created_at as date, status
            FROM resources
            WHERE publisher_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'comment' as type, c.id,
                COALESCE(
                    (SELECT title FROM actions WHERE id = c.action_id),
                    (SELECT resource_name FROM resources WHERE id = c.resource_id),
                    'Comment'
                ) as title,
                COALESCE(c.content, 'No content') as description,
                c.created_at as date,
                'comment' as status
            FROM comments c
            WHERE c.user_id = :user_id AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY date DESC
            LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userActivities as $activity) {
            $activities[] = [
                'type' => $activity['type'] ?? 'unknown',
                'message' => ($activity['title'] ?? 'Untitled') . ' - ' . ($activity['type'] ?? 'Activity'),
                'details' => $activity['description'] ?? 'No description',
                'timestamp' => $activity['date'] ?? date('Y-m-d H:i:s'),
                'status' => $activity['status'] ?? 'unknown'
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "activity" => $activities
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "activity" => []
    ]);
}
?>
```

### 6. Dashboard Action Reject Error (JSON Parse Error)

**Problem:** When rejecting actions in dashboard, getting "Unexpected end of JSON input"

**Location:** `dashboard/script.js` line 1341-1349

**Fix:** Ensure API returns proper JSON even on errors:

Create/Update `api/actions/approve_action.php`:

```php
<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json; charset=utf-8');

if (ob_get_level()) {
    ob_clean();
}

try {
    require_once "../../controllers/actionController.php";
    require_once "../../config/config.php";
    require_once "../../utils/AuthHelper.php";

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode([
            "success" => false,
            "message" => "Action ID is required"
        ]);
        exit;
    }

    $actionId = $data['id'];
    $action = $data['action'] ?? 'approve'; // 'approve' or 'reject'

    $pdo = Config::getConnexion();
    
    // Update status based on action
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    
    $sql = "UPDATE actions SET status = :status WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $newStatus);
    $stmt->bindParam(':id', $actionId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Action " . $newStatus . " successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update action status"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
```

### 7. Reminder System Not Working

**Problem:** Reminder notifications not being sent

**Fix:** Ensure the reminder cron job is set up and the email service is working.

Check `utils/send_reminders.php` and ensure it's being called:

```php
<?php
// This should be run via cron job every hour
require_once "../config/config.php";
require_once "../model/reminder.php";
require_once "../utils/EmailService.php";

try {
    $pdo = Config::getConnexion();
    $reminderModel = new Reminder($pdo);
    
    // Get reminders due in the next hour
    $sql = "SELECT r.*, u.email, u.name, 
            CASE 
                WHEN r.item_type = 'action' THEN a.title
                WHEN r.item_type = 'resource' THEN res.resource_name
            END as item_title
            FROM reminders r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN actions a ON r.item_id = a.id AND r.item_type = 'action'
            LEFT JOIN resources res ON r.item_id = res.id AND r.item_type = 'resource'
            WHERE r.reminder_time <= DATE_ADD(NOW(), INTERVAL 1 HOUR)
            AND r.reminder_time > NOW()
            AND r.sent = 0";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $emailService = new EmailService();
    
    foreach ($reminders as $reminder) {
        // Send email if reminder_type includes 'email'
        if (strpos($reminder['reminder_type'], 'email') !== false || $reminder['reminder_type'] === 'both') {
            $subject = "Reminder: " . ($reminder['item_title'] ?? 'Your Item');
            $message = "This is a reminder about: " . ($reminder['item_title'] ?? 'an item') . "\n\n";
            $message .= "Scheduled for: " . $reminder['reminder_time'];
            
            $emailService->sendEmail($reminder['email'], $reminder['name'], $subject, $message);
        }
        
        // Mark as sent
        $updateSql = "UPDATE reminders SET sent = 1 WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':id', $reminder['id'], PDO::PARAM_INT);
        $updateStmt->execute();
    }
    
    echo "Processed " . count($reminders) . " reminders\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
```

## Implementation Steps

1. **Backup your current files** before making changes
2. Apply fixes in this order:
   - Fix API files first (reports, comments, join_action)
   - Update script.js with all the function fixes
   - Update dashboard script.js
   - Set up reminder cron job

3. **Test each fix:**
   - Test report submission
   - Test viewing participants
   - Test adding and viewing comments
   - Test join/leave functionality
   - Test dashboard recent activity
   - Test approve/reject actions
   - Test reminder creation

4. **Clear browser cache** after applying fixes

## Additional Recommendations

1. **Add proper error logging:**
   ```php
   error_log("Error in [file]: " . $e->getMessage());
   ```

2. **Use browser dev tools** to monitor network requests and console errors

3. **Check database** for proper schema and data integrity

4. **Verify file permissions** on server (644 for PHP files)

5. **Enable detailed error logging** in development:
   ```php
   // In config.php during development only
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

Let me know if you need help implementing any of these fixes!
