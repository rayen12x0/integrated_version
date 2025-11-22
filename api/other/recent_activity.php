<?php
// API endpoint to get recent activity for dashboard
// Real implementation querying database

require_once "../../config/config.php";

header("Content-Type: application/json");

try {
    $pdo = Config::getConnexion();

    $userId = $_GET['user_id'] ?? null;
    $role = $_GET['role'] ?? 'user';

    $activities = [];

    if ($role === 'admin') {
        // Admin sees all recent activity
        $sql = "
            SELECT 'action' as type, id, title, description, created_at as date, status
            FROM actions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'resource' as type, id, resource_name as title, description, created_at as date, status
            FROM resources
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'comment' as type, id,
                CASE
                    WHEN action_id IS NOT NULL THEN (SELECT title FROM actions WHERE id = action_id)
                    WHEN resource_id IS NOT NULL THEN (SELECT resource_name FROM resources WHERE id = resource_id)
                    ELSE 'Comment'
                END as title,
                content as description, created_at as date,
                'comment' as status
            FROM comments
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            UNION ALL
            SELECT 'participation' as type, id,
                (SELECT title FROM actions WHERE id = action_id) as title,
                CONCAT('User joined action: ', (SELECT title FROM actions WHERE id = action_id)) as description,
                created_at as date,
                'active' as status
            FROM action_participants
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY date DESC
            LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $allActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allActivities as $activity) {
            $activities[] = [
                'type' => $activity['type'],
                'title' => $activity['title'] ?: 'Untitled',
                'description' => $activity['description'] ?: 'No description',
                'date' => $activity['date'],
                'status' => $activity['status']
            ];
        }
    } else {
        // Regular user sees activity related to them
        $sql = "
            SELECT 'action' as type, id, title, description, created_at as date, status
            FROM actions
            WHERE creator_id = :user_id
            UNION ALL
            SELECT 'resource' as type, id, resource_name as title, description, created_at as date, status
            FROM resources
            WHERE publisher_id = :user_id
            UNION ALL
            SELECT 'comment' as type, id,
                CASE
                    WHEN action_id IS NOT NULL THEN (SELECT title FROM actions WHERE id = action_id)
                    WHEN resource_id IS NOT NULL THEN (SELECT resource_name FROM resources WHERE id = resource_id)
                    ELSE 'Comment'
                END as title,
                content as description, created_at as date,
                'comment' as status
            FROM comments
            WHERE user_id = :user_id
            UNION ALL
            SELECT 'participation' as type, id,
                (SELECT title FROM actions WHERE id = action_id) as title,
                CONCAT('You joined action: ', (SELECT title FROM actions WHERE id = action_id)) as description,
                created_at as date,
                'active' as status
            FROM action_participants
            WHERE user_id = :user_id
            ORDER BY date DESC
            LIMIT 20";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userActivities as $activity) {
            $activities[] = [
                'type' => $activity['type'],
                'title' => $activity['title'] ?: 'Untitled',
                'description' => $activity['description'] ?: 'No description',
                'date' => $activity['date'],
                'status' => $activity['status']
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "recentItems" => $activities
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error retrieving recent activity: " . $e->getMessage()
    ]);
}
