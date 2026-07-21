<?php

namespace Drupal\ticket_management;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ticket_management\Entity\Ticket;
use Drupal\ticket_management\Entity\TicketInterface;
use Drupal\user\UserInterface;

/**
 * Creates demo users and tickets from database/seed-data/tickets.seed.php.
 */
class TicketSeedService {

  /**
   * Machine names of seed staff accounts, keyed by username.
   *
   * @var array<string, int>
   */
  protected array $userIds = [];

  /**
   * Constructs a TicketSeedService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  /**
   * Loads seed data from the project database/seed-data directory.
   *
   * @return array{users: array<int, array<string, mixed>>, tickets: array<int, array<string, mixed>>}
   *   The seed data array.
   */
  public function loadSeedData(): array {
    $path = dirname(DRUPAL_ROOT) . '/database/seed-data/tickets.seed.php';
    if (!is_readable($path)) {
      throw new \RuntimeException(sprintf('Seed file not found or not readable: %s', $path));
    }

    $data = require $path;
    if (!is_array($data) || !isset($data['users'], $data['tickets'])) {
      throw new \RuntimeException('Seed file must return an array with users and tickets keys.');
    }

    return $data;
  }

  /**
   * Seeds users and tickets.
   *
   * @param bool $force
   *   When TRUE, updates existing seed tickets (matched by title) in place.
   *
   * @return array{users_created: int, users_skipped: int, tickets_created: int, tickets_skipped: int, tickets_updated: int}
   *   Counts of created, skipped, and updated entities.
   */
  public function seed(bool $force = FALSE): array {
    $data = $this->loadSeedData();
    $counts = [
      'users_created' => 0,
      'users_skipped' => 0,
      'tickets_created' => 0,
      'tickets_skipped' => 0,
      'tickets_updated' => 0,
    ];

    foreach ($data['users'] as $user_data) {
      $result = $this->seedUser($user_data);
      $counts[$result]++;
    }

    foreach ($data['tickets'] as $ticket_data) {
      $result = $this->seedTicket($ticket_data, $force);
      $counts[$result]++;
    }

    return $counts;
  }

