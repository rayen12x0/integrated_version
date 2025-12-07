# QUICK START GUIDE - Testing the Stories Feature

## 🎯 IMMEDIATE TESTING STEPS

### 1. Open the Stories Page
```
http://localhost/integration_anitgravity/integrated_version/vue/stories.html
```

### 2. Test Story Modal (Click to View Details)
- Click on any story card
- **Expected**: Modal opens showing full story
- **Features to test**:
  - ✅ Story title, content, author displayed
  - ✅ Reaction buttons visible (❤️👍💡🤝)
  - ✅ Comments section loads
  - ✅ Close button works

### 3. Test "Share Your Story" Button
- Click the "Share Your Story" button (top right)
- **Expected**: Create story modal opens
- **Features to test**:
  - ✅ Form fields visible (Title, Author, Theme, Content, Image)
  - ✅ Can type in all fields
  - ✅ Submit button works
  - ✅ Cancel button closes modal

### 4. Test Reactions
- Click any reaction button (❤️👍💡🤝)
- **Expected**: 
  - Count increases by 1
  - Animation plays (emoji floats up)
  - Button shows "active" state
- Click again:
  - Count decreases by 1
  - Button returns to normal state

### 5. Test Comments
- Open a story modal
- Scroll to comments section
- Type a comment and click "Post Comment"
- **Expected**:
  - Comment appears in the list
  - Comment shows your name and timestamp

### 6. Test Filters
- Use the theme dropdown (All Themes, Personal, Community, etc.)
- **Expected**: Stories filter by selected theme
- Use the language dropdown
- **Expected**: Stories filter by language
- Use the search box
- **Expected**: Stories filter by search term

## 🔍 TROUBLESHOOTING

### If "Share Your Story" button doesn't work:
1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify you're logged in:
   ```javascript
   // Run in console:
   fetch('../api/users/check_auth.php').then(r => r.json()).then(console.log)
   ```
4. If not logged in, go to:
   ```
   http://localhost/integration_anitgravity/integrated_version/auth/login.html
   ```

### If story modal doesn't open:
1. Check browser console for errors
2. Verify stories are loading:
   ```javascript
   // Run in console:
   fetch('../api/stories/get_stories.php').then(r => r.json()).then(console.log)
   ```
3. Check that `stories.js` is loaded (no 404 errors)

### If reactions don't work:
1. Verify you're logged in
2. Check console for API errors
3. Test the API directly:
   ```javascript
   // Run in console:
   fetch('../api/reactions/add_story_reaction.php', {
     method: 'POST',
     headers: {'Content-Type': 'application/json'},
     body: JSON.stringify({story_id: 1, reaction_type: 'heart'})
   }).then(r => r.json()).then(console.log)
   ```

## 🎨 EXPECTED BEHAVIOR

