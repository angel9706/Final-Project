<?php

namespace App\Config;

class Response
{
    /**
     * Send JSON response
     * @param array $data
     * @param int $statusCode
     */
    public static function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Send success response
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error response
     * @param string $message
     * @param mixed $errors
     * @param int $statusCode
     */
    public static function error($message = 'Error', $errors = null, $statusCode = 400)
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Send validation error
     * @param array $errors
     */
    public static function validationError($errors)
    {
        self::error('Validation error', $errors, 422);
    }

    /**
     * Send unauthorized error
     */
    public static function unauthorized()
    {
        self::error('Unauthorized', null, 401);
    }

    /**
     * Send forbidden error
     */
    public static function forbidden()
    {
        self::error('Forbidden', null, 403);
    }

    /**
     * Send not found error
     */
    public static function notFound()
    {
        self::error('Resource not found', null, 404);
    }
}
