<?php

namespace Drupal\cron_service_ui;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\cron_service\CronServiceInterface;
use Drupal\cron_service\CronServiceManagerInterface;

/**
 * Cron service list builder.
 */
class ServiceListBuilder implements ServiceListBuilderInterface {

  use StringTranslationTrait;

  /**
   * The list of cron services.
   *
   * @var \Drupal\cron_service\CronServiceInterface[]
   */
  protected $handlers = [];

  /**
   * The cron service manager.
   *
   * @var \Drupal\cron_service\CronServiceManagerInterface
   */
  protected $cronServiceManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * A constructor.
   *
   * @param \Drupal\cron_service\CronServiceManagerInterface $service_manager
   *   The cron service manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    CronServiceManagerInterface $service_manager,
    StateInterface $state,
    DateFormatterInterface $date_formatter
  ) {
    $this->cronServiceManager = $service_manager;
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function addHandler(CronServiceInterface $service, string $id): void {
    $this->handlers[$id] = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $table = [
      '#type' => 'table',
      '#header' => $this->buildTableHeader(),
    ];
    $table += $this->buildTableRows();
    return $table;
  }

  /**
   * Builds the table header.
   *
   * @return array
   *   The header.
   */
  protected function buildTableHeader(): array {
    return [
      'service' => $this->t('Service name'),
      'next' => $this->t('Schedule'),
      'operations' => $this->t('Operations'),
    ];
  }

  /**
   * Builds the table rows.
   *
   * @return array
   *   The rows.
   */
  protected function buildTableRows(): array {
    $rows = [];

    foreach ($this->handlers as $id => $handler) {
      $rows[] = $this->buildTableRow($id, $handler);
    }

    return $rows;
  }

  /**
   * Builds the cell that displays the next run of the service.
   *
   * @param string $id
   *   The service name.
   * @param \Drupal\cron_service\CronServiceInterface $handler
   *   The cron service.
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceNextRun(
    string $id,
    CronServiceInterface $handler
  ): array {
    $statements = [];

    // @todo Use a method of the cron service manager as soon as it gets one.
    $is_forced = $this->getValue($id, 'forced', FALSE);
    $scheduled_time = $this->cronServiceManager->getScheduledCronRunTime($id);

    if ($is_forced) {
      $statements[] = [
        '#markup' => $this->t('Forced for the next Cron run'),
      ];

      if ($scheduled_time) {
        $statements[] = [
          '#markup' => $this->t('Was scheduled for @time', [
            '@time' => $this->dateFormatter->format($scheduled_time),
          ]),
        ];
      }
    }
    elseif ($scheduled_time) {
      $statements[] = [
        '#markup' => $this->t('Scheduled for @time', [
          '@time' => $this->dateFormatter->format($scheduled_time),
        ]),
      ];
    }
    else {
      $statements[] = [
        '#markup' => $this->t('Next Cron run', [
          '@time' => $this->dateFormatter->format($scheduled_time),
        ]),
      ];
    }

    if (count($statements) > 1) {
      return [
        '#theme' => 'item_list',
        '#items' => $statements,
      ];
    }
    else {
      return $statements;
    }
  }

  /**
   * Builds the list of operation links available for the service.
   *
   * @param string $id
   *   The service name.
   * @param \Drupal\cron_service\CronServiceInterface $handler
   *   The cron service.
   *
   * @return array
   *   The list of links.
   */
  protected function buildServiceOperationLinks(
    string $id,
    CronServiceInterface $handler
  ): array {
    $links = [];

    $links['force'] = [
      'title' => $this->t('Force on next Cron run'),
      'url' => Url::fromRoute('cron_service_ui.service.force', [
        'id' => $id,
      ]),
    ];

    return $links;
  }

  /**
   * Builds the table cell that displays the service operations.
   *
   * @param string $id
   *   The service name.
   * @param \Drupal\cron_service\CronServiceInterface $handler
   *   The cron service.
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceOperations(
    string $id,
    CronServiceInterface $handler
  ): array {
    $links = $this->buildServiceOperationLinks($id, $handler);
    if (empty($links)) {
      return [
        '#markup' => '-',
      ];
    }

    return [
      '#type' => 'operations',
      '#links' => $links,
    ];
  }

  /**
   * Builds the table cell that displays the service name.
   *
   * @param string $id
   *   The service name.
   * @param \Drupal\cron_service\CronServiceInterface $handler
   *   The cron service.
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceName(
    string $id,
    CronServiceInterface $handler
  ): array {
    return [
      '#plain_text' => $id,
    ];
  }

  /**
   * Builds the table row.
   *
   * @param string $id
   *   The service name.
   * @param \Drupal\cron_service\CronServiceInterface $handler
   *   The cron service.
   *
   * @return array
   *   A render array.
   */
  protected function buildTableRow(
    string $id,
    CronServiceInterface $handler
  ): array {
    $row = [];

    $row['service'] = $this->buildServiceName($id, $handler);
    $row['next'] = $this->buildServiceNextRun($id, $handler);
    $row['operations'] = $this->buildServiceOperations($id, $handler);

    return $row;
  }

  /**
   * Retrieves a Cron Service state value from a persistent storage.
   *
   * @param string $id
   *   Service id.
   * @param string $name
   *   Value name.
   * @param mixed $default
   *   Default value to return.
   *
   * @return mixed
   *   Value from the storage.
   */
  protected function getValue(string $id, string $name, $default = NULL) {
    $state_name = sprintf('cron_service.cron.%s.%s', $id, $name);
    return $this->state->get($state_name, $default);
  }

}
