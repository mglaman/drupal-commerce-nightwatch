#!/usr/bin/env php
<?php

/**
 * @file
 * A command line application to install drupal for tests.
 */

use Drupal\TestSite\TestSiteApplication;

if (PHP_SAPI !== 'cli') {
  return;
}

$autoloader = require __DIR__ . '/../../autoload.php';
// Access to all the test classes is required as well.
require_once __DIR__ . '/../tests/bootstrap.php';

$app = new TestSiteApplication($autoloader);
$app->run();
