<img src="https://dealt.fr/logo.svg" width="200"/>

## Dealt PHP SDK

### Installation ⚙️

###### Requirements

[PHP 7+](https://php.net/releases/) - _library tested on php 7.0, 7.1, 7.2, 7.3, 7.4 & 8.1_

###### Composer

```bash
composer require dealt/dealt-sdk
```

### Usage ✨

###### Dealt Client initialization

Initalize the Dealt client with your api key.
You can specify the API environment you want to target using the `DealtEnvironment` constants. Use `DealtEnvironment.TEST` for development purposes.

```php
use Dealt\DealtSDK\DealtClient;
use Dealt\DealtSDK\DealtEnvironment;

$client = new DealtClient([
    "api_key" => "secret_dealt_api_key",
    "env" => DealtEnvironment::$PRODUCTION
]);
```

###### Checking offer availability

Check if an offer is available for a given country / zipCode :

```php
/** @var Dealt\DealtSDK\GraphQL\Types\Object\OfferAvailabilityQuerySuccess */
$offer = $client->offers->availability([
    'offer_id' => 'your-offer-uuid',
    'address'  => [
        'country' => 'France',
        'zip_code' => '75016',
    ]
]);

$available = $offer->available;

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Money */
$net_price = $offer->net_price;

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Money */
$gross_price = $offer->gross_price;

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Money */
$vat_price = $offer->vat_price;
```

###### Getting a mission by id

```php
/** @var Dealt\DealtSDK\GraphQL\Types\Object\MissionQuerySuccess */
$result = $client->missions->get("your-mission-id");

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Mission */
$mission = $result->mission;

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Offer */
$offer = $mission->offer;
```

###### Getting all missions

```php
/** @var Dealt\DealtSDK\GraphQL\Types\Object\MissionsQuerySuccess */
$result = $client->missions->all();

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Mission[] */
$missions = $result->missions;
```

###### Submitting a mission

```php
/** @var Dealt\DealtSDK\GraphQL\Types\Object\SubmitMissionMutationSuccess */
$result = $client->missions->submit([
    "offer_id" => "your-offer-id",
    "address" => [
        "country" => "France",
        "zip_code" => "92190"
    ],
    "customer" => [
        "first_name" => "John",
        "last_name" => "Doe",
        "email_address" => "xxx@yyy.zzz",
        "phone_number" => "+33700000000"
    ],
    "webHookUrl" => "https://optional.webhook.url",
    "extraDetails" => "https://test-shop.com/optional/product.html"
]);

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Mission */
$mission = $result->mission;
```

###### Canceling a mission

```php
/** @var Dealt\DealtSDK\GraphQL\Types\Object\CancelMissionMutationSuccess */
$result = $client->missions->cancel("your-mission-id");

/** @var Dealt\DealtSDK\GraphQL\Types\Object\Mission */
$mission = $result->mission;
```

###### Return types

Common return types you will encounter while interacting with the Dealt PHP SDK :

**Dealt\DealtSDK\GraphQL\Types\Object\Mission**

| property       | type                                           |
| -------------- | ---------------------------------------------- |
| **id**         | string                                         |
| **offer**      | Dealt\\DealtSDK\\GraphQL\\Types\\Object\\Offer |
| **status**     | string                                         |
| **created_at** | string                                         |

**Dealt\DealtSDK\GraphQL\Types\Object\Offer**

| property | type   |
| -------- | ------ |
| **id**   | string |
| **name** | string |

**Dealt\DealtSDK\GraphQL\Types\Object\Money**

| property          | type   |
| ----------------- | ------ |
| **currency_code** | string |
| **amount**        | float  |

### Development 👨🏼‍💻

In order to run the E2E tests you will need to export the following environment variables in your current session (or automatically source them in your .zshrc or .bashrc)

```bash
DEALT_TEST_API_KEY=your-secret-api-key
DEALT_TEST_OFFER_ID=your-offer-id
```

```bash
composer lint # lint source files
composer test:lint # ensure valid codestyle
composer test:types # phpstan reporting
composer test:unit # phpunit tests
```

### Dealt GraphQL API compatibility ✨

| GraphQL Operation     | operation type | supported |
| --------------------- | -------------- | --------- |
| **offerAvailability** | _query_        | ✅        |
| **missions**          | _query_        | ✅        |
| **mission**           | _query_        | ✅        |
| **submitMission**     | _mutation_     | ✅        |
| **cancelMission**     | _mutation_     | ✅        |
