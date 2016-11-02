# It's Working
It's Working is an open source educational environment where teachers can upload files and students can download them. 
Currently in alpha, public beta will begin soon.

##Installation Instructions
It's Working does not have an internal account database. Instead, it uses the database that the school already has. It supports all SQL databases (although I only tested MySQL). If you store your passwords in anything that is not an SQL database, such as Active Directory, please consider making a fork or opening an issue.

First you need to configure your own database. This is done is setup.ini.

It's Working has three account types: Students, Teachers, and Admins. There are two different ways to distinguish between these accounts in the database. 

- Use a different database for each account type

- Have the account type stored in a separate column

It's Working support both ways

If you use three databases, simply fill in the information for each database in the appropriate field (
