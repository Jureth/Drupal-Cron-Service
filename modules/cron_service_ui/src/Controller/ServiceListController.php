<?php

namespace Drupal\cron_service_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides routes for the cron service list.
 */
class ServiceListController extends ControllerBase {

  /**
   * The cron service list builder.
   *
   * @var \Drupal\cron_service_ui\ServiceListBuilderInterface
   */
  protected $serviceListBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $instance */
    $instance = parent::create($container);

    $instance->serviceListBuilder = $container->get(
      'cron_service_ui.list_builder'
    );

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function overview() {
    return [
      'services' => $this->serviceListBuilder->build(),
    ];
  }

}
