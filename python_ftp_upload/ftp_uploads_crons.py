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
    
#MARK: Email Function
#Send email functionalitys
def send_email(sender_email, sender_password, to_emails, cc_emails, bcc_emails, subject, body, mail_host, mail_port, messages):
    try:
        def parse_emails(emails):
            if not emails:
                return []
            if isinstance(emails, str):
                return [e.strip() for e in emails.split(",") if e.strip()]
            return emails  # already a list

        to_emails = parse_emails(to_emails)
        cc_emails = parse_emails(cc_emails)
        bcc_emails = parse_emails(bcc_emails)

        msg = MIMEMultipart()
        msg["From"] = sender_email
        msg["To"] = ", ".join(to_emails)
        msg["Cc"] = ", ".join(cc_emails)
        if bcc_emails:
            msg["Bcc"] = ", ".join(bcc_emails)
        msg["Subject"] = subject
        msg.attach(MIMEText(body, "html"))

        with smtplib.SMTP(mail_host, mail_port) as server:
            server.starttls()
            server.login(sender_email, sender_password)
            all_recipients = to_emails + cc_emails + bcc_emails
            server.sendmail(sender_email, all_recipients, msg.as_string())
        print(f"Email notification sent for file: successfully.")
        messages.append(f"Success: Email notification sent for file: successfully.")
        return True, messages
    except Exception as e:
        print(f"Failed to send email: {e}")
        messages.append(f"Error: Email notification Failed to sent: {e}.")
        return False, messages

#MARK: FTP Folder
#Function to create nested folders in FTP
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
    
#MARK: DB Query
#Function to Update the file status in databse
def update_file_status(cursor, conn, upload_id, status, transfer_using, retry_count, last_error=None):
    cursor.execute("""
        UPDATE xperttrack_ftp_upload_history 
        SET status=%s, transfer_using=%s, last_error=%s, retry_count=%s
        WHERE id=%s
    """, (status, transfer_using, last_error, retry_count, upload_id))
    conn.commit()

#Function to add message in print, log_message
def add_messages(msg_status, current_msg, msgs):
    print(current_msg)
    msgs.append(f"{msg_status}{current_msg}")
    return msgs

