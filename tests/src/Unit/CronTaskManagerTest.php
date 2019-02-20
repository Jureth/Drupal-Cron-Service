<?php

namespace Drupal\Tests\cron_service\Unit;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\cron_service\CronTaskInterface;
use Drupal\cron_service\CronTaskManager;
use Drupal\cron_service\ScheduledCronTaskInterface;
use Drupal\cron_service\TimeControllingCronTaskInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Interface combines all the testing interfaces.
 */
interface CombinedInterface extends TimeControllingCronTaskInterface, ScheduledCronTaskInterface {

}

/**
 * BaseCronTask tests.
 *
 * @group cron_service
 *
 * @coversDefaultClass \Drupal\cron_service\CronTaskManager
 */
class CronTaskManagerTest extends UnitTestCase {

  /**
   * Service injection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $stateSvc;

  /**
   * Service injection.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->stateSvc = $this
      ->getMockBuilder(StateInterface::class)
      ->getMock();
    $this->logger = $this
      ->getMockBuilder(LoggerChannelInterface::class)
      ->getMock();
  }

  /**
   * Cron task mocks factory.
   *
   * @param string $interface
   *   Task interface.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\cron_service\CronTaskInterface
   *   Mocked cron task.
   */
  protected function getTask(string $interface = CronTaskInterface::class) {
    return $this
      ->getMockBuilder($interface)
      ->getMock();
  }

  /**
   * Cron task processor factory.
   *
   * @param array|null $methods
   *   Methods to mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject|\Drupal\cron_service\CronTaskManager
   *   Mocked test object.
   */
  protected function getTestObject(array $methods = NULL) {
    return $this->getMockBuilder(CronTaskManager::class)
      ->setConstructorArgs([
        $this->stateSvc,
        $this->logger,
      ])
      ->setMethods($methods)
      ->getMock();
  }

  /**
   * Tests the class adds and stores, and processes handlers.
   */
  public function testHandlersCanBeAddedAndExecuted() {
    $test_object = $this->getTestObject(['executeHandler']);
    $handlers = [];
    $count = random_int(1, 8);
    for ($i = 0; $i < $count; $i++) {
      $handlers[] = [
        $this->getTask(),
        $this->getRandomGenerator()->name(8, TRUE),
      ];
    }

    foreach ($handlers as $handler) {
      $test_object->addHandler(...$handler);
    }

    $test_object->expects(self::exactly(count($handlers)))
      ->method('executeHandler')
      ->withConsecutive(
        ...array_map(
          function ($handler) {
            return [$handler[1], FALSE];
          },
          $handlers
        )
      )
      ->willReturn(TRUE);

    $test_object->execute();
  }

  /**
   * Tests that execution failed on trying to execute non existing handler.
   */
  public function testMissingHandlersDontRuinAnything() {
    self::assertFalse(
      $this->getTestObject()->executeHandler($this->randomMachineName())
    );
    // Even force should fail.
    self::assertFalse(
      $this->getTestObject()->executeHandler($this->randomMachineName(), TRUE)
    );
  }

  /**
   * Tests that cron task executes the executor when force flag is set.
   */
  public function testForceExecutionIgnoresEverything() {
    // Set time to past.
    $this->stateSvc
      ->expects(self::any())
      ->method('get')
      ->willReturn(time() + 86400);

    $tasks = [
      'id1' => $this->getTask(),
      'id2' => $this->getTask(TimeControllingCronTaskInterface::class),
      'id3' => $this->getTask(ScheduledCronTaskInterface::class),
      'id4' => $this->getTask(CombinedInterface::class),
    ];
    $tasks['id2']->method('shouldRunNow')->willReturn(FALSE);
    $tasks['id4']->method('shouldRunNow')->willReturn(FALSE);

    $test_object = $this->getTestObject();
    foreach ($tasks as $id => $task) {
      $task->expects(self::once())->method('execute')->willReturn(TRUE);

      $test_object->addHandler($task, $id);
      // Force execution.
      $test_object->executeHandler($id, TRUE);
    }
  }

