<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\CustomProperties;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class AzureServiceBusReceiver implements ReceiverInterface {

  /**
   * AzureServiceBusReceiver constructor.
   */
  public function __construct(
    private readonly Connection $connection,
    private readonly SerializerInterface $serializer
  ) {
  }

  /**
   * @inheritDoc
   */
  public function get(): iterable {
    try {
      $brokerMessage = $this->connection->get();
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }

    if (null === $brokerMessage) {
      return;
    }

    $envelope = $this->serializer->decode([
      'body' => $brokerMessage->getBody(),
      'headers' => $this->createHeaders($brokerMessage->getCustomProperties())
    ]);

    yield $envelope->with(new AzureServiceBusReceivedStamp($brokerMessage->getBrokerProperties(), $brokerMessage->getCustomProperties()));
  }

  /**
   * @inheritDoc
   */
  public function ack(Envelope $envelope): void {
    $brokerProperties = $this->findAzureServiceBusReceivedStamp($envelope)->getBrokerProperties();
    try {
      $this->connection->delete($brokerProperties->getSequenceNumber() ?? $brokerProperties->getMessageId(), $brokerProperties->getLockToken());
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }
  }

  /**
   * @inheritDoc
   */
  public function reject(Envelope $envelope): void {
    $brokerProperties = $this->findAzureServiceBusReceivedStamp($envelope)->getBrokerProperties();
    try {
      $this->connection->delete($brokerProperties->getSequenceNumber() ?? $brokerProperties->getMessageId(), $brokerProperties->getLockToken());
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }
  }

  /**
   * @param Envelope $envelope
   *
   * @return AzureServiceBusReceivedStamp
   */
  private function findAzureServiceBusReceivedStamp(Envelope $envelope): AzureServiceBusReceivedStamp {
    $stamp = $envelope->last(AzureServiceBusReceivedStamp::class);

    if (!$stamp instanceof AzureServiceBusReceivedStamp) {
      throw new LogicException('No AzureServiceBusReceivedStamp found in Envelope');
    }

    return $stamp;
  }

  /**
   * @param CustomProperties $customProperties
   *
   * @return array
   */
  private function createHeaders(CustomProperties $customProperties): array {
    return json_decode($customProperties[Connection::MESSAGE_ATTRIBUTE_NAME] ?? [], true);
  }

}
