<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus;

use Symfony\Component\HttpFoundation\Request;

enum ReceiveMode {
  case RECEIVE_AND_DELETE;
  case PEEK_LOCK;

  /**
   * @return string
   */
  public function getHttpMethod(): string {
    return match ($this) {
      self::RECEIVE_AND_DELETE => Request::METHOD_DELETE,
      self::PEEK_LOCK => Request::METHOD_POST,
    };
  }
}
