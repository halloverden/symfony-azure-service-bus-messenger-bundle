Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require halloverden/symfony-azure-service-bus-messenger-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require halloverden/symfony-azure-service-bus-messenger-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    HalloVerden\AzureServiceBusMessengerBundle\HalloVerdenAzureServiceBusMessengerBundle::class => ['all' => true],
];
```

Configuration
============

The Azure Service Bus DSN looks like this, where `sb-endoint` is usually `<namesapce>.servicebus.windows.net`

```
# .env
MESSENGER_TRANSPORT_DSN=azure-service-bus://<sb-endpoint>
```

The transport has a number of options:

| Option                 | Description                      | Default                   |
|------------------------|----------------------------------|---------------------------|
| shared_access_key_name | ASB access key name              | RootManageSharedAccessKey |
| shared_access_key      | ASB access key                   |                           |
| entity_path            | Topic or Queue                   | name of transport         |
| subscription           | Name of subscription             |                           |
| wait_time              | Long polling duration in seconds |                           |

You can change the `entity_path` runtime using the `AzureServiceBusEntityPathStamp`:
```php
$eventBus->dispatch($someMessage, [new AzureServiceBusEntityPathStamp('someEntityPath')]);
```

You can control the `entity_path` used on consume with:
```
php bin/console messenger:consume my_transport --queues=someEntityPath
```
