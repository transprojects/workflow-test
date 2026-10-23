#Backup folder directory
# change_directory = r"\\10.10.10.76\siva test"

# #Define the database details
# host = '10.10.10.110'
# user = 'root'
# password = 'Root@123'   #"root@123"
# database = 'tpl_back_up'    #"workflow"

# #Config smb client details
# username = "User-01"    #".\\highmax"
# user_pwd = "Root@2323"  #"112233"




change_directory = r"\\10.10.10.22\\Backup"

#Define the database details
host = '10.10.10.110'
user = 'root'
password = 'root@123'
database = 'workflow'

#Query to get valid folder path in database
query = "SELECT a_id, stage id FROM inw_dispatch_history WHERE DATE(sent_date) = CURDATE() and file_backedup = 0"

#Config smb client details
username = ".\highmax"
user_pwd = "112233"