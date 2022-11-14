<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus;

class BrokerProperties implements \JsonSerializable {
  private const KEY_MESSAGE_ID = 'MessageId';
  private const KEY_SEQUENCE_NUMBER = 'SequenceNumber';
  private const KEY_LOCK_TOKEN = 'LockToken';
  private const KEY_CONTENT_TYPE = 'ContentType';
  private const KEY_SCHEDULED_ENQUEUE_TIME_UTC = 'ScheduledEnqueueTimeUtc';

  /**
   * BrokerProperties constructor.
   */
  public function __construct(private array $properties = []) {
  }

  /**
   * @return string|null
   */
  public function getMessageId(): ?string {
    return $this->properties[self::KEY_MESSAGE_ID] ?? null;
  }

  /**
   * @return string|null
   */
  public function getSequenceNumber(): ?string {
    return $this->properties[self::KEY_SEQUENCE_NUMBER] ?? null;
  }

  /**
   * @return string|null
   */
  public function getLockToken(): ?string {
    return $this->properties[self::KEY_LOCK_TOKEN] ?? null;
  }

  /**
   * @return string|null
   */
  public function getContentType(): ?string {
    return $this->properties[self::KEY_CONTENT_TYPE] ?? null;
  }

  /**
   * @param string|null $contentType
   *
   * @return $this
   */
  public function setContentType(?string $contentType): self {
    $this->properties[self::KEY_CONTENT_TYPE] = $contentType;
    return $this;
  }

  /**
   * @return \DateTimeInterface|null
   */
  public function getScheduledEnqueueTimeUtc(): ?\DateTimeInterface {
    if (!isset($this->properties[self::KEY_SCHEDULED_ENQUEUE_TIME_UTC])) {
      return null;
    }

    return \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $this->properties[self::KEY_SCHEDULED_ENQUEUE_TIME_UTC]);
  }

  /**
   * @param \DateTimeInterface|null $dateTime
   *
   * @return $this
   */
  public function setScheduledEnqueueTimeUtc(?\DateTimeInterface $dateTime): self {
    $this->properties[self::KEY_SCHEDULED_ENQUEUE_TIME_UTC] = $dateTime?->format(\DateTimeInterface::RFC1123);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function jsonSerialize(): array {
    return $this->properties;
  }

}
