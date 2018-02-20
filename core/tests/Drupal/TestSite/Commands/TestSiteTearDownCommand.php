<?php

namespace Drupal\TestSite\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to tear down a test Drupal site.
 *
 * @internal
 */
class TestSiteTearDownCommand extends Command {

  /**
   * The used PHP autoloader.
   *
   * @var object
   */
  protected $autoloader;

  /**
   * Constructs a new TestSiteTearDownCommand.
   *
   * @param string $autoloader
   *   The used PHP autoloader.
   * @param string|null $name
   *   The name of the command. Passing NULL means it must be set in
   *   configure().
   */
  public function __construct($autoloader, $name = NULL) {
    parent::__construct($name);

    $this->autoloader = $autoloader;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tear-down')
      ->setDescription('Removes a test site added by the install command')
      ->setHelp('All the database tables and files will be removed.')
      ->addArgument('db_prefix', InputArgument::REQUIRED, 'The database prefix for the test site')
      ->addOption('db_url', NULL, InputOption::VALUE_OPTIONAL, 'URL for database or SIMPLETEST_DB', getenv('SIMPLETEST_DB'))
      ->addUsage('test12345678')
      ->addUsage('test12345678 --db_url "mysql://username:password@localhost/databasename#table_prefix"');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $db_prefix = $input->getArgument('db_prefix');
    // Validate the db_prefix argument.
    try {
      $test_database = new TestDatabase($db_prefix);
    }
    catch (\InvalidArgumentException $e) {
      $io = new SymfonyStyle($input, $output);
      $io->getErrorStyle()->error("Invalid database prefix: $db_prefix\n\nValid database prefixes match the regular expression '/test(\d+)$/'. For example, 'test12345678'.");
      // Display the synopsis of the command like Composer does.
      $output->writeln(sprintf('<info>%s</info>', sprintf($this->getSynopsis(), $this->getName())), OutputInterface::VERBOSITY_QUIET);
      return 1;
    }

    $db_url = $input->getOption('db_url');
    putenv("SIMPLETEST_DB=$db_url");

    // Handle the cleanup of the test site.
    $this->tearDown($test_database, $db_url);
    $output->writeln("<info>Successfully uninstalled $db_prefix test site</info>");
  }

  /**
   * Removes a given instance by deleting all the database tables and files.
   *
   * @param \Drupal\Core\Test\TestDatabase $test_database
   *   The test database object.
   * @param string $db_url
   *   The database URL.
   *
   * @see \Drupal\Tests\BrowserTestBase::cleanupEnvironment()
   */
  protected function tearDown(TestDatabase $test_database, $db_url) {
    // Connect to the test database.
    $root = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
    $database = Database::convertDbUrlToConnectionInfo($db_url, $root);
    $database['prefix'] = ['default' => $test_database->getDatabasePrefix()];
    Database::addConnectionInfo(__CLASS__, 'default', $database);

    // Remove all the tables.
    $schema = Database::getConnection('default', __CLASS__)->schema();
    $tables = $schema->findTables('%');
    array_walk($tables, [$schema, 'dropTable']);

    // Delete test site directory.
    $this->fileUnmanagedDeleteRecursive($root . '/' . $test_database->getTestSitePath(), [BrowserTestBase::class, 'filePreDeleteCallback']);
  }

  /**
   * Deletes all files and directories in the specified filepath recursively.
   *
   * Note this version has no dependencies on Drupal core to ensure that the
   * test site can be torn down even if something in the test site is broken.
   *
   * @param $path
   *   A string containing either an URI or a file or directory path.
   * @param callable $callback
   *   (optional) Callback function to run on each file prior to deleting it and
   *   on each directory prior to traversing it. For example, can be used to
   *   modify permissions.
   *
   * @return bool
   *   TRUE for success or if path does not exist, FALSE in the event of an
   *   error.
   *
   * @see file_unmanaged_delete_recursive()
   */
  protected function fileUnmanagedDeleteRecursive($path, $callback = NULL) {
    if (isset($callback)) {
      call_user_func($callback, $path);
    }
    if (is_dir($path)) {
      $dir = dir($path);
      while (($entry = $dir->read()) !== FALSE) {
        if ($entry == '.' || $entry == '..') {
          continue;
        }
        $entry_path = $path . '/' . $entry;
        $this->fileUnmanagedDeleteRecursive($entry_path, $callback);
      }
      $dir->close();

      return rmdir($path);
    }
    return unlink($path);
  }

}
