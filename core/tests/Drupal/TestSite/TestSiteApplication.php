<?php

namespace Drupal\TestSite;

use Drupal\TestSite\Commands\TestSiteInstallCommand;
use Drupal\TestSite\Commands\TestSiteTearDownCommand;
use Symfony\Component\Console\Application;

/**
 * Application wrapper for test site commands.
 *
 * In order to see what commands are available and how to use them run
 * "php core/scripts/test-site.php" from command line and use the help system.
 *
 * @internal
 */
class TestSiteApplication extends Application {

  /**
   * The used PHP autoloader.
   *
   * @var object
   */
  protected $autoloader;

  /**
   * TestSiteApplication constructor.
   *
   * @param object $autoloader
   *   The used PHP autoloader.
   */
  public function __construct($autoloader) {
    $this->autoloader = $autoloader;
    parent::__construct('test-site', '0.1.0');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultCommands() {
    // Even though this is a single command, keep the HelpCommand (--help).
    $default_commands = parent::getDefaultCommands();
    $default_commands[] = new TestSiteInstallCommand();
    $default_commands[] = new TestSiteTearDownCommand($this->autoloader);
    return $default_commands;
  }

}
