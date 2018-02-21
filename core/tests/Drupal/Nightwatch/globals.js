import { spawn } from 'child_process';
import chromedriver from 'chromedriver';

const commandAsWebserver = (command) => {
  if (process.env.DRUPAL_TEST_WEBSERVER_USER) {
    return `sudo -u ${process.env.DRUPAL_TEST_WEBSERVER_USER} ${command}`;
  }
  return command;
};

let phpWebServer;

module.exports = {
  before: (done) => {
    if (!JSON.parse(process.env.DRUPAL_TEST_WEBDRIVER_STANDALONE)) {
      chromedriver.start();
    }
    // Automatically start a webserver.
    if (!process.env.DRUPAL_TEST_BASE_URL) {
      // @todo Use https://www.drupal.org/project/ideas/issues/2911319 once its available.
      process.env.DRUPAL_TEST_BASE_URL = 'http://localhost:8888';
      phpWebServer = spawn(commandAsWebserver('php'), ['-S', 'localhost:8888', '.ht.router.php'], {cwd: '../'});
    }
    done();
  },
  after: (done) => {
    if (!JSON.parse(process.env.DRUPAL_TEST_WEBDRIVER_STANDALONE)) {
      chromedriver.stop();
    }
    if (phpWebServer) {
      phpWebServer.kill();
    }
    done();
  },
  commandAsWebserver,
};
