<?php

namespace Drupal\domain_admin_ui;

use Drupal\Core\Config\StorageInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\domain_config\DomainConfigOverrider;
use Drupal\domain\DomainInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Extend DomainConfigOverrider to allow domain to be set.
 */
class DomainAdminUIConfigOverrider extends DomainConfigOverrider {

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $storage, DomainNegotiatorInterface $domainNegotiator) {
    parent::__construct($storage);

    $this->domainNegotiator = $domainNegotiator;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\domain_config\DomainConfigOverrider::getDomainConfigName()
   */
  public function getDomainConfigName($name, DomainInterface $domain) {
    return parent::getDomainConfigName($name, $domain);
  }

  /**
   * Set the domain.
   *
   * @param DomainInterface $domain
   */
  public function setDomain(DomainInterface $domain) {
    $this->domain = $domain;
  }
  
  /**
   * Set the language.
   *
   * @param LanguageInterface $language
   */
  public function setLanguage(LanguageInterface $language) {
    $this->language = $language;
  }

  /**
   * {@inheritdoc}
   */
  protected function initiateContext() {
    parent::initiateContext();

    // Remove the domain when special conditions are met so the base config file
    // will be loaded without any of the domain overrides. Problem was that this
    // parent function load the domain with the reset bool. This resulted in an
    // always loaded domain object. When the 'all domains' is selected you
    // expect only the core config. Not domain specific config.

    // @todo Disable for non-admin (I removed the domain switcher for the front-end). This should be configurable!

    /** @var \Drupal\Core\Routing\Router $router */
    $router = \Drupal::service('router.no_access_checks');
    $routeArray = $router->matchRequest(\Drupal::request());

    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($routeArray['_route_object']);

    if (!empty($this->domainNegotiator)
      && !$this->domainNegotiator->getSelectedDomainId()
      && \Drupal::currentUser()->hasPermission('use domain admin switcher')
      && $is_admin
    ) {
      $this->domain = NULL;
    }
  }

}
