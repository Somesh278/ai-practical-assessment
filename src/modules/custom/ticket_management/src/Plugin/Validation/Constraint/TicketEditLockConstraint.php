<?php

namespace Drupal\ticket_management\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Prevents edits to locked ticket fields when status is edit-locked.
 */
#[Constraint(
  id: 'TicketEditLock',
  label: new TranslatableMarkup('Ticket field edit lock', [], ['context' => 'Validation']),
  type: 'entity:ticket'
)]
class TicketEditLockConstraint extends CompositeConstraintBase {

  /**
   * Message shown when a locked field is changed.
   *
   * @var string
   */
  public $message = 'Cannot change @field while the ticket is @status.';

  /**
   * {@inheritdoc}
   */
  public function coversFields(): array {
    return ['title', 'description', 'priority', 'assignee'];
  }

}
