import config, utils
from ftplib import FTP, error_perm
from datetime import datetime, timedelta
import time
import pymysql
import os, sys
import smtplib
import shutil
from pathlib import PureWindowsPath
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart



#MARK: Log
#Function to write log messages
def log_message(messages):
    
    log_file_path = os.path.join('logs', f"log_file_{datetime.now().strftime('%Y-%m-%d')}.txt")
    os.makedirs(os.path.dirname(log_file_path), exist_ok=True)

    with open(log_file_path, "a", encoding='utf-8') as log_file:        
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
        print("Xperttrack Database Connected Successfully.")
        return conn, cursor, messages
    except Exception as e:
        print(f'Error in "database_connection" function. {e}')

        subject = f'Xperttrack Database Connection Failed'
        body = f"""\tDatabase Connection Failed in Xperttrack<br>
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

    try:
        with smtplib.SMTP(mail_host, mail_port) as server:
            server.starttls()
            server.login(sender_email, sender_password)
            all_recipients = to_emails + cc_emails + bcc_emails
            server.sendmail(sender_email, all_recipients, msg.as_string())
        print(f"Email notification sent for file: successfully.")
        messages.append(f"Success: Email notification sent for file: successfully.")
        return True, messages
    except Exception as e:
        print(f"Failed to sent email: {e}")
        messages.append(f"Error: Email notification Failed to sent: {e}.")
        return False, messages

#Function to add message in print, log_message
def add_messages(msg_status, current_msg, msgs):
    print(current_msg)
    msgs.append(f"{msg_status}{current_msg}")
    return msgs

#MARK: Main
def main():
    messages = []

    #Check the Database Connection
    conn, cursor, messages = utils.database_connection(messages)
    if not cursor:
        messages = add_messages('Error: ', f'Database Connection Failed in workflow.', messages)
        return log_message(messages), None
    
    #Default email was send or not. Get the status in database "wf_settings" table
    cursor.execute('select set_download_email_auto from wf_settings where set_id = %s', (1, ))
    default_email_status = cursor.fetchone()
    if default_email_status and default_email_status[0] != 'Active':
        default_email_status = False

    #Check the customer credentials are present in the database using customer ID  #MARK: Customer credentials
    cursor.execute("select * from ftp_credentials where cust_id = %s", (42 ,))
    crede = cursor.fetchone()
    if not crede:
        error_msg1 = f'⚠ FTP and Database Credential Missing for the cutomer ID: \"42\" present in "ftp_credentials" table in workflow database.'
        messages = add_messages('Error: ', error_msg1, messages)
        subject = f'⚠ FTP and Database credentials Missing in Database for customer ID: 42'
        body = f"""\t{error_msg1}<br>"""
        messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    creden_columns = [desc[0] for desc in cursor.description]
    creden_dict_rows = dict(zip(creden_columns, crede))

    #MARK: xperxpress DB
    conn12, cursor12, messages = database_connection(creden_dict_rows['db_host'], creden_dict_rows['db_username'], creden_dict_rows['db_password'], creden_dict_rows['db_name'], messages)        #Check xperttract database connectivity
    if not cursor12:
        error_msg7 = f'⚠ Xperttract Database Connection Failed. {local_path}'
        messages = add_messages('Error: ', error_msg7, messages)
        subject = f'⚠ Xperttract Database Connection Failed'
        body = f"""\t⚠ Xperttract Database Connection Failed in workflow \"{local_path}\"<br>
