<?php

namespace Drupal\cron_service_ui;

use Drupal\cron_service\CronServiceInterface;

/**
 * Provides an interface for a cron service list builder.
 */
interface ServiceListBuilderInterface {

  /**
   * Adds handler to the list.
   *
   * Gets called by the target handler compiler pass.
   *
   * @param \Drupal\cron_service\CronServiceInterface $service
   *   The service to add.
   * @param string $id
   *   The service name.
   *
   * @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass
   */
  public function addHandler(CronServiceInterface $service, string $id): void;

  /**
   * Builds the render array of the service list.
   *
   * @return array
   *   The render array.
   */
  public function build(): array;

}