### Story Cards:
- Hover effect: Card lifts up slightly
- Click anywhere on card: Opens modal
- Reaction buttons: Stop event propagation (don't open modal)

### Modals:
- Background: Blurred dark overlay
- Close button: Top right corner
- Click outside: Does NOT close (by design)
- ESC key: Does NOT close (by design)

### Reactions:
- First click: Adds reaction, count +1
- Second click: Removes reaction, count -1
- Animation: Emoji floats up and fades
- Active state: Button has different background

### Comments:
- Load automatically when modal opens
- Post button: Adds comment to list
- AI moderation: Blocks inappropriate words
- Character limit: 1000 characters

## 📊 DEMO DATA

### Test Users:
- **Admin**: ID=1, can approve stories
- **User**: ID=2, can create stories

### Test Stories:
- Should have at least 3-5 stories loaded
- Each with different themes
- Each with reaction counts

## 🔐 LOGIN CREDENTIALS

If you need to log in:
```
Admin Demo:
- Click "Admin Demo" button on login page
- Auto-logs in as admin (ID=1)

User Demo:
- Click "User Demo" button on login page
- Auto-logs in as user (ID=2)

Manual Login:
- Use credentials from your database
```

## ✅ SUCCESS CRITERIA

Your implementation is working if:
1. ✅ Stories page loads without errors
2. ✅ Story cards are visible
3. ✅ Clicking a card opens the modal
4. ✅ "Share Your Story" button opens create modal
5. ✅ Reaction buttons work (count changes)
6. ✅ Comments load and can be posted
7. ✅ Filters work (theme, language, search)
8. ✅ No JavaScript errors in console

## 🚨 COMMON ISSUES & FIXES

### Issue: "Authentication required" error
**Fix**: Log in first at `/auth/login.html`

### Issue: Stories not loading
**Fix**: Check database connection in `/config/config.php`

### Issue: Reactions not working
**Fix**: Verify `story_reactions` table exists in database

### Issue: Modal not opening
**Fix**: Check that `stories.js` is loaded (view page source)

### Issue: Images not displaying
**Fix**: Check `uploads/stories/` folder permissions

## 🛠️ ADMIN MODERATION WORKFLOWS

### 1. Access Admin Dashboard
```
http://localhost/integration_anitgravity/integrated_version/vue/admin_stories.html
```
**Prerequisites**: Must be logged in as admin user

**Expected**: Admin dashboard loads with moderation tools

### 2. Test Moderation Dashboard Stats
Test the `/api/moderation/dashboard.php` endpoint:
```javascript
// Run in console:
fetch('../api/moderation/dashboard.php', {
  headers: {'Authorization': 'Bearer [your-admin-token]'} // if applicable
}).then(r => r.json()).then(console.log)
```
**Expected Response**:
- `pending_reports_count` shows number of pending reports
- `flagged_comments_count` shows flagged comments count
- `banned_users_count` shows banned users count
- `most_reported_stories` shows stories with most reports

### Test getPending() Method

#### Direct Model Test
```php
// In test_api.php or create new test file
require_once 'model/report.php';
$report = new Report();
$stmt = $report->getPending();
echo "Pending reports count: " . $stmt->rowCount() . "\n";
$pendingReports = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($pendingReports);
```

#### Expected Behavior
- Returns PDOStatement object
- `rowCount()` returns number of pending reports
- `fetchAll()` returns array of pending reports with reporter info
- Includes fields: id, reporter_id, reported_item_id, reported_item_type, report_category, report_reason, status, reporter_name, reporter_email

### 3. Test Report Workflows
#### View Reports
- Navigate to reports section
- **Expected**: List of pending reports loaded

#### Take Action on Reports
Test the `/api/moderation/take_action.php` endpoint:
```javascript
// Run in console:
fetch('../api/moderation/take_action.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    report_id: 1,
    action: 'dismiss',  // or 'delete_story', 'ban_user', 'delete_and_ban', 'reviewed'
    admin_notes: 'Test moderation action'
  })
}).then(r => r.json()).then(console.log)
```
**Actions to Test**:
- **dismiss**: Report status changes to 'dismissed'
- **delete_story**: Story is deleted, report status updated
- **ban_user**: User is banned, report status updated
- **delete_and_ban**: Story deleted AND user banned
- **reviewed**: Report marked as reviewed

### 4. Test Comment Moderation
#### View Flagged Comments
- Check flagged comments section in admin dashboard
- **Expected**: Comments with inappropriate content listed

#### Approve Flagged Comments
Test `/api/comments/approve_comment.php`:
```javascript
fetch('../api/comments/approve_comment.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({id: 1})
}).then(r => r.json()).then(console.log)
```

#### Delete Flagged Comments
Test `/api/comments/delete_comment.php`:
```javascript
fetch('../api/comments/delete_comment.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({id: 1})
}).then(r => r.json()).then(console.log)
```

### 5. Test Banned Users Management
#### View Banned Users
Test `/api/moderation/get_banned_users.php`:
```javascript
fetch('../api/moderation/get_banned_users.php')
  .then(r => r.json()).then(console.log)
```
**Expected**: List of banned users with details

#### Unban User
Test `/api/moderation/unban_user.php`:
```javascript
fetch('../api/moderation/unban_user.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({user_id: 1})
}).then(r => r.json()).then(console.log)
```
**Expected**: User is unbanned, can log in again

### 6. Test Story Reporting from User Side
#### Submit Report from Stories UI
1. Open a story modal
2. Click the flag/report button
3. Select a report reason from dropdown
4. Add details and submit
5. **Expected**: Report submitted successfully, appears in admin dashboard

**Report Categories to Test**:
- `hate_speech`: Hate speech or harassment
- `violence`: Violence or dangerous content
- `spam`: Spam or misleading
- `false_information`: False information
- `other`: Other reasons

### 7. Admin UI Integration Tests
**Prerequisites for Admin Tests**: Log in as admin user at `/auth/login.html`

#### Test Story Moderation
1. **Pending Stories**: Should show stories with 'pending' status
2. **Approve Story**: Click approve button, story status changes to 'approved'
3. **Reject Story**: Click reject button, story status changes to 'rejected'
4. **View Story Details**: Click view button, opens story in new tab

#### Test Comment Moderation UI
1. **Flagged Comments**: Should show comments with inappropriate content
2. **Approve Comment**: Changes comment status from 'flagged' to 'active'
3. **Delete Comment**: Permanently removes comment
4. **Comment Details**: Shows story context and user info

#### Test User Management
1. **Banned Users List**: Shows all banned users
2. **Unban Function**: Allows unbanning users
3. **User History**: Shows activity history (if implemented)

### 8. Validation Steps
After each moderation action, verify:
- **Report Status**: Changes from 'pending' to appropriate status
- **Dashboard Counts**: Update accordingly
- **User Access**: Banned users cannot access features
- **Content Removal**: Deleted content no longer appears
- **Notifications**: Admin notifications sent as expected

## 📞 NEED HELP?

Check the browser console (F12) for detailed error messages. Most issues will show up there with specific error descriptions.

## Content Moderation Testing

### Flagged Words Detection
1. Post comment with "damn" → Should be flagged but allowed with status 'flagged'
2. Post comment with "cunt" → Should be rejected with error message
3. Check content_violations table for logs of flagged content
4. Clean comments (without flagged words) should be posted with status 'active'

### Admin Moderation
1. Login as admin (admin@connectforpeace.com)
2. Access /api/comments/get_flagged.php to view flagged comments
3. Verify flagged comments appear in the admin dashboard
4. Use /api/comments/approve_comment.php to approve flagged comments (changes status to 'active')
5. Use /api/moderation/take_action.php to process reports
6. Verify ban/unban functionality works through admin panel
