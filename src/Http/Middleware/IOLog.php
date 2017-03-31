<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Common;
use Monolog\Logger;

class IOLog
{
    static protected $startTime;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!self::$startTime) {
            self::$startTime = microtime(true);
        }
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $message = [
            'response_time'  => microtime(true) - self::$startTime,
            'request_uri'    => $request->getPathInfo(),
            'request_header' => $request->headers->get('Content-Type'),
            'request_body'   => $request->all(),
            'response_body'  => @json_decode($response->getContent(), true) ?: $response->getContent()
        ];

        Common::logger(config('app.app_name'),
            'requestlog.log',
            json_encode($message, JSON_UNESCAPED_UNICODE),
            Logger::INFO
        );

    }


}
