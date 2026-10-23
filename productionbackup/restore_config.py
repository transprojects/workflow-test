#Backup folder directory
# change_directory = r"\\10.10.10.76\siva test"

#Define the database details
host = 'localhost'
user = 'root'
password = 'root@123'   #"root@123"
database = 'workflow'    #"workflow"

#Config smb client details
# username = "User-01"    #".\\highmax"
# user_pwd = "Root@2323"  #"112233"

wf_username = ".\highmax"
wf_pwd = "112233"
wf_directory = r"\\10.10.10.22\\Backup"

#FTP Details
ftp_host = '68.178.166.2'
ftp_port = 21
ftp_username = 'journals@backup.transforma.in'
ftp_password = 'CVz^zsRTcFL)&]Dv'



# #Backup folder directory
change_directory = r"\\10.10.10.22\Backup"

# #Define the database details
# host = 'localhost'
# user = 'root'
# password = "root@123"
# database = "workflow"

# #Query to get valid folder path in database
# query = "SELECT a_id, stage id FROM inw_dispatch_history WHERE DATE(sent_date) = CURDATE()"

# #Config smb client details
# username = ".\\highmax"
# user_pwd = "112233"