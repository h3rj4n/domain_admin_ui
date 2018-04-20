<?php

namespace Drupal\domain_admin_ui\Config;

use Drupal\Core\Config\Config as CoreConfig;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Extend core Config class to save domain specific configuration.
 */
class Config extends CoreConfig {

  /**
   * The Domain negotiator.
   *
   * @var DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Set the Domain negotiator.
   *
   * @param DomainNegotiatorInterface $domain_negotiator
   */
  public function setDomainNegotiator(DomainNegotiatorInterface $domain_negotiator) {
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    // Remember original config name.
    $originalName = $this->name;
    $data = $this->data;
    $originalData = $this->originalData;

    try {
      // Get domain config name for saving.
      $domainConfigName = $this->getDomainConfigName();

      // If config is new and we are currently saving domain specific configuration,
      // save with original name first so that there is always a default configuration.
      if ($this->isNew && $domainConfigName != $originalName) {
        parent::save($has_trusted_data);
      }

      // When the domain config name is the same as the original name it should
      // be handled as a normal config file. No domain specific actions are required.
      if ($domainConfigName === $originalName) {
        parent::save($has_trusted_data);
      }
      else {
        // Switch to use domain config name and save.
        $this->name = $domainConfigName;

        // Only save override changes.
        $this->data = $this->arrayRecursiveDiff($this->data, $this->originalData);
        $this->originalData = [];

        // Problem: The input data contains the data from both:
        //   - theme.settings
        //   - domain.config.domain.theme.settings
        // The diff contains only the data that's different from the original
        // domain specific config. The data should contain of the diff + the
        // domain specific config.
        // @todo Add tests for this!
        // @todo Specific settings can be set to the origin (or parent) value. These settings should not be saved in the domain specific config file! (but removed)
        // @todo Use dependency injection!
        /** @var \Drupal\Core\Config\ImmutableConfig $domainOriginalData */
        $domainOriginalData = \Drupal::getContainer()->get('config.factory')->get($domainConfigName);
        $this->data = array_merge_recursive($domainOriginalData->getRawData(), $this->data);

        parent::save($has_trusted_data);
      }
    }
    catch (\Exception $e) {
      // Reset back to original config name if save fails and re-throw.
      $this->name = $originalName;
      $this->data = $data;
      $this->originalData = $originalData;
      throw $e;
    }

    // Reset back to original config name after saving.
    $this->name = $originalName;
    $this->data = $data;
    $this->originalData = $originalData;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete domain specific config. For example: Color module deletes config before recreating.
    // @todo Can this cause problems?

    $domainConfigName = $this->getDomainConfigName();

    // Overwrite the name with the domain specific name.
    $this->name = $domainConfigName;

    return parent::delete();
  }


  /**
   * Get the domain config name.
   */
  protected function getDomainConfigName() {
    // Return selected config name.
    if ($domain = $this->domainNegotiator->getActiveDomain(FALSE)) {
      $overrider = $this->domainNegotiator->getDomainConfigOverrider();
      $configNames = $overrider->getDomainConfigName($this->name, $domain);
      $language_id = $this->domainNegotiator->getSelectedLanguageId();
      $domain_id = $this->domainNegotiator->getSelectedDomainId();
    }

    // Use default config name if domain hasn't been selected.
    if (empty($domain_id)) {
      return $this->name;
    }

    // Use domain config name if language hasn't been selected.
    if (empty($language_id) || $language_id == 'und') {
      return $configNames['domain'];
    }

    // Return language config name if language has been selected.
    return $configNames['langcode'];
  }

  /**
   * Check config differences recursively.
   *
   * @param unknown $aArray1
   * @param unknown $aArray2
   * @return unknown[]|unknown[][]
   */
  protected function arrayRecursiveDiff($aArray1, $aArray2) {
    $aReturn = array();

    foreach ($aArray1 as $mKey => $mValue) {
      if (array_key_exists($mKey, $aArray2)) {
        if (is_array($mValue)) {
          $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
          if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
        } else {
          if ($mValue != $aArray2[$mKey]) {
            $aReturn[$mKey] = $mValue;
          }
        }
      } else {
        $aReturn[$mKey] = $mValue;
      }
    }

    return $aReturn;
  }
}
