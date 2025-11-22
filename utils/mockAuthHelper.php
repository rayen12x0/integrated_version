<?php
// utils/mockAuthHelper.php
// Helper functions for mock authentication when testing

class MockAuthHelper {
    // Get mock user for testing
    public static function getMockUser() {
        return [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user'  // 'admin' for admin features
        ];
    }
    
    // Check if we're in mock mode or if there's a real user
    public static function getCurrentUserId() {
        // For testing purposes, we'll return a default test user ID
        return 1; // Default test user ID
    }
    
    // Check if current user is admin
    public static function isAdmin() {
        return false; // Set to true for testing admin features
    }
}