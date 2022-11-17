<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AzureServiceBusTransport implements TransportInterface {
  private ?ReceiverInterface $receiver = null;
  private ?SenderInterface $sender = null;

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
   */
  public function send(Envelope $envelope): Envelope {
    return $this->getSender()->send($envelope);
  }

  /**
   * @return ReceiverInterface
   */
  private function getReceiver(): ReceiverInterface {
    return $this->receiver ??= new AzureServiceBusReceiver($this->connection, $this->serializer);
  }

  /**
   * @return SenderInterface
   */
  private function getSender(): SenderInterface {
    return $this->sender ??= new AzureServiceBusSender($this->connection, $this->serializer);
  }

}
