import testmu, { resolveVar, setVar } from '@testmuai/playwright-bindings';
import { expect } from '@playwright/test';

testmu.configure({
  "build": "5dea5ed6-4ed4-4347-aaff-dce1ebfc21a8",
  "name": "Create Account and Contact",
  "tcId": "TC-209",
  "network": (process.env.NETWORK ?? "false").toLowerCase() === "true",
  "autoHealVersion": "AH2",
  "defaultActionTimeoutMs": 10000,
  "defaultNavigationTimeoutMs": 60000,
  "kaneRunV4": true
});

testmu.test("Create Account and Contact", async (page) => {
  let element_0, element_1, element_7, element_2, element_9, element_3, element_4, element_5, element_6, element_14, element_15;
  await testmu.step("Navigate to http://localhost:8095/", { instructionId: "48db4a8f-040c-4954-8f4b-bd621cf73f0d" }, async () => {
    await page.goto("http://localhost:8095/");
  });
  
  await testmu.step("Waiting while logging in using the queued plan", { instructionId: "f29062f0-617a-4df6-93cc-57094cef43d1" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"admin\" into \"Username\"", { instructionId: "5f3c9eb5-dff5-438e-a3ff-6c2f50f2fcee" }, async () => {
    element_0 = page.locator("internal:role=textbox[name=\"Username\"i]");
    await element_0.click();
    await element_0.fill("admin");
  });
  
  await testmu.step("Waiting for the next queued action to run", { instructionId: "fd9f66bf-841c-45de-802a-00b3a27aae87" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Admin12345\" into \"Password\"", { instructionId: "8057eb9e-7461-4a6a-acc1-45ba5210afb0" }, async () => {
    element_1 = page.locator("internal:role=textbox[name=\"Password\"i]");
    await element_1.click();
    await element_1.fill("Admin12345");
  });
  
  await testmu.step("Clicking the Log in button in the login form", { instructionId: "cbe6fcd8-0a50-4b77-8b3e-4bf6ca87cf08" }, async () => {
    element_7 = page.locator("internal:role=button[name=\"Log in\"i]");
    await element_7.click();
  });
  
  await testmu.step("Waiting 1000 ms for the home page to finish loading", { instructionId: "49be2d47-994d-4185-bcae-e8bae273c7ce" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Checking whether the Stream dashlet is present", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("home_page_stream_dashlet_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {57: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(57).textContent.trim() === 'Stream');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"Stream\"i]"], query: "whether the Stream dashlet is present on the home page", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The home page is shown with the \"Stream\" dashlet." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "6893264e-6b06-4dff-bd47-73cd9c13f297" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The home page is shown with the \"Stream\" dashlet.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{home_page_stream_dashlet_check}}", "store_key": "home_page_stream_dashlet_check", "variable_refs": {"{{home_page_stream_dashlet_check}}": "true"}}], "sub_checks": [{"description": "The home page is shown with the \"Stream\" dashlet.", "store_key": "home_page_stream_dashlet_check", "expected_value": "true", "extracted_value": "{{home_page_stream_dashlet_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the home page is shown with the \"Stream\" dashlet"});
  });
  
  await testmu.step("Navigate to http://localhost:8095/#Account/create", { instructionId: "e4d8d580-bfac-41fe-bad4-c60b65ca2289" }, async () => {
    await page.goto("http://localhost:8095/#Account/create");
  });
  
  await testmu.step("Waiting for queued plan to complete", { instructionId: "3ee38d0c-92c2-4cda-92af-8433ede6081f" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Globex Inc\" into \"textbox\"", { instructionId: "01a9e051-00aa-461a-bacc-fdaf9cc9ef83" }, async () => {
    element_2 = page.locator("internal:role=textbox >> nth=0");
    await element_2.click();
    await element_2.fill("Globex Inc");
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "6d99bb88-7c30-4d75-a022-4b3a6aa577f8" }, async () => {
    element_9 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_9.click();
  });
  
  await testmu.step("Navigating to the Account page", { instructionId: "a1b18c53-2b92-4056-83e5-c38ad631d0e4" }, async () => {
    await page.goto("http://localhost:8095/#Account");
  });
  
  await testmu.step("Checking for Globex Inc in the accounts list", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("accounts_list_globex_inc_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {85: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(85).textContent.trim() === 'Globex Inc');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=cell[name=\"Globex Inc\"i]"], query: "whether the accounts list includes the Globex Inc account", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "the accounts list shows \"Globex Inc\"" }));
  });
  
  await testmu.step("Assertion check", { instructionId: "5ee27597-91bf-4ef7-94c0-41fb29657637" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "the accounts list shows \"Globex Inc\"", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{accounts_list_globex_inc_check}}", "store_key": "accounts_list_globex_inc_check", "variable_refs": {"{{accounts_list_globex_inc_check}}": "true"}}], "sub_checks": [{"description": "the accounts list shows \"Globex Inc\"", "store_key": "accounts_list_globex_inc_check", "expected_value": "true", "extracted_value": "{{accounts_list_globex_inc_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the accounts list shows \"Globex Inc\""});
  });
  
  await testmu.step("Navigate to http://localhost:8095/#Contact/create", { instructionId: "7dac9da7-e1f4-4302-bce4-909dd7c610dc" }, async () => {
    await page.goto("http://localhost:8095/#Contact/create");
  });
  
  await testmu.step("Waiting for the queued plan to run", { instructionId: "6cd3e464-6fbd-48ea-8b80-acfcdea6cb8c" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Jane\" into \"First Name\"", { instructionId: "f68763b0-daa5-41ff-a924-7c7281138e1e" }, async () => {
    element_3 = page.locator("internal:role=textbox[name=\"First Name\"i]");
    await element_3.click();
    await element_3.fill("Jane");
  });
  
  await testmu.step("Type \"Smith\" into \"Last Name\"", { instructionId: "949397dc-8ab7-44f8-a11c-33523f4151d0" }, async () => {
    element_4 = page.locator("internal:role=textbox[name=\"Last Name\"i]");
    await element_4.click();
    await element_4.fill("Smith");
  });
  
  await testmu.step("Type \"jane.smith@globex.example.com\" into \"textbox\"", { instructionId: "4a5e0803-f9f9-4c81-8f23-cbe68a3ef1d9" }, async () => {
    element_5 = page.locator("input[type=\"email\"]");
    await element_5.click();
    await element_5.fill("jane.smith@globex.example.com");
  });
  
  await testmu.step("Typing Glob in the account field", { instructionId: "fc22cce1-d55f-490b-8f54-962b52893771" }, async () => {
    element_6 = page.locator("internal:role=textbox[name=\"Select\"i] >> nth=0");
    await element_6.click();
    await element_6.fill("Glob");
  });
  
  await testmu.step("Clicking Globex Inc in account suggestions", { instructionId: "86179965-36dc-418f-956f-d9cb7115d2b8" }, async () => {
    element_14 = page.locator("div >> internal:has-text=\"Globex Inc\"i >> nth=0");
    await element_14.click();
  });
  
  await testmu.step("Clicking Save contact button", { instructionId: "a87d1d33-2a16-4d40-99ac-4db1dbd61be5" }, async () => {
    element_15 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_15.click();
  });
  
  await testmu.step("Waiting 800 ms for the saved contact detail page to finish loading", { instructionId: "7e565ad3-3613-403e-9aef-c064d675c6d4" }, async () => {
    await page.waitForTimeout(800);
  });
  
  await testmu.step("Extracting the contact name from the page title", { instructionId: "43cf0920-e762-46c6-ade2-1c8b22cfe1e4" }, async () => {
    setVar("contact_name_check", await page.title());
  });
  
  await testmu.step("Reading the contact account from the detail page", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("contact_account_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {69: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(69).textContent);\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=link[name=\"Globex Inc\"i]"], query: "the account name shown in the contact details", expectedValue: "Globex Inc", needsUnitConversion: false, operator: "equals", transforms: ["strip"], condition: "The contact detail page shows the account \"Globex Inc\"." }));
  });
  
  await testmu.step("Reading the contact email from the detail page", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("contact_email_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {72: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(72).textContent);\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=button[name=\"jane.smith@globex.example.com\"i]"], query: "the email address shown in the contact details", expectedValue: "jane.smith@globex.example.com", needsUnitConversion: false, operator: "equals", transforms: ["strip"], condition: "The contact detail page shows the email \"jane.smith@globex.example.com\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "440a9727-1552-4a52-9070-0f275a1c1608" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The contact detail page is shown for \"Jane Smith\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "Jane Smith", "extracted_value": "{{contact_name_check}}", "store_key": "contact_name_check", "variable_refs": {"{{contact_name_check}}": "Jane Smith"}}, {"description": "The contact detail page shows the account \"Globex Inc\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "Globex Inc", "extracted_value": "{{contact_account_check}}", "store_key": "contact_account_check", "variable_refs": {"{{contact_account_check}}": "Globex Inc"}}, {"description": "The contact detail page shows the email \"jane.smith@globex.example.com\".", "passed": true, "operator": "equals", "transforms": ["strip"], "json_path": null, "expected": "jane.smith@globex.example.com", "extracted_value": "{{contact_email_check}}", "store_key": "contact_email_check", "variable_refs": {"{{contact_email_check}}": "\n            jane.smith@globex.example.com\n            "}}], "sub_checks": [{"description": "The contact detail page is shown for \"Jane Smith\".", "store_key": "contact_name_check", "expected_value": "Jane Smith", "extracted_value": "{{contact_name_check}}", "operator": "equals", "transforms": ["strip"]}, {"description": "The contact detail page shows the account \"Globex Inc\".", "store_key": "contact_account_check", "expected_value": "Globex Inc", "extracted_value": "{{contact_account_check}}", "operator": "equals", "transforms": ["strip"]}, {"description": "The contact detail page shows the email \"jane.smith@globex.example.com\".", "store_key": "contact_email_check", "expected_value": "jane.smith@globex.example.com", "extracted_value": "{{contact_email_check}}", "operator": "equals", "transforms": ["strip"]}], "composite_operator": "and", "claim": "the contact detail page is shown for \"Jane Smith\" with the account \"Globex Inc\" and the email \"jane.smith@globex.example.com\""});
  });
  
  await testmu.step("Navigate to http://localhost:8095/#Contact", { instructionId: "48ad60ff-9786-41ec-86d7-969eb326cd77" }, async () => {
    await page.goto("http://localhost:8095/#Contact");
  });
  
  await testmu.step("Checking Jane Smith's account in the contacts list", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("jane_smith_globex_inc_contact_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {81: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (Array.from(el(81).querySelectorAll('a')).some(a => a.textContent.trim() === 'Jane Smith') && Array.from(el(81).querySelectorAll('a')).some(a => a.textContent.trim() === 'Globex Inc'));\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=row[name=\"Jane Smith \uf0c8\u00a0Globex Inc jane.\"i]"], query: "whether the contacts list row contains the contact Jane Smith with the account Globex Inc", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The contacts list shows \"Jane Smith\" with the account \"Globex Inc\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "3fc755c2-c220-401c-9c4c-2f617d2b7465" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The contacts list shows \"Jane Smith\" with the account \"Globex Inc\".", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{jane_smith_globex_inc_contact_check}}", "store_key": "jane_smith_globex_inc_contact_check", "variable_refs": {"{{jane_smith_globex_inc_contact_check}}": "true"}}], "sub_checks": [{"description": "The contacts list shows \"Jane Smith\" with the account \"Globex Inc\".", "store_key": "jane_smith_globex_inc_contact_check", "expected_value": "true", "extracted_value": "{{jane_smith_globex_inc_contact_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the contacts list shows \"Jane Smith\" with the account \"Globex Inc\""});
  });
});

testmu.run();