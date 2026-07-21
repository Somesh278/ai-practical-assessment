<?php

namespace Drupal\ticket_management\Drush\Commands;

use Drupal\ticket_management\TicketSeedService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for seeding ticket management demo data.
 */
final class TicketSeedCommands extends DrushCommands {

  /**
   * Constructs a TicketSeedCommands object.
   *
   * @param \Drupal\ticket_management\TicketSeedService $seedService
   *   The ticket seed service.
   */
  public function __construct(
    protected TicketSeedService $seedService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ticket_management.seed'),
    );
  }

  /**
   * Creates demo staff users and sample tickets from database/seed-data.
   */
  #[CLI\Command(name: 'ticket:seed', aliases: ['tms:seed'])]
  #[CLI\Option(name: 'force', description: 'Reset and update existing seed tickets (matched by title).')]
  #[CLI\Usage(name: 'drush ticket:seed', description: 'Create seed users and tickets (skips existing).')]
  #[CLI\Usage(name: 'drush ticket:seed --force', description: 'Reset and update seed tickets matched by title.')]
  public function seed(array $options = ['force' => FALSE]): void {
    $force = (bool) $options['force'];

    try {
      $counts = $this->seedService->seed($force);
    }
    catch (\RuntimeException $exception) {
      $this->logger()->error($exception->getMessage());
      return;
    }

    $this->logger()->success(dt('Seed users: @created created, @skipped already existed.', [
      '@created' => $counts['users_created'],
      '@skipped' => $counts['users_skipped'],
    ]));
    $this->logger()->success(dt('Seed tickets: @created created, @updated updated, @skipped skipped.', [
      '@created' => $counts['tickets_created'],
      '@updated' => $counts['tickets_updated'],
      '@skipped' => $counts['tickets_skipped'],
    ]));
  }

}
