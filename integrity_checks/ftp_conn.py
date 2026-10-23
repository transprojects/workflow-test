#Import neccessary library
import os, sys
from ftplib import FTP, error_perm
import ftp_config
import pymysql
import time
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart


#MARK: Email Function
#Send email functionalitys
def send_email(body):
    """Send email notification for downloaded file"""
    subject = f"Integrity checks FTP File Downloaded:"

    msg = MIMEMultipart()
    msg["From"] = ftp_config.SENDER_EMAIL
    msg["To"] = ", ".join(ftp_config.TO_EMAILS)
    msg["Cc"] = ", ".join(ftp_config.CC_EMAILS)
    msg["Subject"] = subject
    msg.attach(MIMEText(body, "plain"))

    try:
        with smtplib.SMTP(ftp_config.SMTP_SERVER, ftp_config.SMTP_PORT) as server:
            server.starttls()
            server.login(ftp_config.SENDER_EMAIL, ftp_config.SENDER_PASSWORD)
            server.sendmail(ftp_config.SENDER_EMAIL, ftp_config.TO_EMAILS + ftp_config.CC_EMAILS, msg.as_string())
        print(f"Email notification sent for file: successfully.")
    except Exception as e:
        print(f"Failed to send email: {e}")

#MARK: Database Connect
try:
    #Establish the connection to the database
    conn = pymysql.connect(
        host = ftp_config.db_host,
        user = ftp_config.db_user, 
        password = ftp_config.db_password, 
        database = ftp_config.db_name
    )
    cursor = conn.cursor()
except Exception as e:
    print(f'Error in FTP Database Connection. {e}')
    body = f"""Dear Team,

    There was an error in FTP Database Connectivity.

    Error Details: {e}
    
    Note: This is a Auto generated mail, please do not reply to this
    
Thanks & Regards,

FTP Virtual Assistant
Transforma Pvt. Ltd."""
        
    send_email(body)
    sys.exit()

#MARK: FTP Connect
try:
    #FTP Connection
    ftp = FTP()
    ftp.connect(ftp_config.ftp_host, ftp_config.ftp_port)
    ftp.login(ftp_config.ftp_username, ftp_config.ftp_password)
    print("Connected to FTP server.")
except Exception as e:
    print(f'Error in FTP Connection. {e}')
    body = f"""Dear Team,

    There was an error in FTP Connectivity.

    Error Details: {e}
    
    Note: This is a Auto generated mail, please do not reply to this
    
Thanks & Regards,

FTP Virtual Assistant
Transforma Pvt. Ltd."""
        
    send_email(body)
    sys.exit()

base_dir = ftp.pwd()

#MARK: Download file
# while True:
try:
    conn.commit()
    cursor.execute(ftp_config.query)
    rows = cursor.fetchall()
    
    if not rows:
        print(f'No Files is there')
    #Loop through the each file download the file from ftp to 110 server
    for ar_id, ar_name in rows:
        remote_directory = f'{os.path.basename(os.path.splitext(ar_name)[0]) + "/word"}'
        remote_filename = os.path.basename(os.path.splitext(ar_name)[0]) + ".zip"
        local_path = os.path.join(ftp_config.upload_path_110, remote_filename)

        ftp.cwd(remote_directory)
        # Check if file exists on FTP server
        files = ftp.nlst()  # List files in current FTP directory
        if remote_filename in files:
            print(f"File '{remote_filename}' exists on FTP. Downloading...")

            # Ensure local folder exists
            os.makedirs(os.path.dirname(local_path), exist_ok=True)

            # Download the file
            with open(local_path, "wb") as f:
                ftp.retrbinary(f"RETR {remote_filename}", f.write)
            
            cursor.execute('update article set status = 2 where id = %s', (ar_id))
            conn.commit()

            body = f"""Dear Team,

            The file '{remote_filename}' was successfully downloaded to:\n{local_path}.
            
            Note: This is a Auto generated mail, please do not reply to this
            
Thanks & Regards,

FTP Virtual Assistant
Transforma Pvt. Ltd."""

            send_email(body)

            print(f"File downloaded successfully to: {local_path}")
        else:
            print(f"File '{remote_filename}' does not exist on the FTP server.")

            body = f"""Dear Team,

            File '{remote_filename}' does not exist on the FTP server.
            
            Note: This is a Auto generated mail, please do not reply to this
            
Thanks & Regards,

FTP Virtual Assistant
Transforma Pvt. Ltd."""

            send_email(body)

        ftp.cwd(base_dir)
    # time.sleep(ftp_config.CHECK_INTERVAL)
    
except Exception as e:
    print(f'Error in database connection. {e}')
    
    body = f"""Dear Team,

    There was an error in downloading file from FTP on 110 server.

    Error Details: {e}
    
    Note: This is a Auto generated mail, please do not reply to this
    
Thanks & Regards,

FTP Virtual Assistant
Transforma Pvt. Ltd."""
    
    send_email(body)
    # break
    sys.exit()