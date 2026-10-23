import config, utils
from ftplib import FTP, error_perm
from datetime import datetime
import time
import pymysql
import os, sys, requests, json
import smtplib
from pathlib import PureWindowsPath
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart



#MARK: Log
#Function to write log messages
def log_message(messages):
    
    log_file_path = os.path.join("logs", f"log_file_{datetime.now().strftime('%Y-%m-%d')}.txt")
    os.makedirs(os.path.dirname(log_file_path), exist_ok=True)

    with open(log_file_path, "a") as log_file:        
        log_file.write(f"\n\nProgram Run at: {datetime.now()}\n=========================================\n\n")
        for message in messages:
            log_file.write(f"{message}\n")
            log_file.write("-----------------------------------------------------------------------------------------\n")

#MARK: Database
#Function to connect the databse
def database_connection(db_host, db_user, db_password, db_database, messages):
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = db_host, 
            user = db_user, 
            password = db_password, 
            database = db_database
        )
        cursor = conn.cursor()
        print("Database Connected Successfully.")
        return conn, cursor, messages
    except Exception as e:
        print(f'Error in "database_connection" function. {e}')

        subject = f'Database Connection Failed'
        body = f"""\tDatabase Connection Failed in workflow<br>
\t<a href="file://">{e}</a>.<br>"""
        messages = utils.send_email(subject, body, messages)

        return None, None, messages

#MARK: FTP
#Function to creation connection in FTP
def ftp_connection(ftp_host, ftp_port, ftp_username, ftp_password, messages):
    try:
        ftp = FTP()
        ftp.connect(ftp_host, int(ftp_port))
        ftp.login(ftp_username, ftp_password)
        print("Connected to FTP server.")
        return True, ftp, messages
    except Exception as e:
        print(f'Error in "ftp_connection" function. {e}')
        subject = f'FTP Connection Failed'
        body = f"""\tFTP Connection Failed<br>
\t<a href="file://">{e}</a>.<br>"""
        messages = utils.send_email(subject, body, messages)
        return False, None, messages
    
#Function to add message in print, log_message
def add_messages(msg_status, current_msg, msgs):
    print(current_msg)
    msgs.append(f"{msg_status}{current_msg}")
    return msgs

