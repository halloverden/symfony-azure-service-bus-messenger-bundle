<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AzureServiceBusTransport implements TransportInterface, QueueReceiverInterface {
  private ?AzureServiceBusReceiver $receiver = null;
  private ?AzureServiceBusSender $sender = null;

  /**
   * AzureServiceBusTransport constructor.
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
    return $this->getReceiver()->get();
  }

  /**
   * @inheritDoc
   */
  public function ack(Envelope $envelope): void {
    $this->getReceiver()->ack($envelope);
  }

  /**
   * @inheritDoc
   */
  public function reject(Envelope $envelope): void {
    $this->getReceiver()->reject($envelope);
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function send(Envelope $envelope): Envelope {
    return $this->getSender()->send($envelope);
  }

  /**
   * @inheritDoc
   */
  public function getFromQueues(array $queueNames): iterable {
    return $this->getReceiver()->getFromQueues($queueNames);
  }

  /**
   * @return AzureServiceBusReceiver
   */
  private function getReceiver(): AzureServiceBusReceiver {
    return $this->receiver ??= new AzureServiceBusReceiver($this->connection, $this->serializer);
  }

  /**
   * @return AzureServiceBusSender
   */
  private function getSender(): AzureServiceBusSender {
    return $this->sender ??= new AzureServiceBusSender($this->connection, $this->serializer);
  }

}
