<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokeredMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class AzureServiceBusReceiver implements ReceiverInterface, QueueReceiverInterface {
  public const ENTITY_PATH_HEADER = 'X-ASB-Entity-Path';

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
    yield from $this->getEnvelope();
  }

  /**
   * @inheritDoc
   */
  public function ack(Envelope $envelope): void {
    $receivedStamp = $this->findAzureServiceBusReceivedStamp($envelope);
    $brokerProperties = $receivedStamp->getBrokerProperties();
    try {
      $this->connection->delete(
        $brokerProperties->getSequenceNumber() ?? $brokerProperties->getMessageId(),
        $brokerProperties->getLockToken(),
        $this->findAzureServiceBusEntityPathStamp($envelope)->getEntityPath()
      );
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }
  }

  /**
   * @inheritDoc
   */
  public function reject(Envelope $envelope): void {
    $receivedStamp = $this->findAzureServiceBusReceivedStamp($envelope);
    $brokerProperties = $receivedStamp->getBrokerProperties();
    try {
      $this->connection->delete(
        $brokerProperties->getSequenceNumber() ?? $brokerProperties->getMessageId(),
        $brokerProperties->getLockToken(),
        $this->findAzureServiceBusEntityPathStamp($envelope)->getEntityPath()
      );
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }
  }

  /**
   * @inheritDoc
   */
  public function getFromQueues(array $queueNames): iterable {
    foreach ($queueNames as $queueName) {
      yield from $this->getEnvelope($queueName);
    }
  }

  /**
   * @param string|null $queueName
   *
   * @return iterable
   */
  private function getEnvelope(?string $queueName = null): iterable {
    try {
      $brokerMessage = $this->connection->get($queueName);
    } catch (ExceptionInterface $e) {
      throw new TransportException($e->getMessage(), previous: $e);
    }

    if (null === $brokerMessage) {
      return;
    }

    $envelope = $this->serializer->decode([
      'body' => $brokerMessage->getBody(),
      'headers' => $this->createHeaders($brokerMessage),
      'customProperties' => iterator_to_array($brokerMessage->getCustomProperties())
    ]);

    yield $envelope
      ->with(new AzureServiceBusReceivedStamp($brokerMessage->getBrokerProperties(), $brokerMessage->getCustomProperties()))
      ->with(new AzureServiceBusEntityPathStamp($brokerMessage->getEntityPath()));
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
   * @param Envelope $envelope
   *
   * @return AzureServiceBusEntityPathStamp|null
   */
  private function findAzureServiceBusEntityPathStamp(Envelope $envelope): ?AzureServiceBusEntityPathStamp {
    $stamp = $envelope->last(AzureServiceBusEntityPathStamp::class);

    if (!$stamp instanceof AzureServiceBusEntityPathStamp) {
      throw new LogicException('No AzureServiceBusEntityPathStamp found in Envelope');
    }

    return $stamp;
  }

  /**
   * @param BrokeredMessage $brokeredMessage
   *
   * @return array
   */
  private function createHeaders(BrokeredMessage $brokeredMessage): array {
    $customProperties = $brokeredMessage->getCustomProperties();
    return json_decode($customProperties[Connection::MESSAGE_ATTRIBUTE_NAME] ?? '{}', true) + [self::ENTITY_PATH_HEADER => $brokeredMessage->getEntityPath()];
  }

}
