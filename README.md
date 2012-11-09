# Geo

ASYNC HTTP XML JSON GEO SEARCH CACHED PHP SERVER

## Install

Install new configuration by run `composer create-project rithis/geo`.

## Configure (optional)

You can redefine default configuration in `settings.php` file in created configuration directory:

```php
<?php

return [
   'host' => '127.0.0.1',
   'port' => 8888,
   'cache_ttl' => 60*60*24,
   'memory_output_period' => 3,
];
```

Also you can manually configure geocoder providers in `providers.php`:

```php
<?php

use Geocoder\Provider;

return [
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
```

## Run server

```
php -d "apc.shm_size=16M" -d "apc.enable_cli=1" geo.php
```


## Use

You can make any geo query you want. Example:

```
http://localhost:8888/?q=8.8.8.8
http://localhost:8888/?q=Red Square
http://localhost:8888/?q=Paris
http://localhost:8888/?q=McDonalds New York
http://localhost:8888/?q=51.500752,-0.124656
```
