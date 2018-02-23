import path from 'path';
import glob from 'glob';

const regex = /(.+\/tests\/src\/Nightwatch\/Tests)\/.*/g;
const folders = {};
let m;
glob
  .sync("**/tests/src/Nightwatch/Tests/**/*.js", {
    cwd:  path.resolve(process.cwd(), '..'),
  })
  .forEach(file => {
    while ((m = regex.exec(file)) !== null) {
      // This is necessary to avoid infinite loops with zero-width matches
      if (m.index === regex.lastIndex) {
        regex.lastIndex++;
      }

      folders[`../${m[1]}`] = m[1];
    }
  });
const testFolders = ['tests/Drupal/Nightwatch/Tests'].concat(Object.keys(folders));

module.exports = {
  src_folders: testFolders,
  output_folder: process.env.DRUPAL_NIGHTWATCH_OUTPUT,
  custom_commands_path: ['tests/Drupal/Nightwatch/Commands'],
  custom_assertions_path: '',
  page_objects_path: '',
  globals_path: 'tests/Drupal/Nightwatch/globals.js',
  selenium: {
    start_process: false,
  },
  test_settings: {
    default: {
      selenium_port: process.env.DRUPAL_TEST_WEBDRIVER_PORT,
      selenium_host: process.env.DRUPAL_TEST_WEBDRIVER_HOSTNAME,
      default_path_prefix: process.env.DRUPAL_TEST_WEBDRIVER_PATH_PREFIX || '',
      desiredCapabilities: {
        browserName: 'chrome',
        acceptSslCerts: true,
        chromeOptions: {
          args: process.env.DRUPAL_TEST_WEBDRIVER_CHROME_ARGS ? process.env.DRUPAL_TEST_WEBDRIVER_CHROME_ARGS.split(' ') : [],
        },
      },
      screenshots: {
        enabled: true,
        on_failure: true,
        on_error: true,
        path: `${process.env.DRUPAL_NIGHTWATCH_OUTPUT}/screenshots`,
      },
      end_session_on_fail : true
    },
  },
};
