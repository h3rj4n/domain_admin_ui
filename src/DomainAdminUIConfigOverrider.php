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
  public function loadOverrides($names) {
    // This function is overridden because the 'parent::loadOverrides' cannot be
    // manipulated any other way. It's uses the domainNegotiator with a hard
    // refresh (see DomainConfigOverride::initiateContext) which results in the
    // current domain being loaded. When the current domain cannot be found it
    // will fallback to the default domain. Either way, the config will be
    // overwritten by a domain. To prevent this, just return an empty array.
    // This way the selected domain will be leading for the loaded configuration.
    if (empty($this->domainNegotiator) || !$this->domainNegotiator->getSelectedDomainId()) {
      return [];
    }

    return parent::loadOverrides($names);
  }

}