  /**
   * Tests that cron task executes when time has come with updating state.
   *
   * Instead of creating a mock with set of expectations. We simple create a
   * fake but working StateInterface implementation and check data is kept
   * between instances.
   */
  public function testExecutionTimeIsStoredInState() {
    // Value for checking the state setter.
    $next_run_time = random_int(0, 10000);
    $task_name = $this->randomMachineName();
    $this->stateSvc = new StateMock();

    $task = $this->getTask(ScheduledCronTaskInterface::class);
    $task->expects(self::once())
      ->method('getNextExecutionTime')
      ->willReturn($next_run_time);

    $test_object = $this->getTestObject();
    $test_object->addHandler($task, $task_name);
    $test_object->execute();

    self::assertEquals(
      $next_run_time,
      $test_object->getScheduledCronRunTime($task_name)
    );

    $test_object = $this->getTestObject();
    $test_object->addHandler($task, $task_name);
    self::assertEquals(
      $next_run_time,
      $test_object->getScheduledCronRunTime($task_name)
    );

  }

  /**
   * Test executing task when time is come.
   */
  public function testScheduledTasksMustBeExecutedWhenItsTime() {
    $task = $this->getTask(ScheduledCronTaskInterface::class);
    $task->expects(self::once())
      ->method('execute')
      ->willReturn(TRUE);

    // Combined should also be executed when it allows to.
    $task2 = $this->getTask(CombinedInterface::class);
    $task2->expects(self::once())
      ->method('execute')
      ->willReturn(TRUE);
    $task2->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(TRUE);

    $task3 = $this->getTask(CombinedInterface::class);
    $task3->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);
    $task3->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(FALSE);

    $this->stateSvc
      ->expects(self::atLeastOnce())
      ->method('get')
      ->willReturn(0);

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    $test_object
      ->method('getScheduledCronRunTime')
      ->withAnyParameters()
      ->willReturn(0);

    $test_object->addHandler($task, 'task_1');
    $test_object->addHandler($task2, 'task_2');
    $test_object->addHandler($task3, 'task_3');
    $test_object->execute();

  }

  /**
   * Tests that cron task skips the execution when time is not come.
   */
  public function testScheduledTasksMustNotBeExecutedBeforeTheirTime() {
    $task = $this->getTask(ScheduledCronTaskInterface::class);
    $task->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);

    // Combined should allow executing but not be actually executed.
    $task2 = $this->getTask(CombinedInterface::class);
    $task2->expects(self::never())
      ->method('execute')
      ->willReturn(TRUE);
    $task2->expects(self::any())
      ->method('shouldRunNow')
      ->willReturn(TRUE);

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    $test_object
      ->method('getScheduledCronRunTime')
      ->willReturn(time() + 86400);

    $test_object->addHandler($task, 'task_1');
    $test_object->addHandler($task2, 'task_2');
    $test_object->execute();
  }

  /**
   * Tests working with time controlling tasks.
   */
  public function testItRespectsTimeControllingTasks() {
    $task1 = $this->getTask(TimeControllingCronTaskInterface::class);
    $task1->expects(self::atLeastOnce())
      ->method('shouldRunNow')
      ->willReturn(TRUE);
    $task1
      ->expects(self::once())
      ->method('execute');

    $task2 = $this->getTask(TimeControllingCronTaskInterface::class);
    $task2->expects(self::atLeastOnce())
      ->method('shouldRunNow')
      ->willReturn(FALSE);
    $task2->expects(self::never())
      ->method('execute');

    $test_object = $this->getTestObject();
    $test_object->addHandler($task1, 'some_task');
    $test_object->addHandler($task2, 'some_other_task');
    $test_object->execute();
  }

  /**
   * Tests how forcing next runs work.
   */
  public function testForceNextRunWorks() {
    $this->stateSvc = new StateMock();

    $task_name = $this->randomMachineName();
    $task = $this->getTask();
    $task->expects(self::once())
      ->method('execute');

    $test_object = $this->getTestObject(['getScheduledCronRunTime']);
    // It's not the time.
    $test_object->expects(self::any())
      ->method('getScheduledCronRunTime')
      ->willReturn(time() + 86400);

    $test_object->addHandler($task, $task_name);
    self::assertFalse($test_object->shouldRunNow($task_name));
    $test_object->forceNextExecution($task_name);
    self::assertTrue($test_object->shouldRunNow($task_name));
    $test_object->execute();
  }

}
