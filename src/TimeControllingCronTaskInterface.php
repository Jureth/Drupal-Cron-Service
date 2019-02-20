<?php

namespace Drupal\cron_service;

/**
 * Cron task interface with manual control when it should be executed.
 */
interface TimeControllingCronTaskInterface extends CronTaskInterface {

  /**
   * Checks if the task should be executed right now.
   *
   * @return bool
   *   TRUE if task should be executed.
   */
  public function shouldRunNow(): bool;

}
