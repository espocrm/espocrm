Command to run:

```
npx jasmine-browser-runner runSpecs --config=frontend/test/jasmine-browser.json
```

Starting a server (`http://localhost:8888/`):

```
npx jasmine-browser-runner serve --config=frontend/test/jasmine-browser.json
```

Requires *Chromedriver*.

Run `grunt internal` before running tests to build a lib bundle. Then, run `grunt transpile` each time after changes in
source files, before running the tests.

To test a module that is not in the bundle, add it to the `srcFiles` parameter in the `jasmine-browser.json`.
