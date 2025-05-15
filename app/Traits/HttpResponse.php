<?php

namespace App\Traits;

trait HttpResponse
{
    /**
     * Return success response
     */
    protected function success($data, $message = null, $code = 200)
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return error response
     */
    protected function error($data, $message = null, $code = 422)
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return error response for exceptions
     */
    protected function httpError($message = null, $code = 500)
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
        ], $code);
    }
}
