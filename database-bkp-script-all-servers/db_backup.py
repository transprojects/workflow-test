import shutil
import smbclient
import smbclient.path
import config
from datetime import datetime
from ftplib import FTP, error_perm
import os,sys


# Function to log messages
def log_message(messages):
    
    log_file_path = f"{os.path.join(config.change_directory)}\db_logs\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

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
                try:
                    # shutil.copy(file_path, ftp_file_path)
                    with open(file_path, 'rb') as f:
                        ftp.storbinary(f"STOR {file_paths}", f)
                    print(f"Uploaded file to FTP: {file_paths, file_path}")
                    messages.append(f"Uploaded file to FTP: {file_paths, file_path}\n")
                except Exception as e:
                    print(f"Error uploading {file_paths, file_path}: {e}")
                    messages.append(f"Error uploading {file_paths, file_path}: {e}\n")
 
        ftp.quit()
    except Exception as e:
        print(f"FTP upload failed: {e}")
        messages.append(f"FTP upload failed: {e}")
    log_message(messages)

    return messages

def backup(source_directory):
    
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
    
    target_directory = fr"{config.change_directory}/{os.path.basename(source_directory)}"
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
                    if os.path.splitext(target_path)[-1] == ".log" or os.path.splitext(target_path)[-1] == ".zip":
                        with open(source_path, 'rb') as fsrc:
                            with smbclient.open_file(target_path, mode='wb') as fdest:
                                shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                        print(f"Copied file: {source_path} to {target_path}")
                        mesages.append(f"Success: Copied file {source_path} to {target_path}.")

        else:
            if os.path.splitext(target_path)[-1] == ".log" or os.path.splitext(target_path)[-1] == ".zip":
                with open(src, 'rb') as fsrc:
                    with smbclient.open_file(dst, mode='wb') as fdest:
                        shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                print(f"Copied file: {src} to {dst}")
                mesages.append(f"Success: Copied file {src} to {dst}.")
            return "pp"
    
    # Copy the entire folder from source to target directory
    copytree(source_directory, target_directory)

    # Upload to FTP
    ftp_credentials = {
        "host": config.host,
        "port": config.port,
        "username": config.ftp_user,
        "password": config.password
    }
    
    upload_to_ftp(source_directory, ftp_credentials)
    
    log_message(mesages)
    
    
    
path = sys.argv[1]

backup(path)