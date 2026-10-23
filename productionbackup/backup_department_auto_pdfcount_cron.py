import shutil
import smbclient
import pymysql
import smbclient.path
import config_dep
from datetime import datetime
import os, sys, subprocess
from ftplib import FTP, error_perm
from pathlib import PureWindowsPath
  
#Establish the connection to the database
conn = pymysql.connect(
    host = config_dep.host,
    user = config_dep.user,
    password = config_dep.password,
    database = config_dep.database
)
cursor = conn.cursor()
 
#MARK: Log Message
# Function to log messages
def log_message(messages):
    
    #config_dep smb client
    smbclient.ClientConfig(username = config_dep.username, password = config_dep.user_pwd)
    
    log_file_path = f"{config_dep.change_directory}\logs\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

    #Create log directory if not exitsts in the smbclient
    if not smbclient.path.exists(f"{config_dep.change_directory}\logs"):
        smbclient.makedirs(f"{config_dep.change_directory}\logs")
 
    with smbclient.open_file(log_file_path, "a") as log_file:
        
        log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
        for message in messages:
            log_file.write(f"{message}\n")
            log_file.write("-----------------------------------------------------------------------------------------\n")

#MARK: FTP        
def upload_to_ftp(values, ftp_credentials):
    messages = []
    try:
        ftp = FTP()
        ftp.connect(ftp_credentials["host"], ftp_credentials["port"])
        ftp.login(ftp_credentials["username"], ftp_credentials["password"])
        print("Connected to FTP server.")
        messages.append("Connected to FTP server.\n")
        
        values = values[0]
        
        source_directory = os.path.join(values["work_dir"], values["volume_folder"], values["pub_id"], values["current_stage"], values["dept_name"])
        source_dir = source_directory
        directories = source_dir.split("/")
        current_path = ""
        for directory in directories:
            current_path = f"{current_path}/{directory}" if current_path else directory
            try:
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
                dir_path = os.path.join(root, dir_name)
                try:
                    ftp.mkd(dir_path)
                    ftp.cwd(dir_path)
                    print(f"Created directory on FTP: {dir_path}")
                    messages.append(f"Created directory on FTP: {dir_path}\n")
                except:
                    print(f"Directory already exists on FTP: {dir_path}")
                    messages.append(f"Directory already exists on FTP: {dir_path}\n")
 
            for file_name in files:
                file_path = os.path.join(root, file_name)
                try:
                    with open(file_path, 'rb') as f:
                        ftp.storbinary(f"STOR {file_path}", f)
                    print(f"Uploaded file to FTP: {file_path}")
                    messages.append(f"Uploaded file to FTP: {file_path}\n")
                except Exception as e:
                    print(f"Error uploading {file_path}: {e}")
                    messages.append(f"Error uploading {file_path}: {e}\n")
        ftp.quit()
    except Exception as e:
        print(f"FTP upload failed: {e}")
        messages.append(f"FTP upload failed: {e}")
    log_message(messages)

    return messages

#Remove files in smbclient in perticuler folder and last delete the folder
def remove_dir_recursive(dir_path):
    try:
        for entry in smbclient.listdir(dir_path):
            entry_path = os.path.join(dir_path, entry)
            if smbclient.path.isdir(entry_path):
                remove_dir_recursive(entry_path)  # Delete subdirectory recursively
            else:
                smbclient.remove(entry_path)  # Delete file
        smbclient.rmdir(dir_path)  # Now the directory is empty, remove it
        print(f"Deleted directory: {dir_path}")
    except Exception as e:
        print(f"Error deleting {dir_path}: {e}")
 
