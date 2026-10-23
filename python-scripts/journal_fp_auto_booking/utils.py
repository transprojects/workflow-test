import pymysql
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import config
import sys
import io

def database_connection(host, username, password, database):
    try:
        conn = pymysql.connect(
            host = host, 
            user = username, 
            password = password, 
            database = database
        )
        cursor = conn.cursor()
        # print(f"Database {database} in {host} connected Successfully.")
        # messages.append(f"Msg: Database {database} in {host} connected Successfully.") 
        return conn, cursor
    
    except Exception as e:
        print(f'Error accure in "database_connection" function. {e}')
        # messages.append(f'Err: Error accure in "database_connection" function for {database} in {host}. {e}')
        subject = f'⚠ Database Connection Failed in Page_count processing'
        body = f"""<pre>
Dear Team,
⚠ A Database connection attempt failed in Page count processing.
<b>Server: <i>{host}</i></b>
<b>Username: <i>{username}</i></b>
Note: This is an auto-generated email. Please do not reply to this.
Thanks & Regards,
Virtual Assistant
Transforma Pvt. Ltd.
</pre>"""
        send_email(subject, body)
        return None, None


if sys.stdout.encoding != 'utf-8':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def send_email(subject, body, messages):
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
        print(f"Email notification sent for file: '{subject}' successfully.")
        messages.append(f"Email notification sent for: '{subject}' successfully.")
    except Exception as e:
        print(f"Failed to send email: {e}")
        messages.append(f"Error: Email notification Failed to sent for file: {body}.")
