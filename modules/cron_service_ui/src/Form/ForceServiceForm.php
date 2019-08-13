<?php

namespace Drupal\cron_service_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form that forces service execution on the next cron run.
 */
class ForceServiceForm extends ConfirmFormBase {

  /**
   * The form state key of the service ID.
   */
  const SERVICE_ID_KEY = 'service_id';

  /**
   * The route name of the service list overview.
   */
  const OVERVIEW_ROUTE = 'cron_service_ui.services.overview';

  /**
   * The cron service manager.
   *
   * @var \Drupal\cron_service\CronServiceManagerInterface
   */
  protected $cronServiceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $instance */
    $instance = parent::create($container);

    $instance->cronServiceManager = $container->get('cron_service.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cron_service_ui_force_service';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure to execute the service on the next Cron run?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute(static::OVERVIEW_ROUTE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    string $id = NULL
  ) {
    // @todo Validate that it's an existing service.
    if (!isset($id)) {
      throw new \InvalidArgumentException('The service ID must be speified.');
    }

    $form_state->set(static::SERVICE_ID_KEY, $id);

    $form = parent::buildForm($form, $form_state);

    // The getDescription() method doesn't give us any context, so we have to
    // override the description here.
    $form['description']['#markup'] = $this->t(
      'This forces execution of the %id service during the next Cron run instead of a scheduled time, if any.',
      ['%id' => $id]
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->get(static::SERVICE_ID_KEY);
    $this->cronServiceManager->forceNextExecution($id);

    $this->messenger()
      ->addStatus($this->t('Service execution forced.'));
    $form_state->setRedirect(static::OVERVIEW_ROUTE);
  }

}
