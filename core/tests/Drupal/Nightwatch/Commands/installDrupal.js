const exec = require('child_process').exec;

/**
 * @param browser
 * @param cookieValue
 * @returns {*}
 */
const setupCookie = function (browser, cookieValue, done) {
  const matches = process.env.BASE_URL.match(/^https?\:\/\/([^\/?#]+)(?:[\/?#]|$)/i);
  const domain = matches[1];
  const path = matches[2];

  return browser
    // See https://bugs.chromium.org/p/chromedriver/issues/detail?id=728#c10
    .url(process.env.BASE_URL)
    .setCookie({
      name: 'SIMPLETEST_USER_AGENT',
      // Colons needs to be URL encoded to be valid.
      value: encodeURIComponent(cookieValue),
      path: path,
      domain: domain,
    }, done);
};

exports.command = function installDrupal(setupClass = '', done) {
  const self = this;

  let dbOption = '';
  if (process.env.DB_URL && process.env.DB_URL !== undefined && process.env.DB_URL.length > 0) {
    dbOption = `--db_url ${process.env.DB_URL}`;
  }

  exec(`sudo -u ${process.env.WEBSERVER_USER} php ./scripts/test-site.php install --setup_class ${setupClass} --base_url ${process.env.BASE_URL} ${dbOption} --json`, (err, output) => {
    if (err) {
      console.error(err);
      return done(err);
    }
    const install_data = JSON.parse(output);
    setupCookie(self, install_data.user_agent, done);
  });


  return this;
};
