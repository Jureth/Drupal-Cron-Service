<?php

namespace Drupal\cron_service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;

/**
 * Collects cron tasks, manages their schedule an executes them on time.
 */
class CronTaskManager implements CronTaskManagerInterface {

  /**
   * Injected state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Injected logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Task services.
   *
   * @var \Drupal\cron_service\CronTaskInterface[]
   */
  protected $handlers = [];

  /**
   * CronProcessor constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal State.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Drupal logger.
   */
  public function __construct(StateInterface $state, LoggerChannelInterface $logger) {
    $this->state = $state;
    $this->log = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function addHandler(CronTaskInterface $task, string $id) {
    $this->handlers[$id] = $task;
  }

  /**
   * Executes all the handlers.
   */
  public function execute() {
    foreach (array_keys($this->handlers) as $id) {
      $this->executeHandler($id, FALSE);
    }
  }

  /**
   * Stores the value in a persistent storage.
   *
   * @param string $id
   *   Task id.
   * @param string $name
   *   Value name.
   * @param mixed $value
   *   Value to store.
   */
  protected function storeValue(string $id, string $name, $value) {
    $state_name = sprintf('cron_service.cron.%s.%s', $id, $name);
    $this->state->set($state_name, $value);
  }

  /**
   * Retrieves a value from a persistent storage.
   *
   * @param string $id
   *   Task id.
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

  /**
   * Returns next execution time.
   *
   * @param string $id
   *   Handler Id.
   *
   * @return int
   *   Unix timestamp.
   */
  public function getScheduledCronRunTime(string $id): int {
    return $this->handlers[$id] instanceof ScheduledCronTaskInterface
      ? (int) $this->getValue($id, 'schedule', 0)
      : 0;
  }

  /**
   * Returns true if the task can be executed.
   *
   * @param string $id
   *   Handler id.
   *
   * @return bool
   *   TRUE if it's time to run.
   */
  public function shouldRunNow(string $id): bool {
    if ($this->isForced($id)) {
      return TRUE;
    }
    $result = $this->getScheduledCronRunTime($id) <= time();
    if ($this->handlers[$id] instanceof TimeControllingCronTaskInterface) {
      $result = $result && $this->handlers[$id]->shouldRunNow();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function executeHandler(string $id, $force = FALSE): bool {
    if (isset($this->handlers[$id])) {
      if ($force || $this->shouldRunNow($id)) {
        $this->log->info('Start executing ' . $id);
        $this->handlers[$id]->execute();
        $this->scheduleNextRunTime($id);
        $this->resetForceNextExecution($id);
        $this->log->debug($id . ' finished executing');
        return TRUE;
      }
      else {
        $this->log->debug(sprintf('Skip execution of %s until %s', $id, date('c', $this->getScheduledCronRunTime($id))));
        return FALSE;
      }
    }
    else {
      $this->log->warning('Attempted to execute non existing cron handler', ['id' => $id]);
      return FALSE;
    }
  }

  /**
   * Updates next run time in state.
   *
   * @param string $id
   *   Handler Id.
   */
  protected function scheduleNextRunTime(string $id) {
    if ($this->handlers[$id] instanceof ScheduledCronTaskInterface) {
      $next = $this->handlers[$id]->getNextExecutionTime();
      $this->storeValue($id, 'schedule', $next);
      // For unknown reason cache invalidation doesn't work on calling set()
      // which causes shouldRunNow() return the wrong value for some time.
      $this->state->resetCache();
      $this->log->debug(sprintf('Next run is set to %s server time', date('r', $next)));
    }
  }

  /**
   * Sets to force next execution of the task.
   *
   * It doesn't immediately executes the task but it forces to bypass all the
   * schedule checks on the next run.
   *
   * @param string $id
   *   Task id.
   */
  public function forceNextExecution(string $id) {
    $this->storeValue($id, 'forced', TRUE);
  }

  /**
   * Check whether the task execution was forced or not.
   *
   * @param string $id
   *   Task id.
   *
   * @return bool
   *   TRUE if the task execution was forced.
   */
  protected function isForced(string $id): bool {
    return (bool) $this->getValue($id, 'forced', FALSE);
  }

  /**
   * Resets force flag for the task.
   *
   * @param string $id
   *   Task id.
   */
  protected function resetForceNextExecution(string $id) {
    $this->storeValue($id, 'forced', FALSE);
  }

}
