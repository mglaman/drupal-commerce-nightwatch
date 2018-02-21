import { execSync } from 'child_process';
import { commandAsWebserver } from '../globals';

exports.command = function installDrupal(setupClass = '', callback) {
  const self = this;

  const dbOption = process.env.DRUPAL_DB_URL.length > 0 ? `--db_url ${process.env.DRUPAL_DB_URL}` : '';
  let dbPrefix = '';

  try {
    // Single slash is replaced with 2 slashes because it will get printed on the command line,
    // which will be escaped again by the PHP script.
    const install = execSync(commandAsWebserver(`php ./scripts/test-site.php install --setup_class ${setupClass.replace(/\\/g, '\\\\')} --base_url ${process.env.DRUPAL_BASE_URL} ${dbOption} --json`));
    const installData = JSON.parse(install.toString());
    dbPrefix = installData.db_prefix;
    const matches = process.env.DRUPAL_BASE_URL.match(/^https?\:\/\/([^\/?#]+)(?:[\/?#]|$)/i);
    const domain = matches[1];
    const path = matches[2];
    this
      .url(process.env.DRUPAL_BASE_URL)
      .setCookie({
        name: 'SIMPLETEST_USER_AGENT',
        // Colons needs to be URL encoded to be valid.
        value: encodeURIComponent(installData.user_agent),
        path: path,
        domain: domain,
      })
  }
  catch(error) {
    this.assert.fail(error);
    // Nightwatch doesn't like it when no actions are added in command file.
    this.pause(200);
  }

  if (typeof callback === 'function') {
    callback.call(self, dbPrefix);
  }
  return this;
};
