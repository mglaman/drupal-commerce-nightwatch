const chromedriver = require('chromedriver');

module.exports = {
  before: (done) => {
    if (!JSON.parse(process.env.CHROME_STANDALONE)) {
      chromedriver.start();
    }
    done();
  },
  after: (done) => {
    if (!JSON.parse(process.env.CHROME_STANDALONE)) {
      chromedriver.stop();
    }
    done();
  },
};
