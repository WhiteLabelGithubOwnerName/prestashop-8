[![Build Status](https://travis-ci.org/WhiteLabelGithubOwnerName/php-sdk.svg?branch=master)](https://travis-ci.org/WhiteLabelGithubOwnerName/php-sdk)

# WhiteLabelName PHP Library

The WhiteLabelName PHP library wraps around the WhiteLabelName API. This library facilitates your interaction with various services such as transactions, accounts, and subscriptions.


## Documentation

[WhiteLabelName Web Service API](https://app-wallee.com/doc/api/web-service)

## Requirements

- PHP 5.6.0 and above

## Installation

You can use **Composer** or **install manually**

### Composer

The preferred method is via [composer](https://getcomposer.org). Follow the
[installation instructions](https://getcomposer.org/doc/00-intro.md) if you do not already have
composer installed.

Once composer is installed, execute the following command in your project root to install this library:

```sh
composer require whitelabelmachinename/sdk
```

### Manual Installation

Alternatively you can download the package in its entirety. The [Releases](../../releases) page lists all stable versions.

Uncompress the zip file you download, and include the autoloader in your project:

```php
require_once '/path/to/php-sdk/autoload.php';
```

## Usage
The library needs to be configured with your account's space id, user id, and secret key which are available in your [WhiteLabelName
account dashboard](https://app-wallee.com/account/select). Set `space_id`, `user_id`, and `api_secret` to their values.

### Configuring a Service

```php
require_once(__DIR__ . '/autoload.php');

// Configuration
$spaceId = 405;
$userId = 512;
$secret = 'FKrO76r5VwJtBrqZawBspljbBNOxp5veKQQkOnZxucQ=';

// Setup API client
$client = new \WhiteLabelMachineName\Sdk\ApiClient($userId, $secret);

// Get API service instance
$client->getTransactionService();
$client->getTransactionPaymentPageService();

```

To get started with sending transactions, please review the example below:

```php
require_once(__DIR__ . '/autoload.php');

// Configuration
$spaceId = 405;
$userId = 512;
$secret = 'FKrO76r5VwJtBrqZawBspljbBNOxp5veKQQkOnZxucQ=';

// Setup API client
$client = new \WhiteLabelMachineName\Sdk\ApiClient($userId, $secret);

// Create transaction
$lineItem = new \WhiteLabelMachineName\Sdk\Model\LineItemCreate();
$lineItem->setName('Red T-Shirt');
$lineItem->setUniqueId('5412');
$lineItem->setSku('red-t-shirt-123');
$lineItem->setQuantity(1);
$lineItem->setAmountIncludingTax(29.95);
$lineItem->setType(\WhiteLabelMachineName\Sdk\Model\LineItemType::PRODUCT);


$transactionPayload = new \WhiteLabelMachineName\Sdk\Model\TransactionCreate();
$transactionPayload->setCurrency('EUR');
$transactionPayload->setLineItems(array($lineItem));
$transactionPayload->setAutoConfirmationEnabled(true);

$transaction = $client->getTransactionService()->create($spaceId, $transactionPayload);

// Create Payment Page URL:
$redirectionUrl = $client->getTransactionPaymentPageService()->paymentPageUrl($spaceId, $transaction->getId());

header('Location: ' . $redirectionUrl);

```
### HTTP Client
You can either use `php curl` or `php socket` extentions. It is recommend you install the necessary extentions and enable them on your system.

You have to ways two specify which HTTP client you prefer.

```php
$userId = 512;
$secret = 'FKrO76r5VwJtBrqZawBspljbBNOxp5veKQQkOnZxucQ=';

// Setup API client
$client = new \WhiteLabelMachineName\Sdk\ApiClient($userId, $secret);

$httpClientType = \WhiteLabelMachineName\Sdk\Http\HttpClientFactory::TYPE_CURL; // or \WhiteLabelMachineName\Sdk\Http\HttpClientFactory::TYPE_SOCKET

$client->setHttpClientType($httpClientType);

//Setup a custom connection timeout if needed. (Default value is: 25 seconds)
$client->setConnectionTimeout(20);
```

You can also specify the HTTP client via the `WLM_HTTP_CLIENT` environment variable. The possible string values are `curl` or `socket`.


```php
<?php
putenv('WLM_HTTP_CLIENT=curl');
?>
```

## License

Please see the [license file](https://github.com/WhiteLabelGithubOwnerName/php-sdk/blob/master/LICENSE) for more information.
