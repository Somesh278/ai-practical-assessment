<?php

namespace Drupal\ticket_management\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates ticket status transitions.
 */
#[Constraint(
  id: 'TicketStatusTransition',
  label: new TranslatableMarkup('Ticket status transition', [], ['context' => 'Validation']),
  type: 'entity:ticket'
)]
class TicketStatusTransitionConstraint extends SymfonyConstraint {

  /**
   * Message shown when a status transition is not allowed.
   *
   * @var string
   */
  public $message = 'Cannot move a ticket from @from to @to.';

}