  /**
   * Creates or reuses a seed staff user.
   *
   * @param array<string, mixed> $user_data
   *   User seed values (name, mail, password).
   *
   * @return string
   *   Either users_created or users_skipped.
   */
  protected function seedUser(array $user_data): string {
    $storage = $this->entityTypeManager->getStorage('user');
    $existing = $storage->loadByProperties(['mail' => $user_data['mail']]);
    $user = reset($existing);

    if ($user instanceof UserInterface) {
      $this->userIds[$user_data['name']] = (int) $user->id();
      if (!$user->hasRole('ticket_staff')) {
        $user->addRole('ticket_staff');
        $user->save();
      }
      return 'users_skipped';
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->create([
      'name' => $user_data['name'],
      'mail' => $user_data['mail'],
      'pass' => $user_data['password'],
      'status' => 1,
    ]);
    $user->addRole('ticket_staff');
    $user->save();

    $this->userIds[$user_data['name']] = (int) $user->id();
    return 'users_created';
  }

  /**
   * Creates a seed ticket when one with the same title does not exist.
   *
   * @param array<string, mixed> $ticket_data
   *   Ticket seed values.
   * @param bool $force
   *   When TRUE, updates existing seed tickets instead of skipping them.
   *
   * @return string
   *   tickets_created, tickets_skipped, or tickets_updated.
   */
  protected function seedTicket(array $ticket_data, bool $force = FALSE): string {
    $storage = $this->entityTypeManager->getStorage('ticket');
    $existing = $storage->loadByProperties(['title' => $ticket_data['title']]);
    $ticket = reset($existing);

    if ($ticket instanceof TicketInterface) {
      if (!$force) {
        return 'tickets_skipped';
      }

      $this->resetTicket($ticket);
      $storage->resetCache([$ticket->id()]);
      $ticket = $storage->loadUnchanged($ticket->id());
      if (!$ticket instanceof TicketInterface) {
        throw new \RuntimeException('Failed to reload ticket after reset.');
      }
      $this->populateTicket($ticket, $ticket_data);
      $this->applyStatus($ticket, $ticket_data['status']);
      return 'tickets_updated';
    }

    /** @var \Drupal\ticket_management\Entity\TicketInterface $ticket */
    $ticket = $storage->create([]);
    $this->populateTicket($ticket, $ticket_data);

    $target_status = $ticket_data['status'];
    if ($target_status !== Ticket::STATUS_OPEN) {
      $this->applyStatus($ticket, $target_status);
    }

    return 'tickets_created';
  }

  /**
   * Sets ticket field values from seed data.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket entity.
   * @param array<string, mixed> $ticket_data
   *   Ticket seed values.
   */
  protected function populateTicket(TicketInterface $ticket, array $ticket_data): void {
    $reporter_id = $this->resolveUserId($ticket_data['reporter']);
    $assignee_name = $ticket_data['assignee'] ?? NULL;
    $assignee_id = $assignee_name ? $this->resolveUserId($assignee_name) : NULL;

    $ticket->setTitle($ticket_data['title']);
    $ticket->set('description', $ticket_data['description']);
    $ticket->set('priority', $ticket_data['priority']);
    $ticket->setOwnerId($reporter_id);
    $ticket->set('assignee', $assignee_id);
    $ticket->save();
  }

  /**
   * Resets a ticket to open status before reapplying seed values.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket entity.
   */
  protected function resetTicket(TicketInterface $ticket): void {
    if ($ticket->getStatus() === Ticket::STATUS_OPEN) {
      return;
    }

    // Tickets cannot be deleted and closed/cancelled cannot transition back to
    // open — seed reset writes status directly for local demo data only.
    $this->database->update('ticket')
      ->fields(['status' => Ticket::STATUS_OPEN])
      ->condition('id', $ticket->id())
      ->execute();
    $this->entityTypeManager->getStorage('ticket')->resetCache([$ticket->id()]);
    $ticket->set('status', Ticket::STATUS_OPEN);
  }

  /**
   * Walks valid transitions from open to the target status.
   *
   * @param \Drupal\ticket_management\Entity\TicketInterface $ticket
   *   The ticket entity.
   * @param string $target_status
   *   The desired status value.
   */
  protected function applyStatus(TicketInterface $ticket, string $target_status): void {
    $path = $this->buildTransitionPath(Ticket::STATUS_OPEN, $target_status);
    foreach ($path as $status) {
      $ticket->setStatus($status);
      $ticket->save();
    }
  }

  /**
   * Builds a shortest valid transition path between two statuses.
   *
   * @param string $from
   *   Starting status.
   * @param string $to
   *   Target status.
   *
   * @return string[]
   *   Ordered status values to apply (excluding the starting status).
   */
  protected function buildTransitionPath(string $from, string $to): array {
    if ($from === $to) {
      return [];
    }

    $queue = [[$from]];
    $visited = [$from => TRUE];

    while ($queue) {
      $path = array_shift($queue);
      $current = end($path);
      $next_states = Ticket::ALLOWED_TRANSITIONS[$current] ?? [];

      foreach ($next_states as $next) {
        if (isset($visited[$next])) {
          continue;
        }

        $candidate = [...$path, $next];
        if ($next === $to) {
          return array_slice($candidate, 1);
        }

        $visited[$next] = TRUE;
        $queue[] = $candidate;
      }
    }

    throw new \RuntimeException(sprintf('No valid transition path from %s to %s.', $from, $to));
  }

  /**
   * Resolves a seed username to a user ID.
   *
   * @param string $username
   *   The username from seed data.
   *
   * @return int
   *   The user ID.
   */
  protected function resolveUserId(string $username): int {
    if (isset($this->userIds[$username])) {
      return $this->userIds[$username];
    }

    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]);
    $user = reset($users);
    if ($user instanceof UserInterface) {
      $this->userIds[$username] = (int) $user->id();
      return $this->userIds[$username];
    }

    throw new \RuntimeException(sprintf('Seed user not found: %s', $username));
  }

}
