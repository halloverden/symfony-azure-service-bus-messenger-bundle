<?php

namespace HalloVerden\AzureServiceBusMessengerBundle\Azure\ServiceBus;

use Traversable;

/**
 * All keys in a custom properties must be case insensitive,
 *  and it cannot contain multiple values
 *
 * @psalm-template TKey of array-key
 * @psalm-template T
 * @template-extends \ArrayAccess<TKey, T>
 */
final class CustomProperties implements \ArrayAccess, \IteratorAggregate {
  private array $properties;
  private array $originalProperties;

  /**
   * CustomProperties constructor.
   */
  public function __construct(array $properties = []) {
    $this->properties = \array_change_key_case($properties);
    $this->originalProperties = $properties;
  }

  /**
   * @param array $headers
   *
   * @return static
   */
  public static function createWithResponseHeaders(array $headers): self {
    $properties = [];
    foreach ($headers as $name => $values) {
      if (isset($values[0])) {
        $properties[$name] = \stripslashes(\trim($values[0], '"'));
      }
    }

    return new self($properties);
  }

  /**
   * @inheritDoc
   */
  public function offsetExists(mixed $offset): bool {
    return \array_key_exists($this->keyToLower($offset), $this->properties);
  }

  /**
   * @inheritDoc
   */
  public function offsetGet(mixed $offset): mixed {
    return $this->properties[$this->keyToLower($offset)] ?? null;
  }

  /**
   * @inheritDoc
   */
  public function offsetSet(mixed $offset, mixed $value): void {
    if (null === $offset) {
      $this->properties[] = $value;
      return;
    }

    $this->properties[$this->keyToLower($offset)] = $value;
    $this->originalProperties[$offset] = $value;
  }

  /**
   * @inheritDoc
   */
  public function offsetUnset(mixed $offset): void {
    unset($this->properties[$offset = $this->keyToLower($offset)]);

    foreach ($this->originalProperties as $key => $value) {
      if ($this->keyToLower($key) === $offset) {
        unset($this->originalProperties[$key]);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getIterator(): Traversable {
    return new \ArrayIterator($this->originalProperties);
  }

  /**
   * @param mixed $offset
   *
   * @return mixed
   */
  private function keyToLower(mixed $offset): mixed {
    if (\is_string($offset)) {
      return \strtolower($offset);
    }

    return $offset;
  }

}
