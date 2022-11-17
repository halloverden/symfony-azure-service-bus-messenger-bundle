<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Transport;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\BrokeredMessage;
use HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus\ServiceBusClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
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
    private readonly string $entityPath,
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
    $entityPath = $options['entity_path'] ?? $options['transport_name'] ?? new InvalidArgumentException('The given Azure Service Bus DSN must contain an entity path (entity_path={entity_path})');
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
   * @return BrokeredMessage|null
   * @throws ExceptionInterface
   */
  public function get(): ?BrokeredMessage {
    return $this->client->receiveMessage($this->entityPath, $this->subscription, $this->waitTime);
  }

  /**
   * @param BrokeredMessage $brokeredMessage
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function send(BrokeredMessage $brokeredMessage): void {
    $this->client->sendMessage($this->entityPath, $brokeredMessage);
  }

  /**
   * @param string $messageIdOrSequenceNumber
   * @param string $lockToken
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function delete(string $messageIdOrSequenceNumber, string $lockToken): void {
    $this->client->deleteMessage($this->entityPath, $messageIdOrSequenceNumber, $lockToken, $this->subscription);
  }

}
