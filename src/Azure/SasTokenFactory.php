<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure;

final class SasTokenFactory {

  /**
   * SasToken constructor.
   */
  public function __construct(
    private readonly string $sharedAccessKeyName,
    private readonly string $sharedAccessKey
  ) {
  }

  /**
   * See https://learn.microsoft.com/en-us/rest/api/eventhub/generate-sas-token#php
   *
   * @param string $url
   *
   * @return string
   */
  public function create(string $url): string {
    $uri = \strtolower(\rawurldecode(\strtolower($url)));
    $expires = time() + 3600;
    $toSign = $uri. "\n" . $expires;
    $signature = rawurlencode(\base64_encode(\hash_hmac('sha256', $toSign, $this->sharedAccessKey, true)));
    return \sprintf('SharedAccessSignature sig=%s&se=%s&skn=%s&sr=%s', $signature, $expires, $this->sharedAccessKeyName, $uri);
  }

}
