# It's Working
It's Working is an open source educational environment where teachers can upload files and students can download them. 
We are currently in public beta, and we are testing with a closed testing group in The Netherlands.
##Features

It's Working is based around groups. A teacher should be assigned one group for each class he/she teaches. Both students and teacher can then view this group from the main page. The main page shows the groups in a collapsing directory structure, not unlike file browsers everyone is familiar with.

The teacher can then upload files, make folders, and delete the files again in this group, also like a common directory structure. There is no limit to the amount and levels of folders that can be made (other than the physical storage space). Teachers can also make rich text files, formatted in html, directly from the browser using markdown.

Teachers can also make projects, where students submit files and teachers can view and grade these files. If they have set a deadline, the submission time will show green – provided the file was submitted in time. If it was not, the submission time will be red instead. 

Groups are created and managed by Admins. Admins can make an unlimited amount of groups, with an unlimited amount of members. 

There is no need to make new accounts on It's Working; it simply uses your existing account database.

##Installation Instructions

0. Install php and any SQL version (preferably mySQL)

1. Copy the content of the repository's "its" directory into the desired web location on your server

2. Configure setup.ini (more information below)

3. Run setup.php by going to http://<your website>/<directory>/setup.php

4. Make sure to prevent unauthorised users from accessing setup.ini, as this file has your SQL account information and password

###Configuring setup.ini

#### The account databases

It's Working does not have an internal account database. Instead, it uses the database that the school already has. It supports all SQL databases (although I only tested MySQL). If you store your passwords in anything that is not an SQL database, such as Active Directory, please consider making a fork or opening an issue.

First you need to configure It's Working to access your own account database. This is done is setup.ini.

It's Working has three account types: Students, Teachers, and Admins. There are two different ways to distinguish between these accounts in the database. 

- Use a different database or each account type

- Use a different table in the same database for each account type

- Have the account type stored in a separate column

It's Working supports all three ways

- If you use three databases, simply fill in the information for each database in the appropriate field (These include server, SQL credentials, Database name, Table name, and the names of the relevant columns)

- If you use three tables in the same database, fill in identical database information (server, SQL credentials, Database name) for all three databases, but use different table information (table name, column names)

- If you use a separate column in one table, fill in identical information for all three databases, but fill in the condition. This will distinguish between the three different account types. The condition must be a valid SQL condition, without where. For example, if you have a separate "type" column where students are identified with "student", simply set $ext1_condition to "type = student". This will accept any valid SQL condition, so if there are multiple qualifying columns operators such as AND and OR are also accepted.

Each database also has an option to configure what account type is stored in what database. If anyone is found in multiple databases, their account type will be set to that of the first database they will be found in.

Also note the setting for $ext1_write. This setting will let It's Working know if it has write access to the database. This variable currently has no consequences, but in future updates, might allow users to change their passwords via It's Working if enabled.

####The internal database

Apart from the external account database there is also an internal database. This is the database in which It's Working stores the information that it needs to work. It is reccommended that you give It's Working its own database with it's own account, for security reasons. This account then needs full access on said database. Note that while all SQL programs should work, only mySQL has been tested. While the account databases only need some simple commands, the chance of something going wrong is higher on the internal database.

####Debug mode

The last setting in setup.ini is debug mode. Debug mode is meant for server administrators to solve issues related to It's Working. Debug mode will:

- Enable PHP error messages

- Show SQl error messages

- Show some information about what's going on in the script, even if everything does actually work

- Prevent files from redirecting back to the main page after their completion

It is recommended to leave this mode off unless there is actually something wrong with the system, as leaving it on while users have access to the site will pose a major security risk.
