import testmu, { resolveVar, setVar } from '@testmuai/playwright-bindings';
import { expect } from '@playwright/test';

testmu.configure({
  "build": "f5c42277-391a-4c97-bf6b-11143cf496ac",
  "name": "Create and Verify New Account",
  "tcId": "TC-206",
  "network": (process.env.NETWORK ?? "false").toLowerCase() === "true",
  "autoHealVersion": "AH2",
  "defaultActionTimeoutMs": 10000,
  "defaultNavigationTimeoutMs": 60000,
  "kaneRunV4": true
});

testmu.test("Create and Verify New Account", async (page) => {
  let element_0, element_1, element_7, element_2, element_3, element_4, element_5, element_12, element_6, element_14, element_15, element_16;
  await testmu.step("Navigate to http://localhost:8095/", { instructionId: "aebc7e4c-c3cc-4ad6-b0ca-b787c0e018a6" }, async () => {
    await page.goto("http://localhost:8095/");
  });
  
  await testmu.step("Waiting for queued actions to complete", { instructionId: "89f92dd4-bdec-4047-a795-4f5e868bd1d7" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"admin\" into \"Username\"", { instructionId: "2b35acf8-d779-4bfb-ae81-6b34965be6e6" }, async () => {
    element_0 = page.locator("internal:role=textbox[name=\"Username\"i]");
    await element_0.click();
    await element_0.fill("admin");
  });
  
  await testmu.step("Waiting for the plan to proceed with entering the Password and logging in", { instructionId: "c403d273-09ba-4ce8-90d5-f97f363bffaf" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Admin12345\" into \"Password\"", { instructionId: "6dcae9b4-6c95-4ba5-a100-867ad6515cb0" }, async () => {
    element_1 = page.locator("internal:role=textbox[name=\"Password\"i]");
    await element_1.click();
    await element_1.fill("Admin12345");
  });
  
  await testmu.step("Clicking Log in submit button below password input", { instructionId: "f75b7f1e-21ec-4789-b846-fb4b6a33a941" }, async () => {
    element_7 = page.locator("internal:role=button[name=\"Log in\"i]");
    await element_7.click();
  });
  
  await testmu.step("Waiting for the home page to finish loading", { instructionId: "47597b03-1d1a-4e2a-b8e5-839f25e1ebc4" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Checking whether the Stream dashlet is present", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("home_page_stream_dashlet", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {57: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(57).textContent.trim() === 'Stream');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"Stream\"i]"], query: "whether the Stream dashlet is present on the home page", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The home page is shown with the \"Stream\" dashlet." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "dd1d6fb7-a342-445f-b1d7-dfc23a6eb928" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The home page is shown with the \"Stream\" dashlet.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{home_page_stream_dashlet}}", "store_key": "home_page_stream_dashlet", "variable_refs": {"{{home_page_stream_dashlet}}": "true"}}], "sub_checks": [{"description": "The home page is shown with the \"Stream\" dashlet.", "store_key": "home_page_stream_dashlet", "expected_value": "true", "extracted_value": "{{home_page_stream_dashlet}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "Verify the home page is shown with the \"Stream\" dashlet, waiting for it to load if needed."});
  });
  
  await testmu.step("Navigate to http://localhost:8095/#Account/create", { instructionId: "5c81cae3-fbb9-4d44-9b86-736fa30ad29b" }, async () => {
    await page.goto("http://localhost:8095/#Account/create");
  });
  
  await testmu.step("Waiting for the queued plan to run", { instructionId: "93575854-c17a-4ce3-8315-00615f1fc8a6" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Acme Corporation\" into \"textbox\"", { instructionId: "3d56fd2c-6c2a-4e69-9023-e5c5f9df82f8" }, async () => {
    element_2 = page.locator("internal:role=textbox >> nth=0");
    await element_2.click();
    await element_2.fill("Acme Corporation");
  });
  
  await testmu.step("Type \"https://acme.example.com\" into \"textbox\"", { instructionId: "a7f91381-445f-4c2b-8b97-9b4b264109d5" }, async () => {
    element_3 = page.locator("internal:role=textbox >> nth=1");
    await element_3.click();
    await element_3.fill("https://acme.example.com");
  });
  
  await testmu.step("Type \"info@acme.example.com\" into \"textbox\"", { instructionId: "84a3ebde-1570-47b9-ac26-7add26b3b95b" }, async () => {
    element_4 = page.locator("input[type=\"email\"]");
    await element_4.click();
    await element_4.fill("info@acme.example.com");
  });
  
  await testmu.step("Type \"Cust\" into \"textbox\"", { instructionId: "7901f50e-3d1f-4230-901c-6717e2438846" }, async () => {
    element_5 = page.locator(".selectize-control.form-control.main-element > .selectize-input > input >> nth=0");
    await element_5.click();
    await element_5.fill("Cust");
  });
  
  await testmu.step("Clicking the Customer option in the account type dropdown", { instructionId: "e987f081-5d1c-41ec-b673-d8e1a90e947b" }, async () => {
    element_12 = page.locator("div >> internal:has-text=/^Customer$/ >> nth=1");
    await element_12.click();
  });
  
  await testmu.step("Typing Manu in industry field", { instructionId: "1574519b-23ce-49a8-b25c-8c5fd70c1803" }, async () => {
    element_6 = page.locator("div:nth-child(2) > .field > .selectize-control > .selectize-input > input");
    await element_6.click();
    await element_6.fill("Manu");
  });
  
  await testmu.step("Clicking Manufacturing in the industry dropdown", { instructionId: "7091fc22-94f9-41b3-a667-6884d24aca92" }, async () => {
    element_14 = page.locator("div >> internal:has-text=/^Manufacturing$/ >> nth=1");
    await element_14.click();
  });
  
  await testmu.step("Clicking Save account button", { instructionId: "061c1b82-6901-4218-851d-07bcd47d2cba" }, async () => {
    element_15 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_15.click();
  });
  
  await testmu.step("Navigate to http://localhost:8095/#Account", { instructionId: "c51f5920-5cdc-49f0-b5d8-e40399b8e75a" }, async () => {
    await page.goto("http://localhost:8095/#Account");
  });
  
  await testmu.step("Clicking Acme Corporation account link in Accounts list", { instructionId: "3cb21094-153d-41d6-bdd3-3b49a01614dd" }, async () => {
    element_16 = page.locator("internal:role=link[name=\"Acme Corporation\"i]");
    await element_16.click();
  });
  
  await testmu.step("Waiting 800 ms for the Acme Corporation account details to load", { instructionId: "85827e84-b9c4-4bc9-b85f-48578db8e440" }, async () => {
    await page.waitForTimeout(800);
  });
  
  await testmu.step("Reading the account detail page title", { instructionId: "6f4fd653-9ce3-468d-989e-67616b3048d9" }, async () => {
    setVar("account_detail_page_check", await page.title());
  });
  
  await testmu.step("Reading the account type from the account details", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("account_type_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {60: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(60).querySelector('[data-name=\"type\"] .text-default')?.textContent.trim() ?? null);\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["#account-detail-5796"], query: "the account type in the account details", expectedValue: "Customer", needsUnitConversion: false, operator: "equals", transforms: ["strip"], condition: "The account type is \"Customer\"." }));
  });
  
  await testmu.step("Reading the account industry from the account details", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("account_industry_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {60: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(60).querySelector('[data-name=\"industry\"] .text-default')?.textContent.trim() ?? null);\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["#account-detail-5796"], query: "the account industry in the account details", expectedValue: "Manufacturing", needsUnitConversion: false, operator: "equals", transforms: ["strip"], condition: "The account industry is \"Manufacturing\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "1de62624-ad28-49ca-8916-7ee200f4fb92" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The account detail page is shown for \"Acme Corporation\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "Acme Corporation", "extracted_value": "{{account_detail_page_check}}", "store_key": "account_detail_page_check", "variable_refs": {"{{account_detail_page_check}}": "Acme Corporation"}}, {"description": "The account type is \"Customer\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "Customer", "extracted_value": "{{account_type_check}}", "store_key": "account_type_check", "variable_refs": {"{{account_type_check}}": "Customer"}}, {"description": "The account industry is \"Manufacturing\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "Manufacturing", "extracted_value": "{{account_industry_check}}", "store_key": "account_industry_check", "variable_refs": {"{{account_industry_check}}": "Manufacturing"}}], "sub_checks": [{"description": "The account detail page is shown for \"Acme Corporation\".", "store_key": "account_detail_page_check", "expected_value": "Acme Corporation", "extracted_value": "{{account_detail_page_check}}", "operator": "equals", "transforms": ["strip"]}, {"description": "The account type is \"Customer\".", "store_key": "account_type_check", "expected_value": "Customer", "extracted_value": "{{account_type_check}}", "operator": "equals", "transforms": ["strip"]}, {"description": "The account industry is \"Manufacturing\".", "store_key": "account_industry_check", "expected_value": "Manufacturing", "extracted_value": "{{account_industry_check}}", "operator": "equals", "transforms": ["strip"]}], "composite_operator": "and", "claim": "the account detail page is shown for \"Acme Corporation\" with the type \"Customer\" and the industry \"Manufacturing\""});
  });
  
  await testmu.step("Navigating to the Account page in the browser", { instructionId: "bc62b648-cf11-4097-854e-d7028da7d84e" }, async () => {
    await page.goto("http://localhost:8095/#Account");
  });
  
  await testmu.step("Waiting 800 ms for the Accounts list to finish loading", { instructionId: "5a8e7229-58cf-4c55-9d39-ca982223d871" }, async () => {
    await page.waitForTimeout(800);
  });
  
  await testmu.step("Checking for Acme Corporation in the accounts list", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("accounts_list_acme_corporation_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {85: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(85).textContent.trim() === 'Acme Corporation');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=cell[name=\"Acme Corporation\"i]"], query: "whether the accounts list includes the Acme Corporation account", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The accounts list shows \"Acme Corporation\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "fce2f011-71ca-4d8d-9b8b-3bcd3ad96aad" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The accounts list shows \"Acme Corporation\".", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{accounts_list_acme_corporation_check}}", "store_key": "accounts_list_acme_corporation_check", "variable_refs": {"{{accounts_list_acme_corporation_check}}": "true"}}], "sub_checks": [{"description": "The accounts list shows \"Acme Corporation\".", "store_key": "accounts_list_acme_corporation_check", "expected_value": "true", "extracted_value": "{{accounts_list_acme_corporation_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the accounts list shows \"Acme Corporation\""});
  });
});

testmu.run();