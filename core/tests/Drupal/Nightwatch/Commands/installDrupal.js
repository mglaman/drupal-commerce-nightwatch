import { execSync } from 'child_process';

exports.command = function installDrupal(setupClass = '', callback) {
  const self = this;

  let dbOption = process.env.DB_URL.length > 0 ? `--db_url ${process.env.DB_URL}` : '';

  try {
    // Single slash is replaced with 2 slashes because it will get printed on the command line, which will be escaped
    // again by the PHP script.
    const install = execSync(`sudo -u ${process.env.WEBSERVER_USER} php ./scripts/test-site.php install --setup_class ${setupClass.replace(/\\/g, '\\\\')} --base_url ${process.env.BASE_URL} ${dbOption} --json`);
    const installData = JSON.parse(install.toString());
    const matches = process.env.BASE_URL.match(/^https?\:\/\/([^\/?#]+)(?:[\/?#]|$)/i);
    const domain = matches[1];
    const path = matches[2];
    this
      .url(process.env.BASE_URL)
      .setCookie({
        name: 'SIMPLETEST_USER_AGENT',
        // Colons needs to be URL encoded to be valid.
        value: encodeURIComponent(installData),
        path: path,
        domain: domain,
      })
  }
  catch(error) {
    this.assert.fail(error);
    // Nightwatch doesn't like it when no actions are added in command file.
    this.pause(0);
  }

  if (typeof callback === 'function') {
    callback.call(self);
  }
  return this;
};
