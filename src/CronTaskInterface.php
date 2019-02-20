<?php

namespace Drupal\cron_service;

/**
 * Interface for cron task data processors.
 */
interface CronTaskInterface {

  /**
   * Main execution method.
   */
  public function execute();

}
