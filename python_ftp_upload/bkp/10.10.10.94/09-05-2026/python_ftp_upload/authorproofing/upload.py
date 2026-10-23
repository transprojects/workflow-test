import os
import time
import utils
import config
from pathlib import PureWindowsPath
from datetime import datetime
from ftplib import FTP, error_perm
from io import BytesIO

#LOG MESSAGES
def log_message(messages):
    log_file_path = os.path.join("logs", f"log_file_{datetime.now().strftime('%Y-%m-%d')}.txt")
    os.makedirs(os.path.dirname(log_file_path), exist_ok=True)
    with open(log_file_path, "a") as log_file:        
        log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=============================================================\n")
        for message in messages:
            log_file.write(f"{message}\n")
        log_file.write(f"=============================================================\n")
        log_file.write(f"END OF LOGS")

#DB CONNECTION
def db_conn(db_host, db_user, db_password, db_database):
    conn, cursor,  messages= utils.database_connection(db_host, db_user, db_password, db_database)
    return conn, cursor,  messages

#CONSTRUCTING LOCAL PATH
def get_local_path(work_dir, volume_folder, job_id, stage):
    wf_directory = os.path.join(work_dir, volume_folder, job_id, stage, 'Delivery')
    wf_directory = PureWindowsPath(wf_directory)
    return wf_directory

#CONSTRUCTING FTP PATH
def get_dest_path(work_dir, job_id, stage, customer_id, cursor):
    base_direct = work_dir[:-1] if work_dir.endswith('/') else work_dir
    # remove_numbers = ''.join(char for char in stage if not char.isdigit())
    ftp_dir_path = "/".join(["REV", os.path.basename(os.path.dirname(base_direct)), os.path.basename(base_direct), job_id])

    last_folder_path = ''
    last_stage_path = ''
    stage_extension = ''

    cursor.execute("""SELECT folder_path, 'REV1' AS stage, last_stage, stage_ext FROM ftp_customer_stage WHERE cust_id = %s AND FIND_IN_SET(%s, stage) > 0 """, (customer_id, 'REV1'))
    get_folder_paths = cursor.fetchall()
    for fol_path in get_folder_paths:
        last_folder_path= fol_path[0]
        stage= fol_path[1]
        last_stage_path= fol_path[2]
        stage_extension= fol_path[3]

    if last_folder_path == 'Empty':
        last_folder_path = ''
    if last_stage_path:
        ftp_dir_path = "/".join([ftp_dir_path, stage])
    if last_folder_path:
        ftp_dir_path = "/".join([ftp_dir_path, last_folder_path])
    
    # stage_extension= "zip"

    return ftp_dir_path, stage_extension

def get_source_path(customer_name, j_code, job_id):
    file_name= "_".join([j_code, job_id + ".zip"])
    source_folder_path= "/".join(["Out", customer_name])
    source_path= "/".join(["Out", customer_name, file_name + ".zip"])
    return source_path, source_folder_path, file_name

def ftp_file_check(source_folder_path, file_name, src_ftp):
    src_ftp.cwd("/")
    for part in source_folder_path.split("/"):
        if part:
            try:
                src_ftp.cwd(part)
            except:
                print(f"Source folder not found: {source_folder_path}")
                messages_text = f"Err: Source folder not found: {source_folder_path}"
                subject = f'⚠ Source Folder Not Found on FTP'
                body = f"""<pre>
                Dear Team,<br><br>
                \t⚠ Source folder was not found on FTP server.<br>
                \tFolder path: {source_folder_path}<br><br>
                \tNote: This is an auto generated mail, please do not reply to this.<br><br>
                Thanks & Regards,<br>
                FTP Virtual Assistant<br>
                Transforma Pvt. Ltd.</pre>"""
                utils.send_email(subject, body, messages_text)
                return False

    files_list = src_ftp.nlst()
    if file_name not in files_list and file_name.lower() not in [f.lower() for f in files_list]:
        print("File not present in Source FTP.")
        messages_text = f"Err: File not present in Source FTP."
        subject = f'⚠ File Not Found on Source FTP'
        body = f"""<pre>
        Dear Team,<br><br>
        \t⚠ The file was not found on the source FTP server.<br>
        \tFile: {file_name}<br>
        \tFolder: {source_folder_path}<br><br>
        \tNote: This is an auto generated mail, please do not reply to this.<br><br>
        Thanks & Regards,<br>
        FTP Virtual Assistant<br>
        Transforma Pvt. Ltd.</pre>"""
        utils.send_email(subject, body, messages_text)
        return False

    return True

