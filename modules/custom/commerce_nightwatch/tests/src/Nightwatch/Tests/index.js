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
      .assert.containsText('body', 'My product')
      .useCss().click('input[name=op]');
    browser
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'My product added to your cart.')
      .useXpath().click('//a[text()="your cart"]');
    browser
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Shopping cart')
      .useCss().click('input[name=op]');
    browser
      .waitForElementVisible('body', 1000)
      .assert.containsText('body', 'Returning Customer')
      .assert.containsText('body', 'Guest Checkout')
      .assert.containsText('body', 'Proceed to checkout. You can optionally create an account at the end.')
      .useCss().click('input[value="Continue as Guest"]');

    browser.setValue('input[name="contact_information[email]"]', 'myemail@example.com');
    browser.setValue('input[name="contact_information[email_confirm]"]', 'myemail@example.com');
    browser.setValue('input[name="billing_information[profile][address][0][address][given_name]"]', 'Johnny');
    browser.setValue('input[name="billing_information[profile][address][0][address][family_name]"]', 'Maple Bacon');
    browser.setValue('input[name="billing_information[profile][address][0][address][address_line1]"]', '2334 Breakfast Ave');
    browser.setValue('input[name="billing_information[profile][address][0][address][postal_code]"]', '94043');
    browser.setValue('input[name="billing_information[profile][address][0][address][locality]"]', 'Mountain View');
    browser.setValue('input[name="billing_information[profile][address][0][address][administrative_area]"]', 'CA');

    // Do iframe switching.
    browser.frame('braintree-hosted-field-number');

    browser.end();
  },
};
