<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokeredMessage;
use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\ServiceBusClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Connection {
  public const MESSAGE_ATTRIBUTE_NAME = 'X-Symfony-Messenger';

  private const DEFAULT_OPTIONS = [
    'shared_access_key_name' => 'RootManageSharedAccessKey',
    'shared_access_key' => null,
    'entity_path' => null,
    'subscription' => null,
    'wait_time' => null,
    'transport_name' => null,
  ];

  /**
   * Connection constructor.
   */
  public function __construct(
    private readonly ServiceBusClient $client,
    private readonly ?string $entityPath = null,
    private readonly ?string $subscription = null,
    private readonly ?int $waitTime = null
  ) {
  }

  /**
   * @param string                   $dsn
   * @param array                    $options
   * @param HttpClientInterface|null $httpClient
   *
   * @return static
   */
  public static function fromDsn(string $dsn, array $options, ?HttpClientInterface $httpClient = null): self {
    if (false === $parsedUrl = parse_url($dsn)) {
      throw new InvalidArgumentException(\sprintf('The given Azure Service Bus DSN "%s" is invalid.', $dsn));
    }

    $query = [];
    if (isset($parsedUrl['query'])) {
      parse_str($parsedUrl['query'], $query);
    }

    // check for extra keys in options
    $optionsExtraKeys = array_diff(array_keys($options), array_keys(self::DEFAULT_OPTIONS));
    if (0 < \count($optionsExtraKeys)) {
      throw new InvalidArgumentException(sprintf('Unknown option found: [%s]. Allowed options are [%s].', implode(', ', $optionsExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
    }

    // check for extra keys in $query
    $queryExtraKeys = array_diff(array_keys($query), array_keys(self::DEFAULT_OPTIONS));
    if (0 < \count($queryExtraKeys)) {
      throw new InvalidArgumentException(sprintf('Unknown option found in DSN: [%s]. Allowed options are [%s].', implode(', ', $queryExtraKeys), implode(', ', array_keys(self::DEFAULT_OPTIONS))));
    }

    $options = $query + $options + self::DEFAULT_OPTIONS;

    $sharedAccessKeyName = $options['shared_access_key_name'];
    $sharedAccessKey = $options['shared_access_key'] ?? new InvalidArgumentException('The given Azure Service Bus DSN must contain a shared access key (shared_access_key={key})');
    $entityPath = $options['entity_path'] ?? $options['transport_name'] ?? null;
    $subscription = $options['subscription'] ?? null;
    $waitTime = $options['wait_time'] ?? null;

    if (null !== $waitTime && !(ctype_digit($waitTime) || is_int($waitTime))) {
      throw new InvalidArgumentException('Timeout in the Azure Service Bus DSN must be number');
    }

    $serviceBusClient = new ServiceBusClient($httpClient ?? HttpClient::create(), self::createBaseUri($parsedUrl), $sharedAccessKeyName, $sharedAccessKey);
    return new self($serviceBusClient, $entityPath, $subscription, $waitTime);
  }

  /**
   * @param array $parsedUrl
   *
   * @return string
   */
  private static function createBaseUri(array $parsedUrl): string {
    $host = $parsedUrl['host'] ?? throw new InvalidArgumentException('The given Azure Service Bus DSN must contain a hostname');
    return \sprintf('https://%s/', $host);
  }

  /**
   * @param string|null $entityPath
   *
   * @return BrokeredMessage|null
   * @throws ExceptionInterface
   */
  public function get(?string $entityPath = null): ?BrokeredMessage {
    $entityPath ??= $this->entityPath ?? throw new TransportException('entityPath not found');
    return $this->client->receiveMessage($entityPath, $this->subscription, $this->waitTime)?->setEntityPath($entityPath);
  }

  /**
   * @param BrokeredMessage $brokeredMessage
   * @param string|null     $entityPath
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function send(BrokeredMessage $brokeredMessage, ?string $entityPath = null): void {
    $entityPath ??= $this->entityPath ?? throw new TransportException('entityPath not found');
    $this->client->sendMessage($entityPath, $brokeredMessage);
  }

  /**
   * @param string      $messageIdOrSequenceNumber
   * @param string      $lockToken
   * @param string|null $entityPath
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function delete(string $messageIdOrSequenceNumber, string $lockToken, string $entityPath = null): void {
    $entityPath ??= $this->entityPath ?? throw new TransportException('entityPath not found');
    $this->client->deleteMessage($entityPath, $messageIdOrSequenceNumber, $lockToken, $this->subscription);
  }

}