#MARK: Main
def main(retry_count = None):
    messages = []

    #Database Connection Check
    conn, cursor, messages = utils.database_connection(messages)
    if not cursor:
        messages = add_messages('Error: ', f'Database Connection Failed.', messages)
        return log_message(messages), None
    
    #Default email was send or not. Get the status in database "wf_settings" table
    cursor.execute('select set_default_email_auto, set_success_email_auto from wf_settings where set_id = %s', (1, ))
    default_email_status = cursor.fetchone()
    default_email_send = True
    success_email_send = False
    if default_email_status and default_email_status[0] != 'Active':
        default_email_send = False
    if default_email_status and default_email_status[1] == 'Active':
        success_email_send = True

    #Get the uploaded files list in database
    cursor.execute('select * from xperttrack_ftp_upload_history where status not in (%s, %s)', ('SUCCESS', 'PENDING', ))
    rows = cursor.fetchall()    #MARK: Get upload files
    if not rows:
        error_msg3 = f"No articles found in the journal workflow for upload to Xperttrack FTP."
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_send:
            subject = f'Xperttrack FTP Upload Skipped – No Articles Found'
            body = f"""\tNo articles are currently available in the journal workflow for upload to Xperttrack FTP.<br>"""
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
        ftp_file_path = row['remote_path']
        # file_path = row['local_path'].replace('/', config.os_slash)
        file_path = PureWindowsPath(row['local_path'])
        upload_id = row['id']
        #Check the file was present in local system or not
        if not os.path.exists(file_path):
            error_msg5 = f'Transferd file Does not exists in the workflow {file_path}. {row["article_id"]}'
            messages = add_messages('Error: ', error_msg5, messages)
            subject = error_msg5
            body = f"""\t⚠ The file transfer failed <a href="file://">{file_path}</a> file does not exists in workflow.<br>"""
            messages = utils.send_email(subject, body, messages)
            update_file_status(cursor, conn, upload_id, "YET_TO_UPLOAD", 'Crons', retry_count, error_msg5)
            continue

        update_file_status(cursor, conn, upload_id, "PENDING", 'Crons', retry_count)

        #Create FTP Folder and transfered the file
        if ftp_folder_creation(ftp_dir_path, ftp):
            try:        #MARK: Upload Progress
                #Function to upload file from workflow to FTP and calculate the progress in percentage
                def upload_with_progress(cursor, conn, ftp, local_path, remote_path, uplo_id, blocksize=65536):
                    file_size = os.path.getsize(local_path)
                    uploaded = 0
                    last_print = time.time() 

                    def handle_block(block):
                        nonlocal uploaded, last_print
                        uploaded += len(block)
                        percent = (uploaded / file_size) * 100
                        size_in_mp = uploaded / (1024 * 1024)

                        now = time.time()
                        if now - last_print >= 1:   # print only every 30 seconds
                            percent = uploaded / file_size * 100
                            print(f"Upload progress: {percent:.2f}% - uploaded {size_in_mp:.2f} MB.")
                            cursor.execute('update xperttrack_ftp_upload_history set ftp_file_size = %s where id = %s', (f'Size: {size_in_mp:.2f} MB, Percentage: {percent:.2f}%', uplo_id, ))
                            conn.commit()
                            last_print = now
                    #Write the file in FTP
                    with open(local_path, "rb") as f:
                        ftp.storbinary(f"STOR {remote_path}", f, blocksize, callback=handle_block)

                    size_in_mb = file_size / (1024 * 1024)
                    cursor.execute('UPDATE xperttrack_ftp_upload_history SET ftp_file_size=%s WHERE id=%s', (f'Size: {size_in_mb:.2f} MB, Percentage: 100%', uplo_id))
                    conn.commit()
                print(f"File Uploading to FTP: {ftp_file_path}")

                current_datetime = datetime.now()
                cursor.execute('update xperttrack_ftp_upload_history set start_time = %s where id = %s', (current_datetime, upload_id, ))
                upload_status = upload_with_progress(cursor, conn, ftp, file_path, ftp_file_path, upload_id)
                file_size = os.path.getsize(file_path) / (1024 * 1024)
                time_diffrence = datetime.now() - current_datetime

                #Update status in xperttract database
                if row['cust_id'] != 82:
                    conn12, cursor12, messages = database_connection(creden_dict_rows['db_host'], creden_dict_rows['db_username'], creden_dict_rows['db_password'], creden_dict_rows['db_name'], messages)        #Check xperttract database connectivity
                    if not cursor:
                        error_msg7 = f'Xperttract Database Connection Failed. {file_path} - {row["article_id"]}'
                        messages = add_messages('Error: ', error_msg7, messages)
                        subject = f'Xperttract Database Connection Failed - {row["article_id"]}'
                        body = f"""\tXperttract Database Connection Failed in workflow \"{file_path}\"<br>
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
                        error_msg = f'Stage_with was not defined for customer_id: \"{row["cust_id"]}\", stage: \"{row["stage"]}\" in ftp_customer_stage table. {file_path} - {row["article_id"]}'
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
                
                cursor.execute('update xperttrack_ftp_upload_history set status = %s, transfer_using = %s, ftp_file_size = %s, end_time = %s, total_time = %s where id = %s', ('SUCCESS', 'Crons', f'Size: {file_size:.2f} MB, Percentage: 100%', datetime.now(), time_diffrence, upload_id, ))
                conn.commit()

                if success_email_send:
                    subject = f'File Upload to FTP. {row["article_id"]}'     #Success Email
                    body = f"""\tThe file was successfully transferred from <a href="file://{file_path}">{file_path}</a> in workflow to <a href="file://">{ftp_file_path}</a> in FTP.<br>"""
                    messages = utils.send_email(subject, body, messages)

                print(f"Successfully Uploaded file to FTP: {ftp_file_path}\n\n------------------\n\n")
                messages.append(f"Success: Uploaded file to FTP: {ftp_file_path}")

                #Check if current stage is "FP" then upload the file in another FTP
                if row['stage'] == 'FP' and row['cust_id'] != 82:    #MARK: xpertproof FTP
                    try:
                        #xpertproof FTP Connection Check
                        xpertproof_status, xpertproof_ftp, messages = ftp_connection(creden_dict_rows['xpertproof_host'], creden_dict_rows['xpertproof_port'], creden_dict_rows['xpertproof_username'], creden_dict_rows['xpertproof_password'], messages)
                        if not xpertproof_status:
                            messages = add_messages('Error: ', f'xpertproof FTP server Connection Failed.', messages)
                            # log_message(messages)
                            subject = f'xpertproof FTP server Connection Failed. {row["article_id"]}'
                            body = f"""\t⚠ xpertproof FTP server Connection Failed. for \"{creden_dict_rows['xpertproof_host']}\" for \"{file_path}\".<br>"""
                            messages = utils.send_email(subject, body, messages)
                            continue
                        
                        #Get customer name and journal code from "adm_customer_master" and "adm_journal" tabals
                        query = """SELECT acm.cust_name, aj.j_code FROM adm_customer_master acm JOIN adm_journals aj ON aj.j_cust_id = acm.id WHERE acm.id = %s AND aj.j_id = %s"""
                        cursor.execute(query, (row["cust_id"], row["jour_id"]))
                        result = cursor.fetchone()
                        if not result:
                            error_msg4 = f"Missing data for customer ID: {row['cust_id']} and journal ID: {row['jour_id']}"
                            messages = add_messages('Error: ', error_msg4, messages)
                            continue
                        customer_name, journal_code = result
                        xpertproof_dir_path = "/".join(['In', customer_name, journal_code, row['article_id']])

                        #Check the folder exits and upload the file to xpertproof FTP
                        if ftp_folder_creation(xpertproof_dir_path, xpertproof_ftp):
                            xpertproof_ftp_filename = xpertproof_dir_path + '/' + row['file_name']
                            upload_status = upload_with_progress(cursor, conn, xpertproof_ftp, file_path, xpertproof_ftp_filename, upload_id)

                            if success_email_send:
                                subject = f'File Upload to xpertproof FTP for FP stage Files. {row["article_id"]}'     #Success Email
                                body = f"""\tThe file was successfully transferred from <a href="file://{file_path}">{file_path}</a> in workflow to <a href="file://">{xpertproof_ftp_filename}</a> in xpertproof FTP.<br>"""
                                messages = utils.send_email(subject, body, messages)

                            print(f"Successfully Uploaded file to xpertproof FTP: {xpertproof_ftp_filename}\n\n------------------\n\n")
                            messages.append(f"Success: Uploaded file to xpertproof FTP: {xpertproof_ftp_filename}")
                    except Exception as e:
                        error_msg11 = f'Error Uploading File in xpertproof FTP: {xpertproof_ftp_filename} ----- {e}'
                        messages = add_messages('Error: ', error_msg11, messages)
                        subject = f'File Upload to xpertproof FTP Failed. {row["article_id"]}'
                        body = f"""\t⚠ The file transfer failed for uploading file in xpertproof FTP <a href="file:">{xpertproof_ftp_filename}</a> the directory in FTP.<br>"""
                        messages = utils.send_email(subject, body, messages)

                #MARK: Send Email
                #Check email was which way to sending
                cursor.execute('select mail_type, email_id, upload_type from ftp_customer_stage where cust_id = %s and FIND_IN_SET(%s, stage)', (row['cust_id'], row['stage'], ))
                check_email_type = cursor.fetchall()
                emails_detail = False

                if check_email_type:
                    #Get the email details from database and send the email to client
                    cursor.execute('select * from customer_email_settings where id = %s', (check_email_type[0][1], ))
                    emails_detail = cursor.fetchone()
                    if not emails_detail:
                        error_msg6 = f'No email credentials for \"{row["cust_id"]}\" ID in \"customer_email_settings\" tabel. {row["article_id"]}'
                        messages = add_messages('Error: ', error_msg6, messages)
                        subject = error_msg6
                        body = error_msg6 + '</br>'
                        messages = utils.send_email(subject, body, messages)
                        return log_message(messages), None
                    email_columns = [desc[0] for desc in cursor.description]
                    email_dict_rows = dict(zip(email_columns, emails_detail))
                    error_columns = []
                    for mail_error_column in ['to_mail', 'mail_subject', 'mail_text']:
                        if not row[mail_error_column]:
                            error_columns.append(mail_error_column)
                    error_columns = ', '.join(ff for ff in error_columns)
                    if error_columns:
                        error_msg62 = f'\"{error_columns}\" was not present in database please check. {row["article_id"]}'
                        messages = add_messages('Error: ', error_msg62, messages)
                        subject = f'Error in File Upload in FTP. Mail Column Value Missing. {row["article_id"]}'
                        body = '\t' + error_msg62 + '</br>'
                        messages = utils.send_email(subject, body, messages)
                        continue
                
                if check_email_type and check_email_type[0][0] == 'API': # and check_email_type[0][2] != 'FTP'    #and row['cust_id'] != 82
                    print("Using API to send email...")
                    # Build payload
                    data = {
                        "sender": {
                            "name": email_dict_rows.get('email_from_name'),
                            "email": email_dict_rows.get('from_api_email')
                        },
                        "to": [{"email": row['to_mail']}],
                        "subject": row['mail_subject'],
                        "htmlContent": row['mail_text']
                    }

                    if row.get('to_cc'):
                        data["cc"] = [{"email": e.strip()} for e in row['to_cc'].split(',') if e.strip()]
                    if row.get('to_bcc'):
                        data["bcc"] = [{"email": e.strip()} for e in row['to_bcc'].split(',') if e.strip()]

                    # Make request
                    headers = {
                        "accept": "application/json",
                        "api-key": email_dict_rows['email_key'],
                        "content-type": "application/json"
                    }
                    response = requests.post(email_dict_rows['api_endpoint'], headers=headers, data=json.dumps(data))

                    # Handle response
                    if response.status_code == 201:
                        print("Successfully mail send Using API...")
                        cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('SUCCESS', upload_id, ))
                        conn.commit()
                    else:
                        print(f"❌ Failed to send email. HTTP code: {response.status_code}")
                        print("Response:", response.text)
                        error_msg19 = f'Email notification send to client is Failed. {file_path} - {row["article_id"]}'
                        messages = add_messages('Error: ', error_msg19, messages)
                        subject = f'❌ Email notification send to client is Failed in API. {row["article_id"]}'
                        body = f"""\tEmail notification send to client is Failed in API - {row["article_id"]}. <br>{response.status_code}<br>{response.text}.<br>data: {data}<br>"""
                        messages = utils.send_email(subject, body, messages)
                        cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('FAILED', upload_id, ))
                        conn.commit()
                elif check_email_type:
                    from_mail = email_dict_rows['from_email']
                    from_password = email_dict_rows['from_password']
                    mail_host = email_dict_rows['host']
                    mail_port = email_dict_rows['port']
                    email_status, messages = send_email(from_mail, from_password, row['to_mail'], row['to_cc'], row['to_bcc'], row['mail_subject'], row['mail_text'], mail_host, mail_port, messages)
                    if not email_status:
                        email_status, messages = send_email(from_mail, from_password, row['to_mail'], row['to_cc'], row['to_bcc'], row['mail_subject'], row['mail_text'], mail_host, mail_port,  messages)

                    if not email_status:
                        error_msg7 = f'Email notification send to client is Failed. {file_path} - {row["article_id"]}'
                        messages = add_messages('Error: ', error_msg7, messages)
                        subject = f'Email notification send to client is Failed - {row["article_id"]}'
                        body = f"""\tEmail notification send to client is Failed. <br>\"{messages[-2]}\".<br>"""
                        messages = utils.send_email(subject, body, messages)
                        cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('FAILED', upload_id, ))
                        conn.commit()
                    else:
                        cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('SUCCESS', upload_id, ))
                        conn.commit()
                
                #Connect the Xperttract database and update the stage_with column value
                if emails_detail:       #MARK: Xpertract DB
                    #Update the status in xperttract database
                    cursor12.execute('update inw_inward_dtl set stage_with = %s where workflow_id = %s', (db_stage, row['inw_id'], ))
                    conn12.commit()
                    conn12.close()
                    cursor.execute('update xperttrack_ftp_upload_history set db_status = %s, client_status = %s where id = %s', ('SUCCESS', db_stage, upload_id, ))
                    conn.commit()

            except Exception as e:
                error_msg8 = f'Error Uploading File in FTP: {ftp_file_path} ----- {e} - {row["article_id"]}'
                messages = add_messages('Error: ', error_msg8, messages)
                update_file_status(cursor, conn, upload_id, "FAILED", 'Crons', retry_count, str(e))
                subject = f'File Upload to FTP Failed. {row["article_id"]}'
                body = f"""\t⚠ The file transfer failed for: from <a href="file:">{file_path}</a> in workflow to <a href="file://">{ftp_file_path}</a> in FTP{e}.<br><br>"""
                messages = utils.send_email(subject, body, messages)
        else:
            error_msg9 = f'Failed to Create FTP Path: {ftp_dir_path}. {row["article_id"]}'
            messages = add_messages('Error: ', error_msg9, messages)
            update_file_status(cursor, conn, upload_id, "FAILED", 'Crons', error_msg9)
            subject = f'File to Create FTP Path. {row["article_id"]}'
            body = f"""\t⚠ The file transfer failed for creating <a href="file:">{ftp_dir_path}</a> the directory in FTP.<br>"""
            messages = utils.send_email(subject, body, messages)
    
    return log_message(messages), "Success"



#MARK: Start
if __name__ == '__main__':
    dd = main(retry_count=0)
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