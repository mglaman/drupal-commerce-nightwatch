module.exports = {
  'Test page': (browser) => {
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
