from ftplib import FTP
import os

# FTP server details
FTP_SERVER = "68.178.166.2"
FTP_USERNAME = "journals@backup.transforma.in"
FTP_PASSWORD = "CVz^zsRTcFL)&]Dv"
FTP_PORT = 21

def connect_to_ftp():
    try:
        # Establish FTP connection
        ftp = FTP()
        ftp.connect(FTP_SERVER, FTP_PORT)
        ftp.login(FTP_USERNAME, FTP_PASSWORD)
        print(f"Connected to FTP server: {FTP_SERVER}")
        return ftp
    except Exception as e:
        print(f"Error connecting to FTP: {e}")
        return None

def create_directory(ftp, dir_path):
    try:
        # Create directory on FTP server
        dirs = dir_path.split("/")
        for dir_name in dirs:
            if dir_name not in ftp.nlst():
                ftp.mkd(dir_name)
            ftp.cwd(dir_name)
        print(f"Directory {dir_path} created/exists on FTP server.")
    except Exception as e:
        print(f"Error creating directory {dir_path}: {e}")

def upload_file(ftp, local_file_path, remote_file_path):
    try:
        print(local_file_path, remote_file_path)
        if os.path.exists(local_file_path):
            print("file present")
        with open(local_file_path, "rb") as file:
            ftp.storbinary(f"STOR {remote_file_path}", file)
        print(f"File {local_file_path} uploaded to {remote_file_path} on FTP server.")
    except Exception as e:
        print(f"Error uploading file {local_file_path} to {remote_file_path}: {e}")

def main():
    # Connect to FTP
    ftp = connect_to_ftp()
    if not ftp:
        return

    # Test directory and file details
    remote_dir = "productionbackup/new"
    local_test_file = "backup.py"
    remote_test_file = f"backup.py"

    # Create a test file
    with open(local_test_file, "w") as f:
        f.write("This is a test file for FTP upload.")

    # Create directory on FTP server
    create_directory(ftp, remote_dir)

    # Upload test file
    upload_file(ftp, local_test_file, remote_test_file)

    # Close FTP connection
    ftp.quit()
    print("FTP connection closed.")

if __name__ == "__main__":
    main()