\t<a href="file://">{conn12}</a>.<br>"""
        messages = utils.send_email(subject, body, messages)
        log_message(messages)

    #Get the uploaded files list in database
    cursor12.execute('select * from submission_tracking_system where file_download_status not in (%s, %s)', ('SUCCESS', 'PENDING', ))
    # cursor.execute('select * from submission_tracking_system where file_download_status in (%s)', ('VERIFY_FAILED', ))
    rows = cursor12.fetchall()    #MARK: Get downloaded files

    if not rows:
        error_msg3 = f"⚠ No Article present to download from Xperttrack FTP (REVISES AND FINALS) (TSP)  to Inhouse Journal Workflow Automation Folder."
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_status:
            subject = f'⚠ No Article present to Download from Xperttrack FTP (REVISES AND FINALS) (TSP)'
            body = f"""\t{error_msg3}.<br>"""
            messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    columns = [desc[0] for desc in cursor12.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]
    
    for row in dict_rows:
        customer_name = row['cloud_file_path'].split('/')[1]
        journal_name = row['cloud_file_path'].split('/')[4]
        
        # #Get customer name and journal code from "adm_customer_master" and "adm_journal" tabals
        # query = """SELECT adm_cus.id, adm_jou.j_id, adm_jou.j_name FROM adm_customer_master adm_cus join adm_journals adm_jou on adm_jou.j_cust_id = adm_cus.id where adm_cus.cust_name = %s and adm_jou.j_code = %s"""
        # cursor.execute(query, (customer_name, journal_name, ))
        # result = cursor.fetchone()
        # if not result:
        #     error_msg4 = f"Missing data for customer ID: {row['cust_id']} and journal ID: {row['jour_id']}"
        #     messages = add_messages('Error: ', error_msg4, messages)
        #     continue
        # cust_id = result[0]
        # journ_id = result[1]
        # journal_title = result[2]
        
        #Check the current customer was active or inactive in database
        if not creden_dict_rows['status'] == 'Active':
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'The current customer \"{row["cust_id"]}\" was not active. Inactive in database \"ftp_credentialas\" table. Please activate it in database.', row['id']))
            error_msg12 = f'⚠ The current customer \"{row["cust_id"]}\" was not active. Inactive in database \"ftp_credentialas\" table. Please activate it in database.'
            messages = add_messages('Error: ', error_msg12, messages)
            return log_message(messages), None

        #Make FTP Connection Check based on each customer
        status, ftp, messages = ftp_connection(creden_dict_rows['host'], creden_dict_rows['port'], creden_dict_rows['username'], creden_dict_rows['password'], messages)
        if not status:
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ FTP was not connected for \"{creden_dict_rows["host"]}\" this host.', row['id']))
            error_msg10 = f'⚠ FTP was not connected for \"{creden_dict_rows["host"]}\" this host.'
            messages = add_messages('Error: ', error_msg10, messages)
            subject = f'⚠ FTP Connectivity Failed for: \"{creden_dict_rows["host"]}\"'
            body = f"""\t{error_msg10}<br>"""
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        
        ftp_file_path = '/'.join(row['cloud_file_path'].split('/')[2:])
        local_path = os.path.join(row['workflow_file_path'], os.path.basename(ftp_file_path))
        local_path = PureWindowsPath(local_path)

        try:    #MARK: Download file
            def download_with_progress(ftp, local_path, remote_file_path, messages, blocksize=65536):
                try:
                    directory, filename = os.path.split(remote_file_path)
                    ftp.cwd(directory)
                except Exception as e:
                    cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ Folder does not exists in FTP. {directory}', row['id']))
                    messages = add_messages('Error: ', f'⚠ Folder does not exists in FTP. {directory}', messages)
                    log_message(messages)
                    subject = f'⚠ Folder does not exists in FTP.'
                    body = f"""\t⚠ Folder does not exists in FTP. for \"{directory}\".<br>"""
                    messages = utils.send_email(subject, body, messages)
                    return False, messages

                files = ftp.nlst()
                if filename not in files:
                    cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ File does not exist on FTP. {remote_file_path}', row['id']))
                    messages = add_messages('Error: ', f'⚠ File does not exist on FTP. {remote_file_path}', messages)
                    log_message(messages)
                    subject = f'⚠ File does not exist on FTP.'
                    body = f"""\t⚠ File does not exist on FTP. for \"{ftp_file_path}\".<br>"""
                    messages = utils.send_email(subject, body, messages)
                    return False, messages
                
                cursor12.execute("""UPDATE submission_tracking_system SET file_download_status=%s, start_time=%s WHERE id=%s""", ('PENDING', datetime.now(), row['id']))
                conn12.commit()

                current_datetime = datetime.now()
                file_size = ftp.size(filename)
                downloaded = 0
                last_print = time.time()

                def handle_block(block):
                    nonlocal downloaded, last_print
                    f.write(block)
                    downloaded += len(block)
                    now = time.time()
                    if now - last_print >= 1:  # print every 1 second
                        percent = (downloaded / file_size) * 100
                        size_in_mb = downloaded / (1024 * 1024)
                        print(f"Download progress: {percent:.2f}% - {size_in_mb:.2f} MB downloaded")
                        cursor12.execute('update submission_tracking_system set download_zipsize = %s where id = %s', (f'Size: {size_in_mb:.2f} MB, Percentage: {percent:.2f}%', row["id"], ))
                        conn12.commit()
                        last_print = now

                # Ensure local folder exists
                os.makedirs(os.path.dirname(local_path), exist_ok=True)

                with open(local_path, 'wb') as f:
                    ftp.retrbinary(f'RETR {filename}', handle_block, blocksize=blocksize)

                time_diffrence = datetime.now() - current_datetime
                size_in_mb = file_size / (1024 * 1024)
                cursor12.execute('UPDATE submission_tracking_system SET download_zipsize=%s, total_time_taken=%s, file_download_status=%s, end_time=%s WHERE id=%s', (f'Size: {size_in_mb:.2f} MB, Percentage: 100%', time_diffrence, 'SUCCESS', datetime.now(), row['id']))
                conn12.commit()

                print(f"Download complete: {local_path} ({file_size / (1024*1024):.2f} MB)")
                return True, messages

            print(f"File download started from FTP: {ftp_file_path}")
            download_result, messages = download_with_progress(ftp, local_path, ftp_file_path, messages)
            if not download_result:
                continue

            #Success message
            subject = f'✓ File {row["article_id"]} Download from FTP.'
            body = f"""\t✓ The file was successfully download from <a href="file://{ftp_file_path}">{ftp_file_path}</a> in FTP to <a href="file://">{local_path}</a> in workflow.<br>"""
            messages = utils.send_email(subject, body, messages)

            print(f"Successfully Download file from FTP: {ftp_file_path}")
            messages.append(f"Success: Download file from FTP: {ftp_file_path}")
            
        except Exception as e:
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s, file_download_status=%s WHERE id=%s""", (f'⚠ Error occur in downloading file {ftp_file_path} to {local_path} for article ID {row["article_id"]} {e}', 'Error', row['id']))
            messages = add_messages('Error: ', f'⚠ Error occur in downloading file {ftp_file_path} to {local_path} for article ID {row["article_id"]} {e}', messages)
            log_message(messages)
            subject = f'⚠ Error occur in downloading file {row["article_id"]}'
            body = f"""\t⚠ Error occur in downloading file. from {ftp_file_path} to \"{local_path}\".<br>{e}"""
            messages = utils.send_email(subject, body, messages)
            continue

        rev_count = ''.join(num if num.isdigit() else '' for num in row['stage'])
        rev_type = ''.join(num if not num.isdigit() else '' for num in row['stage'])

        #Get the due date count from the database
        cursor.execute('select due_days, wf_id, skip_dept, add_dept, d_email_id from ftp_customer_stage where cust_id=%s AND FIND_IN_SET(%s, stage)', (row['cust_id'], f'{row["stage"]}'))  #MARK: Due days
        due_date_count = cursor.fetchone()
        if not due_date_count:
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ Due days count was not found in ftp_customer_stage table in workflow for {row["article_id"]}.', row['id']))
            error15 = f'⚠ Due days count was not found in ftp_customer_stage table in workflow for {row["article_id"]}.'
            messages = add_messages('Error: ', {error15}, messages)
            log_message(messages)
            subject = f'⚠ Due days count missing in ftp_customer_stage table in workflow database.'
            body = f"""\t '{error15}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue

        #Get the holidays list from the database
        cursor.execute('select wf_hdate from wf_holidays where wf_status="0"')
        holidays_list = cursor.fetchall()
        if not holidays_list:
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ Holidays table was not present in the workflow database.', row['id']))
            error_msg13 = f'⚠ Holidays table was not present in the workflow database.'
            messages = add_messages('Error: ', error_msg13, messages)
            log_message(messages)
            subject = f'⚠ Holidays table does not exists in the workflow database.'
            body = f"""\t{error_msg13}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue
        
        holiday_dates = {d[0] for d in holidays_list}

        due_date = datetime.now()
        added_days = 0
        while added_days < due_date_count[0]:
            due_date += timedelta(days=1)
            if due_date.weekday() == 6:  # Sunday
                continue
            if due_date.date() in holiday_dates:
                continue
            added_days += 1

        #Check the table for already the file present in the database
        cursor.execute('select * from inw_revisions_dtl where inw_id=%s and revision_count=%s and type=%s', (row['workflow_id'], rev_count, rev_type))
        already_exists = cursor.fetchall()
        if already_exists:
            error_msg14 = f'⚠ The file was already present in the database for {row["workflow_id"]} and {row["stage"]}. {row["id"]}. {row["article_id"]}'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg14, row['id']))
            messages = add_messages('Error: ', error_msg14, messages)
            log_message(messages)
            subject = f'⚠ File already present in the database. {row["article_id"]}'
            body = f"""\t{error_msg14}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue
        
        #Insert the new entry in database for file download
        cursor.execute('insert into inw_revisions_dtl (inw_id, correction_pages, revision_count, received_date, due_date, file_name, created_by, created_on, type, comments) values (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', (row['workflow_id'], "0", rev_count, datetime.now(), due_date, row['workflow_file_path'], "54", datetime.now(), rev_type, "No Commends"))
        conn.commit()
        print(f'SUCCESS: Insert value in inw_revisions_dtl table successfully. for {row["id"]}')

        #Update the table before check it is present or not
        cursor.execute('select * from inw_inward_dtl where id=%s', (row['workflow_id'], ))
        get_present = cursor.fetchone()
        if not get_present:
            error_msg15 = f'⚠ {row["workflow_id"]} was does not present in the inw_inward_dtl table. for {row["article_id"]}'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg15, row['id']))
            messages = add_messages('Error: ', error_msg15, messages)
            log_message(messages)
            subject = f'⚠ {row["workflow"]} value does not exists in the workflow database. for {row["article_id"]}'
            body = f"""\t{error_msg15}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue

        #MARK: Query updates
        journ_tab_key = f'j_{rev_type.lower()}_wf'
        
        cursor.execute(f"select adm_jour.{journ_tab_key} as journal_value, adm_wf_mas.wf_id, adm_wf_mas.wf_code, adm_dept_mas.dept_name from adm_journals adm_jour join adm_workflow_master adm_wf_mas on adm_wf_mas.wf_name = adm_jour.{journ_tab_key} join adm_dept_master adm_dept_mas on adm_dept_mas.dept_code = SUBSTRING_INDEX(adm_wf_mas.wf_code, ',', 1) where adm_jour.j_id=%s", (row['journal_id'], ))
        get_journal_table = cursor.fetchone()
        journal_value, wf_id, wf_code, dept_name = get_journal_table
        # print(journal_value, wf_id, wf_code, dept_name)
        
        if due_date_count[1]:
            wf_id = due_date_count[1]
        
        if not get_journal_table:
            error_msg16 = f'⚠ {row["journal_id"]} was not present in adm_journals table in workflow database while updating inw_inward_dtl table. for {row["article_id"]}'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg16, row['id']))
            messages = add_messages('Error: ', error_msg16, messages)
            log_message(messages)
            subject = f'⚠ {row["journal_id"]} not present in adm_journals table in workflow database. for {row["article_id"]}'
            body = f"""\t{error_msg16}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue

        cursor.execute('update inw_inward_dtl set stage =%s, prev_stage =%s, status=%s, department_id=%s, wf_id=%s where id=%s', (row['stage'], row['stage_prev'], dept_name, wf_code.split(',')[0], wf_id, row['workflow_id'], ))
        conn.commit()
        print(f'SUCCESS: Update value in inw_inward_dtl table successfully.')

        prev_stage_path = row['workflow_file_path'].replace(row['stage'], row['stage_prev'])
        prev_stage_path = prev_stage_path.replace('downloads', 'production')
        if not os.path.exists(prev_stage_path):
            error_msg17 = f'⚠ {prev_stage_path} is does not exists in the workflow. previou stage folder not present in workflow. So does not change files from previous stage to current stage.'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg17, row['id']))
            messages = add_messages('Error: ', error_msg17, messages)
            log_message(messages)
            subject = f'⚠ {prev_stage_path} Path does not exists in the workflow. for {row["article_id"]}'
            body = f"""\t{error_msg17}<br>"""
            messages = utils.send_email(subject, body, messages)
            # continue
        else:
            #MARK: Copy files
            #Get the add_folder, skip_folders to copy the files from previous stage to current stage
            folder_list_to_change_all = []
            for dept_codes in (due_date_count[2], due_date_count[3]):
                if dept_codes:
                    sub_folder_list = []
                    for d_co in dept_codes.split(','):
                        cursor.execute("SELECT dept_name FROM adm_dept_master WHERE dept_code = %s", (d_co,))
                        result1 = cursor.fetchall()
                        if result1:
                            sub_folder_list.append(result1[0][0])
                    folder_list_to_change_all.append(sub_folder_list)
            # print(folder_list_to_change_all)

            #Copy files from previous stage to current stage
            for root, dirs, files in os.walk(prev_stage_path):
                rel_path = os.path.relpath(root, prev_stage_path)
                if rel_path in [folder_list_to_change_all[0]]:
                    continue
                if any(nuw in rel_path for nuw in folder_list_to_change_all[1]):
                    dest_root = os.path.join(row['workflow_file_path'], rel_path)
                    dest_root = dest_root.replace('downloads', 'production')
                    dest_root = PureWindowsPath(dest_root)
                    os.makedirs(dest_root, exist_ok=True)

                    #Copy files
                    for file in files:
                        src_file = os.path.join(root, file)
                        dest_file = os.path.join(dest_root, file)
                        try:
                            if os.path.exists(dest_file):
                                os.remove(dest_file)
                            shutil.copy2(src_file, dest_file)
                        except Exception as e:
                            print(f'Error while copy files. {e}')
            print(f'SUCCESS: File copy from previous stage to curent stage successfully.')
    
        #MARK: Mail sent
        #Get the email details from database and sent the email to client
        cursor.execute('select from_email, from_password, host, port from customer_email_settings where id = %s', (due_date_count[4], ))
        emails_detail = cursor.fetchall()
        if not emails_detail:
            error_msg6 = f'⚠ No email credentials for \"{creden_dict_rows["email_id"]}\" ID in \"email_settings\" tabel.'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg6, row['id']))
            messages = add_messages('Error: ', error_msg6, messages)
            subject = error_msg6
            body = error_msg6 + '</br>'
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        
        from_mail = emails_detail[0][0]
        from_password = emails_detail[0][1]
        mail_host = emails_detail[0][2]
        mail_port = emails_detail[0][3]
        email_status, messages = send_email(from_mail, from_password, row['mail_to'], row['mail_cc'], row['mail_bcc'], row['mail_subject'], row['mail_body'], mail_host, mail_port, messages)
        if not email_status:
            email_status, messages = send_email(from_mail, from_password, row['mail_to'], row['mail_cc'], row['mail_bcc'], row['mail_subject'], row['mail_body'], mail_host, mail_port, messages)

        if not email_status:
            error_msg7 = f'⚠ Email notification sent to client is Failed for {row["article_id"]}'
            cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (error_msg7, row['id']))
            messages = add_messages('Error: ', error_msg7, messages)
            subject = error_msg7
            body = f"""\t⚠ Email notification sent to client is Failed. <br>\"{messages[-2]}\".<br>"""
            messages = utils.send_email(subject, body, messages)
            cursor12.execute('update submission_tracking_system set mail_sent = %s where id = %s', ('FAILED', row['id'], ))
            conn12.commit()
        else:
            cursor12.execute('update submission_tracking_system set mail_sent = %s where id = %s', ('Sent', row['id'], ))
            conn12.commit()
        print(f'SUCCESS: Email sent successfully for client.\n\n------------------\n\n')

    return log_message(messages), "Success"



#MARK: Start
if __name__ == '__main__':
    dd = main()
    # print(dd)
