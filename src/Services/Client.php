<?php

namespace MedSpec\LaravelPostNLAPI\Services;

use Exception;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    protected static $client;

    public function __construct()
    {
        self::initGuzzleClient();
    }

    private static function initGuzzleClient()
    {
        self::$client = new GuzzleClient();
    }

    private static function prepareUrl($url, $customer)
    {
        $fullUrl = config('postnlapi.api.url');
        $fullUrl .= $url;
        
        if(!is_null($customer)){
            $fullUrl .= '&CustomerCode='.$customer->getCustomerCode().'&CustomerNumber='.$customer->getCustomerNumber();
        }

        return $fullUrl;
    }

    private static function handleResponse($data)
    {
        try {
            return json_decode($data, true);
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    public static function get($url, $customer = null )
    {
        $fullUrl = self::prepareUrl($url, $customer);

        try {
            $response = self::$client->request('GET', $fullUrl, [
                'headers' => [
                    'Content-Type' 	=> 'application/json; charset=UTF-8',
                    'apikey'		      => config('postnlapi.api.key'),
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                self::initGuzzleClient();

                return self::handleResponse($response->getBody());
            } else {
                throw new Exception('PostNL not reporting 200 status', 1);
            }
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    public static function post($url, $data, $customer)
    {
        $fullUrl = config('postnlapi.api.url').$url;

        try {
            $response = self::$client->request('POST', $fullUrl, [
                'headers' => [
                    'Content-Type' 	=> 'application/json; charset=UTF-8',
                    'apikey'		      => config('postnlapi.api.key'),
                ],
                'json' => $data,
            ]);
            if ($response->getStatusCode() == 200) {
                self::initGuzzleClient();

                return self::handleResponse($response->getBody());
            } else {
                throw new Exception('PostNL not reporting 200 status', 1);
            }
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }
}
