<?php

namespace Drupal\ticket_management\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ticket_management\Entity\Ticket;
use Drupal\ticket_management\Entity\TicketInterface;

/**
 * Form controller for Ticket add and edit forms.
 */
class TicketForm extends ContentEntityForm {

  /**
   * Non-status fields guarded by the edit lock.
   *
   * @var string[]
   */
  protected const EDIT_LOCKED_FIELDS = [
    'title',
    'description',
    'priority',
    'assignee',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\ticket_management\Entity\TicketInterface $ticket */
    $ticket = $this->entity;

    if ($ticket->isNew()) {
      // Status is forced server-side on create; never expose it on the form.
      if (isset($form['status'])) {
        $form['status']['#access'] = FALSE;
      }
    }
    else {
      $this->disableEditLockedFields($form, $ticket);
      $this->limitStatusOptions($form, $ticket);
    }

    return $form;
  }

  /**
   * Disables non-status fields when the ticket status is edit-locked.
   *
   * Fields remain visible so users can read current values; the save-time
   * TicketEditLock constraint enforces that locked values cannot change.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket being edited.
   */
  protected function disableEditLockedFields(array &$form, TicketInterface $ticket): void {
    if (!in_array($this->getStoredStatus($ticket), Ticket::EDIT_LOCKED_STATUSES, TRUE)) {
      return;
    }

    foreach (self::EDIT_LOCKED_FIELDS as $field_name) {
      if (isset($form[$field_name]['widget'])) {
        $form[$field_name]['widget']['#disabled'] = TRUE;
      }
    }
  }

  /**
   * Limits the status select to the stored status and legal next states.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket being edited.
   */
  protected function limitStatusOptions(array &$form, TicketInterface $ticket): void {
    if (!isset($form['status']['widget'][0]['value']['#options'])) {
      return;
    }

    $stored_status = $this->getStoredStatus($ticket);
    $allowed = array_flip(Ticket::getSelectableStatuses($stored_status));
    $form['status']['widget'][0]['value']['#options'] = array_intersect_key(
      $form['status']['widget'][0]['value']['#options'],
      $allowed
    );
  }

  /**
   * Gets the stored ticket status before unsaved form edits.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket being edited.
   *
   * @return string
   *   The stored status value.
   */
  protected function getStoredStatus(TicketInterface $ticket): string {
    if ($ticket->isNew()) {
      return $ticket->getStatus();
    }

    if (isset($ticket->original) && $ticket->original instanceof TicketInterface) {
      return $ticket->original->getStatus();
    }

    $unchanged = $this->entityTypeManager->getStorage('ticket')->loadUnchanged($ticket->id());
    if ($unchanged instanceof TicketInterface) {
      return $unchanged->getStatus();
    }

    return $ticket->getStatus();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    /** @var \Drupal\ticket_management\Entity\TicketInterface $ticket */
    $ticket = $this->entity;

    if ($ticket->isNew()) {
      $ticket->setOwnerId($this->currentUser()->id());
      $ticket->setStatus(Ticket::STATUS_OPEN);
    }

    $result = parent::save($form, $form_state);

    $message_arguments = ['%label' => $ticket->toLink()->toString()];
    $logger_arguments = [
      '%label' => $ticket->label(),
      'link' => $ticket->toLink($this->t('View'))->toString(),
    ];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Ticket %label has been created.', $message_arguments));
      $this->logger('ticket_management')->notice('Ticket %label has been created.', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('Ticket %label has been updated.', $message_arguments));
      $this->logger('ticket_management')->notice('Ticket %label has been updated.', $logger_arguments);
    }

    $form_state->setRedirect('entity.ticket.canonical', ['ticket' => $ticket->id()]);

    return $result;
  }

}
