<?php

namespace Drupal\cron_service;

/**
 * Cron task with scheduled next time run.
 */
interface ScheduledCronTaskInterface extends CronTaskInterface {

  /**
   * Returns the next execution time.
   *
   * Returns the timestamp before that the task shouldn't be executed. Because
   * of cron implementation the exact time when the task will be executed can't
   * be evaluated.
   *
   * @return int
   *   Unix Timestamp.
   */
  public function getNextExecutionTime(): int;

}
