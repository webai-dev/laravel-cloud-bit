<?php

namespace App\Exceptions;

use App\Util\Enums\Environment;
use Exception;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use WebThatMatters\Apparatus\Exceptions\ApparatusException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
        BitValidationException::class,
        RecaptchaException::class,
        PermissionException::class,
        JWTException::class,
        SignatureInvalidException::class,
        ApparatusException::class,
        StorageExceededException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @throws
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
     * @throws \ReflectionException
     * @return \Illuminate\Http\Response|Response
     */
    public function render($request, Exception $exception)
    {

        $class = (new \ReflectionClass($exception))->getShortName();

        //Render stacktrace in development when not requesting JSON
        if (!$request->expectsJson() && config('app.env') != Environment::PRODUCTION) {
            return parent::render($request, $exception);
        }
        
        $response = [
            'error'  => $class,
            'message'=> $exception->getMessage()
        ];
        
        $code = 500;
        
        if ($exception instanceof HttpException) {
            $code = $exception->getStatusCode();
        }
        
        if ($exception instanceof ModelNotFoundException) {
            $response['error'] = 'EntityNotFound';
            $class = explode("\\",$exception->getModel());
            $item = $class[count($class) - 1];

            $response['message'] = trans('exceptions.not_found',compact('item'));
            $code = 404;
        }
        
        if ($exception instanceof ValidationException) {
            $response['data'] = $exception->validator->errors();
            $code = 422;
        }
        
        if($exception instanceof BitValidationException){
            $response['data'] = $exception->getErrors();
            $code = 422;
        }
        
        if($exception instanceof PermissionException || $exception instanceof AuthorizationException){
            $code = 403;
        }
        
        if ($exception instanceof RecaptchaException) {
            $response['data'] = $exception->getErrors();
            $code = 400;
        }

        if($exception instanceof JWTException || $exception instanceof SignatureInvalidException){
            $code = 400;
        }


        if($code >= 500 && config('app.env') == Environment::PRODUCTION){
            $response['message'] = __('exceptions.internal_error');
        }

        if ($exception instanceof BillingException){
            $code = $exception->getCode();
        }

        if ($exception instanceof StorageExceededException){
            $code = 400;
        }

        if ($exception instanceof ApparatusException){
            $code = 400;
            $response['message'] = $exception->getMessage();
            $response['apparatus_error'] = $exception->getErrorCode();
            $response['data'] = $exception->getData();
        }

        return response()->json($response,$code);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }
}
