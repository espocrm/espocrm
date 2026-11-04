# End-to-end (e2e) tests for web

Browser end-to-end tests for EspoCRM, authored with [KaneCLI](https://www.testmuai.com/kane-cli/) by TestMu AI. Each test is a plain-English `_test.md` file; KaneCLI runs it in a real Chrome browser and records each step so later runs replay without re-authoring.

| Test | What it covers |
|---|---|
| `account_test.md` | Logging in, creating a customer account with a website, email, type and industry, and checking its detail page and the Accounts list |
| `contact_test.md` | Logging in, creating an account, creating a contact linked to it through the Account field, and checking the contact's detail page and the Contacts list |

They complement the PHPUnit suites in `tests/` by going through the whole app in a browser. Nothing here is picked up by PHPUnit or copied into the Grunt build.

## Run the tests

The tests expect EspoCRM at `http://localhost:8095` on a fresh database, with the admin user `admin` / `Admin12345`. For example, with the official image and MariaDB:

```bash
docker network create espo-e2e
docker run -d --name espo-e2e-db --network espo-e2e \
  -e MARIADB_ROOT_PASSWORD=rootpass -e MARIADB_DATABASE=espocrm \
  -e MARIADB_USER=espocrm -e MARIADB_PASSWORD=espopass mariadb:11
docker run -d --name espo-e2e --network espo-e2e -p 8095:80 \
  -e ESPOCRM_DATABASE_HOST=espo-e2e-db -e ESPOCRM_DATABASE_USER=espocrm \
  -e ESPOCRM_DATABASE_PASSWORD=espopass -e ESPOCRM_ADMIN_USERNAME=admin \
  -e ESPOCRM_ADMIN_PASSWORD=Admin12345 -e ESPOCRM_SITE_URL=http://localhost:8095 \
  espocrm/espocrm:latest
```

Both tests create their own records, so reset the database (or recreate the containers) before running them again.

### With KaneCLI

```bash
npm install -g @testmuai/kane-cli
kane-cli login
kane-cli testmd run e2e/kane/account_test.md --agent
kane-cli testmd run e2e/kane/contact_test.md --agent
```

### With the exported Playwright code

Each test also has an exported Playwright (JavaScript) version under `output-<test>/playwright-javascript-code/`:

```bash
cd e2e/kane/output-contact/playwright-javascript-code
npm install
npx playwright install chromium
node test.js
```

Copy `.env.example` to `.env` and fill in your TestMu AI (LambdaTest) credentials first.

## Editing a test

Edit the step text in the `_test.md` file and run it again with `kane-cli testmd run`. Changed steps (and the steps after them) are re-recorded; unchanged steps replay from `output-<test>/`.