#FTP DIRECTORY CREATION
def ftp_folder_creation(ftp_path, ftp):
    try:
        directories = ftp_path.split("/")
        current_path = ""
        for directory in directories:
            current_path = f"{current_path}/{directory}" if current_path else directory
            try:
                ftp.mkd(current_path)
                # print(f"Created directory: {current_path}")
            except error_perm as e:
                if "550" in str(e):
                    pass
                    # print(f"Directory '{current_path}' already exists.")
                else:
                    print(f"Error creating directory '{current_path}': {e}")
                    
                    return False
            except Exception as e:
                print(f"Unexpected error: {e}")
                return False
        print(f"Created directory: {ftp_path}")
        return True
    except Exception as e:
        print(f'Error creating FTP directories {ftp_path}: {e}')
        return False


def main():
    messages = []
    
    #LOCAL DB CONNECTION
    conn1, cursor1, message= db_conn(config.localdb_host, config.localdb_user, config.localdb_password, config.localdb_database) 
    messages.append(message)
    if not cursor1:
        return None
    
    cursor1.execute(""" Select * from ftp_credentials where id = %s """,(1, ))
    adb_creds= cursor1.fetchone()
    if not adb_creds:
        print("No credentials found")
        return None
    xpp_host, xpp_usn, xpp_pss, xpp_db = adb_creds[16], adb_creds[17], adb_creds[18], adb_creds[19] 

    conn2, cursor2, message= db_conn(xpp_host, xpp_usn, xpp_pss, xpp_db )
    messages.append(message)
    if not cursor2:
        return None

    #Getting Project ID from DB
    cursor2.execute(""" SELECT project_id FROM inw_proof_dtl WHERE xperttrack_sync = %s AND is_author_submitted = %s""", ('1', 1))
    project_rows = cursor2.fetchall()
    if not project_rows:
        print("No project IDs found.")
        messages.append(f"Msg: No project IDs found with the Constraints")
        subject = '⚠ No Project IDs Found for Processing'
        body = """<pre>
        Dear Team,<br><br>
        \tNo project IDs were found matching the required conditions.<br>
        \tWorkflow execution was stopped.<br><br>
        \tNote: This is an auto-generated email. Please do not reply.<br><br>
        Thanks & Regards,<br>
        FTP Virtual Assistant<br>
        Transforma Pvt. Ltd.
        </pre>"""
        message = utils.send_email(subject, body, None)
        messages.append(message)
        return None
    project_ids = []
    for row in project_rows:
        project_ids.append(row[0])
    messages.append(f"Msg: {len(project_ids)} project IDs found with the Constraints")
    # print(project_ids)

    customer_dict = {}
    #Getting Customer id from inw_inward_dtl table
    project_ids = [ 19671 ]
    for pid in project_ids:
        cursor2.execute("""SELECT * FROM inw_inward_dtl WHERE id = %s""", (pid, ))
        inw_cust = cursor2.fetchall()
        if inw_cust:
            customer_id= inw_cust[0][2] #in AWs Db it is 2
            if customer_id not in customer_dict:
                customer_dict [customer_id] = []
            customer_dict[customer_id].append(pid)
        else:
            print(f"Customer ID is not found in DB for {pid}")
            messages.append(f"Err: Customer ID is not found in DB for {pid}")
            subject = '⚠ Customer ID Not Found in Database'
            body = f"""<pre>
            Dear Team,<br><br>
            \tCustomer ID could not be retrieved for project ID: {pid}.<br>
            \tPlease verify the entry in inw_inward_dtl.<br><br>
            \tNote: This is an auto-generated email. Please do not reply.<br><br>
            Regards,<br>
            FTP Virtual Assistant<br>
            Transforma Pvt. Ltd.
            </pre>"""
            message = utils.send_email(subject, body, None)
            messages.append(message)

        messages.append(f"{customer_dict}")
    # print (customer_dict)
        
    for cust_id in (customer_dict.keys()): 
        print(f"Working on Customer_id {cust_id}") 
        messages.append(f"Msg: Working on Customer_id {cust_id}") 
        cursor1.execute(""" SELECT * FROM ftp_credentials WHERE cust_id = %s """, (cust_id,)) 
        ftp_cred= cursor1.fetchone() 
        creden_columns = [desc[0] for desc in cursor1.description] 
        creden_dict_rows = dict(zip(creden_columns, ftp_cred))
        if not ftp_cred:
            print(f"FTP Credentials are not found for {cust_id} in DB")
            messages.append(f"Err: FTP Credentials are not found for {cust_id} in DB")
            subject = '⚠ FTP Credentials Missing'
            body = f"""<pre>
            Dear Team,<br><br>
            \tFTP credentials were not found for Customer ID: {cust_id}.<br>
            \tWorkflow execution for this customer has been halted.<br><br>
            \tNote: This is an auto-generated email. Please do not reply.<br><br>
            Regards,<br>
            FTP Virtual Assistant<br>
            Transforma Pvt. Ltd.
            </pre>"""
            message = utils.send_email(subject, body, None)
            messages.append(message)
        # print(creden_dict_rows)
        conn3, cursor3, message= db_conn(creden_dict_rows['xpertproof_db_host'], creden_dict_rows['xpertproof_db_username'], creden_dict_rows['xpertproof_db_password'], creden_dict_rows['xpertproof_db_name'])
        messages.append(message)
        if not cursor3:
            return None
        #xpertproof ftp
        s_status, src_ftp, message= utils.ftp_connection(creden_dict_rows['xpertproof_host'], creden_dict_rows['xpertproof_port'], creden_dict_rows['xpertproof_username'], creden_dict_rows['xpertproof_password'], None)
        messages.append(message)
        if not s_status:
            print(f"Err: FTP connection failed for host {creden_dict_rows['host']}")
            continue
        #xperttrack ftp
        d_status, dst_ftp, message= utils.ftp_connection(creden_dict_rows['host'], creden_dict_rows['port'], creden_dict_rows['username'], creden_dict_rows['password'], None)
        messages.append(message)
        if not d_status:
            print(f"Err: FTP connection failed for host {creden_dict_rows['host']}")
            continue

        for project in customer_dict[cust_id]:
            print(project)
            cursor3.execute("""Select id, work_dir, volume_folder, pub_id, cust_id, j_id, department_id, stage from inw_inward_dtl where id=%s and stage= "FP" and stage_with="AUTHOR" """, (project, ))
            inw_rows= cursor3.fetchone()
            if not inw_rows:
                print("Details not found")
                messages.append(f"Msg: Details not found in DB for {project} and customer {cust_id}")
                return None
            work_dir = inw_rows[1]
            volume_folder = inw_rows[2]
            job_id = inw_rows[3]
            customer_id = inw_rows[4]
            j_id = inw_rows[5]
            dept_id = inw_rows[6]
            stage = inw_rows[7]

            cursor3.execute(""" Select * from adm_customer_master where id= %s """,(customer_id, ) )
            cust_det= cursor3.fetchone()
            customer_name= cust_det[2]
            cursor3.execute(""" Select * from adm_journals where j_id=%s""", (j_id))
            j_det= cursor3.fetchone()
            j_code= j_det[4]

            source_path, source_folder_path, file_name= get_source_path(customer_name, j_code, job_id)
            messages.append(f"Local Path: {source_path}")
            dest_path, stage_ext = get_dest_path(work_dir, job_id, stage, customer_id, cursor1)
            messages.append(f"FTP Path: {dest_path}")
            print(file_name)

            dst_file_name= f"{job_id}.{stage_ext}"
            dst_full_path= "/".join([dest_path, dst_file_name])
            # print(dst_full_path)
           
            status = ftp_file_check(source_folder_path, file_name, src_ftp)
            if not status:
                return None
            messages.append(f"Msg: File exists on source FTP: {file_name}")

            buffer = BytesIO()
            try:
                print(f"Downloading from source FTP: {source_path}")
                messages.append(f"Msg: Downloading from source FTP: {source_path}")
                src_ftp.retrbinary(f"RETR {file_name}", buffer.write)
                buffer.seek(0)
                print("File downloaded successfully from source FTP.")
                messages.append("Msg: File downloaded successfully from source FTP.")

            except Exception as e:
                print(f"Error downloading file: {file_name}, {e}")
                messages.append(f"Err: Error downloading file: {file_name}, {e}")
                buffer = None
                subject = f'⚠ Error Downloading File from Source FTP'
                body = f"""<pre>
                Dear Team,<br><br>
                \t⚠ An error occurred while downloading the file from the source FTP server.<br>
                \tFile: {file_name}<br>
                \tFolder: {source_folder_path}<br>
                \tError Details: {e}<br><br>
                \tNote: This is an auto generated mail, please do not reply to this.<br><br>
                Thanks & Regards,<br>
                FTP Virtual Assistant<br>
                Transforma Pvt. Ltd.</pre>"""
                msg = utils.send_email(subject, body, messages)
                messages.append(msg)

            try:
                if ftp_folder_creation(dest_path, dst_ftp):
                    print(f"Uploading to destination FTP: {dst_full_path}")
                    messages.append(f"Msg: Uploading to destination FTP: {dst_full_path}")

                    dst_ftp.storbinary(f"STOR {dst_full_path}", buffer)

                    print("File uploaded successfully to destination FTP.")
                    messages.append("Msg: File uploaded successfully to destination FTP.")
                    subject = f'✅ File Uploaded Successfully to Destination FTP'
                    body = f"""<pre>
                    Dear Team,<br><br>
                    \t✅ File has been successfully uploaded to the destination FTP server.<br>
                    \tFile: {dst_file_name}<br>
                    \tFolder: {dest_path}<br><br>
                    \tNote: This is an auto generated mail, please do not reply to this.<br><br>
                    Thanks & Regards,<br>
                    FTP Virtual Assistant<br>
                    Transforma Pvt. Ltd.</pre>"""
                    msg = utils.send_email(subject, body, messages)
                    messages.append(msg)



            except Exception as e:
                print(f"Error in uploading file to FTP {e}")
                messages.append(f"Error in uploading file to FTP {e}")
                subject = f'⚠ Error Uploading File to Destination FTP'
                body = f"""<pre>
                Dear Team,<br><br>
                \t⚠ An error occurred while uploading the file to the destination FTP server.<br>
                \tFile: {dst_file_name}<br>
                \tFolder: {dest_path}<br>
                \tError Details: {e}<br><br>
                \tNote: This is an auto generated mail, please do not reply to this.<br><br>
                Thanks & Regards,<br>
                FTP Virtual Assistant<br>
                Transforma Pvt. Ltd.</pre>"""
                msg = utils.send_email(subject, body, messages)
                messages.append(msg)


            # # UPDATE STAGE
            # cursor2.execute("UPDATE inw_inward_dtl SET stage='REV1' WHERE id=%s", (project,))
            # conn2.commit()
            # print("Stage Updated to REV1 in DB")
            # messages.append("Stage Updated to REV1 in DB")
        
    log_message(messages)
    conn1.close()
    conn2.close()
    conn3.close()


if __name__ == '__main__':
    dd = main()