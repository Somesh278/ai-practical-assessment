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
 * Validates the TicketEditLock constraint.
 */
class TicketEditLockConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Field machine names guarded by the edit lock.
   *
   * @var string[]
   */
  protected const LOCKED_FIELDS = [
    'title',
    'description',
    'priority',
    'assignee',
  ];

  /**
   * Constructs a TicketEditLockConstraintValidator object.
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
    if (!$entity instanceof TicketInterface || $entity->isNew()) {
      return;
    }

    /** @var \Drupal\ticket_management\Plugin\Validation\Constraint\TicketEditLockConstraint $constraint */
    $original = $this->getOriginalEntity($entity);
    if (!$original instanceof TicketInterface) {
      return;
    }

    $stored_status = $original->getStatus();
    if (!in_array($stored_status, Ticket::EDIT_LOCKED_STATUSES, TRUE)) {
      return;
    }

    $status_label = $entity->getFieldDefinition('status')
      ->getSetting('allowed_values')[$stored_status] ?? $stored_status;

    foreach (self::LOCKED_FIELDS as $field_name) {
      if (!$this->fieldValuesEqual($original, $entity, $field_name)) {
        $field_label = $entity->getFieldDefinition($field_name)->getLabel();
        $this->context->buildViolation($constraint->message, [
          '@field' => $field_label,
          '@status' => $status_label,
        ])
          ->atPath($field_name)
          ->addViolation();
      }
    }
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

  /**
   * Compares a field value between the original and incoming ticket.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $original
   *   The stored ticket.
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The incoming ticket.
   * @param string $field_name
   *   The field machine name.
   *
   * @return bool
   *   TRUE when the values are equal.
   */
  protected function fieldValuesEqual(TicketInterface $original, TicketInterface $entity, string $field_name): bool {
    if ($field_name === 'assignee') {
      return (int) $original->get('assignee')->target_id === (int) $entity->get('assignee')->target_id;
    }

    return $original->get($field_name)->value === $entity->get($field_name)->value;
  }

}
