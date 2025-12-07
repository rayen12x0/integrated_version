# Moderation Tables API Reference

## flagged_words
- **Purpose**: Dictionary of inappropriate words
- **Columns**: word (VARCHAR), category (ENUM), severity (ENUM), auto_action (ENUM)
- **Usage**: Queried by Comment::checkForFlaggedWords()

## content_violations
- **Purpose**: Audit log of all moderation violations
- **Columns**: content_type, content_id, user_id, flagged_word, word_category, severity, status
- **Usage**: Logged by Comment::logViolation()
- **Admin Access**: Query for moderation dashboard

## ban_log
- **Purpose**: Track user ban/unban history
- **Columns**: user_id, banned_by, reason, action_type, banned_at, unbanned_at
- **Usage**: Used by ModerationController::banUser() and unbanUser() methods