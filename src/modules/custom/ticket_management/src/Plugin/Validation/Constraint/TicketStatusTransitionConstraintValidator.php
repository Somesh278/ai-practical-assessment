<?php

namespace Drupal\ticket_management\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ticket_management\Entity\Ticket;
use Drupal\ticket_management\Entity\TicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketStatusTransition constraint.
 */
class TicketStatusTransitionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a TicketStatusTransitionConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint): void {
    if (!$entity instanceof TicketInterface) {
      return;
    }

    if ($entity->isNew()) {
      return;
    }

    /** @var \Drupal\ticket_management\Plugin\Validation\Constraint\TicketStatusTransitionConstraint $constraint */
    $original = $this->getOriginalEntity($entity);
    if (!$original instanceof TicketInterface) {
      return;
    }

    $from_status = $original->getStatus();
    $to_status = $entity->getStatus();

    if ($from_status === $to_status) {
      return;
    }

    $allowed = Ticket::ALLOWED_TRANSITIONS[$from_status] ?? [];
    if (in_array($to_status, $allowed, TRUE)) {
      return;
    }

    $labels = $entity->getFieldDefinition('status')->getSetting('allowed_values');
    $this->context->buildViolation($constraint->message, [
      '@from' => $labels[$from_status] ?? $from_status,
      '@to' => $labels[$to_status] ?? $to_status,
    ])
      ->atPath('status')
      ->addViolation();
  }

  /**
   * Loads the ticket state before this save.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The ticket being validated.
   *
   * @return \Drupal\ticket_management\Entity\TicketInterface|null
   *   The original ticket, or NULL when unavailable.
   */
  protected function getOriginalEntity(TicketInterface $entity): ?TicketInterface {
    if (isset($entity->original) && $entity->original instanceof TicketInterface) {
      return $entity->original;
    }

    if ($entity->id()) {
      $unchanged = $this->entityTypeManager->getStorage('ticket')->loadUnchanged($entity->id());
      if ($unchanged instanceof TicketInterface) {
        return $unchanged;
      }
    }

    return NULL;
  }

}
