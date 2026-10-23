import os, sys
import shutil
import smbclient
import pymysql
from datetime import datetime
from pathlib import PureWindowsPath
 
import smbclient.path
import config
from ftplib import FTP, error_perm

#Establish the connection to the database
conn = pymysql.connect(
    host = config.host, 
    user = config.user, 
    password = config.password, 
    database = config.database
)
cursor = conn.cursor()

#Get the folder path from the database in current date and do not backup files only
cursor.execute(config.query)    #config.query
rows = cursor.fetchall()

if not rows:
    print({"message": "There are no records found in the database to process or backup."})

#Config smb client
smbclient.ClientConfig(username = config.username, password = config.user_pwd)

# Function to log messages
def log_message(messages):
    
    log_file_path = f"{config.change_directory}\logs\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

    with smbclient.open_file(log_file_path, "a") as log_file:
        
        log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
        for message in messages:
            log_file.write(f"{message}\n")
            log_file.write("-----------------------------------------------------------------------------------------\n")
			
def upload_to_ftp(source_directory, ftp_credentials):
    messages = []
    try:
        ftp = FTP()
        ftp.connect(ftp_credentials["host"], ftp_credentials["port"])
        ftp.login(ftp_credentials["username"], ftp_credentials["password"])
        print("Connected to FTP server.")
        messages.append("Connected to FTP server.\n")
        
        source_directory = source_directory.replace("/", "\\")

        source_dir = source_directory.replace("\\", "/")
        
        directories = source_dir.split("/")
        current_path = ""
        for directory in directories:
            #current_path = f"{os.path.join(current_path, directory)}" if current_path else directory
            current_path = f"{current_path}/{directory}" if current_path else directory
            try:
                # os.mkdir(current_path)
                ftp.mkd(current_path)
                print(f"Created directory: {current_path}")
            except error_perm as e:
                if "550" in str(e):
                    print(f"Directory '{current_path}' already exists.")
                else:
                    print(f"Error creating directory '{current_path}': {e}")
            except Exception as e:
                print(f"Unexpected error: {e}")
        
        for root, dirs, files in os.walk(source_directory):
            for dir_name in dirs:
                dir_path = f"{os.path.join(root, dir_name)}"
                dir_path = dir_path.replace("\\", "/")
                # dir_paths = os.path.relpath(dir_path, source_directory)
                # ftp_dir_path = os.path.join(current_path, dir_paths)
                try:
                    # os.mkdir(ftp_dir_path)
                    ftp.mkd(dir_path)
                    # ftp.cwd(dir_path)
                    print(f"Created directory on FTP: {dir_path}")
                    messages.append(f"Created directory on FTP: {dir_path}\n")
                except:
                    print(f"Directory already exists on FTP: {dir_path}")
                    messages.append(f"Directory already exists on FTP: {dir_path}\n")
 
            for file_name in files:
                file_path = f"{os.path.join(root, file_name)}"
                file_paths = file_path.replace("\\", "/")
                # relative_path = os.path.relpath(file_path, source_directory)
                # ftp_file_path = os.path.join(current_path, relative_path) 
                
                try:
                    # shutil.copy(file_path, ftp_file_path)
                    with open(file_path, 'rb') as f:
                        ftp.storbinary(f"STOR {file_paths}", f)
                    print(f"Uploaded file to FTP: {file_paths, file_path}")
                    messages.append(f"Uploaded file to FTP: {file_paths, file_path}\n")
                except Exception as e:
                    print(f"Error uploading {file_paths, file_path}: {e}")
                    messages.append(f"Error uploading {file_paths, file_path}: {e}\n")
                    
        # with open(f"\log_file\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt", "a")as f:
        #     f.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
        #     for mes in messages:
        #         f.write(mes)
 
        ftp.quit()
    except Exception as e:
        print(f"FTP upload failed: {e}")
        messages.append(f"FTP upload failed: {e}")
    log_message(messages)

    return messages
	
