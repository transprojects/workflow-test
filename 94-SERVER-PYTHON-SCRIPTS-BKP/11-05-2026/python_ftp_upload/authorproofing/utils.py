import pymysql
from ftplib import FTP, error_perm
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import config

def database_connection(host, username, password, database):
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = host, 
            user = username, 
            password = password, 
            database = database
        )
        cursor = conn.cursor()
        print(f"Database {database} in {host} connected Successfully.")
        messages= f"Msg: Database {database} in {host} connected Successfully."
        return conn, cursor, messages
    
    except Exception as e:
        print(f'Error accure in "database_connection" function. {e}')
        messages= f'Err: Error accure in "database_connection" function for {database} in {host}. {e}'
        subject = '⚠ Database Connection Failed in Workflow'
        body = f"""<pre>
        Dear Team,<br><br>
        \t⚠ A database connection attempt failed in the workflow.<br>
        \tServer: <a>{host}</a><br><br>
        \tNote: This is an auto-generated email. Please do not reply.<br><br>
        Thanks & Regards,<br>
        FTP Virtual Assistant<br>
        Transforma Pvt. Ltd.
        </pre>"""
        messages = send_email(subject, body, messages)
        return None, None, messages

def ftp_connection(ftp_host, ftp_port, ftp_username, ftp_password, messages):
    try:
        ftp = FTP()
        ftp.connect(ftp_host, int(ftp_port))
        ftp.login(ftp_username, ftp_password)
        print(f"Connected to FTP server - {ftp_host}")
        messages= f"Msg: Connected to FTP server {ftp_host}"
        return True, ftp, messages
    except Exception as e:
        print(f'Error in "ftp_connection" function. {e}')
        messages= f"Err: Connection failed for FTP server {ftp_host}"
        subject = '⚠ FTP Server Connection Failed in Workflow'
        body = f"""<pre>
        Dear Team,<br><br>
        \t⚠ A FTP Server connection attempt failed in the workflow.<br>
        \tServer: <a>{ftp_host}</a><br><br>
        \tNote: This is an auto-generated email. Please do not reply.<br><br>
        Thanks & Regards,<br>
        FTP Virtual Assistant<br>
        Transforma Pvt. Ltd.
        </pre>"""
        messages = send_email(subject, body, messages)
        return False, None, messages


def send_email(subject, body, messages):
    """Send email notification for downloaded file"""
    subject = subject  

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
        print(f"Email notification sent for file: {subject} successfully.")
        messages= f"Success: Email notification sent for file: {subject} successfully."
    except Exception as e:
        print(f"Failed to send email: {e}")
        messages= f"Error: Email notification Failed to sent for file: {body}."
    return messages