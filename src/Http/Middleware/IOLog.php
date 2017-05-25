<?php

namespace App\Http\Middleware;

use Closure;
use Log;
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
        $common = app('Common');
        if ($this->blackList($request->getPathInfo())) return true;
        $message = [
            'response_time'  => microtime(true) - self::$startTime,
            'request_uri'    => $request->getPathInfo(),
            'request_header' => $request->headers->all(),
            'request_body'   => $common->logReduce($request->all()),
            'response_body'  => $common->logReduce($response->getContent())
        ];

        $common->logger(config('app.app_name'),
            'requestlog.log',
            $message,
            Logger::INFO
        );

    }

}
