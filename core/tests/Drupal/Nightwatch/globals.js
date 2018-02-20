import { spawn } from 'child_process';
import chromedriver from 'chromedriver';

const commandAsWebserver = (command) => {
  if (process.env.WEBSERVER_USER) {
    return `sudo -u ${process.env.WEBSERVER_USER} ${command}`;
  }
  return command;
};

let phpWebServer;

module.exports = {
  before: (done) => {
    if (!JSON.parse(process.env.CHROME_STANDALONE)) {
      chromedriver.start();
    }
    // Automatically start a webserver.
    if (!process.env.BASE_URL) {
      // @todo Use https://www.drupal.org/project/ideas/issues/2911319 once its available.
      process.env.BASE_URL = 'http://localhost:8888';
      phpWebServer = spawn(commandAsWebserver('php'), ['-S', 'localhost:8888', '.ht.router.php'], {cwd: '../'});
    }
    done();
  },
  after: (done) => {
    if (!JSON.parse(process.env.CHROME_STANDALONE)) {
      chromedriver.stop();
    }
    if (phpWebServer) {
      phpWebServer.kill();
    }
    done();
  },
  commandAsWebserver,
};
