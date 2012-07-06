This is the frontend GUI used to perform various auditing tasks on data stored 
in MySQL databases.
The interface allows one to browse efficiently records from one or multiple 
tables, filter out records, export filters and save analysis sessions.

Basically, the GUI is nothing more than a SQL query generator with added 
features to ease auditing tasks and address data decoding or performance issues.

GUI and import scripts may be used to analyze ANY table and have features that 
go beyond Active Directory specific tasks. Import scripts are generic and will
import ANY tabulated file to a mysql table and generate column names according 
to the first line of the parsed file.

PLEASE NOTE THE FOLLOWING:
- this was developped as a "proof of concept tool" and is clearly not up to 
coding and quality standards;
- this tool "does its job" and was developped and patched in the heat of the 
moment, so it is clearly not easily extensible as such;
- clean and proper versions are being developped "from scratch" and may be 
released at a later date.

INSTALLATION INSTRUCTIONS
Please refer to INSTALL.txt to set up the tool.

USAGE
Please refer to USAGE.txt to have a summary of how to import data and start 
analyzing ACEs. Also, see FEATURES.txt for a summary of use cases and features 
of the GUI.

ACTIVE DIRECTORY SECURITY ASSESSMENT
Details on methodologies, procedures and "what to look for" during a security 
audit of Active Directory permissions are discussed in our paper, available here
(in french only):
https://www.sstic.org/2012/presentation/audit_ace_active_directory/


