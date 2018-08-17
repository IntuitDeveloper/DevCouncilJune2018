# Intuit v4 CakePHP App

This app is to demo some of the use cases provided during the Aug 2018 Focus Group

This app is hard coded with the Bearer Auth token `qbo_auth => 'Bearer: ...'` in the Config/app.php

This app is hard coded with the Global ID `qbo_global_id` in the Config/app.php

Source code is located in `/src/Command/UseCase(#)Step(#)Command.php`

## Usage
clone repo and run `composer install`
To use this demo modify the Config/app.php and add the `qbo_auth` Bearer token and add `qbo_global_id`. All commands are run from a command link

### Use Case 1 - **FINISHED FAILED** 
My app does Vendor management and Billing for SMBs. My customers create and manage all the Bills within my app. Recently I have been getting a lot of request to integrate with QBO. I want to keep my data in sync with QBO. Here's what my flow looks like.
1. **PASS** Read all paid bills with status = PAID (txnTypeFilter = 'bill' && status = 'paid'). `bin/cake use_case1_step1`
2. **PASS** Create a new Bill. Include at least 2 line items and the header details (txnDate and vendor). The response should only contain the id and txnDate. `bin/cake use_case1_step2`
3. **PASS** Create a BillPayment via v3 for that Bill
4. **PASS** Read all Open Bills (status='open') for month of ~~June~~ August and in the same request read for one vendor (header.contact.id=<>). `bin/cake use_case1_step4`
  * *I would expect bills_for_vendor to return all bills and not just open bills*
5. **PASS** Update one of the open bills with the first service item `bin/cake use_case1_step5`
  * Would expect a patch available instead of having to send full data. Service Item seems like I should be using Hours which cannot be added to a EXPENSE
6. **PASS** Create a Purchase Order `bin/cake use_case1_step6`
7. **FAIL** Create a Bill and link to the PO from Step 6 `bin/cake use_case1_step7`
  * *Was able to get the Purchase Order and convert it over to a Bill but unable to link them together very little documentation available*

#### Overall feedback.
* filterBy needs definitions and examples
* with needs definitions and examples
* Used GraphQL Inquirer to figure out elements to request
* Used Insomnia to alter and extend the query
* Used the query in Insomnia and brought it into my php app
* Would be helpful to have defined examples. My uses include storing all the data in the database and limiting my direct queries on the API.

### Use Case 2 - Not Started
I am SMB running a Digital agency. I have 3 employees that often travel to client sites. I have to pay my employee for the travel expense and get it reimbursed from my client. I use a third part application to record expenses and invoices (that is linked to the expense) for my customer. Here's how the flow works:
1. Create a customer via UI/API (v3 API)
2. Create an employee via UI/API (v3 API)
3. Create an expense and mark as Billable via API (v3 API)
4. Read all unapplied v4 Billable expense. (Billable Expense is a non-posting transaction that QBO automatically creates every time an expense is marked as Billable)

### Use Case 3 - Not Started
I am a dogwalker and I invoice my customers on a monthly basis. I use a third party app to help keep track of the # of days/hours worked per client. At the end of the month I manually reconcile the data from the app into QBO, and this takes a lot of time. Instead I would prefer that the app push this data into QBO as a non posting transaction as I enter them.
1. Create customer via UI/API (v3 API)
2. Create a delayed charge/estimate via the v4 API
3. Read the delayed charge (query by id using variables)
4. Read all delayed charges/estimates created in the month of ~~June~~ August (with:"dateRange='custom' && lowDate='2018-08-01' && highDate='2018-08-30'")
5. Read an invoice that has a linked delayed charge
6. Read the delayed charge and read the details

### Use Case 4 - Not started
I support subscription/on demand payment methods for my service. For those on subscription service I would like to invoice my customers every 2 months. I use a CRM tool to manage my customers and ideally I would like to specify which of my customers need a recurring vs a on demand subscription from within this tool.
1. Create a recurring template via the API
2. Read the id of the recurring template
3. Use Recurring invoice via UI

### Use Case 5 - Not started
Tax Calculation

#### Setup - On the QBO UI
1. Go to the Taxes tab. Setup Taxes through the Tax Setup wizard with California as the agency.
2. Navigate to the Sales tab. Create two customers, on marked "Tax Exempt" with the exemption reason set to "Resale" (through the Tax Info. tab).
3. Create 2 items:
  * One with the "Sales Tax Category" set to "Apparel & Clothing" & the field "what you sell" set to "General Clothing"
  * The other with the "Sales Tax Category" set to "Healthcare & Drugs" & the field "what you sell" set to "Prescription Drugs".

#### Use Case - Through v4 API
1. Provide a source and destination address both in California with the "Clothing" item
2. Provide a source and destination address both in California and a combination of "Clothing" and "Healthcare" items.
3. Provide a source address in California and a destination address in Arizona & a combination of "Clothing" and "Healthcare" items.
4. Add Arizona as an additional agency in the company through the UI. Repeat Step 3.
5. Repeat Step 2 but with the Transaction Date earlier than the Nexus Start Date.
6. Repeat Step 2 by sending the Exempt customer id in the request.
