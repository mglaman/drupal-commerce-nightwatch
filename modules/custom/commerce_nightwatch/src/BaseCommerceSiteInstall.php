<?php

namespace Drupal\commerce_nightwatch;

use Drupal\commerce_store\Entity\Store;
use Drupal\TestSite\TestSetupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseCommerceSiteInstall implements TestSetupInterface {

  public static $modules = [
    'commerce',
    'commerce_product',
    'commerce_checkout',
    'commerce_payment',
  ];

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  public function setup() {
    $container = \Drupal::getContainer();
    $this->installModulesFromClassProperty($container);
    $this->store = $this->createStore();
  }

  /**
   * Install modules defined by `static::$modules`.
   *
   * To install test modules outside of the testing environment, add
   * @code
   * $settings['extension_discovery_scan_tests'] = TRUE;
   * @endcode
   * to your settings.php.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  protected function installModulesFromClassProperty(ContainerInterface $container) {
    $class = get_class($this);
    $modules = [];
    while ($class) {
      if (property_exists($class, 'modules')) {
        $modules = array_merge($modules, $class::$modules);
      }
      $class = get_parent_class($class);
    }
    if ($modules) {
      $modules = array_unique($modules);
      $container->get('module_installer')->install($modules, TRUE);
    }
  }


  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $entity->save();
    $entity = $storage->load($entity->id());
    return $entity;
  }

  protected function createStore() {
    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('USD');
    $store = Store::create([
      'type' => 'online',
      'uid' => 1,
      'name' => 'My Awesome Store',
      'mail' => 'store@example.com',
      'address' => [
        'country_code' => 'US',
        'address_line1' => '123 Appleseed Lane',
        'locality' => 'Milwaukee',
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'default_currency' => 'USD',
      'billing_countries' => [
        'US',
      ],
    ]);
    $store->save();
    /** @var \Drupal\commerce_store\StoreStorage $store_storage */
    $store_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_store');
    $store_storage->markAsDefault($store);

    $store = Store::load($store->id());

    return $store;
  }

}
