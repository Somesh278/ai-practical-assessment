<?php

declare(strict_types=1);

namespace Drupal\Tests\ticket_management\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ticket_management\Entity\Ticket;
use Drupal\ticket_management\Entity\TicketInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Kernel tests for Ticket entity validation and constraints.
 *
 * @group ticket_management
 */
class TicketEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'options',
    'comment',
    'ticket_management',
  ];

  /**
   * The reporter user ID used when creating tickets.
   *
   * @var int
   */
  protected int $reporterId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('ticket');
    $this->installConfig(['field', 'system']);

    $reporter = User::create([
      'name' => 'ticket_reporter',
      'mail' => 'reporter@example.com',
      'status' => 1,
    ]);
    $reporter->save();
    $this->reporterId = (int) $reporter->id();
  }

  /**
   * Tests all five valid status transitions succeed.
   *
   * @param string $from_status
   *   The starting status.
   * @param string $to_status
   *   The target status.
   *
   * @dataProvider validTransitionProvider
   */
  public function testValidTransitions(string $from_status, string $to_status): void {
    $ticket = $this->createTicketInStatus($from_status);
    $ticket->setStatus($to_status);

    $violations = $ticket->validate();
    $this->assertCount(0, $violations, (string) $violations);

    $ticket->save();
    $this->assertSame($to_status, $ticket->getStatus());
  }

  /**
   * Data provider for valid status transitions.
   *
   * @return array<string, array{string, string}>
   *   Test cases keyed by description.
   */
  public static function validTransitionProvider(): array {
    return [
      'open to in_progress' => [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS],
      'in_progress to resolved' => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED],
      'resolved to closed' => [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED],
      'open to cancelled' => [Ticket::STATUS_OPEN, Ticket::STATUS_CANCELLED],
      'in_progress to cancelled' => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_CANCELLED],
    ];
  }

  /**
   * Tests invalid status transitions are rejected.
   *
   * @param string $from_status
   *   The starting status.
   * @param string $to_status
   *   The attempted target status.
   *
   * @dataProvider invalidTransitionProvider
   */
  public function testInvalidTransitionsRejected(string $from_status, string $to_status): void {
    $ticket = $this->createTicketInStatus($from_status);
    $ticket->setStatus($to_status);

    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString(
      'Cannot move a ticket from',
      (string) $violations->get(0)->getMessage()
    );
    $this->assertSame('status', $violations->get(0)->getPropertyPath());
  }

  /**
   * Data provider for invalid status transitions.
   *
   * @return array<string, array{string, string}>
   *   Test cases keyed by description.
   */
  public static function invalidTransitionProvider(): array {
    return [
      'skip-ahead open to resolved' => [Ticket::STATUS_OPEN, Ticket::STATUS_RESOLVED],
      'skip-ahead in_progress to closed' => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_CLOSED],
      'backward in_progress to open' => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_OPEN],
      'backward resolved to in_progress' => [Ticket::STATUS_RESOLVED, Ticket::STATUS_IN_PROGRESS],
      'from closed to open' => [Ticket::STATUS_CLOSED, Ticket::STATUS_OPEN],
      'from closed to in_progress' => [Ticket::STATUS_CLOSED, Ticket::STATUS_IN_PROGRESS],
      'from closed to resolved' => [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED],
      'from closed to cancelled' => [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED],
      'from cancelled to open' => [Ticket::STATUS_CANCELLED, Ticket::STATUS_OPEN],
      'from cancelled to in_progress' => [Ticket::STATUS_CANCELLED, Ticket::STATUS_IN_PROGRESS],
      'from cancelled to resolved' => [Ticket::STATUS_CANCELLED, Ticket::STATUS_RESOLVED],
      'from cancelled to closed' => [Ticket::STATUS_CANCELLED, Ticket::STATUS_CLOSED],
    ];
  }

  /**
   * Tests that saving without a status change is not flagged as invalid.
   */
  public function testSameStatusSaveSucceeds(): void {
    $ticket = $this->createTicketInStatus(Ticket::STATUS_IN_PROGRESS);
    $ticket->set('description', 'Updated description without status change');

    $violations = $ticket->validate();
    $this->assertCount(0, $violations, (string) $violations);

    $ticket->save();
    $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->getStatus());
    $this->assertSame('Updated description without status change', $ticket->get('description')->value);
  }

  /**
   * Tests that a non-open status submitted on create is forced to open.
   */
  public function testCreateForcesOpenStatus(): void {
    $ticket = $this->entityTypeManager()->getStorage('ticket')->create([
      'title' => 'Create status test',
      'description' => 'Description',
      'priority' => 'low',
      'status' => Ticket::STATUS_CLOSED,
      'uid' => $this->reporterId,
    ]);
    $ticket->save();

    $this->assertSame(Ticket::STATUS_OPEN, $ticket->getStatus());
  }

  /**
   * Tests required-field validation for title, description, and priority.
   *
   * @param string $missing_field
   *   The required field to omit.
   *
   * @dataProvider requiredFieldProvider
   */
  public function testRequiredFieldValidation(string $missing_field): void {
    $values = [
      'title' => 'Required field test',
      'description' => 'Description',
      'priority' => 'medium',
      'uid' => $this->reporterId,
    ];
    unset($values[$missing_field]);

    $ticket = $this->entityTypeManager()->getStorage('ticket')->create($values);
    $violations = $ticket->validate();

    $this->assertGreaterThan(0, $violations->count());
    $paths = [];
    foreach ($violations as $violation) {
      $paths[] = $violation->getPropertyPath();
    }
    $this->assertContains($missing_field, $paths);
  }

  /**
   * Data provider for required field validation.
   *
   * @return array<string, array{string}>
   *   Test cases keyed by field name.
   */
  public static function requiredFieldProvider(): array {
    return [
      'missing title' => ['title'],
      'missing description' => ['description'],
      'missing priority' => ['priority'],
    ];
  }

  /**
   * Tests edit-lock rejects non-status field changes via direct entity save.
   *
   * @param string $locked_status
   *   The edit-locked status to test.
   * @param string $field_name
   *   The locked field being changed.
   *
   * @dataProvider editLockedFieldChangeProvider
   */
  public function testEditLockRejectsDirectFieldChange(string $locked_status, string $field_name): void {
    $ticket = $this->createTicketInStatus($locked_status);

    /** @var \Drupal\ticket_management\Entity\TicketInterface $loaded */
    $loaded = $this->entityTypeManager()->getStorage('ticket')->load($ticket->id());
    $this->applyLockedFieldChange($loaded, $field_name);

    $violations = $loaded->validate();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertStringContainsString(
      'Cannot change',
      (string) $violations->get(0)->getMessage()
    );
    $this->assertSame($field_name, $violations->get(0)->getPropertyPath());
  }

  /**
   * Data provider for edit-locked field changes.
   *
   * @return array<string, array{string, string}>
   *   Test cases keyed by description.
   */
  public static function editLockedFieldChangeProvider(): array {
    $cases = [];
    foreach (Ticket::EDIT_LOCKED_STATUSES as $status) {
      foreach (['title', 'description', 'priority', 'assignee'] as $field_name) {
        $cases["$status changes $field_name"] = [$status, $field_name];
      }
    }
    return $cases;
  }

  /**
   * Tests a status-only change is allowed while resolved.
   */
  public function testEditLockAllowsStatusOnlyChangeWhileResolved(): void {
    $ticket = $this->createTicketInStatus(Ticket::STATUS_RESOLVED);

    /** @var \Drupal\ticket_management\Entity\TicketInterface $loaded */
    $loaded = $this->entityTypeManager()->getStorage('ticket')->load($ticket->id());
    $loaded->setStatus(Ticket::STATUS_CLOSED);

    $violations = $loaded->validate();
    $this->assertCount(0, $violations, (string) $violations);

    $loaded->save();
    $this->assertSame(Ticket::STATUS_CLOSED, $loaded->getStatus());
    $this->assertSame($ticket->getTitle(), $loaded->getTitle());
    $this->assertSame($ticket->get('priority')->value, $loaded->get('priority')->value);
  }

  /**
   * Tests priority is explicitly rejected by the edit-lock constraint.
   */
  public function testEditLockRejectsPriorityChangeWhileResolved(): void {
    $ticket = $this->createTicketInStatus(Ticket::STATUS_RESOLVED);
    $this->assertSame('medium', $ticket->get('priority')->value);

    /** @var \Drupal\ticket_management\Entity\TicketInterface $loaded */
    $loaded = $this->entityTypeManager()->getStorage('ticket')->load($ticket->id());
    $loaded->set('priority', 'high');

    $violations = $loaded->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('priority', $violations->get(0)->getPropertyPath());
    $this->assertStringContainsString(
      'Cannot change',
      (string) $violations->get(0)->getMessage()
    );
  }

  /**
   * Creates and saves a ticket with default field values.
   *
   * @param array $values
   *   Optional values to override defaults.
   *
   * @return \Drupal\ticket_management\Entity\TicketInterface
   *   The saved ticket.
   */
  protected function createTicket(array $values = []): TicketInterface {
    $defaults = [
      'title' => 'Test ticket',
      'description' => 'Test description',
      'priority' => 'medium',
      'uid' => $this->reporterId,
    ];

    /** @var \Drupal\ticket_management\Entity\TicketInterface $ticket */
    $ticket = $this->entityTypeManager()->getStorage('ticket')->create($values + $defaults);
    $ticket->save();
    return $ticket;
  }

  /**
   * Creates a ticket and advances it to the requested status.
   *
   * @param string $status
   *   The target status.
   *
   * @return \Drupal\ticket_management\Entity\TicketInterface
   *   The saved ticket in the requested status.
   */
  protected function createTicketInStatus(string $status): TicketInterface {
    $ticket = $this->createTicket();

    $chains = [
      Ticket::STATUS_OPEN => [],
      Ticket::STATUS_IN_PROGRESS => [Ticket::STATUS_IN_PROGRESS],
      Ticket::STATUS_RESOLVED => [
        Ticket::STATUS_IN_PROGRESS,
        Ticket::STATUS_RESOLVED,
      ],
      Ticket::STATUS_CLOSED => [
        Ticket::STATUS_IN_PROGRESS,
        Ticket::STATUS_RESOLVED,
        Ticket::STATUS_CLOSED,
      ],
      Ticket::STATUS_CANCELLED => [Ticket::STATUS_CANCELLED],
    ];

    $this->assertArrayHasKey($status, $chains);

    foreach ($chains[$status] as $next_status) {
      $ticket->setStatus($next_status);
      $ticket->save();
    }

    return $ticket;
  }

  /**
   * Applies a direct field change for edit-lock testing.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket to modify.
   * @param string $field_name
   *   The field machine name.
   */
  protected function applyLockedFieldChange(TicketInterface $ticket, string $field_name): void {
    switch ($field_name) {
      case 'title':
        $ticket->setTitle('Changed via direct entity save');
        break;

      case 'description':
        $ticket->set('description', 'Changed via direct entity save');
        break;

      case 'priority':
        $ticket->set('priority', $ticket->get('priority')->value === 'high' ? 'low' : 'high');
        break;

      case 'assignee':
        $ticket->set('assignee', $this->reporterId);
        break;
    }
  }

  /**
   * Tests staff with edit ticket fields cannot update closed/cancelled tickets.
   *
   * @param string $status
   *   A transition-final status.
   *
   * @dataProvider transitionFinalStatusProvider
   */
  public function testTransitionFinalTicketsDenyStaffUpdateAccess(string $status): void {
    $staff = $this->createStaffUser();

    $ticket = $this->createTicketInStatus($status);
    $handler = $this->entityTypeManager()->getAccessControlHandler('ticket');

    $this->assertFalse(
      $handler->access($ticket, 'update', $staff),
      sprintf('Staff should not have update access on %s tickets.', $status)
    );
  }

  /**
   * Tests staff retain update access on resolved tickets (disabled fields).
   */
  public function testResolvedTicketAllowsStaffUpdateAccessForDisabledFields(): void {
    $staff = $this->createStaffUser();

    $ticket = $this->createTicketInStatus(Ticket::STATUS_RESOLVED);
    $handler = $this->entityTypeManager()->getAccessControlHandler('ticket');

    $this->assertTrue(
      $handler->access($ticket, 'update', $staff),
      'Staff should retain update access on resolved tickets to view disabled fields.'
    );
  }

  /**
   * Data provider for transition-final statuses.
   *
   * @return array<string, array{string}>
   *   Test cases keyed by status.
   */
  public static function transitionFinalStatusProvider(): array {
    return [
      'closed' => [Ticket::STATUS_CLOSED],
      'cancelled' => [Ticket::STATUS_CANCELLED],
    ];
  }

  /**
   * Tests status field access uses the stored assignee, not the reporter (uid).
   */
  public function testAssigneeStatusFieldAccessUsesStoredAssignee(): void {
    $assignee = User::create([
      'name' => 'ticket_assignee',
      'mail' => 'assignee@example.com',
      'status' => 1,
    ]);
    $assignee->save();

    $other_staff = User::create([
      'name' => 'other_staff',
      'mail' => 'other@example.com',
      'status' => 1,
    ]);
    $other_staff->save();

    $ticket = $this->createTicketInStatus(Ticket::STATUS_OPEN);
    $ticket->set('assignee', $assignee->id());
    $ticket->save();

    $handler = $this->entityTypeManager()->getAccessControlHandler('ticket');

    // Simulate unsaved form state where assignee was cleared in memory.
    $ticket->get('assignee')->setValue([]);

    $this->assertNotSame((int) $assignee->id(), $ticket->getOwnerId());
    $this->assertTrue(
      $handler->fieldAccess('edit', $ticket->getFieldDefinition('status'), $assignee, $ticket->get('status')),
      'Stored assignee should retain status field edit access.'
    );
    $this->assertFalse(
      $handler->fieldAccess('edit', $ticket->getFieldDefinition('status'), $other_staff, $ticket->get('status')),
      'Non-assignee should not receive status field edit access.'
    );
  }

  /**
   * Tests assigning a blocked user as assignee is rejected.
   */
  public function testBlockedAssigneeRejected(): void {
    $blocked = User::create([
      'name' => 'blocked_assignee',
      'mail' => 'blocked@example.com',
      'status' => 0,
    ]);
    $blocked->save();

    $ticket = $this->createTicket();
    $ticket->set('assignee', $blocked->id());

    $violations = $ticket->validate();
    $this->assertGreaterThan(0, $violations->count());
    $this->assertSame('assignee', $violations->get(0)->getPropertyPath());
    $this->assertStringContainsString(
      'active, non-blocked user',
      (string) $violations->get(0)->getMessage()
    );
  }

  /**
   * Tests selectable status options include only legal next states.
   *
   * @param string $current_status
   *   The stored ticket status.
   * @param string[] $expected
   *   Expected selectable status values.
   *
   * @dataProvider selectableStatusProvider
   */
  public function testSelectableStatuses(string $current_status, array $expected): void {
    $this->assertSame($expected, Ticket::getSelectableStatuses($current_status));
  }

  /**
   * Data provider for selectable status options.
   *
   * @return array<string, array{string, string[]}>
   *   Test cases keyed by description.
   */
  public static function selectableStatusProvider(): array {
    return [
      'open' => [
        Ticket::STATUS_OPEN,
        [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_CANCELLED],
      ],
      'in_progress' => [
        Ticket::STATUS_IN_PROGRESS,
        [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
      ],
      'resolved' => [
        Ticket::STATUS_RESOLVED,
        [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED],
      ],
      'closed' => [
        Ticket::STATUS_CLOSED,
        [Ticket::STATUS_CLOSED],
      ],
      'cancelled' => [
        Ticket::STATUS_CANCELLED,
        [Ticket::STATUS_CANCELLED],
      ],
    ];
  }

  /**
   * Creates a staff user with edit ticket fields permission.
   *
   * @return \Drupal\user\UserInterface
   *   The staff user.
   */
  protected function createStaffUser() {
    if (!Role::load('ticket_staff')) {
      $role = Role::create([
        'id' => 'ticket_staff',
        'label' => 'Ticket staff',
      ]);
      $role->grantPermission('edit ticket fields');
      $role->grantPermission('view tickets');
      $role->save();
    }

    $staff = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
      'status' => 1,
    ]);
    $staff->addRole('ticket_staff');
    $staff->save();

    return $staff;
  }

  /**
   * Returns the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager() {
    return $this->container->get('entity_type.manager');
  }

}
