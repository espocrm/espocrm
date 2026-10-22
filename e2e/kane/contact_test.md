---
mode: testing
url: http://localhost:8095/
max_steps: 40
---

# Create a contact for an account

## Log in
Open http://localhost:8095/.
Enter "admin" as the username and "Admin12345" as the password, and click "Log in".
Verify the home page is shown with the "Stream" dashlet, waiting for it to load if needed.

## Create the account
Open http://localhost:8095/#Account/create.
Enter "Globex Inc" as the name and click "Save".
Open http://localhost:8095/#Account.
Verify the accounts list shows "Globex Inc", waiting for it to update if needed.

## Create the contact
Open http://localhost:8095/#Contact/create.
Enter "Jane" as the first name, "Smith" as the last name and "jane.smith@globex.example.com" as the email.
In the "Account" field, type "Globex" and choose "Globex Inc" from the suggestions.
Click "Save".
Verify the contact detail page is shown for "Jane Smith" with the account "Globex Inc" and the email "jane.smith@globex.example.com", waiting for it to load if needed.

## Check the contacts list
Open http://localhost:8095/#Contact.
Verify the contacts list shows "Jane Smith" with the account "Globex Inc", waiting for it to update if needed.
