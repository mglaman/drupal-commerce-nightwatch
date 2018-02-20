module.exports = {
  'Test page': (browser) => {
    browser
      .installDrupal('\\Drupal\\TestSite\\TestSiteInstallTestScript')
      .relativeURL('/test-page')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Test page text')
      .end();
  },
};
