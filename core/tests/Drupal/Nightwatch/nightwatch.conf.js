module.exports = {
  src_folders: ['tests/Drupal/Nightwatch/Tests'],
  output_folder: process.env.NIGHTWATCH_OUTPUT,
  custom_commands_path: ['tests/Drupal/Nightwatch/Commands'],
  custom_assertions_path: '',
  page_objects_path: '',
  globals_path: 'tests/Drupal/Nightwatch/globals.js',
  selenium: {
    start_process: false,
  },
  test_settings: {
    default: {
      selenium_port: 9515,
      selenium_host: process.env.WEBDRIVER_HOSTNAME,
      default_path_prefix: '',
      desiredCapabilities: {
        browserName: 'chrome',
        acceptSslCerts: true,
        chromeOptions: {
          args: process.env.CHROME_ARGS.split(' '),
        },
      },
      screenshots: {
        enabled: true,
        on_failure: true,
        on_error: true,
        path: `${process.env.NIGHTWATCH_OUTPUT}/screenshots`,
      },
    },
  },
};
