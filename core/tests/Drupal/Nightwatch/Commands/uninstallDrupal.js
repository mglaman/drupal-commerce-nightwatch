exports.command = function uninstallDrupal(dbPrefix = '', callback) {
  const self = this;
  console.log(`Uninstalling ${dbPrefix}`);

  // Nightwatch doesn't like it when no actions are added in command file.
  this.pause(200);

  if (typeof callback === 'function') {
    callback.call(self);
  }
  return this;
};
