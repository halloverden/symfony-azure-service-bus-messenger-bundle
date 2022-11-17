<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus;

use HalloVerden\AzureServiceBusMessengerBundle\Azure\SasTokenFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ServiceBusClient {
  private const RECEIVE_SUBSCRIPTION_MESSAGE_PATH = '%s/subscriptions/%s/messages/head';
  private const RECEIVE_MESSAGE_PATH = '%s/messages/head';
  private const SEND_MESSAGE_PATH = '%s/messages';
  private const DELETE_SUBSCRIPTION_MESSAGE_PATH = '%s/subscriptions/%s/messages/%s/%s';
  private const DELETE_MESSAGE_PATH = '%s/messages/%s/%s';

  private const BROKERED_PROPERTIES_HEADER = 'brokerproperties';

  private readonly SasTokenFactory $sasTokenFactory;

  /**
   * ServiceBusClient constructor.
   */
  public function __construct(
    private readonly HttpClientInterface $client,
    private readonly string $baseUrl,
    string $sharedAccessKeyName,
    string $sharedAccessKey,
  ) {
    $this->sasTokenFactory = new SasTokenFactory($sharedAccessKeyName, $sharedAccessKey);
  }

  /**
   * @param string      $entityPath
   * @param string|null $subscription
   * @param int|null    $timeout
   * @param ReceiveMode $receiveMode
   *
   * @return BrokeredMessage|null
   * @throws ExceptionInterface
   */
  public function receiveMessage(string $entityPath, ?string $subscription = null, ?int $timeout = null, ReceiveMode $receiveMode = ReceiveMode::PEEK_LOCK): ?BrokeredMessage {
    $url = $this->createReceiveMessageUrl($entityPath, $subscription);

    $query = [];
    if (null !== $timeout) {
      $query['timeout'] = $timeout;
    }

    $response = $this->client->request($receiveMode->getHttpMethod(), $url, [
      'headers' => [
        'Authorization' => $this->sasTokenFactory->create($url)
      ],
      'query' => $query
    ]);

    if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
      return null;
    }

    $content = $response->getContent();
    $headers = $response->getHeaders();

    return new BrokeredMessage($content, $this->createBrokerProperties($headers), CustomProperties::createWithResponseHeaders($headers));
  }

  /**
   * @param string          $entityPath
   * @param BrokeredMessage $brokeredMessage
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function sendMessage(string $entityPath, BrokeredMessage $brokeredMessage): void {
    $url = $this->createUrl(self::SEND_MESSAGE_PATH, $entityPath);

    $headers = [
      'Authorization' => $this->sasTokenFactory->create($url),
      'BrokerProperties' => json_encode($brokeredMessage->getBrokerProperties()),
      ...$brokeredMessage->getCustomProperties()
    ];

    if (null !== $contentType = $brokeredMessage->getBrokerProperties()->getContentType()) {
      $headers['Content-Type'] = $contentType;
    }

    $response = $this->client->request(Request::METHOD_POST, $url, [
      'headers' => $headers,
      'body' => $brokeredMessage->getBody()
    ]);

    $response->getContent();
  }

  /**
   * @param string      $entityPath
   * @param string      $messageIdOrSequenceNumber
   * @param string      $lockToken
   *
   * @param string|null $subscription
   *
   * @return void
   * @throws ExceptionInterface
   */
  public function deleteMessage(string $entityPath, string $messageIdOrSequenceNumber, string $lockToken, ?string $subscription = null): void {
    $url = $this->createDeleteMessageUrl($entityPath, $messageIdOrSequenceNumber, $lockToken, $subscription);

    $response = $this->client->request(Request::METHOD_DELETE, $url, [
      'headers' => [
        'Authorization' => $this->sasTokenFactory->create($url),
      ]
    ]);

    $response->getContent();
  }

  /**
   * @param string      $entityPath
   * @param string|null $subscription
   *
   * @return string
   */
  private function createReceiveMessageUrl(string $entityPath, ?string $subscription = null): string {
    if (null === $subscription) {
      return $this->createUrl(self::RECEIVE_MESSAGE_PATH, $entityPath);
    }

    return $this->createUrl(self::RECEIVE_SUBSCRIPTION_MESSAGE_PATH, $entityPath, $subscription);
  }

  /**
   * @param string      $entityPath
   * @param string      $messageIdOrSequenceNumber
   * @param string      $lockToken
   * @param string|null $subscription
   *
   * @return string
   */
  private function createDeleteMessageUrl(string $entityPath, string $messageIdOrSequenceNumber, string $lockToken, ?string $subscription = null): string {
    if (null === $subscription) {
      return $this->createUrl(self::DELETE_MESSAGE_PATH, $entityPath, $messageIdOrSequenceNumber, $lockToken);
    }

    return $this->createUrl(self::DELETE_SUBSCRIPTION_MESSAGE_PATH, $entityPath, $subscription, $messageIdOrSequenceNumber, $lockToken);
  }

  /**
   * @param string $path
   * @param mixed  ...$values
   *
   * @return string
   */
  private function createUrl(string $path, mixed ...$values): string {
    return $this->baseUrl . \sprintf($path, ...$values);
  }

  /**
   * @param array $headers
   *
   * @return BrokerProperties
   */
  private function createBrokerProperties(array & $headers): BrokerProperties {
    if (!isset($headers[self::BROKERED_PROPERTIES_HEADER][0])) {
      return new BrokerProperties();
    }

    $brokeredPropertiesArray = json_decode($headers[self::BROKERED_PROPERTIES_HEADER][0], true);
    unset($headers[self::BROKERED_PROPERTIES_HEADER]);
    return new BrokerProperties($brokeredPropertiesArray);
  }

}
