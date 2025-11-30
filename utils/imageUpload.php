<?php
/**
 * Image upload utility functions for the Connect for Peace platform
 */
class ImageUpload {

    private static $uploadDir = __DIR__ . '/../uploads/';
    private static $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    private static $maxFileSize = 5 * 1024 * 1024; // 5MB

    /**
     * Upload an image file
     * @param array $file The $_FILES entry for the image
     * @param string $category The category of content (actions or resources)
     * @return array Result with success status and image URL
     */
    public static function uploadImage($file, $category) {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
        }

        // Validate file size
        if ($file['size'] > self::$maxFileSize) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit of ' . (self::$maxFileSize / 1024 / 1024) . 'MB'];
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::$allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
        }

        // Create upload directory if it doesn't exist
        $categoryDir = self::$uploadDir . $category;
        if (!is_dir($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $categoryDir . '/' . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'image_url' => '/uploads/' . $category . '/' . $filename
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
    }

    /**
     * Delete an image file
     * @param string $imageUrl The URL of the image to delete
     */
    public static function deleteImage($imageUrl) {
        if ($imageUrl && !empty($imageUrl) && strpos($imageUrl, '/uploads/') !== false) {
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $imageUrl;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}