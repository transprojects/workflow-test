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
    cursor.execute("select * from ftp_credentials where cust_id = %s", (66 ,))
    crede = cursor.fetchone()
    if not crede:
        error_msg1 = f'⚠ FTP and Database Credential Missing for the cutomer ID: \"66\" present in "ftp_credentials" table in workflow database.'
        messages = add_messages('Error: ', error_msg1, messages)
        subject = f'⚠ FTP and Database credentials Missing in Database for customer ID: 66'
        body = f"""\t{error_msg1}<br>"""
        messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    creden_columns = [desc[0] for desc in cursor.description]
    creden_dict_rows = dict(zip(creden_columns, crede))

    #MARK: xperxpress DB
    conn12, cursor12, messages = database_connection(creden_dict_rows['db_host'], creden_dict_rows['db_username'], creden_dict_rows['db_password'], creden_dict_rows['db_name'], messages)        #Check xperttract database connectivity
    if not cursor12:
        error_msg7 = f'⚠ Xperttract Database Connection Failed.'
        messages = add_messages('Error: ', error_msg7, messages)
        subject = f'⚠ Xperttract Database Connection Failed'
        body = f"""\t⚠ Xperttract Database Connection Failed.<br>
\t<a href="file://">{conn12}</a>.<br>"""
        messages = utils.send_email(subject, body, messages)
        log_message(messages)

    #Get the uploaded files list in database
    # cursor12.execute('select * from submission_tracking_system where file_download_status = %s and mail_sent in (%s)', ('SUCCESS', 'Pending', ))
    cursor.execute('select * from submission_tracking_system where file_download_status = %s and mail_sent in (%s)', ('SUCCESS', 'Pending', ))
    rows = cursor12.fetchall()

    if not rows:
        error_msg3 = f"⚠ No Article present for mail sent to client from Xperttrack FTP (TSP)"
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_status:
            subject = f'⚠ No Article present for mail sent to client from Xperttrack FTP (TSP)'
            body = f"""\t{error_msg3}.<br>"""
            messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    columns = [desc[0] for desc in cursor12.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]
    
    for row in dict_rows:
        #MARK: Mail sent
        #Get the email details from database and sent the email to client
        cursor.execute('select from_email, from_password from email_settings where id = %s', (creden_dict_rows['email_id'], ))
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
        email_status, messages = send_email(from_mail, from_password, row['mail_to'], row['mail_cc'], row['mail_bcc'], row['mail_subject'], row['mail_body'], messages)
        if not email_status:
            email_status, messages = send_email(from_mail, from_password, row['mail_to'], row['mail_cc'], row['mail_bcc'], row['mail_subject'], row['mail_body'], messages)
        
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
