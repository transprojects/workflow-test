import shutil
import smbclient
import pymysql
import smbclient.path
import config
from datetime import datetime
import os,sys

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

    log_file_path = f"logs\log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

    with open(log_file_path, "a") as log_file:

        log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
        for message in messages:
            log_file.write(f"{message}\n")
            log_file.write("-----------------------------------------------------------------------------------------\n")

def delete(file_name):

    mesages = []

    #Get the stage and journal id
    cursor.execute("select stage, j_id from inw_inward_dtl where pub_id = %s", (file_name, ))
    stages = cursor.fetchall()
    path = config.path
    
    for stage in stages:
        sta = stage[0]  #"REV2"
        j_id = stage[1]
        
        #Get the journal code
        cursor.execute("select j_code from adm_journals where j_id = %s", (j_id, ))
        journals = cursor.fetchall()
        j_code = journals[0][0]

        for stag in config.stages:
            if stag in sta:
                number = "".join(num for num in sta if num.isdigit())
                if number:
                    for no in range(int(number) - 1, 0, -1):
                        sub_path = os.path.join(path, "TSP", stag, "TSP", j_code, file_name, stag + str(no))
                        if os.path.exists(sub_path):
                            shutil.rmtree(sub_path)
                            mesages.append(f'Success: Delete folder "{sub_path}".')
                        else:
                            mesages.append(f'Error: Folder does not exits "{sub_path}".')
                break
            
            m_path = os.path.join(path, "TSP", stag, "TSP", j_code, file_name)
            if os.path.exists(m_path):
                for item in os.listdir(m_path):
                    item_path = os.path.join(m_path, item)                
                    # Check if it's a file or directory and remove it
                    if os.path.isfile(item_path):
                        os.remove(item_path)  # Remove file
                    elif os.path.isdir(item_path):
                        shutil.rmtree(item_path)  # Remove directory

                mesages.append(f'Success: Delete folder "{m_path}".')
            else:
                mesages.append(f'Error: Folder does not exits "{os.path.join(path, stag)}".')

    log_message(mesages)


name = sys.argv[1]

delete(name)