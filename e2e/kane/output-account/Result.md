---
test: ../account_test.md
status: passed
started: 2026-10-16T14:26:16.045Z
duration_s: 144
session_id: b5f8333d-e31a-4ab3-9506-5e7e639e8068
---

# Create a customer account — Result

## Log in ✓ passed (6.91s)
md5: 4787b889ecc161f6525a2d2fcfcfbdcb
Open http://localhost:8095/.
Enter "admin" as the username and "Admin12345" as the password, and click "Log in".
Verify the home page is shown with the "Stream" dashlet, waiting for it to load if needed.

## Create the account ✓ passed (57.6s)
md5: f8f48514cb7469f5c34df50bf690f88a
Open http://localhost:8095/#Account/create.
Enter "Acme Corporation" as the name, "https://acme.example.com" as the website and "info@acme.example.com" as the email.
Select "Customer" as the type and "Manufacturing" as the industry.
Click "Save".

## Check the account ✓ passed (72.6s)
md5: 23a0d9a218fb8ff4580ff590c6ea5909
Verify the account detail page is shown for "Acme Corporation" with the type "Customer" and the industry "Manufacturing", waiting for it to load if needed.
Open http://localhost:8095/#Account.
Verify the accounts list shows "Acme Corporation", waiting for it to update if needed.