#MARK: Backup
def backup(values, no_of_folders_present):
    
    mesages = []
    
    safe_timestamp = datetime.now().strftime('%Y-%m-%d_%H-%M-%S')
    for value in values:
        try:
            #config_dep smb client
            smbclient.ClientConfig(username = config_dep.username, password = config_dep.user_pwd)
            
            source_directory = os.path.join(value["work_dir"], value["volume_folder"], value["pub_id"], value["current_stage"], value["dept_name"])
            
            if not os.path.exists(source_directory):
                mesages.append(f"The source directory {source_directory} does not exits.")
                print(f"The source directory {source_directory} does not exits.")
                continue
                # return {'status': False, 'message': f"The source directory {source_directory} does not exits."}
            
            target_directory = fr"{config_dep.change_directory}"
            
            # Ensure the target directory is accessible
            if not smbclient.path.exists(target_directory):
                mesages.append(f"Error: The target directory {target_directory} is not accessible.")
                print(f"The target directory {target_directory} is not accessible.")
                continue
                # return {'status': False, 'message': f"The target directory {target_directory} is not accessible."}
            
            target_directory = config_dep.change_directory + "/" + source_directory.replace("E:\\","").replace("E:/","") + '/' + safe_timestamp
            check_directory = config_dep.change_directory + "/" + source_directory.replace("E:\\","").replace("E:/","")

            if not smbclient.path.exists(target_directory):
                smbclient.makedirs(target_directory)
                print(f"Created directory: {target_directory}")
                mesages.append(f"Success: Created directory {target_directory} Successfully.")

            #Delete more then 5 folder present in the smbclient
            all_entries = smbclient.listdir(check_directory)
            all_folders = [f for f in all_entries if smbclient.path.isdir(os.path.join(check_directory, f))]
            all_folders.sort(key=lambda f: smbclient.stat(os.path.join(check_directory, f)).st_mtime)
            #MARK: Delete 5 folder
            if len(all_folders) > no_of_folders_present:
                folders_to_delete = all_folders[:-no_of_folders_present]  # Keep last 5
                for folder in folders_to_delete:
                    folder_path = os.path.join(check_directory, folder)
                    remove_dir_recursive(folder_path)
                    print(f"Success: Deleted the more then 5 oldest file: {folder_path}")
                    mesages.append(f"Success: Deleted the more then 5 oldest file {folder_path}.")
            
            # Function to copy files using shutil and smbclient
            def copytree(src, dst):
                if os.path.isdir(src):
                    for item in os.listdir(src):
                        source_path = os.path.join(src, item)
                        target_path = os.path.join(dst, item)
        
                        if os.path.isdir(source_path):
                            try:
                                smbclient.makedirs(target_path)  # Create the directory on the SMB share
                                print(f"Success: Created directory {target_path} Successfully.")
                                mesages.append(f"Success: Created directory {target_path} Successfully.")
                            except OSError as e:
                                print(f"Error: Unable to create directory {target_path} as it already exists.")
                                mesages.append(f"Error: Unable to create directory {target_path} as it already exists.")
        
                            copytree(source_path, target_path)  # Recursively copy subdirectories
                        else:
                            if smbclient.path.exists(target_path):
                                print(f"Error: File already present in Backup directoy {target_path}.")
                                mesages.append(f"Error: File already present in Backup directoy {target_path}.")
                            else:
                                with open(source_path, 'rb') as fsrc:
                                    with smbclient.open_file(target_path, mode='wb') as fdest:
                                        shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                                print(f"Copied file: {src} to {dst}")
                                mesages.append(f"Success: Copied file {src} to {dst}.")
        
                else:
                    if smbclient.path.exists(dst):
                        print(f"Error: File already present in Backup directoy {dst}.")
                        mesages.append(f"Error: File already present in Backup directoy {dst}.")
                    else:
                        with open(src, 'rb') as fsrc:
                            with smbclient.open_file(dst, mode='wb') as fdest:
                                shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                        print(f"Copied file: {src} to {dst}")
                        mesages.append(f"Success: Copied file {src} to {dst}.")
                    return "pp"
                
            cursor.execute("UPDATE inw_transactions SET file_backedup = 2,file_backup_path= %s WHERE project_id = %s AND stage = %s AND dept = %s AND id = %s",(source_directory, value["project_id"], value["current_stage"], value["dept"], value["id"]))
            
            # Copy the entire folder from source to target directory
            copytree(source_directory, target_directory)

            cursor.execute("UPDATE inw_transactions SET file_backedup = 1, file_backup_deleted = 0 WHERE project_id = %s AND stage = %s AND dept = %s AND id = %s""", (value["project_id"], value["current_stage"], value["dept"], value["id"]))

            #Call the page count script #MARK: Page count
            cursor.execute('select * from inw_transactions where dept in (10, 14) and completion_status = 1 and id = %s', (value["id"], ))
            get_rows = cursor.fetchall()
            if get_rows:
                sub_pro_result = subprocess.run(['py', PureWindowsPath(config_dep.page_count_python_file), str(value['project_id'])], capture_output=True, text=True)
                if sub_pro_result.returncode == 0:
                    print(sub_pro_result.stdout, "====")
                else:
                    print(sub_pro_result.stderr, "----")
                    
        except Exception as e:
            return {'status': False, 'message': f'Error accure in backup...!...{e}'}
    
    log_message(mesages)
    return {'status': True, 'message': 'Successfully backuped...!'}
    

#MARK: Initial
if __name__ == '__main__':
    #Get the folder path from the database in current date and do not backup files only
    cursor.execute(config_dep.query)    #config.query
    rows = cursor.fetchall()

    if not rows:
        print({"message": "There are no records found in the database to process or backup."})
        sys.exit()

    columns = [desc[0] for desc in cursor.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]

    cursor.execute('select set_version_count from wf_settings where set_id = %s', (1, ))
    no_of_folders_present = cursor.fetchone()
    
    if no_of_folders_present:
        no_of_folders_present = no_of_folders_present[0]
    else:
        no_of_folders_present = 5

    result = backup(dict_rows, no_of_folders_present)

    if not result['status']:
        print(f'{result["message"]}')

    # Upload to FTP
    ftp_credentials = {
        "host": "68.178.166.2",
        "port": 21,
        "username": "journals@backup.transforma.in",
        "password": "CVz^zsRTcFL)&]Dv"
    }

    if 'peerj' in dict_rows['work_dir'].lower():
        upload_to_ftp(dict_rows, ftp_credentials)

# 650329