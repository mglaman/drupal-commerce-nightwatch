module.exports = {
  'Test page': (browser) => {
    let installedDbPrefix = null;
    browser
      .installDrupal('\\Drupal\\TestSite\\TestSiteInstallTestScript', (dbPrefix) => {
        browser
          .relativeURL('/test-page')
          .waitForElementVisible('body', 1000)
          .assert.containsText('body', 'Test page text')
          .uninstallDrupal(dbPrefix)
          .end()
      });
  },
};
