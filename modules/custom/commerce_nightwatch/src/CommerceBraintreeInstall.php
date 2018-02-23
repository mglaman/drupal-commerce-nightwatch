<?php

namespace Drupal\commerce_nightwatch;

use Drupal\commerce_payment\Entity\PaymentGateway;

class CommerceBraintreeInstall extends BaseCommerceSiteInstall {

  public static $modules = [
    'commerce_braintree',
  ];

  public function setup() {
    parent::setup();

    if (!getenv('BRAINTREE_MERCHANT_ID')) {
      throw new \InvalidArgumentException('Missing BRAINTREE_MERCHANT_ID');
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'braintree',
      'label' => 'Braintree',
      'plugin' => 'braintree_hostedfields',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'merchant_id' => getenv('BRAINTREE_MERCHANT_ID'),
      'public_key' => getenv('BRAINTREE_PUBLIC_KEY'),
      'private_key' => getenv('BRAINTREE_PRIVATE_KEY'),
      'merchant_account_id' => [
        'USD' => getenv('BRAINTREE_MERCHANT_ACCOUNT_ID'),
      ],
      'display_label' => 'Braintree',
      'payment_method_types' => ['credit_card', 'paypal'],
    ]);
    $gateway->save();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => 'sku123',
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);
  }

}
