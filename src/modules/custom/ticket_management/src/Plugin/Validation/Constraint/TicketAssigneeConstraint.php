<?php

namespace Drupal\ticket_management\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates ticket assignee references point to active users.
 */
#[Constraint(
  id: 'TicketAssignee',
  label: new TranslatableMarkup('Ticket assignee', [], ['context' => 'Validation']),
  type: 'entity:ticket'
)]
class TicketAssigneeConstraint extends SymfonyConstraint {

  /**
   * Message shown when the assignee is blocked or missing.
   *
   * @var string
   */
  public $message = 'Assignee must be an active, non-blocked user.';

}
