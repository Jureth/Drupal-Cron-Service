<?php

namespace Drupal\cron_service_ui;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\cron_service\CronServiceManagerInterface;
use Drupal\cron_service\TimeControllingCronServiceInterface;

/**
 * Cron service list builder.
 */
class ServiceListBuilder implements ServiceListBuilderInterface {

  use StringTranslationTrait;

  /**
   * The cron service manager.
   *
   * @var \Drupal\cron_service\CronServiceManagerInterface
   */
  protected $cronServiceManager;

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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    CronServiceManagerInterface $service_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->cronServiceManager = $service_manager;
    $this->dateFormatter = $date_formatter;
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

    foreach ($this->cronServiceManager->getHandlerIds() as $id) {
      $rows[] = $this->buildTableRow($id);
    }

    return $rows;
  }

  /**
   * Builds the cell that displays the next run of the service.
   *
   * @param string $id
   *   The service name.
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceNextRun(string $id): array {
    $statements = [];

    $scheduled_time = $this->cronServiceManager->getScheduledCronRunTime($id);

    if ($this->cronServiceManager->isForced($id)) {
      $statements[] = [
        '#markup' => $this->t('Forced for the next Cron run'),
      ];

      if ($scheduled_time) {
        $statements[] = [
          '#markup' => $this->t(
            'Was scheduled for @time',
            [
              '@time' => $this->dateFormatter->format($scheduled_time),
            ]
          ),
        ];
      }
    }
    else {
      if ($scheduled_time) {
        $statements[] = [
          '#markup' => $this->t(
            'Scheduled for @time',
            [
              '@time' => $this->dateFormatter->format($scheduled_time),
            ]
          ),
        ];
      }
      else {
        $statements[] = [
          '#markup' => $this->t('Will be executed at next Cron run'),
        ];
      }
      if (\Drupal::getContainer()->get(
          $id
        ) instanceof TimeControllingCronServiceInterface) {
        $statements[] = [
          '#markup' => $this->t(
            'The service may not be executed because of its own pre-run checks until its forced'
          ),
        ];
      }
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
   *
   * @return array
   *   The list of links.
   */
  protected function buildServiceOperationLinks(string $id): array {
    $links = [];

    $links['force'] = [
      'title' => $this->t('Force on next Cron run'),
      'url' => Url::fromRoute(
        'cron_service_ui.service.force',
        [
          'id' => $id,
        ]
      ),
    ];

    return $links;
  }

  /**
   * Builds the table cell that displays the service operations.
   *
   * @param string $id
   *   The service name.
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceOperations(string $id): array {
    $links = $this->buildServiceOperationLinks($id);
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
   *
   * @return array
   *   A render array.
   */
  protected function buildServiceName(string $id): array {
    return [
      '#plain_text' => $id,
    ];
  }

  /**
   * Builds the table row.
   *
   * @param string $id
   *   The service name.
   *
   * @return array
   *   A render array.
   */
  protected function buildTableRow(string $id): array {
    $row = [];

    $row['service'] = $this->buildServiceName($id);
    $row['next'] = $this->buildServiceNextRun($id);
    $row['operations'] = $this->buildServiceOperations($id);

    return $row;
  }

}
