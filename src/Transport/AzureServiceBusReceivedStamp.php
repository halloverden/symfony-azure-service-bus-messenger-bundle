<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokerProperties;
use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\CustomProperties;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AzureServiceBusReceivedStamp implements NonSendableStampInterface {
  private BrokerProperties $brokerProperties;
  private CustomProperties $customProperties;
  private string $entityPath;

  /**
   * AzureServiceBusReceivedStamp constructor.
   */
  public function __construct(BrokerProperties $brokerProperties, CustomProperties $customProperties, string $entityPath) {
    $this->brokerProperties = $brokerProperties;
    $this->customProperties = $customProperties;
    $this->entityPath = $entityPath;
  }

  /**
   * @return BrokerProperties
   */
  public function getBrokerProperties(): BrokerProperties {
    return $this->brokerProperties;
  }

  /**
   * @return CustomProperties
   */
  public function getCustomProperties(): CustomProperties {
    return $this->customProperties;
  }

  /**
   * @return string
   */
  public function getEntityPath(): string {
    return $this->entityPath;
  }

}
