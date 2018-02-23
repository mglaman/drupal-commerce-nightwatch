<?php

namespace Drupal\commerce_nightwatch;

use Drupal\TestSite\TestSetupInterface;

class TestCommerceSiteInstall implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install([
        'commerce',
        'commerce_checkout',
        'commerce_payment',
        'commerce_braintree',
        'test_page_test'
    ]);
  }

}
