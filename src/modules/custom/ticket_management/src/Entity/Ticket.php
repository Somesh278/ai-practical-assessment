<?php

namespace Drupal\ticket_management\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Ticket entity.
 *
 * @ContentEntityType(
 *   id = "ticket",
 *   label = @Translation("Ticket"),
 *   label_collection = @Translation("Tickets"),
 *   label_singular = @Translation("ticket"),
 *   label_plural = @Translation("tickets"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ticket",
 *     plural = "@count tickets"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\ticket_management\TicketAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ticket_management\Form\TicketForm",
 *       "add" = "Drupal\ticket_management\Form\TicketForm",
 *       "edit" = "Drupal\ticket_management\Form\TicketForm",
 *     },
 *   },
 *   base_table = "ticket",
 *   admin_permission = "administer tickets",
 *   collection_permission = "view tickets",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "uid" = "uid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/ticket/{ticket}",
 *     "add-form" = "/ticket/add",
 *     "edit-form" = "/ticket/{ticket}/edit",
 *     "collection" = "/tickets",
 *   },
 *   constraints = {
 *     "TicketStatusTransition" = {},
 *     "TicketEditLock" = {},
 *     "TicketAssignee" = {},
 *   },
 * )
 */
class Ticket extends ContentEntityBase implements TicketInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Status value: open.
   */
  public const STATUS_OPEN = 'open';

  /**
   * Status value: in progress.
   */
  public const STATUS_IN_PROGRESS = 'in_progress';

  /**
   * Status value: resolved.
   */
  public const STATUS_RESOLVED = 'resolved';

  /**
   * Status value: closed.
   */
  public const STATUS_CLOSED = 'closed';

  /**
   * Status value: cancelled.
   */
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * Statuses where non-status fields are edit-locked.
   *
   * @var string[]
   */
  public const EDIT_LOCKED_STATUSES = [
    self::STATUS_RESOLVED,
    self::STATUS_CLOSED,
    self::STATUS_CANCELLED,
  ];

  /**
   * Statuses where no further status transitions are allowed.
   *
   * @var string[]
   */
  public const TRANSITION_FINAL_STATUSES = [
    self::STATUS_CLOSED,
    self::STATUS_CANCELLED,
  ];

  /**
   * Allowed status transitions keyed by current status.
   *
   * @var array<string, string[]>
   */
  public const ALLOWED_TRANSITIONS = [
    self::STATUS_OPEN => [
      self::STATUS_IN_PROGRESS,
      self::STATUS_CANCELLED,
    ],
    self::STATUS_IN_PROGRESS => [
      self::STATUS_RESOLVED,
      self::STATUS_CANCELLED,
    ],
    self::STATUS_RESOLVED => [
      self::STATUS_CLOSED,
    ],
    self::STATUS_CLOSED => [],
    self::STATUS_CANCELLED => [],
  ];

  /**
   * Returns status values selectable on the edit form for a stored status.
   *
   * @param string $current_status
   *   The stored ticket status.
   *
   * @return string[]
   *   The current status plus any legal next states.
   */
  public static function getSelectableStatuses(string $current_status): array {
    $next_states = self::ALLOWED_TRANSITIONS[$current_status] ?? [];
    return array_values(array_unique(array_merge([$current_status], $next_states)));
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    parent::preCreate($storage, $values);
    $values['status'] = self::STATUS_OPEN;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('The ticket title.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('The ticket description.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -9,
        'settings' => [
          'rows' => 6,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Priority'))
      ->setDescription(new TranslatableMarkup('The ticket priority.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The ticket status.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_OPEN)
      ->setSetting('allowed_values', [
        self::STATUS_OPEN => 'Open',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_RESOLVED => 'Resolved',
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_CANCELLED => 'Cancelled',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assignee'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Assignee'))
      ->setDescription(new TranslatableMarkup('The user assigned to work this ticket.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -6,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid']
      ->setLabel(new TranslatableMarkup('Reporter'))
      ->setDescription(new TranslatableMarkup('The user who created the ticket.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the ticket was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the ticket was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Whether the status field may be edited for the given status.
   *
   * Mirrors data-model.md field-level edit lock table.
   *
   * @param string $status
   *   The current ticket status.
   *
   * @return bool
   *   TRUE if the status field is editable.
   */
  public static function isStatusFieldEditable(string $status): bool {
    return !in_array($status, self::TRANSITION_FINAL_STATUSES, TRUE);
  }

  /**
   * Whether non-status fields may be edited for the given status.
   *
   * Mirrors data-model.md field-level edit lock table.
   *
   * @param string $status
   *   The current ticket status.
   *
   * @return bool
   *   TRUE if title, description, priority, and assignee are editable.
   */
  public static function areOtherFieldsEditable(string $status): bool {
    return !in_array($status, self::EDIT_LOCKED_STATUSES, TRUE);
  }

  /**
   * Whether a status is edit-locked for non-status fields.
   *
   * @param string $status
   *   The current ticket status.
   *
   * @return bool
   *   TRUE when non-status fields are locked.
   */
  public static function isEditLocked(string $status): bool {
    return !self::areOtherFieldsEditable($status);
  }

  /**
   * Whether a status allows no further transitions.
   *
   * @param string $status
   *   The current ticket status.
   *
   * @return bool
   *   TRUE when no status transitions remain.
   */
  public static function isTransitionFinal(string $status): bool {
    return in_array($status, self::TRANSITION_FINAL_STATUSES, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): static {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): static {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->getTitle();
  }

}
