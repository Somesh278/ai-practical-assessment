<?php

namespace Drupal\ticket_management;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ticket_management\Entity\Ticket;
use Drupal\ticket_management\Entity\TicketInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for tickets.
 */
class TicketAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a TicketAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\ticket_management\Entity\TicketInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view tickets')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'update':
        $stored_status = $this->getStoredStatus($entity);
        $can_edit_fields = $account->hasPermission('edit ticket fields')
          && !Ticket::isTransitionFinal($stored_status);
        $can_transition = $this->canTransitionStatus($entity, $account, $stored_status);
        return AccessResult::allowedIf($can_edit_fields || $can_transition)
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::forbidden()
          ->addCacheableDependency($entity);

      default:
        return AccessResult::neutral()->addCacheableDependency($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'create tickets')
      ->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResult {
    if ($operation !== 'edit' || !$items instanceof FieldItemListInterface) {
      return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }

    $entity = $items->getEntity();
    if (!$entity instanceof TicketInterface) {
      return AccessResult::neutral();
    }

    $field_name = $field_definition->getName();
    $stored_status = $this->getStoredStatus($entity);

    switch ($field_name) {
      case 'status':
        return AccessResult::allowedIf(
          $this->canTransitionStatus($entity, $account, $stored_status)
          && Ticket::isStatusFieldEditable($stored_status)
        )
          ->cachePerPermissions()
          ->cachePerUser()
          ->addCacheableDependency($entity);

      case 'title':
      case 'description':
      case 'priority':
      case 'assignee':
        return AccessResult::allowedIfHasPermission($account, 'edit ticket fields')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'uid':
        return AccessResult::forbidden('The reporter cannot be changed.')
          ->addCacheableDependency($entity);

      default:
        return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }
  }

  /**
   * Whether the account may change ticket status.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The ticket.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param string $stored_status
   *   The stored ticket status before unsaved edits.
   *
   * @return bool
   *   TRUE when status transitions are permitted for this account.
   */
  protected function canTransitionStatus(TicketInterface $entity, AccountInterface $account, string $stored_status): bool {
    if (Ticket::isTransitionFinal($stored_status)) {
      return FALSE;
    }

    if ($account->hasPermission('administer tickets')) {
      return TRUE;
    }

    $assignee_id = $this->getStoredAssigneeId($entity);
    return $assignee_id > 0 && $assignee_id === (int) $account->id();
  }

  /**
   * Gets the stored assignee user ID before unsaved form edits.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The ticket.
   *
   * @return int
   *   The assignee user ID, or 0 when unassigned.
   */
  protected function getStoredAssigneeId(TicketInterface $entity): int {
    $stored = $this->getStoredEntity($entity);
    $source = $stored ?? $entity;
    return (int) $source->get('assignee')->target_id;
  }

  /**
   * Gets the stored ticket status, before any unsaved form edits.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The ticket.
   *
   * @return string
   *   The stored status value.
   */
  protected function getStoredStatus(TicketInterface $entity): string {
    $stored = $this->getStoredEntity($entity);
    return $stored ? $stored->getStatus() : $entity->getStatus();
  }

  /**
   * Loads the unchanged ticket entity when available.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $entity
   *   The ticket.
   *
   * @return \Drupal\ticket_management\Entity\TicketInterface|null
   *   The stored ticket, or NULL for new entities.
   */
  protected function getStoredEntity(TicketInterface $entity): ?TicketInterface {
    if ($entity->isNew()) {
      return NULL;
    }

    if (isset($entity->original) && $entity->original instanceof TicketInterface) {
      return $entity->original;
    }

    if ($entity->id()) {
      $unchanged = $this->entityTypeManager->getStorage('ticket')->loadUnchanged($entity->id());
      if ($unchanged instanceof TicketInterface) {
        return $unchanged;
      }
    }

    return $entity;
  }

}
