<?php

namespace Drupal\cron_service_ui;

use Drupal\cron_service\CronServiceInterface;

/**
 * Provides an interface for a cron service list builder.
 */
interface ServiceListBuilderInterface {

  /**
   * Builds the render array of the service list.
   *
   * @return array
   *   The render array.
   */
  public function build(): array;

}
