let databasePrefix;

module.exports = {
  before : function(browser) {
    browser.installDrupal('\\Drupal\\TestSite\\TestSiteInstallTestScript', (dbPrefix) => {
      databasePrefix = dbPrefix;
    })
  },
  after : function(browser, done) {
    browser.uninstallDrupal(databasePrefix);
    done();
  },
  'Test page': (browser) => {
    browser
      .relativeURL('/test-page')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Test page text')
      .end();
  },
};
