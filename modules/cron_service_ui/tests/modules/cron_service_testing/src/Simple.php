<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\CronServiceInterface;

class Simple implements CronServiceInterface {

  /**
   * This method will be called by CronServiceManager.
   */
  public function execute() {
    return 'false';
  }
}
