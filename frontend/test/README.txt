Command to run:

```
npx jasmine-browser-runner runSpecs --config=frontend/test/jasmine-browser.json
```

Requires a chromedriver.

Run `grunt client` before running tests to build a source bundle.

To test a module that is not in the bundle add it to the `srcFiles` parameter in the `jasmine-browser.json`.
