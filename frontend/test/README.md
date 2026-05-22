Command to run:

```
npx jasmine-browser-runner runSpecs --config=frontend/test/jasmine-browser.json
```

Starting a server (`http://localhost:8888/`):

```
npx jasmine-browser-runner serve --config=frontend/test/jasmine-browser.json
```

Requires *Chromedriver*.

**Snap Chromium patch (re-apply after `npm install`):** Edit
`node_modules/jasmine-browser-runner/lib/webdriver.js` and add
`'--remote-debugging-pipe'` to the `headlessChrome` args array (replacing any
`--user-data-dir` workaround). This bypasses the `DevToolsActivePort` snap
confinement issue without which chromedriver cannot start a session.

Run `grunt internal` before running tests to build a lib bundle. Then, run `grunt transpile` each time after changes in
source files, before running the tests.

To test a module that is not in the bundle, add it to the `srcFiles` parameter in the `jasmine-browser.json`.
