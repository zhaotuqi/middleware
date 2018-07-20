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
        $cost_time = microtime(true) - self::$startTime;

        $message = [
            'response_time'  => $cost_time,
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

	    // 生产环境上报接口状态
	    if (app()->environment() == 'production' || app()->environment() == 'pro') {
		    $url = env("FALCON_AGENT");
		    if ( ! empty($url)) {
			    $falcon_message = [
				    'response_time' => $cost_time,
				    'request_uri'   => $request->getPathInfo(),
				    'status_code'   => $response->getStatusCode(),
				    'server_name'   => config('app.app_name'),
			    ];

			    try {
				    $common->request($url, $falcon_message);
			    } catch (\Exception $e) {
			    }
		    }
	    }
    }

}
