import pymysql
import config
from datetime import datetime
import os, sys
from ftplib import FTP, error_perm
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from pathlib import PureWindowsPath
import time


#MARK: Log
#Function to write log messages
def log_message(messages):
    
    log_file_path = f"logs/log_file_{datetime.now().strftime('%Y-%m-%d')}.txt"

    # with open(log_file_path, "a") as log_file:        
    #     log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
    #     for message in messages:
    #         log_file.write(f"{message}\n")
    #         log_file.write("-----------------------------------------------------------------------------------------\n")

#MARK: Database
#Function to connect the databse
def database_connection():
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = config.db_host, 
            user = config.db_user, 
            password = config.db_password, 
            database = config.db_database
        )
        cursor = conn.cursor()
        return conn, cursor
    except Exception as e:
        print(f'Error accure in "database_connection" function. {e}')
        return None, None
    
#MARK: Email Function
#Send email functionalitys
def send_email(subject, body, messages):
    """Send email notification for downloaded file"""
    subject = subject    #f"Integrity checks FTP File Downloaded:"

    msg = MIMEMultipart()
    msg["From"] = config.SENDER_EMAIL
    msg["To"] = ", ".join(config.TO_EMAILS)
    msg["Cc"] = ", ".join(config.CC_EMAILS)
    msg["Subject"] = subject
    msg.attach(MIMEText(body, "html"))

    try:
        with smtplib.SMTP(config.SMTP_SERVER, config.SMTP_PORT) as server:
            server.starttls()
            server.login(config.SENDER_EMAIL, config.SENDER_PASSWORD)
            server.sendmail(config.SENDER_EMAIL, config.TO_EMAILS + config.CC_EMAILS, msg.as_string())
        print(f"Email notification sent for file: successfully.")
        messages.append(f"Success: Email notification sent for file: successfully.")
    except Exception as e:
        print(f"Failed to send email: {e}")
        messages.append(f"Error: Email notification Failed to sent for file: {body}.")
    return messages
    
#MARK: DB Query
#Function to Update the file status in databse
def update_file_status(cursor, conn, upload_id, status, last_error=None):
    cursor.execute("""
        UPDATE xperttrack_ftp_upload_history 
        SET status=%s, last_error=%s
        WHERE id=%s
    """, (status, last_error, upload_id))
    conn.commit()
            
    
#MARK: Main
def main(a_id):
    messages = []

    conn, cursor = database_connection()        #Check database connectivity
    if not cursor:
        print(f'Database Connection Failed.')
        messages.append("Error: Database Connection Failed in Workflow.\n")
        subject = f'⚠ Database Connection Failed in Workflow'
        body = f"""<pre>
Dear Team,<br><br>
\t⚠ Database Connection Failed in workflow<br>
\t<a href="file://">{conn}</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None

    #Get path from "inw_inward_dtl" table
    cursor.execute('select id, work_dir, volume_folder, pub_id, cust_id, j_id, department_id, stage from inw_inward_dtl where id = %s', (a_id, ))
    rows1 = cursor.fetchall()
    if not rows1:
        print(f"No Article ID found in Database. Please enter the correct Article ID.")
        messages.append("Error: No Article ID found in Database. Please enter the correct Article ID.\n")
        subject = f'⚠ Article ID does not Exists in Database {a_id}'
        body = f"""<pre>
