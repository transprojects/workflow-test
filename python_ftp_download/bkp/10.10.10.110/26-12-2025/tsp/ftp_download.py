import config, utils
from ftplib import FTP, error_perm
from datetime import datetime, timedelta
import time
import pymysql
import os, sys
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart



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
def send_email(sender_email, sender_password, to_emails, cc_emails, bcc_emails, subject, body, messages):
    
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
        with smtplib.SMTP(config.SMTP_SERVER, config.SMTP_PORT) as server:
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
        messages = add_messages('Error: ', f'Database Connection Failed.', messages)
        return log_message(messages), None
    
    #Default email was send or not. Get the status in database "wf_settings" table
    cursor.execute('select set_download_email_auto from wf_settings where set_id = %s', (1, ))
    default_email_status = cursor.fetchone()
    if default_email_status and default_email_status[0] != 'Active':
        default_email_status = False

    #Check the credentials are present in the database  #MARK: Customer credentials
    cursor.execute("select * from ftp_credentials where cust_id = %s", (42 ,))
    crede = cursor.fetchone()
    if not crede:
        error_msg1 = f'No FTP and Database Credential for the cutomer ID: \"42\"present in "ftp_credentials" table.'
        messages = add_messages('Error: ', error_msg1, messages)
        subject = f'FTP and Database credentials Missing in Database for customer ID: 42'
        body = f"""\t{error_msg1}<br>"""
        messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    creden_columns = [desc[0] for desc in cursor.description]
    creden_dict_rows = dict(zip(creden_columns, crede))

    #MARK: xperxpress DB
    conn12, cursor12, messages = database_connection(creden_dict_rows['db_host'], creden_dict_rows['db_username'], creden_dict_rows['db_password'], creden_dict_rows['db_name'], messages)        #Check xperttract database connectivity
    if not cursor:
        error_msg7 = f'Xperttract Database Connection Failed. {local_path}'
        messages = add_messages('Error: ', error_msg7, messages)
        subject = f'Xperttract Database Connection Failed'
        body = f"""\tXperttract Database Connection Failed in workflow \"{local_path}\"<br>
\t<a href="file://">{conn}</a>.<br>"""
        messages = utils.send_email(subject, body, messages)
        log_message(messages)

    #Get the uploaded files list in database
    cursor12.execute('select * from article_tracking_info where file_download_status not in (%s, %s)', ('SUCCESS', 'PENDING', ))
    # cursor12.execute('select * from article_tracking_info where file_download_status in (%s)', ('VERIFY_FAILED', ))
    rows = cursor12.fetchall()    #MARK: Get downloaded files
    if not rows:
        error_msg3 = f"No Article present to tranfer from Xperttrack FTP (TSP)  to Inhouse Journal Workflow Automation Folder."
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_status:
            subject = f'No Article present to Download from Xperttrack FTP (TSP)'
            body = f"""\tNo Article present to tranfer from Xperttrack FTP (TSP)  to Inhouse Journal Workflow Automation Folder.<br>"""
            messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    columns = [desc[0] for desc in cursor12.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]
    
    for row in dict_rows:

        customer_name = row['godaddy_article_path'].split('/')[1]
        journal_name = row['godaddy_article_path'].split('/')[4]
        
        #Get customer name and journal code from "adm_customer_master" and "adm_journal" tabals
        query = """SELECT adm_cus.id, adm_jou.j_id, adm_jou.j_name FROM adm_customer_master adm_cus join adm_journals adm_jou on adm_jou.j_cust_id = adm_cus.id where adm_cus.cust_name = %s and adm_jou.j_code = %s"""
        cursor.execute(query, (customer_name, journal_name, ))
        result = cursor.fetchone()
        if not result:
            error_msg4 = f"Missing data for customer ID: {row['cust_id']} and journal ID: {row['jour_id']}"
            messages = add_messages('Error: ', error_msg4, messages)
            continue
        cust_id = result[0]
        journ_id = result[1]
        journal_title = result[2]
        
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
            subject = f'FTP Connectivity Failed: \"{creden_dict_rows["host"]}\"'
            body = f"""\t{error_msg10}<br>"""
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        
        ftp_file_path = '/'.join(row['godaddy_article_path'].split('/')[2:])
        local_path = os.path.join(config.local_downloaded_path, os.path.basename(ftp_file_path))

        try:    #MARK: Download file
            def download_with_progress(ftp, local_path, remote_file_path, blocksize=65536):
                try:
                    directory, filename = os.path.split(remote_file_path)
                    ftp.cwd(directory)
                except Exception as e:
                    messages = add_messages('Error: ', f'Folder doesnot exists in FTP. {directory}', messages)
                    log_message(messages)
                    subject = f'Folder does not exists in FTP.'
                    body = f"""\t⚠ Folder does not exists in FTP. for \"{directory}\".<br>"""
                    messages = utils.send_email(subject, body, messages)
                    return False

                files = ftp.nlst()
                if filename not in files:
                    messages = add_messages('Error: ', f'File does not exist on FTP. {remote_file_path}', messages)
                    log_message(messages)
                    subject = f'File does not exist on FTP.'
                    body = f"""\t⚠ File does not exist on FTP. for \"{ftp_file_path}\".<br>"""
                    messages = utils.send_email(subject, body, messages)
                    return False
                
                cursor12.execute("""UPDATE article_tracking_info SET file_download_status=%s, cron_starttime=%s WHERE id=%s""", ('PENDING', datetime.now(), row['id']))
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
                        cursor12.execute('update article_tracking_info set download_zipsize = %s where id = %s', (f'Size: {size_in_mb:.2f} MB, Percentage: {percent:.2f}%', row["id"], ))
                        conn12.commit()
                        last_print = now

                # Ensure local folder exists
                os.makedirs(os.path.dirname(local_path), exist_ok=True)

                with open(local_path, 'wb') as f:
                    ftp.retrbinary(f'RETR {filename}', handle_block, blocksize=blocksize)

                time_diffrence = datetime.now() - current_datetime
                size_in_mb = file_size / (1024 * 1024)
                cursor12.execute('UPDATE article_tracking_info SET download_zipsize=%s, total_time=%s, file_download_status=%s, cron_endtime=%s WHERE id=%s', (f'Size: {size_in_mb:.2f} MB, Percentage: 100%', time_diffrence, 'SUCCESS', datetime.now(), row['id']))
                conn12.commit()

                print(f"Download complete: {local_path} ({file_size / (1024*1024):.2f} MB)")
                return True

            download_result = download_with_progress(ftp, local_path, ftp_file_path)
            if not download_result:
                continue

            #Success message
            subject = f'File Download from Xperttrack FTP (TSP)  to Journal Workflow Automation Folder.'
            body = f"""\t⚠ The file was successfully download from <a href="file://{ftp_file_path}">{ftp_file_path}</a> in FTP to <a href="file://">{local_path}</a> in workflow.<br>"""
            messages = utils.send_email(subject, body, messages)

            print(f"Successfully Download file from FTP: {ftp_file_path}\n\n------------------\n\n")
            messages.append(f"Success: Download file from FTP: {ftp_file_path}")
            
        except Exception as e:
            messages = add_messages('Error: ', f'Error accure in downloading file {ftp_file_path} to {local_path} {e}', messages)
            log_message(messages)
            subject = f'Error accure in downloading file.'
            body = f"""\t⚠ Error accure in downloading file. from {ftp_file_path} to \"{local_path}\".<br>"""
            messages = utils.send_email(subject, body, messages)
            continue
        file_size = os.path.getsize(local_path)

        cursor.execute('select * from inw_inward_dtl where job_id=%s', (row['pub_id']))
        check_already = cursor.fetchone()
        if check_already is None:

            # List of holidays as strings
            holidays = [
                '2025-01-01', '2025-01-14', '2025-01-15', '2025-01-26',
                '2025-04-14', '2025-05-01', '2025-08-15', '2025-08-27',
                '2025-10-01', '2025-10-02', '2025-10-20', '2025-12-31'
            ]

            # Convert holiday strings to datetime.date objects
            holiday_dates = {datetime.strptime(d, '%Y-%m-%d').date() for d in holidays}

            due_date = datetime.now()
            added_days = 0
            while added_days < 3:
                due_date += timedelta(days=1)
                if due_date.weekday() == 6:  # Sunday
                    continue
                if due_date.date() in holiday_dates:
                    continue
                added_days += 1

            workflow_direc = 'E:/New_Workflow_Files/JOURNAL/production/JOURNALS/' + customer_name + f'/{journal_name}/'

            #MARK: Update Query
            cursor.execute('insert into inw_inward_dtl (cust_id, j_id, job_id, journal_title, article_title, recv_dt, accpt_dt, due_dt, pub_id, created_dt, stage, wf_id, department_id, status, work_dir, volume_folder, inw_type, approved_by, zip_size, fig) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', (cust_id, journ_id, row["pub_id"], journal_title,row["pub_id"], datetime.now(), datetime.now(), due_date, row["pub_id"], datetime.now(), "FP", 145, 1, "CE", workflow_direc, "vol00000", "NULL", 54, file_size, 0))
            conn.commit()
            pri_id = cursor.lastrowid

            cursor12.execute('select * from inw_inward_dtl where pub_id = %s', (row["pub_id"]))
            check_exists = cursor12.fetchone()
            if not check_exists:
                messages = add_messages('Error: ', f'File was doe not present in the xpertxpress database. {local_path}', messages)
                log_message(messages)
                subject = f'File does not present in xpertxpress Database.'
                body = f"""\t⚠ File ws does not present in xpertxpress database. for \"{local_path}\".<br>"""
                messages = utils.send_email(subject, body, messages)
            else:
                cursor12.execute('update inw_inward_dtl set workflow_id=%s, zip_size=%s where pub_id=%s', (pri_id, file_size, row["pub_id"]))
                conn12.commit()
        else:
            messages = add_messages('Error: ', f'File was already present in the workflow database. {local_path}', messages)
            log_message(messages)
            subject = f'File already present in workflow Database.'
            body = f"""\t⚠ File already present in workflow database. for \"{local_path}\".<br>"""
            messages = utils.send_email(subject, body, messages)

    return log_message(messages), "Success"



#MARK: Start
if __name__ == '__main__':
    dd = main()
    # print(dd)
