<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\ScheduledCronServiceInterface;

/**
 * Service fixture.
 */
class Scheduled implements ScheduledCronServiceInterface {

  /**
   * {@inheritDoc}
   */
  public function execute() {
    return 'false';
  }

  /**
   * {@inheritDoc}
   */
  public function getNextExecutionTime(): int {
    return (new \DateTime('2200-01-01'))->getTimestamp();
  }

}
