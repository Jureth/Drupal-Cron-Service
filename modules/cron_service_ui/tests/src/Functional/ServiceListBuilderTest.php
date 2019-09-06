<?php

namespace Drupal\Tests\cron_service_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class ServiceListBuilderTest
 *
 * @group cron_service_ui
 */
class ServiceListBuilderTest extends BrowserTestBase {

  protected static $modules = ['cron_service', 'cron_service_ui', 'cron_service_testing'];

  protected $webUser;

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

  public function testFormExists() {
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
    foreach($services as $id) {
      $this->assertSession()->pageTextContains($id);
      $this->assertSession()->linkByHrefExists(sprintf('/admin/config/system/cron/services/%s/force', $id));
    }
    $this->drupalLogout();
    $this->drupalGet('/admin/config/system/cron/services');
    $this->assertSession()->statusCodeEquals(403);
  }

}
