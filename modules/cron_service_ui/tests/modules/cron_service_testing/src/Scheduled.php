<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\ScheduledCronServiceInterface;

class Scheduled implements ScheduledCronServiceInterface {

  /**
   * This method will be called by CronServiceManager.
   */
  public function execute() {
    return 'false';
  }

  /**
   * Returns the next execution time.
   *
   * Returns the timestamp before which the service must not be executed.
   * Because of cron implementation the exact time when the service will be
   * executed can't be evaluated.
   *
   * @return int
   *   Unix Timestamp.
   */
  public function getNextExecutionTime(): int {
    return (new \DateTime('2200-01-01'))->getTimestamp();
  }

}
