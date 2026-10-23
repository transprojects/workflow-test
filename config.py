#Backup folder directory
change_directory = r"\\10.10.10.22\Backup"

#Define the database details
host = 'localhost'
user = 'root'
password = 'root@123'
database = 'workflow'

#Query to get valid folder path in database
query = "SELECT a_id, stage id FROM inw_dispatch_history WHERE DATE(sent_date) = CURDATE() and file_backedup = 0"

#Config smb client details
username = ".\highmax"
user_pwd = "112233"


# #Backup folder directory
# change_directory = r"\\10.10.10.76\siva test"   #\\10.10.10.110\Backup

# #Define the database details
# host = '10.10.10.110'
# user = 'root'
# password = 'root@123'
# database = 'workflow'

# #Query to get valid folder path in database
# query = "SELECT work_dir, volume_folder FROM inw_inward_dtl WHERE DATE(created_dt) = CURDATE()"

# #Config smb client details
# username = "User-01"
# user_pwd = "Root@4321"
