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

	/**
	 * 请求日志 & 上报接口状态
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Illuminate\Http\Response $response 
	 */
    public function terminate($request, $response)
    {
    	/** @var \App\Libraries\Common $common */
        $common = app('Common');
        
	    $falcon_message = [
		    'response_time'  => microtime(true) - self::$startTime,
		    'request_uri'    => $request->getPathInfo(),
		    'status_code'    => $response->getStatusCode(),
		    'server_name'    => config('app.app_name'),
	    ];

	    try {
		    $url = env('FALCON_API', 'http://10.10.32.180/falcon_agent');
		    $common->request($url, $falcon_message);
	    } catch (\Exception $e) {
	    }
	    
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
