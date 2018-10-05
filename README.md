Zen Cart 360 Sales Reporting
============================

For many Zen Cart users, the Best Customers report (Admin->Reports->Customer Orders-Total) gives sufficient data, especially when my [Lifetime Customer Value modification](https://github.com/zencart/zencart/pull/1802) is added. 

However, it does not handle the following situations: 
* Transactions which occur outside of the store (direct bank transfers and sales prior to the store being set up).
* Business Accounts which have multiple payers: if john@bigcorp.com and judy@bigcorp.com both make payments, it's not immediately clear what the total value of the BigCorp account is. 

360 Sales Reporting addresses both of these needs in the following way: 
* Customer records are amended with a new field to identify a master customer id.  In this way, customers can be grouped. 
* Two tables are provided to handle both direct deposits by customers and customer transactions which occur outside the store. 

## Usage
Once 360 Sales Reporting is installed, entries on the Best Customers report change from this: 

![old format](https://www.thatsoftwareguy.com/img/github_images/stats_customers_old.png)

to this: 

![new format](https://www.thatsoftwareguy.com/img/github_images/stats_customers_new.png)

(The customer_id, company name and email address are added.)

The customers sidebar, as shown on the Admin->Customers->Customers page, is also modified from this: 

![old format](https://www.thatsoftwareguy.com/img/github_images/old_customers_sidebox.png)

to this: 
 
![new format](https://www.thatsoftwareguy.com/img/github_images/new_customers_sidebox.png)

The lifetime value is shown from the cart data for this customer, but also the Master Lifetime Value, which is the sum of: 

* sales to this customer
* sales to other customers where this customer is the master account
* direct deposits by this customer
* sales to this customer prior to the store being set up
* deposits and pre-store sales by other email addresses which are linked to this email address. 

## Installation

1. Run the SQL files in the "sql" directory from Admin->Tools->Install SQL Patches. 

1. Set the master account where needed for customers you wish to group together.  For example, if customer 288 is the master account for customer 276, use the command
`UPDATE customers SET master_account = 288 where customers_id = 276; `

3. Install the files in the `new_files` directory 

4. Merge the files in the `updated_core_files` directory. 

5. If needed, import data to the `direct_deposit` and `pre_store` tables. 

## Importing Data from PayPal 
Here are some recommendations for importing data from a PayPal export to import into the pre_store table:  

- Clean the data as follows to remove unwanted rows as follows:  Sort the data by the following fields 
    - total, to find and remove remove withdrawls
    - status, to find and remove refunds
    - type, to find and remove currency conversions 

- remove all columns except Date, Name, Email, Gross
- format Date as YYYY-MM-DD.
- format Gross as number no comma
- remove header, strip commas. 

You will then be able to import the resultant file directly to the 
pre_store table.  When importing Excel data to the pre_store table, 
follow these steps: 
- Be sure the column order of your data matches the column order
of the target table. 
- From Excel, use File->Export->Change File Type, and set the file type
to CSV, and save the file.
- Edit the resulting .csv file with a text editor like Notepad, and
prepend a `0,` to each row of your data.
- In PHPMyAdmin, click import, select file
- Set format to csv, set columns enclosed with to blank
- Check "Update data when duplicate keys found on import" 

