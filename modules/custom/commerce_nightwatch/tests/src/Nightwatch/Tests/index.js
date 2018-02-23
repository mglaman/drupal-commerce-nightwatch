let databasePrefix;

module.exports = {
  '@tags': ['commerce'],
  before: function(browser) {
    browser.installDrupal('\\Drupal\\commerce_nightwatch\\CommerceBraintreeInstall', (dbPrefix) => {
      databasePrefix = dbPrefix;
    })
  },
  after: function(browser, done) {
    browser.uninstallDrupal(databasePrefix);
    done();
  },
  'Test payment form integration': (browser) => {
    browser
      .relativeURL('/product/1')
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'My product');
    browser.click('input[name=op]');
    browser
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'My product added to your cart.')
      .useXpath().click('//a[text()="your cart"]').useCss();
    browser
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Shopping cart');
    browser.end();
  },
};
