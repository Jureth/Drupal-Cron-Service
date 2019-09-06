<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\TimeControllingCronServiceInterface;

class TimeControlling implements TimeControllingCronServiceInterface {

  /**
   * This method will be called by CronServiceManager.
   */
  public function execute() {
    return 'false';
  }

  /**
   * Checks if the service should be executed right now.
   *
   * @return bool
   *   TRUE if service should be executed.
   */
  public function shouldRunNow(): bool {
    return FALSE;
  }
}
