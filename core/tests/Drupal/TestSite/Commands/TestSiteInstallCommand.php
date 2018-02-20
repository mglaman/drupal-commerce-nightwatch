<?php

namespace Drupal\TestSite\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\FunctionalTestSetupTrait;
use Drupal\Core\Test\TestSetupTrait;
use Drupal\TestSite\TestSetupInterface;
use Drupal\Tests\RandomGeneratorTrait;
use Drupal\Tests\SessionTestTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to create a test Drupal site.
 *
 * @internal
 */
class TestSiteInstallCommand extends Command {

  use FunctionalTestSetupTrait {
    installParameters as protected installParametersTrait;
  }
  use RandomGeneratorTrait;
  use SessionTestTrait;
  use TestSetupTrait {
    changeDatabasePrefix as protected changeDatabasePrefixTrait;
  }

  /**
   * The install profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Time limit in seconds for the test.
   *
   * @var int
   */
  protected $timeLimit = 500;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix;

  /**
   * The language to install the site in.
   *
   * @var string
   */
  protected $langcode = 'en';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('install')
      ->setDescription('Creates a test Drupal site')
      ->setHelp('The details to connect to the test site created will be displayed upon success. It will contain the database prefix and the user agent.')
      ->addOption('setup_class', NULL, InputOption::VALUE_OPTIONAL, 'A PHP class to setup configuration used by the test, for example, \Drupal\TestSite\TestSiteInstallTestScript')
      ->addOption('db_url', NULL, InputOption::VALUE_OPTIONAL, 'URL for database or SIMPLETEST_DB', getenv('SIMPLETEST_DB'))
      ->addOption('base_url', NULL, InputOption::VALUE_OPTIONAL, 'Base URL for site under test or SIMPLETEST_BASE_URL', getenv('SIMPLETEST_BASE_URL'))
      ->addOption('install_profile', NULL, InputOption::VALUE_OPTIONAL, 'Install profile to install the site in. Defaults to testing', 'testing')
      ->addOption('langcode', NULL, InputOption::VALUE_OPTIONAL, 'The language to install the site in. Defaults to en', 'en')
      ->addOption('json', NULL, InputOption::VALUE_NONE, 'Output test site connection details in JSON')
      ->addUsage('--setup_class "\Drupal\TestSite\TestSiteInstallTestScript" --json')
      ->addUsage('--install_profile demo_umami --langcode fr')
      ->addUsage('--base_url "http://example.com" --db_url "mysql://username:password@localhost/databasename#table_prefix"');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Validate the setup class prior to installing a database to avoid creating
    // unnecessary sites.
    $this->validateSetupClass($input->getOption('setup_class'));
    // Ensure we can install a site in the sites/simpletest directory.
    $this->ensureDirectory();

    $db_url = $input->getOption('db_url');
    $base_url = $input->getOption('base_url');
    putenv("SIMPLETEST_DB=$db_url");
    putenv("SIMPLETEST_BASE_URL=$base_url");

    // Manage site fixture.
    $this->setup($input->getOption('install_profile'), $input->getOption('setup_class'), $input->getOption('langcode'));

    $user_agent = drupal_generate_test_ua($this->databasePrefix);
    if ($input->getOption('json')) {
      $output->writeln(json_encode([
        'db_prefix' => $this->databasePrefix,
        'user_agent' => $user_agent,
      ]));
    }
    else {
      $output->writeln('<info>Successfully installed a test site</info>');
      $io = new SymfonyStyle($input, $output);
      $io->table([], [
        ['Database prefix', $this->databasePrefix],
        ['User agent', $user_agent],
      ]);
    }
  }

  /**
   * Validates the setup class.
   *
   * @param string|null $class
   *   The setup class to validate.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the class does not exist or does not implement
   *   \Drupal\TestSite\TestSetupInterface.
   */
  protected function validateSetupClass($class) {
    if ($class === NULL) {
      return;
    }
    if (!class_exists($class)) {
      throw new \InvalidArgumentException("There was a problem loading $class");
    }

    if (!is_subclass_of($class, TestSetupInterface::class)) {
      throw new \InvalidArgumentException('You need to define a class implementing \Drupal\TestSite\TestSetupInterface');
    }
  }

  /**
   * Ensures that the sites/simpletest directory exists and is writable.
   */
  protected function ensureDirectory() {
    $root = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    if (!is_writable($root . '/sites/simpletest')) {
      if (!@mkdir($root . '/sites/simpletest')) {
        throw new \RuntimeException($root . '/sites/simpletest must exist and be writable to install a test site');
      }
    }
  }

  /**
   * Creates a test drupal installation.
   *
   * @param string $profile
   *   (optional) The installation profile to use.
   * @param string $setup_class
   *   (optional) Setup class. A PHP class to setup configuration used by the
   *   test.
   * @param string $langcode
   *   (optional) The language to install the site in.
   */
  public function setup($profile = 'testing', $setup_class = NULL, $langcode = 'en') {
    $this->profile = $profile;
    $this->langcode = $langcode;
    $this->setupBaseUrl();
    $this->prepareEnvironment();
    $this->installDrupal();

    if ($setup_class) {
      $this->executeSetupClass($setup_class);
    }
  }

  /**
   * Installs Drupal into the test site.
   */
  protected function installDrupal() {
    $this->initUserSession();
    $this->prepareSettings();
    $this->doInstall();
    $this->initSettings();
    $container = $this->initKernel(\Drupal::request());
    $this->initConfig($container);
    $this->installModulesFromClassProperty($container);
    $this->rebuildAll();
  }

  /**
   * Uses the setup file to configure Drupal.
   *
   * @param string $class
   *   The full qualified class name, which should setup Drupal for tests. For
   *   example this class could create content types and fields or install
   *   modules. The class needs to implement TestSetupInterface.
   *
   * @see \Drupal\TestSite\TestSetupInterface
   */
  protected function executeSetupClass($class) {
    /** @var \Drupal\TestSite\TestSetupInterface $instance */
    $instance = new $class();
    $instance->setup();
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = $this->installParametersTrait();
    $parameters['parameters']['langcode'] = $this->langcode;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function changeDatabasePrefix() {
    // Ensure that we use the database from SIMPLETEST_DB environment variable.
    Database::removeConnection('default');
    $this->changeDatabasePrefixTrait();
  }

}
