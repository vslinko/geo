<?php

require_once __DIR__ . '/vendor/autoload.php';

use Geocoder\Result\Geocoded,
    React\Http\Response,
    React\Http\Request,
    Geocoder\Provider;

// Settings
$settings = [
    'host' => '127.0.0.1',
    'port' => 8888,
    'cache_ttl' => 60*60*24,
    'memory_output_period' => 3,
];

if (is_readable(__DIR__ . '/settings.php')) {
    $settings = array_merge($settings, include __DIR__ . '/settings.php');
}

// Adapter
$buzz = new Buzz\Browser(new Buzz\Client\Curl());
$adapter = new Geocoder\HttpAdapter\BuzzHttpAdapter($buzz);

// Providers
if (is_readable(__DIR__ . '/providers.php')) {
    $providers = include __DIR__ . '/providers.php';
} else {
    $providers = [
        new Provider\ChainProvider([
            new Provider\FreeGeoIpProvider($adapter),
            new Provider\HostIpProvider($adapter),
            new Provider\GoogleMapsProvider($adapter),
            new Provider\OpenStreetMapsProvider($adapter),
            new Provider\MapQuestProvider($adapter),
            new Provider\OIORestProvider($adapter),
            new Provider\GeocoderCaProvider($adapter),
            new Provider\GeocoderUsProvider($adapter),
            new Provider\DataScienceToolkitProvider($adapter),
            new Provider\YandexProvider($adapter),
        ])
    ];
}

// Geocoder
$geocoder = new Geocoder\Geocoder();
$geocoder->registerProviders($providers);

// Espresso
$app = new React\Espresso\Application();

$controller = function ($fetcher, $formatter) {
    return function (Request $request, Response $response) use ($fetcher, $formatter) {
        $result = $fetcher($request);

        if ($result) {
            $formatter($request, $response, $result);
        } else {
            $response->writeHead(400);
        }

        $response->end();
    };
};

// Formatter
$json = new Geocoder\Dumper\GeoJsonDumper();
$xml = new Geocoder\Dumper\GpxDumper();
$formatter = function (Request $request, Response $response, Geocoded $result) use ($json, $xml) {
    $headers = $request->getHeaders();

    if (isset($headers['Accept']) && strpos($headers['Accept'], 'application/xml') !== false) {
        $response->writeHead(200, ['Content-Type' => 'application/xml']);
        $response->write($xml->dump($result));
    } else {
        $response->writeHead(200, ['Content-Type' => 'application/json']);
        $response->write($json->dump($result));
    }
};

// Cache
$cached = function ($q, $fetcher) use ($settings) {
    $result = apc_fetch($q, $success);

    if (!$success) {
        $result = $fetcher($q);
        apc_store($q, $result, $settings['cache_ttl']);
    }

    return $result;
};

// Routes
$app->get('/', $controller(function (Request $request) use ($geocoder, $cached) {
    $query = $request->getQuery();

    if (isset($query['q'])) {
        return $cached($query['q'], function ($q) use ($geocoder) {
            if (strpos($q, ',') !== false && preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $q)) {
                list($lat, $lon) = explode(',', $q);
                return $geocoder->reverse($lat, $lon);
            } else {
                return $geocoder->geocode($q);
            }
        });
    }

    return false;
}, $formatter));

// Server
$stack = new React\Espresso\Stack($app);

if ($settings['memory_output_period'] > 0) {
    $stack['loop']->addPeriodicTimer($settings['memory_output_period'], function () {
        $mem = round(memory_get_usage() / 1024 / 1024);
        $apc = apc_sma_info();
        $apc = round(($apc['seg_size'] - $apc['avail_mem']) / 1024 / 1024);

        echo "MEM: {$mem}M APC:{$apc}M\n";
    });
}

$stack->listen($settings['port'], $settings['host']);