Dear Team,<br><br>
\t⚠ No Article ID found in Database. Please enter the correct Article ID {a_id}.<br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None
    
    work_dir = rows1[0][1]
    volume_folder = rows1[0][2]
    job_id = rows1[0][3]
    customer_id = rows1[0][4]
    journ_id = rows1[0][5]
    dept_id = rows1[0][6]
    stage = rows1[0][7]
    
    #Get stage from "inw_dispatch_history" table
    cursor.execute("select stage, idh_id, from_mail, to_mail, mail_text, to_cc, to_bcc, mail_subject from inw_dispatch_history where a_id = %s and stage = %s and dispatch_type != %s order by idh_id desc", (rows1[0][0], stage, 'MOVED', ))
    rows2 = cursor.fetchall()
    if not rows2:
        print(f'Error: No stage found for Article "{job_id}" in Database. Please enter the Valid id to file transfer.')
        messages.append(f'Error: No stage found for Article "{job_id}" in Database. Please enter the Valid id to file transfer.\n')
        subject = f'⚠ Article stage not fount in database for ID {job_id}'
        body = f"""<pre>
Dear Team,<br><br>
\t⚠ No stage found for Article "{job_id}" in Database. Please enter the Valid id to file transfer.<br>
\t<a href="file://">{job_id}</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None
    
    dispage_stage = rows2[0][0]
    disp_from_mail = rows2[0][2].strip(' ,') if rows2[0][2] else rows2[0][2]
    disp_to_mail = rows2[0][3].strip(' ,') if rows2[0][3] else rows2[0][3]
    disp_mail_text = rows2[0][4]
    disp_to_cc = rows2[0][5].strip(' ,') if rows2[0][5] else rows2[0][5]
    disp_to_bcc = rows2[0][6].strip(' ,') if rows2[0][6] else rows2[0][6]
    disp_mail_subject = rows2[0][7]

    # for row in rows2:
    #MARK: Generate path
    # cahnge_slash = work_dir.replace('/', config.os_slash)
    wf_directory = os.path.join(work_dir, volume_folder, job_id, stage, 'Delivery')
    wf_directory = PureWindowsPath(wf_directory)
    remove_numbers = ''.join(char for char in stage if not char.isdigit())

    #handle trailing slash in work_dir
    base_direct = work_dir[:-1] if work_dir.endswith('/') else work_dir

    #Generate FTP Folder path
    ftp_dir_path = "/".join([remove_numbers, os.path.basename(os.path.dirname(base_direct)), os.path.basename(base_direct), job_id])

    last_folder_path = ''
    last_stage_path = ''
    #Create path from database "ftp_customer_stage"
    cursor.execute('select folder_path, stage, last_stage, stage_ext from ftp_customer_stage where cust_id = %s', (customer_id, ))
    get_folder_paths = cursor.fetchall()

    for fol_path in get_folder_paths:
        stage_split = fol_path[1].split(',')
        stage_split = [sta.strip() for sta in stage_split]
        if stage in stage_split:
            last_folder_path = fol_path[0] if fol_path[0] else 'Empty'
            last_stage_path = fol_path[2]
            stage_extension = fol_path[3]
            break
    if not last_folder_path:
        error_msg = f'Folder path was not defined for customer_id: "{customer_id}", stage: "{stage}" in ftp_customer_stage table.'
        print(error_msg)
        messages.append(error_msg)
        subject = f'⚠ Folder path Missing in "ftp_customer_stage" Table.'
        body = f"""<pre>
Dear Team,<br><br>
\t⚠ {error_msg}<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        messages.append(f'Error: Folder path was not defined for customer_id: "{customer_id}", stage: "{stage}" in ftp_customer_stage table.')
        return None

    if last_folder_path == 'Empty':
        last_folder_path = ''
    if last_stage_path:
        ftp_dir_path = "/".join([ftp_dir_path, stage])
    if last_folder_path:
        ftp_dir_path = "/".join([ftp_dir_path, last_folder_path])
    # #Generate FTP Folder path
    # ftp_dir_path = "/".join([remove_numbers, os.path.basename(os.path.dirname(base_direct)), os.path.basename(base_direct), job_id, stage, 'transforma-files'])
    
    if os.path.exists(wf_directory):    #Check the path exists in the machine
        # for file in os.listdir(wf_directory):
            file = f'{job_id}.{stage_extension}'
            file_path = os.path.join(wf_directory, file)
            #If filename was not matched with pub_id then Rename the file name as pub_id 
#             if file != f'{job_id}.{stage_extension}' and file.endswith(stage_extension):
#                 os.rename(file_path, os.path.join(wf_directory, f'{job_id}.{stage_extension}'))
#                 old_ff = file_path
#                 file = f'{job_id}.{stage_extension}'
#                 file_path = os.path.join(wf_directory, f'{job_id}.{stage_extension}')

