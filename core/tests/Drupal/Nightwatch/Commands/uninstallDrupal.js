import { execSync } from 'child_process';
import { commandAsWebserver } from '../globals';

exports.command = function uninstallDrupal(dbPrefix = '', callback) {
  const self = this;

  // Nightwatch doesn't like it when no actions are added in command file.
  const dbOption = process.env.DB_URL.length > 0 ? `--db_url ${process.env.DB_URL}` : '';
  try {
    execSync(commandAsWebserver(`php ./scripts/test-site.php tear-down ${dbOption} ${dbPrefix}`));
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
