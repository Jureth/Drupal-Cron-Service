<?php

namespace Drupal\cron_service_testing;

use Drupal\cron_service\TimeControllingCronServiceInterface;

/**
 * Service fixture.
 */
class TimeControlling implements TimeControllingCronServiceInterface {

  /**
   * {@inheritDoc}
   */
  public function execute() {
    return 'false';
  }

  /**
   * {@inheritDoc}
   */
  public function shouldRunNow(): bool {
    return FALSE;
  }

}
