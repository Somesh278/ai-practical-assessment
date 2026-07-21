<?php

namespace Drupal\ticket_management\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ticket_management\Entity\TicketInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the TicketAssignee constraint.
 */
class TicketAssigneeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a TicketAssigneeConstraintValidator object.
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

    /** @var \Drupal\ticket_management\Plugin\Validation\Constraint\TicketAssigneeConstraint $constraint */
    $assignee_id = (int) $entity->get('assignee')->target_id;
    if ($assignee_id === 0) {
      return;
    }

    $assignee = $this->entityTypeManager->getStorage('user')->load($assignee_id);
    if (!$assignee instanceof UserInterface || !$assignee->isActive()) {
      $this->context->buildViolation($constraint->message)
        ->atPath('assignee')
        ->addViolation();
    }
  }

}
