import pymysql
from ftplib import FTP, error_perm
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart

import config



#MARK: Database
#Function to connect the databse
def database_connection(messages):
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = config.db_host, 
            user = config.db_user, 
            password = config.db_password, 
            database = config.db_database
        )
        cursor = conn.cursor()
        print("Workflow Database Connected Successfully.")
        return conn, cursor, messages
    except Exception as e:
        print(f'Error accure in "database_connection" function. {e}')

        subject = f'Database Connection Failed'
        body = f"""
\tDatabase Connection Failed in workflow<br>
\t<a href="file://">{e}</a>.<br><br>"""
        # messages = send_email(subject, body, messages)

        return None, None, messages
    
#MARK: FTP
#Function to creation connection in FTP
def ftp_connection(messages):
    try:
        ftp_credentials = {
            "host": config.ftp_host,
            "port": config.ftp_port,
            "username": config.ftp_username,
            "password": config.ftp_password
        }

        ftp = FTP()
        ftp.connect(ftp_credentials["host"], ftp_credentials["port"])
        ftp.login(ftp_credentials["username"], ftp_credentials["password"])
        print("Connected to FTP server.")
        return True, ftp, messages
    except Exception as e:
        print(f'Error in "ftp_connection" function. {e}')
        subject = f'FTP Connection Failed'
        body = f"""
\tFTP Connection Failed<br>
\t<a href="file://">{e}</a>.<br><br>"""
        messages = send_email(subject, body, messages)
        return False, None, messages
    
#MARK: Email Function
#Send email functionalitys
def send_email(subject, body, messages):
    """Send email notification for downloaded file"""
    body = f"""<pre>
Dear Team,<br>
{body}
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""

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
        print(f"Failed to sent email: {e}")
        messages.append(f"Error: Email notification Failed to sent for file: {body}.")
    return messages