<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus;

class BrokeredMessage {
  private ?string $entityPath = null;

  /**
   * BrokeredMessage constructor.
   */
  public function __construct(
    private readonly string $body,
    private readonly BrokerProperties $brokerProperties = new BrokerProperties(),
    private readonly CustomProperties $customProperties = new CustomProperties()
  ) {
  }

  /**
   * @return string
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * @return BrokerProperties
   */
  public function getBrokerProperties(): BrokerProperties {
    return $this->brokerProperties;
  }

  /**
   * @return CustomProperties<string, string>
   */
  public function getCustomProperties(): CustomProperties {
    return $this->customProperties;
  }

  /**
   * @return string|null
   */
  public function getEntityPath(): ?string {
    return $this->entityPath;
  }

  /**
   * @param string|null $entityPath
   *
   * @return BrokeredMessage
   */
  public function setEntityPath(?string $entityPath): BrokeredMessage {
    $this->entityPath = $entityPath;
    return $this;
  }

}
