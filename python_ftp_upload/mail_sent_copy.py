import config, utils
from ftplib import FTP, error_perm
from datetime import datetime, timedelta
import time
import pymysql
import os, sys
import smtplib
import shutil, requests, json
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

    #Get the uploaded files list in database
    cursor.execute('select * from xperttrack_ftp_upload_history where status = %s and mail_send in (%s, %s)', ('SUCCESS', 'FAILED', 'PENDING', ))
    rows = cursor.fetchall()

    if not rows:
        error_msg3 = f"⚠ No Article present for mail sent to client from Xperttrack FTP (TSP)"
        messages = add_messages('Error: ', error_msg3, messages)
        if default_email_status:
            subject = f'⚠ No Article present for mail sent to client from Xperttrack FTP (TSP)'
            body = f"""\t{error_msg3}.<br>"""
            messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    columns = [desc[0] for desc in cursor.description]
    dict_rows = [dict(zip(columns, row)) for row in rows]
    
    for row in dict_rows:
        #MARK: Mail sent
        #Check email was which way to sending
        cursor.execute('select mail_type, email_id from ftp_customer_stage where cust_id = %s and FIND_IN_SET(%s, stage)', (row['cust_id'], row['stage'], ))
        check_email_type = cursor.fetchall()

        if not check_email_type:
            error_msg9 = f'No email ID for customer ID = {row["cust_id"]} and stage = {row["stage"]}\"ftp_customer_stage\" tabel. - {row["article_id"]}'
            messages = add_messages('Error: ', error_msg9, messages)
            subject = error_msg9
            body = error_msg9 + '</br>'
            messages = utils.send_email(subject, body, messages)
            continue

        #Get the email details from database and send the email to client
        cursor.execute('select * from customer_email_settings where id = %s', (check_email_type[0][1], ))
        emails_detail = cursor.fetchone()
        if not emails_detail:
            error_msg6 = f'No email credentials for \"{row["cust_id"]}\" ID in \"customer_email_settings\" tabel. ID = {check_email_type[0][1]}'
            messages = add_messages('Error: ', error_msg6, messages)
            subject = error_msg6
            body = error_msg6 + '</br>'
            messages = utils.send_email(subject, body, messages)
            continue
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

        if check_email_type[0][0] == 'API':
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
                cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('SUCCESS', row['id'], ))
                conn.commit()
            else:
                print(f"❌ Failed to send email. HTTP code: {response.status_code}")
                print("Response:", response.text)
                error_msg19 = f'Email notification send to client is Failed. {row["article_id"]}'
                messages = add_messages('Error: ', error_msg19, messages)
                subject = f'❌ Email notification send to client is Failed in API. {row["article_id"]}'
                body = f"""\tEmail notification send to client is Failed in API. <br>{response.status_code}<br>{response.text}.<br>data: {data}<br>"""
                messages = utils.send_email(subject, body, messages)
                cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('FAILED', row['id'], ))
                conn.commit()
        else:
            from_mail = email_dict_rows['from_email']
            from_password = email_dict_rows['from_password']
            mail_host = email_dict_rows['host']
            mail_port = email_dict_rows['port']
            email_status, messages = send_email(from_mail, from_password, row['to_mail'], row['to_cc'], row['to_bcc'], row['mail_subject'], row['mail_text'], mail_host, mail_port, messages)
            if not email_status:
                email_status, messages = send_email(from_mail, from_password, row['to_mail'], row['to_cc'], row['to_bcc'], row['mail_subject'], row['mail_text'], mail_host, mail_port,  messages)

            if not email_status:
                error_msg7 = f'Email notification send to client is Failed. {row["article_id"]}'
                messages = add_messages('Error: ', error_msg7, messages)
                subject = f'Email notification send to client is Failed. {row["article_id"]}'
                body = f"""\tEmail notification send to client is Failed. <br>\"{messages[-2]}\".<br>"""
                messages = utils.send_email(subject, body, messages)
                cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('FAILED', row["id"], ))
                conn.commit()
            else:
                cursor.execute('update xperttrack_ftp_upload_history set mail_send = %s where id = %s', ('SUCCESS', row['id'], ))
                conn.commit()
                print(f'SUCCESS: Email sent successfully for client.\n\n------------------\n\n')

    return log_message(messages), "Success"



#MARK: Start
if __name__ == '__main__':
    dd = main()
    # print(dd)
