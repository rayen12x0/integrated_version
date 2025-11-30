<?php
/**
 * Standardized API Response Handler
 */
class ApiResponse {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public static function error($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
?>