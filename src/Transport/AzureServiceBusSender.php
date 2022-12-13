<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokeredMessage;
use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokerProperties;
use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\CustomProperties;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class AzureServiceBusSender implements SenderInterface {

  /**
   * AzureServiceBusSender constructor.
   */
  public function __construct(
    private readonly Connection $connection,
    private readonly SerializerInterface $serializer
  ) {
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function send(Envelope $envelope): Envelope {
    $encodedMessage = $this->serializer->encode($envelope);

    $brokerProperties = new BrokerProperties();

    $delayStamp = $envelope->last(DelayStamp::class);
    if ($delayStamp instanceof DelayStamp) {
      $brokerProperties->setScheduledEnqueueTimeUtc(new \DateTimeImmutable('@' . time() + intval($delayStamp->getDelay() / 1000)));
    }

    if (isset($encodedMessage['headers']['Content-Type'])) {
      $brokerProperties->setContentType($encodedMessage['headers']['Content-Type']);
    }

    try {
      $this->connection->send(
        new BrokeredMessage(
          $encodedMessage['body'],
          $brokerProperties,
          new CustomProperties([Connection::MESSAGE_ATTRIBUTE_NAME => \json_encode($encodedMessage['headers'] ?? [])])
        ),
        $this->getEntityPath($envelope)
      );
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }

    return $envelope;
  }

  /**
   * @param Envelope $envelope
   *
   * @return string|null
   */
  private function getEntityPath(Envelope $envelope): ?string {
    $entityPathStamp = $envelope->last(AzureServiceBusEntityPathStamp::class);

    if ($entityPathStamp instanceof AzureServiceBusEntityPathStamp) {
      return $entityPathStamp->getEntityPath();
    }

    return null;
  }

}
