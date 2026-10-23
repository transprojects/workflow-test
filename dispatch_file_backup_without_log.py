import os
import shutil
import smbclient
import pymysql
import random

import smbclient.path
import config

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

#Config smb client
smbclient.ClientConfig(username = config.username, password = config.user_pwd)

for row in rows:
    # dd = "select work_dir from inw_inward_dtl where "
    # sp = row[0].split("production/JOURNALS/")
    cursor.execute(f"select work_dir, volume_folder, pub_id from inw_inward_dtl where id = {row[0]}")
    # ro = cursor.fetchall()
    stages = cursor.fetchall()
    # print(stages)
    for stage in stages:
        # print(stage)
        sp = stage[0].split("production/JOURNALS/")

        source_directory = os.path.join(stage[0], stage[1], stage[2], row[1])     #E:/New_Workflow_Files/JOURNAL/production/JOURNALS/
        dupli_directory = os.path.join("New_Workflow_Files/JOURNAL/production/JOURNALS/", sp[1], stage[1], stage[2], row[1])     #E:/New_Workflow_Files/JOURNAL/production/JOURNALS/
        try:
            # Define the target network path (shared folder on the target machine)
            target_directory = fr"{config.change_directory}"
        except Exception as e:
            print(e)

        # Check if the source directory exists
        if not os.path.exists(source_directory):
            raise FileNotFoundError(f"The source directory {source_directory} does not exist.")
        
        # Ensure the target directory is accessible
        if not smbclient.path.exists(target_directory):
            raise FileNotFoundError(f"The target directory {target_directory} is not accessible.")
        
        target_directory = fr"{config.change_directory}/{dupli_directory}"
        if not smbclient.path.exists(target_directory):
            smbclient.makedirs(target_directory)

        # Function to copy files using shutil and smbclient
        def copytree(src, dst):
            
            cursor.execute("UPDATE inw_dispatch_history SET file_backedup = 1 WHERE a_id = %s AND stage = %s",(row[0], row[1]))
            conn.commit()
            
            if os.path.isdir(src):
                for item in os.listdir(src):
                    # print(item)
                    source_path = os.path.join(src, item)
                    target_path = os.path.join(dst, item)

                    if os.path.isdir(source_path):
                        try:
                            smbclient.makedirs(target_path)  # Create the directory on the SMB share
                            # print(f"Created directory: {target_path}")
                        except OSError as e:
                            print(f"Error creating directory {target_path}: {e}")

                        copytree(source_path, target_path)  # Recursively copy subdirectories
                    else:
                        with open(source_path, 'rb') as fsrc:
                            with smbclient.open_file(target_path, mode='wb') as fdest:
                                shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                        
            else:
                with open(src, 'rb') as fsrc:
                    with smbclient.open_file(dst, mode='wb') as fdest:
                        shutil.copyfileobj(fsrc, fdest)  # Copy files directly
                # print(f"Copied file: {src} to {dst}")
                return "pp"
        
        # Copy the entire folder from source to target directory
        copytree(source_directory, target_directory)

conn.commit()
conn.close()

    