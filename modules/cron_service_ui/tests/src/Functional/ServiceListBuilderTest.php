<?php

namespace Drupal\Tests\cron_service_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for ServiceListBuilder class.
 *
 * @group cron_service_ui
 */
class ServiceListBuilderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cron_service',
    'cron_service_ui',
    'cron_service_testing',
  ];

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $webUser;

  /**
   * Test service.
   *
   * @var \Drupal\cron_service\CronServiceManager
   */
  protected $cronManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(
      [
        'access cron service ui',
      ]
    );
    $this->drupalLogin($this->webUser);
    $this->cronManager = \Drupal::getContainer()->get('cron_service.manager');
  }

  /**
   * Checks the page exists and contains services list.
   */
  public function testServicesListPageExists() {
    $this->drupalGet('/admin/config/system/cron/services');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('service name');
    $this->assertSession()->pageTextContains('Schedule');
    $this->assertSession()->pageTextContains('Operations');

    $services = [
      'testcron.simple_service',
      'testcron.scheduled',
      'testcron.time_controlling',
    ];
    foreach ($services as $id) {
      $this->assertSession()->pageTextContains($id);
      $this->assertSession()->linkByHrefExists(
        sprintf('/admin/config/system/cron/services/%s/force', $id)
      );
    }
    $this->drupalLogout();
    $this->drupalGet('/admin/config/system/cron/services');
    $this->assertSession()->statusCodeEquals(403);
  }

}