#                 print(f'Error: Filename was missmatch in Database and directory file.')
#                 subject = f'⚠ Filename was missmatch while insert tabel. {job_id}'
#                 body = f"""<pre>
# Dear Team,<br><br>
# \t⚠ Error Accure while insert table filename was missmatch. {job_id}<br><br>
# \t⚠ Filename renamed from {old_ff} to {file_path}. Then insert the value in table.<br><br>
# \tNote: This is an auto generated mail, please do not reply to this.<br><br>
# Thanks & Regards,<br>
# FTP Virtual Assistant<br>
# Transforma Pvt. Ltd.</pre>"""
#                 messages = send_email(subject, body, messages)
#                 messages.append(f'Error: Error Accure in insert value in table {file_path}.')

            ftp_file_path = '/'.join([ftp_dir_path, os.path.basename(file_path)])
            # if file.lower() == f'{job_id.lower()}.{stage_extension}':    #Transfer only pdf, zip only
            try:
                    #MARK: Insert Table
                    cursor.execute("SELECT id, status FROM xperttrack_ftp_upload_history WHERE inw_id = %s AND remote_path = %s", (a_id, ftp_file_path))
                    result = cursor.fetchone()

                    if result:
                        upload_id = result[0]
                        if result[1] == 'SUCCESS':
                            print(f"File Already Transfered from workflow to FTP.")
                            messages.append("Error: File Already Transfered from workflow to FTP.\n")
                            # continue
                            return None
                        print(f'Success: Record already exists in DB for file: "{ftp_file_path}". Updating status.')
                        messages.append(f'Success: Record already exists in DB for file: "{ftp_file_path}". Updating status.')
                        update_file_status(cursor, conn, upload_id, "YET_TO_UPLOAD")
                    else:
                        if os.path.exists(file_path):
                            file_size = os.path.getsize(file_path) / (1024 * 1024)
                        else:
                            file_size = 0
                        #Insert file details in database
                        cursor.execute("INSERT INTO xperttrack_ftp_upload_history (inw_id, dispatch_id, cust_id, jour_id, file_extension, article_id, file_name, local_path, remote_path, local_file_size, status, stage, dept_id, from_mail, to_mail, to_cc, to_bcc, mail_subject, mail_text) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", (a_id, rows2[0][1], customer_id, journ_id, os.path.splitext(file)[-1].replace('.', ''), job_id, file, file_path, ftp_file_path, f'{file_size:.2f}MB', 'YET_TO_UPLOAD', stage, dept_id, disp_from_mail, disp_to_mail, disp_to_cc, disp_to_bcc, disp_mail_subject, disp_mail_text))
                        conn.commit()
                        upload_id = cursor.lastrowid
                        print(f'Success: Entry is created in the xperttrack_ftp_upload_history table for the file "{file_path}".')
                        messages.append(f'Success: entry is created in the xperttrack_ftp_upload_history table for the file "{file_path}"\n.')

                        subject = f'✓ {job_id} Inserted into upload_history table in stage {stage}.'
                        body = f"""<pre>
Dear Team,<br><br>
\t✓ {job_id} Successfully Inserted into ftp_upload_history table for stage {stage}.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                        messages = send_email(subject, body, messages)
            except Exception as e:
                    print(f'Error: Error in Insert value in table {e}')
                    subject = f'⚠ Error in Insert value in table. {job_id}'
                    body = f"""<pre>
Dear Team,<br><br>
\t⚠ Error Accure in insert value in table {file_path} {e}.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                    messages = send_email(subject, body, messages)
                    messages.append(f'Error: Error Accure in insert value in table {file_path}.')
    else:
        print(f'Error: Delivery folder Does not exists in the workflow {wf_directory}.')
        subject = f'⚠ Delivery Folder does not exists in workflow.'
        body = f"""<pre>
Dear Team,<br><br>
\t⚠ The file transfer failed <a href="file://">{wf_directory}</a> Delivery folder does not exists in workflow.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        messages.append(f'Error: Delivery folder Does not exists in the workflow {wf_directory}.')

    log_message(messages)


#MARK: Initial
if __name__ == "__main__":
    a_id = sys.argv[1]         #12220

    main(a_id)