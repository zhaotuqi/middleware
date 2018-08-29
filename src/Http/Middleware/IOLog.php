<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Monolog\Logger;
use Monitor;

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
		    if (env("FALCON") && class_exists('Monitor')) {
		        try {
                    Monitor\Client::inc($request->getPathInfo() . "_qpm");
                    Monitor\Client::cost($request->getPathInfo() . "_cost", $cost_time * 1000); // 耗时是以毫秒计算
                } catch (Exception $e) {
                    Log::info('记录失败' . $e->getMessage());
                }
            }
	    }
    }

}
