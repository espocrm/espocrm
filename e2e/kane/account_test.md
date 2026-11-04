---
mode: testing
url: http://localhost:8095/
max_steps: 40
---

# Create a customer account

## Log in
Open http://localhost:8095/.
Enter "admin" as the username and "Admin12345" as the password, and click "Log in".
Verify the home page is shown with the "Stream" dashlet, waiting for it to load if needed.

## Create the account
Open http://localhost:8095/#Account/create.
Enter "Acme Corporation" as the name, "https://acme.example.com" as the website and "info@acme.example.com" as the email.
Select "Customer" as the type and "Manufacturing" as the industry.
Click "Save".

## Check the account
Verify the account detail page is shown for "Acme Corporation" with the type "Customer" and the industry "Manufacturing", waiting for it to load if needed.
Open http://localhost:8095/#Account.
Verify the accounts list shows "Acme Corporation", waiting for it to update if needed.
