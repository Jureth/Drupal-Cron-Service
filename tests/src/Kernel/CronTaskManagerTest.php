<?php

namespace Drupal\Tests\cron_service\Kernel;

use Drupal\cron_service\CronTaskManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that tasks processor is integrated in Drupal.
 *
 * @group cron_service
 */
class CronTaskManagerTest extends KernelTestBase {

  static protected $modules = ['cron_service'];

  /**
   * Tests the service exists.
   */
  public function testServiceExists() {
    self::assertInstanceOf(CronTaskManagerInterface::class, $this->container->get('cron_service.manager'));
  }

  /**
   * Test that hook_cron executes the service.
   */
  public function testCronExecutesTheService() {
    $test_object = $this->getMockBuilder(CronTaskManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $test_object->expects(self::atLeastOnce())
      ->method('execute');

    $this->container->set('cron_service.manager', $test_object);

    $this->container->get('module_handler')->invoke('cron_service', 'cron');
  }

}
