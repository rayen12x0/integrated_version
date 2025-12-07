<?php
// test_moderation.php - Test file to verify moderation functionality

require_once 'config/config.php';
require_once 'model/comment.php';

$pdo = Config::getConnexion();
$commentModel = new Comment($pdo);

echo "Testing Comment Moderation Functionality...\n\n";

// Test 1: Clean comment (should succeed)
echo "Test 1: Clean comment\n";
$result1 = $commentModel->create([
    'user_id' => 1,
    'story_id' => 1,
    'content' => 'This is a clean comment'
]);
echo "Result: " . json_encode($result1) . "\n\n";

// Test 2: Low severity word (should flag but allow)
echo "Test 2: Low severity word (damn)\n";
$result2 = $commentModel->create([
    'user_id' => 1,
    'story_id' => 1,
    'content' => 'This is damn annoying'
]);
echo "Result: " . json_encode($result2) . "\n\n";

// Test 3: Critical word (should reject)
echo "Test 3: Critical word (cunt)\n";
$result3 = $commentModel->create([
    'user_id' => 1,
    'story_id' => 1,
    'content' => 'This contains cunt word'
]);
echo "Result: " . json_encode($result3) . "\n\n";

// Test 4: Multiple flagged words
echo "Test 4: Multiple flagged words (shit and kill)\n";
$result4 = $commentModel->create([
    'user_id' => 1,
    'story_id' => 1,
    'content' => 'This shit will kill you'
]);
echo "Result: " . json_encode($result4) . "\n\n";

// Test 5: High severity word
echo "Test 5: High severity word (asshole)\n";
$result5 = $commentModel->create([
    'user_id' => 1,
    'story_id' => 1,
    'content' => 'You are a total asshole'
]);
echo "Result: " . json_encode($result5) . "\n\n";

echo "Moderation tests completed!\n\n";

echo "Expected Results:\n";
echo "- Test 1: Should succeed with status 'active'\n";
echo "- Test 2: Should succeed with status 'flagged' (damn is low severity)\n";
echo "- Test 3: Should be rejected (cunt is critical)\n";
echo "- Test 4: Should succeed with status 'flagged' (multiple words, none critical)\n";
echo "- Test 5: Should succeed with status 'flagged' (asshole is high severity)\n";