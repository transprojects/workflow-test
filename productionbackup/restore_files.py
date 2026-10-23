import shutil
import smbclient
import pymysql
import smbclient.path
import restore_config
from datetime import datetime
import os, sys, subprocess
from ftplib import FTP, error_perm
from pathlib import PureWindowsPath
  
#Establish the connection to the database
conn = pymysql.connect(
    host = restore_config.host,
    user = restore_config.user,
    password = restore_config.password,
    database = restore_config.database
)
cursor = conn.cursor()
 
#MARK: Log Message
#Function to log messages
def log_message(messages):
    try:
        #config_dep smb client
        smbclient.ClientConfig(username = restore_config.wf_username, password = restore_config.wf_pwd)
        
        log_file_path = f"{restore_config.change_directory}\logs\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

        #Create log directory if not exitsts in the smbclient
        if not smbclient.path.exists(f"{restore_config.change_directory}\logs"):
            smbclient.makedirs(f"{restore_config.change_directory}\logs")
    
        with smbclient.open_file(log_file_path, "a") as log_file:
            
            log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
            for message in messages:
                log_file.write(f"{message}\n")
                log_file.write("-----------------------------------------------------------------------------------------\n")
    except Exception as e:
        print(f"Error in log message function. {e}")
        pass

#Function to check if an entry is a directory
def is_directory(ftp, name):
    current = ftp.pwd()
    try:
        ftp.cwd(name)  #Try changing to the directory
        ftp.cwd(current)  #Go back if successful
        return True
    except:
        return False

#Function to copy files and folders recursively
def copy_and_overwrite(ftp, src, dst, message):
    global global_path
    try:
        
        ftp.cwd(src)  #Change to the remote directory
        
        smbclient.ClientConfig(username = restore_config.wf_username, password = restore_config.wf_pwd)
        
        items = ftp.nlst()
        print(items)
        # sys.exit()
        for index, item in enumerate(items):
            # Skip current and parent directory (CRITICAL)
            if item in (".", ".."):
                continue
            remote_path = f"{ftp.pwd()}/{item}"
            local_path = os.path.join(dst, item)
            print(local_path, remote_path)
            
            if item == "New_Workflow_Files":
                local_path = os.path.join(dst, item + "_restore")
            
            if is_directory(ftp, item):  #If it's a directory, recurse
                #Ensure the local destination exists
                if not smbclient.path.exists(local_path):
                    smbclient.makedirs(local_path)
                    print(f"Succes: Folder create successfully {local_path}.")
                    message.append(f"Succes: Folder create successfully {local_path}.")
                else:
                    #pass
                    print(f"Succes: Folder already exists {local_path}.")
                    message.append(f"Succes: Folder already exists {local_path}.")
                copy_and_overwrite(ftp, remote_path, local_path, message)
                ftp.cwd("..")
                
            else:  #If it's a file, download
                if not smbclient.path.exists(local_path):
                    print(f"Success: Restoring File: {remote_path} -> {local_path}")
                    message.append(f"Success: Restoring File: {remote_path} -> {local_path}")
                    with smbclient.open_file(local_path, "wb") as f:
                        ftp.retrbinary(f"RETR {item}", f.write)
                    
                else:
                    #pass
                    print(f"Succes: File already exists {local_path}.")
                    message.append(f"Succes: File already exists {local_path}.")

        # ftp.cwd("..")  #Go back up one level after processing
        return message
    except Exception as e:
        print(f"----------------Error in copy_and_overwrite: {e}")
        message.append(f"Error in copy_and_overwrite: {e}")
        return message
 
#MARK: Backup
def restore_file(file_path):
    try:
        message = []
        global_path = ""
        smbclient.ClientConfig(username = restore_config.wf_username, password = restore_config.wf_pwd)

        #Upload to FTP
        ftp_credentials = {
            "host": restore_config.ftp_host,
            "port": restore_config.ftp_port,
            "username": restore_config.ftp_username,
            "password": restore_config.ftp_password
        }

        try:
            ftp = FTP()
            ftp.connect(ftp_credentials["host"], ftp_credentials["port"])
            ftp.login(ftp_credentials["username"], ftp_credentials["password"])
            print("Connected to FTP server.")
            message.append("Connected to FTP server.\n")
        except Exception as e:
            print(f'FTP Connection Failed. {e}')
            message.append(f'FTP Connection Failed. {e}')
            return message
        print(file_path)
        if file_path:
            path_original = file_path
            path_copy = os.path.join(restore_config.wf_directory, path_original.replace("New_Workflow_Files", "New_Workflow_Files_restore").replace("/", os.sep).replace('E:/', ''))
            if not smbclient.path.exists(path_copy):
                smbclient.makedirs(path_copy)
            print(path_copy)
            copy_and_overwrite(ftp, path_original, path_copy, message)
        # else:
            # path_original = "E:"
            # path_copy = restore_config.wf_directory
            # if not smbclient.path.exists(path_copy):
            #     smbclient.makedirs(path_copy)
            # copy_and_overwrite(ftp, path_original, path_copy)

        #Close FTP connection after completion
        ftp.quit()
        print(f"Restored complete.")
        message.append(f"Restore process complete.")

        return message
    except Exception as e:
        return message
    

#MARK: Initial
if __name__ == '__main__':
    path = sys.argv[1]

    result = restore_file(path)

    log_message(result)

    # Upload to FTP
    # ftp_credentials = {
    #     "host": "workflow.transformainc.com",
    #     "port": 21,
    #     "username": "production@workflow.transformainc.com",
    #     "password": "N^]%Ehn+C-M1"
    # }

    # upload_to_ftp(dict_rows, ftp_credentials)

# 650329