#MARK: Main
def main():
    messages = []

    #Database Connection Check
    conn, cursor, messages = utils.database_connection(messages)
    if not cursor:
        messages = add_messages('Error: ', f'Workflow Database Connection Failed.', messages)
        return log_message(messages), None
    
    #Default email was send or not. Get the status in database "wf_settings" table
    cursor.execute('select set_default_email_auto from wf_settings where set_id = %s', (1, ))
    default_email_status = cursor.fetchone()
    default_email_send = True
    if default_email_status and default_email_status[0] != 'Active':
        default_email_send = False

    #Get the uploaded files list in database
    cursor.execute('select * from xperttrack_ftp_upload_history where client_status = %s and db_status not in (%s) and status = "SUCCESS" and mail_send = "Success"', ('TRANSFORMA', 'SUCCESS', ))
    rows = cursor.fetchall()    #MARK: Get upload files
    if not rows:
        error_msg3 = f"No articles found in the journal workflow for update the status in Xperttrack DB."
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_send:
            subject = f'Xperttrack Database Upload Skipped – No Articles Found'
            body = f"""\tNo articles found in the journal workflow for update the status in Xperttrack DB.<br>"""
            messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    columns = [desc[0] for desc in cursor.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]

    for row in dict_rows:
        #Check the credentials are present in the database  #MARK: Customer credentials
        cursor.execute("select * from ftp_credentials where cust_id = %s", (row['cust_id'] ,))
        crede = cursor.fetchone()
        if not crede:
            error_msg1 = f'No FTP and Database Credential for the cutomer ID: \"{row["cust_id"]}\"present in "ftp_credentials" table.'
            messages = add_messages('Error: ', error_msg1, messages)
            subject = f'FTP and Database credentials Missing in Database for customer ID: {row["cust_id"]} - {row["article_id"]}'
            body = f"""\t{error_msg1}<br>"""
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        creden_columns = [desc[0] for desc in cursor.description]
        creden_dict_rows = dict(zip(creden_columns, crede))

        #Check the current customer was active or inactive in database
        if not creden_dict_rows['status'] == 'Active':
            error_msg12 = f'The current customer \"{row["cust_id"]}\" was not active. Inactive in database \"ftp_credentialas\" table. Please activate it in database.'
            messages = add_messages('Error: ', error_msg12, messages)
            return log_message(messages), None

        #FTP Connection Check for each customer based
        status, ftp, messages = ftp_connection(creden_dict_rows['host'], creden_dict_rows['port'], creden_dict_rows['username'], creden_dict_rows['password'], messages)
        if not status:
            messages = add_messages('Error: ', f'FTP server Connection Failed.', messages)
            error_msg10 = f'FTP was not connected for \"{creden_dict_rows["host"]}\" this host.'
            messages = add_messages('Error: ', error_msg10, messages)
            subject = f'FTP Connectivity Failed: \"{creden_dict_rows["host"]}\" - {row["article_id"]}'
            body = f"""\t{error_msg10}<br>"""
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        
        ftp_dir_path = os.path.dirname(row['remote_path'])
        ftp_file_path = (row['remote_path'])
        upload_id = row['id']

        ftp.cwd(ftp_dir_path)

        files = ftp.nlst()  # returns list of filenames
        if os.path.basename(ftp_file_path) not in files:
            print(f"File does not exists on FTP. {row['remote_path']}")
        
        try:
            #MARK: Xpertract DB
            #Update status in xperttract database
            conn12, cursor12, messages = database_connection(creden_dict_rows['db_host'], creden_dict_rows['db_username'], creden_dict_rows['db_password'], creden_dict_rows['db_name'], messages)        #Check xperttract database connectivity
            if not cursor:
                error_msg7 = f'Xperttract Database Connection Failed. - {row["article_id"]}'
                messages = add_messages('Error: ', error_msg7, messages)
                subject = f'Xperttract Database Connection Failed - {row["article_id"]}'
                body = f"""\tXperttract Database Connection Failed in workflow<br>
    \t<a href="file://">{conn}</a>.<br>"""
                messages = utils.send_email(subject, body, messages)
                continue
            
            #Get the final updated stages in table and update in xperttract database
            db_update_stage = False                    
            cursor.execute('select stage_with, stage from ftp_customer_stage where cust_id = %s', (row['cust_id'], ))
            updated_stage = cursor.fetchall()
            for fol_path in updated_stage:
                stage_split = fol_path[1].split(',')
                stage_split = [sta.strip() for sta in stage_split]
                if row['stage'] in stage_split:
                    db_update_stage = True
                    db_stage = fol_path[0]
                    break

            if not db_update_stage:
                error_msg = f'Stage_with was not defined for customer_id: \"{row["cust_id"]}\", stage: \"{row["stage"]}\" in ftp_customer_stage table. - {row["article_id"]}'
                messages = add_messages('Error: ', error_msg, messages)
                subject = f'Stage_with Missing in "ftp_customer_stage" Table. {row["article_id"]}'
                body = f"""\t⚠ {error_msg}<br>"""
                messages = utils.send_email(subject, body, messages)
                messages.append(f'Error: stage_with was not defined for customer_id: \"{row["cust_id"]}\", stage: \"{row["stage"]}\" in ftp_customer_stage table.')
                continue
            
            #Update the status in xperttract database
            cursor12.execute('update inw_inward_dtl set stage_with = %s where workflow_id = %s', (db_stage, row['inw_id'], ))
            conn12.commit()
            cursor.execute('update xperttrack_ftp_upload_history set db_status = %s, client_status = %s where id = %s', ('SUCCESS', db_stage, upload_id, ))
            conn.commit()

        except Exception as e:
            error_msg8 = f'Error Updating status in Xperttrack Database: ----- {e} - {row["article_id"]}'
            messages = add_messages('Error: ', error_msg8, messages)
            subject = f'⚠ Error in Database status update. {row["article_id"]}'
            body = f"""\t⚠ Error in Updating status in Xpertract Database The file transfer failed for: from <a href="file:"></a> in workflow to <a href="file://">{ftp_file_path}</a> in FTP{e}.<br><br>"""
            messages = utils.send_email(subject, body, messages)
        
    return log_message(messages), "Success"


#MARK: Start
if __name__ == '__main__':
    dd = main()
    # print(dd)





# import os
# import time
# import shutil

# def move_with_progress(src_path, dest_path, blocksize=65536):
#     file_size = os.path.getsize(src_path)  # total size in bytes
#     uploaded = 0
#     last_print = time.time()

#     with open(src_path, "rb") as src, open(dest_path, "wb") as dst:
#         while True:
#             block = src.read(blocksize)
#             if not block:
#                 break
#             dst.write(block)

#             uploaded += len(block)
#             percent = (uploaded / file_size) * 100
#             size_in_mp = uploaded / (1024 * 1024)

#             now = time.time()
#             if now - last_print >= 1:  # print every 1 second
#                 print(f"Move progress: {percent:.2f}% - uploaded {size_in_mp:.2f} MB.")
#                 last_print = now

#     # After copy is complete, remove original
#     os.remove(src_path)
#     print(f"\n✅ Move complete: {dest_path}")




# pm@transforma.in
# Fay90556