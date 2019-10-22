<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\CronServiceInterface;

/**
 * Service fixture.
 */
class Simple implements CronServiceInterface {

  /**
   * {@inheritDoc}
   */
  public function execute() {
    return 'false';
  }

}
