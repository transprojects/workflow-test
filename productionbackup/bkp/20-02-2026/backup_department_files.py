import shutil
import smbclient
import pymysql
import smbclient.path
import config
from datetime import datetime
import os,sys
from ftplib import FTP, error_perm
  
#Establish the connection to the database
conn = pymysql.connect(
    host = config.host,
    user = config.user,
    password = config.password,
    database = config.database
)
cursor = conn.cursor()
 
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
 
 
def backup(source_directory, a_id, stage, dept_id,trans_id):
    
    mesages = []
    
    #Config smb client
    smbclient.ClientConfig(username = config.username, password = config.user_pwd)
    
    if not os.path.exists(source_directory):
        raise FileNotFoundError(f"The source directory {source_directory} does not exits.")
    
    target_directory = fr"{config.change_directory}"
    
    # Ensure the target directory is accessible
    if not smbclient.path.exists(target_directory):
        mesages.append(f"Error: The target directory {target_directory} is not accessible.")
        raise FileNotFoundError(f"The target directory {target_directory} is not accessible.")
    
    target_directory = fr"{config.change_directory}/{source_directory.replace("E:\\","").replace("E:/","")}"
    if not smbclient.path.exists(target_directory):
        smbclient.makedirs(target_directory)
        print(f"Created directory: {target_directory}")
        mesages.append(f"Success: Created directory {target_directory} Successfully.")
        
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
                    #if smbclient.path.exists(target_path):
                     #   print(f"Error: File already present in Backup directoy {target_path}.")
                     #   mesages.append(f"Error: File already present in Backup directoy {target_path}.")
                    #else:
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
        
    cursor.execute("UPDATE inw_transactions SET file_backedup = 2,file_backup_path= %s WHERE project_id = %s AND stage = %s AND dept = %s AND id = %s",(path,a_id, stage,dept_id,trans_id))
    
    # Copy the entire folder from source to target directory
    copytree(source_directory, target_directory)
    
    cursor.execute("UPDATE inw_transactions SET file_backedup = 1 WHERE project_id = %s AND stage = %s AND dept = %s AND id = %s",(a_id, stage,dept_id,trans_id))
    cursor.execute("UPDATE inw_transactions SET file_backup_deleted = 0 WHERE project_id = %s AND stage = %s AND dept = %s AND id = %s",(a_id, stage,dept_id,trans_id))
    

    
    log_message(mesages)
    
    
    
path = sys.argv[1]
a_id = sys.argv[2]
stage = sys.argv[3]
dept_id = sys.argv[4]
trans_id = sys.argv[5]
backup(path, a_id, stage,dept_id,trans_id)

# Upload to FTP
ftp_credentials = {
    "host": "workflow.transformainc.com",
    "port": 21,
    "username": "production@workflow.transformainc.com",
    "password": "N^]%Ehn+C-M1"
}

#upload_to_ftp(path, ftp_credentials)
