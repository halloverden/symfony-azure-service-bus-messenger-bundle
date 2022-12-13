<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class AzureServiceBusEntityPathStamp implements NonSendableStampInterface {
  private string $entityPath;

  /**
   * AzureServiceBusEntityPathStamp constructor.
   */
  public function __construct(string $entityPath) {
    $this->entityPath = $entityPath;
  }

  /**
   * @return string
   */
  public function getEntityPath(): string {
    return $this->entityPath;
  }

}
