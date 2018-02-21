module.exports = {
  src_folders: ['tests/Drupal/Nightwatch/Tests'],
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
      selenium_port: process.env.DRUPAL_NIGHTWATCH_WEBDRIVER_PORT,
      selenium_host: process.env.DRUPAL_NIGHTWATCH_WEBDRIVER_HOSTNAME,
      default_path_prefix: process.env.DRUPAL_NIGHTWATCH_WEBDRIVER_PATH_PREFIX || '',
      desiredCapabilities: {
        browserName: 'chrome',
        acceptSslCerts: true,
        chromeOptions: {
          args: process.env.DRUPAL_NIGHTWATCH_CHROME_ARGS ? process.env.DRUPAL_NIGHTWATCH_CHROME_ARGS.split(' ') : [],
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
