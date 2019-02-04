<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->wantsJson()) {
            return response()->json([
                'error' => trans('api-message.RESOURCES_NOT_FOUND'),
            ], 404);
        }
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException) {

            switch ($exception->getPrevious()) {
                case \Tymon\JWTAuth\Exceptions\TokenExpiredException::class:
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.TOKEN_EXPIRED'),
                    ], $exception->getStatusCode());
                case \Tymon\JWTAuth\Exceptions\TokenInvalidException::class:
                case \Tymon\JWTAuth\Exceptions\TokenBlacklistedException::class:
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.INVALID_OR_BLACKLISTED_TOKEN'),
                    ], $exception->getStatusCode());
                default:
                    return response()->json([
                        'status' => 0,
                        'message' => trans('api-message.UNAUTHORIZED_ACTION'),
                    ], $exception->getStatusCode());
            }
        }

        return parent::render($request, $exception);
    }
}
