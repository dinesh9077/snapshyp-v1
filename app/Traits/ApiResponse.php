<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($data = [], $message = 'Success', $code = 200)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function errorResponse($message = 'Something went wrong', $code = 400, $errors = [])
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
	
    protected function validateResponse($message = 'Something went wrong', $code = 422, $errors = [])
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    } 
}
