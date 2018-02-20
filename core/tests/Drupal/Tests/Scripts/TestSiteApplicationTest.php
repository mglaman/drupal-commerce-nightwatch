<?php

namespace Drupal\Tests\Scripts;

use Drupal\Core\Database\Database;
use Drupal\Core\Test\TestDatabase;
use Drupal\TestSite\TestSiteInstallTestScript;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests core/scripts/test-site.php.
 *
 * @group Setup
 *
 * This test uses the Drupal\Core\Database\Database class which has a static.
 * Therefore run in an separate process to avoid side effects.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @see \Drupal\TestSite\TestSiteApplication
 * @see \Drupal\TestSite\Commands\TestSiteInstallCommand
 * @see \Drupal\TestSite\Commands\TestSiteTearDownCommand
 */
class TestSiteApplicationTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->root = dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  /**
   * @coversNothing
   */
  public function testInstallWithNonExistingClass() {
    $php_binary_finder = new PhpExecutableFinder();
    $php_binary_path = $php_binary_finder->find();

    // Create a connection to the DB configured in SIMPLETEST_DB.
    $connection = Database::getConnection('default', $this->addTestDatabase(''));
    $table_count = count($connection->schema()->findTables('%'));

    $command_line = $php_binary_path . ' core/scripts/test-site.php install --setup_class "this-class-does-not-exist" --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->run();

    $this->assertContains('There was a problem loading this-class-does-not-exist', $process->getErrorOutput());
    $this->assertSame(1, $process->getExitCode());
    $this->assertCount($table_count, $connection->schema()->findTables('%'), 'No additional tables created in the database');
  }

  /**
   * @coversNothing
   */
  public function testInstallWithNonSetupClass() {
    $php_binary_finder = new PhpExecutableFinder();
    $php_binary_path = $php_binary_finder->find();

    // Create a connection to the DB configured in SIMPLETEST_DB.
    $connection = Database::getConnection('default', $this->addTestDatabase(''));
    $table_count = count($connection->schema()->findTables('%'));

    $command_line = $php_binary_path . ' core/scripts/test-site.php install --setup_class "' . static::class . '" --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->run();

    $this->assertContains('You need to define a class implementing \Drupal\TestSite\TestSetupInterface', $process->getErrorOutput());
    $this->assertSame(1, $process->getExitCode());
    $this->assertCount($table_count, $connection->schema()->findTables('%'), 'No additional tables created in the database');
  }

  /**
   * @coversNothing
   */
  public function testInstallScript() {
    $simpletest_path = $this->root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'simpletest';
    if (!is_writable($simpletest_path)) {
      $this->markTestSkipped("Requires the directory $simpletest_path to exist and be writable");
    }
    $php_binary_finder = new PhpExecutableFinder();
    $php_inary_path = $php_binary_finder->find();

    // Install a site using the JSON output.
    $command_line = $php_inary_path . ' core/scripts/test-site.php install --json --setup_class "' . TestSiteInstallTestScript::class . '" --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();

    $this->assertSame(0, $process->getExitCode());
    $result = json_decode($process->getOutput(), TRUE);
    $db_prefix = $result['db_prefix'];
    $this->assertStringStartsWith('simpletest' . substr($db_prefix, 4) . ':', $result['user_agent']);

    $http_client = new Client();
    $request = (new Request('GET', getenv('SIMPLETEST_BASE_URL') . '/test-page'))
      ->withHeader('User-Agent', trim($result['user_agent']));

    $response = $http_client->send($request);
    // Ensure the test_page_test module got installed.
    $this->assertContains('Test page | Drupal', (string) $response->getBody());

    // Ensure that there are files and database tables for tear down command to
    // clean up.
    $key = $this->addTestDatabase($db_prefix);
    $this->assertGreaterThan(0, count(Database::getConnection('default', $key)->schema()->findTables('%')));
    $test_database = new TestDatabase($db_prefix);
    $test_file = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . '.htkey';
    $this->assertFileExists($test_file);

    // Install another site so we can ensure tear down only removes one site at
    // a time. Use the regular output.
    $command_line = $php_inary_path . ' core/scripts/test-site.php install --setup_class "' . TestSiteInstallTestScript::class . '" --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertContains('Successfully installed a test site', $process->getOutput());
    $this->assertSame(0, $process->getExitCode());
    $regex = '/Database prefix\s+([^\s]*)/';
    $this->assertRegExp($regex, $process->getOutput());
    preg_match('/Database prefix\s+([^\s]*)/', $process->getOutput(), $matches);
    $other_db_prefix = $matches[1];
    $other_key = $this->addTestDatabase($other_db_prefix);
    $this->assertGreaterThan(0, count(Database::getConnection('default', $other_key)->schema()->findTables('%')));

    // Now test the tear down process as well.
    $command_line = $php_inary_path . ' core/scripts/test-site.php tear-down ' . $db_prefix . ' --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains("Successfully uninstalled $db_prefix test site", $process->getOutput());

    // Ensure that all the tables and files for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $key)->schema()->findTables('%'));
    $this->assertFileNotExists($test_file);

    // Ensure the other site's tables and files still exist.
    $this->assertGreaterThan(0, count(Database::getConnection('default', $other_key)->schema()->findTables('%')));
    $test_database = new TestDatabase($other_db_prefix);
    $test_file = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . '.htkey';
    $this->assertFileExists($test_file);

    // Tear down the other site installed. Tear down should work if the test
    // site is broken. Prove this by removing its settings.php.
    $test_site_settings = $this->root . DIRECTORY_SEPARATOR . $test_database->getTestSitePath() . DIRECTORY_SEPARATOR . 'settings.php';
    $this->assertTrue(unlink($test_site_settings));
    $command_line = $php_inary_path . ' core/scripts/test-site.php tear-down ' . $other_db_prefix . ' --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    // Set the timeout to a value that allows debugging.
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());
    $this->assertContains("Successfully uninstalled $other_db_prefix test site", $process->getOutput());

    // Ensure that all the tables and files for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $other_key)->schema()->findTables('%'));
    $this->assertFileNotExists($test_file);
  }

  /**
   * @coversNothing
   */
  public function testInstallInDifferentLanguage() {
    $simpletest_path = $this->root . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . 'simpletest';
    if (!is_writable($simpletest_path)) {
      $this->markTestSkipped("Requires the directory $simpletest_path to exist and be writable");
    }
    $php_binary_finder = new PhpExecutableFinder();
    $php_binary_path = $php_binary_finder->find();

    $command_line = $php_binary_path . ' core/scripts/test-site.php install --json --langcode fr --setup_class "' . TestSiteInstallTestScript::class . '" --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertEquals(0, $process->getExitCode());

    $result = json_decode($process->getOutput(), TRUE);
    $db_prefix = $result['db_prefix'];
    $http_client = new Client();
    $request = (new Request('GET', getenv('SIMPLETEST_BASE_URL') . '/test-page'))
      ->withHeader('User-Agent', trim($result['user_agent']));

    $response = $http_client->send($request);
    // Ensure the test_page_test module got installed.
    $this->assertContains('Test page | Drupal', (string) $response->getBody());
    $this->assertContains('lang="fr"', (string) $response->getBody());

    // Now test the tear down process as well.
    $command_line = $php_binary_path . ' core/scripts/test-site.php tear-down ' . $db_prefix . ' --db_url "' . getenv('SIMPLETEST_DB') . '"';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(0, $process->getExitCode());

    // Ensure that all the tables for this DB prefix are gone.
    $this->assertCount(0, Database::getConnection('default', $this->addTestDatabase($db_prefix))->schema()->findTables('%'));
  }

  /**
   * @coversNothing
   */
  public function testTearDownDbPrefixValidation() {
    $php_binary_finder = new PhpExecutableFinder();
    $php_binary_path = $php_binary_finder->find();

    $command_line = $php_binary_path . ' core/scripts/test-site.php tear-down not-a-valid-prefix';
    $process = new Process($command_line, $this->root);
    $process->setTimeout(500);
    $process->run();
    $this->assertSame(1, $process->getExitCode());
    $this->assertContains('Invalid database prefix: not-a-valid-prefix', $process->getErrorOutput());
  }

  /**
   * Adds the installed test site to the database connection info.
   *
   * @param string $db_prefix
   *   The prefix of the installed test site.
   *
   * @return string
   *   The database key of the added connection.
   */
  protected function addTestDatabase($db_prefix) {
    $database = Database::convertDbUrlToConnectionInfo(getenv('SIMPLETEST_DB'), $this->root);
    $database['prefix'] = ['default' => $db_prefix];
    $target = __CLASS__ . $db_prefix;
    Database::addConnectionInfo($target, 'default', $database);
    return $target;
  }

}
