<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Traits\ApiResponse;
use Laravel\Passport\Exceptions\OAuthServerException;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * A list of exception types that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {

            // 🔹 OAuth / Passport token exceptions
            if ($exception instanceof OAuthServerException) {
                return $this->errorResponse('Unauthorized: ' . $exception->getMessage(), 401);
            }
 
            // 🔹 404 Not Found
            if ($exception instanceof NotFoundHttpException) {
                return $this->errorResponse('Resource not found', 404);
            }

            // 🔹 AuthenticationException (missing token, unauthenticated)
            if ($exception instanceof AuthenticationException) {
                return $this->errorResponse('Unauthorized: Token missing or invalid', 401);
            }

            // 🔹 Any other exception
            return $this->errorResponse(
                'Something went wrong',
                method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500,
                ['exception' => $exception->getMessage()]
            );
        }

        // 🔹 For non-API requests, use default Laravel render
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
		if ($request->is('api/*') || $request->expectsJson()) {
			return $this->errorResponse('Unauthorized: Token missing or invalid', 401);
		}

		// Web request → redirect to login page
		return redirect()->guest(route('login'));
    }
}
