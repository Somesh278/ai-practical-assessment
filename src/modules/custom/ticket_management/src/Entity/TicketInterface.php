<?php

namespace Drupal\ticket_management\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for Ticket entities.
 */
interface TicketInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the ticket title.
   *
   * @return string
   *   The ticket title.
   */
  public function getTitle(): string;

  /**
   * Sets the ticket title.
   *
   * @param string $title
   *   The ticket title.
   *
   * @return $this
   */
  public function setTitle(string $title): static;

  /**
   * Gets the ticket status value.
   *
   * @return string
   *   The status machine name.
   */
  public function getStatus(): string;

  /**
   * Sets the ticket status value.
   *
   * @param string $status
   *   The status machine name.
   *
   * @return $this
   */
  public function setStatus(string $status): static;

}