mesages = []
for row in rows:

    cursor.execute(f"select work_dir, volume_folder, pub_id from inw_inward_dtl where id = {row[0]}")
    stages = cursor.fetchall()
    
    for stage in stages:
        
        sp = stage[0].split("production/JOURNALS/")
        #os.path.sep
        source_directory1 = os.path.join(stage[0], stage[1], stage[2], row[1])     #E:/New_Workflow_Files/JOURNAL/production/JOURNALS/
        source_directory = source_directory1.replace('E:', 'J:').replace('New_Workflow_Files/', '')
        source_directory = PureWindowsPath(source_directory)
        #print(source_directory,'===--')
        dupli_directory = os.path.join("New_Workflow_Files/JOURNAL/production/JOURNALS/", sp[1], stage[1], stage[2], row[1])     #E:/New_Workflow_Files/JOURNAL/production/JOURNALS/
		
        try:
            # Define the target network path (shared folder on the target machine)
            target_directory = fr"{config.change_directory}"
        except Exception as e:
            print(e)

        # Check if the source directory exists
        if not os.path.exists(source_directory):
            print(f"The source directory {source_directory} does not exist.")
            mesages.append(f"Error: The source directory {source_directory} does not exist.")
            # raise FileNotFoundError(f"The source directory {source_directory} does not exist.")
        
        # Ensure the target directory is accessible
        if not smbclient.path.exists(target_directory):
            mesages.append(f"Error: The target directory {target_directory} is not accessible.")
            raise FileNotFoundError(f"The target directory {target_directory} is not accessible.")

        target_directory = fr"{config.change_directory}/{dupli_directory}"
        if not smbclient.path.exists(target_directory):
            smbclient.makedirs(target_directory)

        # Function to copy files using shutil and smbclient
        def copytree(src, dst):

            if os.path.isdir(src):
                for item in os.listdir(src):
                    # print(item)
                    source_path = os.path.join(src, item)
                    target_path = os.path.join(dst, item)

                    if os.path.isdir(source_path):
                        try:
                            smbclient.makedirs(target_path)  # Create the directory on the SMB share
                            print(f"Created directory: {target_path}")
                            mesages.append(f"Success: Created directory {target_path} Successfully.")
                        except OSError as e:
                            print(f"Error creating directory {target_path}: {e}")
                            mesages.append(f"Error: Unable to create directory {target_path} as it already exists.")

                        copytree(source_path, target_path)  # Recursively copy subdirectories
                    else:
                        if smbclient.path.exists(target_path):
                            print(f"file already present in server.")
                            mesages.append(f"Error: File already present in Backup directoy {target_path}.")
                        else:
                            with open(source_path, 'rb') as fsrc:
                                with smbclient.open_file(target_path, mode='wb') as fdest:
                                    shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                            print(f"Copied file: {src} to {dst}")
                            mesages.append(f"Success: Copied file {src} to {dst}.")
  
            else:
                if smbclient.path.exists(dst):
                    print(f"file already present in server.")
                    mesages.append(f"Error: File already present in Backup directoy {dst}.")
                else:
                    with open(src, 'rb') as fsrc:
                        with smbclient.open_file(dst, mode='wb') as fdest:
                            shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                    print(f"Copied file: {src} to {dst}")
                    mesages.append(f"Success: Copied file {src} to {dst}.")
                return "pp"
            
        cursor.execute("UPDATE inw_dispatch_history SET file_backedup = 2 WHERE a_id = %s AND stage = %s",(row[0], row[1]))
        
        # Copy the entire folder from source to target directory
        copytree(source_directory, target_directory)
		
		# Upload to FTP
        ftp_credentials = {
            "host": "workflow.transformainc.com",
            "port": 21,
            "username": "production@workflow.transformainc.com",
            "password": "N^]%Ehn+C-M1"
        }
		
        upload_to_ftp(source_directory, ftp_credentials)

        cursor.execute("UPDATE inw_dispatch_history SET file_backedup = 1 WHERE a_id = %s AND stage = %s",(row[0], row[1]))
        cursor.execute("UPDATE inw_dispatch_history SET file_backup_deleted = 0 WHERE a_id = %s AND stage = %s",(row[0], row[1]))

log_message(mesages)

conn.commit()
conn.close()

    