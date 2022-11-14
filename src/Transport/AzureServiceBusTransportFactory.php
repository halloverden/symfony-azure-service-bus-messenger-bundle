<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AzureServiceBusTransportFactory implements TransportFactoryInterface {

  public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface {
    return new AzureServiceBusTransport(Connection::fromDsn($dsn, $options), $serializer);
  }

  public function supports(string $dsn, array $options): bool {
    return \str_starts_with($dsn, 'azure-service-bus://');
  }

}
