---
test: ../contact_test.md
status: passed
started: 2026-10-16T14:51:06.992Z
duration_s: 162
session_id: 44dcc23d-4155-4a9d-bf3a-a1e38826f0e0
---

# Create a contact for an account — Result

## Log in ✓ passed (41.9s)
md5: 4787b889ecc161f6525a2d2fcfcfbdcb
Open http://localhost:8095/.
Enter "admin" as the username and "Admin12345" as the password, and click "Log in".
Verify the home page is shown with the "Stream" dashlet, waiting for it to load if needed.

## Create the account ✓ passed (34.5s)
md5: 16be84f6c49db0af9f46e6de63dd57b2
Open http://localhost:8095/#Account/create.
Enter "Globex Inc" as the name and click "Save".
Open http://localhost:8095/#Account.
Verify the accounts list shows "Globex Inc", waiting for it to update if needed.

## Create the contact ✓ passed (59.1s)
md5: 71f89a91e9f206c871dd6549ed5bc8c9
Open http://localhost:8095/#Contact/create.
Enter "Jane" as the first name, "Smith" as the last name and "jane.smith@globex.example.com" as the email.
In the "Account" field, type "Globex" and choose "Globex Inc" from the suggestions.
Click "Save".
Verify the contact detail page is shown for "Jane Smith" with the account "Globex Inc" and the email "jane.smith@globex.example.com", waiting for it to load if needed.

## Check the contacts list ✓ passed (20.6s)
md5: 32f6e7a1d4707bdba5852a20e7aa375b
Open http://localhost:8095/#Contact.
Verify the contacts list shows "Jane Smith" with the account "Globex Inc", waiting for it to update if needed.
