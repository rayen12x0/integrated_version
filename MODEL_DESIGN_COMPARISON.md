# Model Method Design Comparison

## Overview

This document explains the design differences between similar `getPending()` methods across different models and the rationale behind each approach.

## Method Comparison Table

| Model | Method | Return Type | Use Case | Reason |
|-------|--------|-------------|----------|--------|
| `Story` | `getPending()` | Array | Display pending stories with full data | Frontend needs complete story objects |
| `Action` | `getPending()` | Array | Display pending actions with full data | Frontend needs complete action objects |
| `Report` | `getPending()` | PDOStatement | Get count for dashboard stats | Only count needed, more efficient |

## Detailed Analysis

### Story Model Approach
```php
// In model/Story.php
public function getPending() {
    // Returns array of all pending stories
    $query = "SELECT * FROM stories WHERE status = 'pending'";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```
**Use Case**: Admin needs to view and manage pending stories  
**Rationale**: Full story data needed for display, editing, review process

### Action Model Approach  
```php
// In model/Action.php (similar pattern)
public function getPending() {
    // Returns array of all pending actions
    $query = "SELECT * FROM actions WHERE status = 'pending'";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```
**Use Case**: Admin needs to review pending actions  
**Rationale**: Complete action details required for approval process

### Report Model Approach
```php
// In model/Report.php
public function getPending() {
    // Returns PDOStatement for efficient counting
    $query = "SELECT r.*, u.name as reporter_name, u.email as reporter_email
              FROM reports r
              JOIN users u ON r.reporter_id = u.id
              WHERE r.status = 'pending'";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt; // PDOStatement for rowCount() optimization
}
```
**Use Case**: Dashboard needs count of pending reports  
**Rationale**: Only count is needed, so avoid loading all report data

## Performance Impact

### Scenario: 1000 Pending Items

| Model | Data Loaded | Memory Usage | Query Performance |
|-------|-------------|--------------|-------------------|
| Story | All 1000 stories | High | Slower |
| Action | All 1000 actions | High | Slower |  
| Report | Only count needed | Low | Faster |

## Integration Requirements

### Story/Action Models
- Frontend displays full details
- Admin needs access to complete objects
- Pagination and detailed review required

### Report Model  
- Dashboard only shows statistics
- `rowCount()` is called frequently
- Efficiency over completeness prioritized

## Best Practice Guidelines

Use **array return** when:
- Full data objects needed for display
- Frontend requires multiple fields from each record
- Detailed processing of individual records required

Use **PDOStatement return** when:
- Only count of records needed
- Performance is critical (frequent calls)
- Memory usage optimization important
- Single aggregate value required

## Conclusion

The different approaches are intentional design decisions based on specific use cases. The Report model's approach optimizes for dashboard performance, while Story and Action models prioritize data completeness for admin review workflows.