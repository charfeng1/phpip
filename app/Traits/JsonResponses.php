<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait for consistent JSON response formatting across controllers.
 *
 * Provides standardized success and error response methods to ensure
 * API responses follow a consistent structure throughout the application.
 */
trait JsonResponses
{
    /**
     * Return a success JSON response.
     *
     * @param  mixed  $data  The data to include in the response
     * @param  string  $message  Success message
     * @param  int  $code  HTTP status code
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message  Error message
     * @param  int  $code  HTTP status code
     * @param  mixed  $errors  Additional error details
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a redirect response in JSON format (for AJAX requests).
     *
     * @param  string  $url  The URL to redirect to
     * @param  string|null  $message  Optional success message
     */
    protected function redirectResponse(string $url, ?string $message = null): JsonResponse
    {
        $response = [
            'redirect' => $url,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Return a validation error response.
     *
     * @param  array  $errors  Validation errors
     * @param  string  $message  Error message
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return a not found error response.
     *
     * @param  string  $message  Error message
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return an unauthorized error response.
     *
     * @param  string  $message  Error message
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden error response.
     *
     * @param  string  $message  Error message
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }
}
