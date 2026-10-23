import pymysql
import config
from datetime import datetime
import os, sys
from ftplib import FTP, error_perm
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import time


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
def database_connection():
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = config.db_host, 
            user = config.db_user, 
            password = config.db_password, 
            database = config.db_database
        )
        cursor = conn.cursor()
        return conn, cursor
    except Exception as e:
        print(f'Error accure in "database_connection" function. {e}')
        return None, None

#MARK: FTP
#Function to creation connection in FTP
def ftp_connection():
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
        return True, ftp
    except Exception as e:
        print(f'Error in "ftp_connection" function. {e}')
        return False, None
    
#MARK: Email Function
#Send email functionalitys
def send_email(subject, body, messages):
    """Send email notification for downloaded file"""
    subject = subject    #f"Integrity checks FTP File Downloaded:"

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
        print(f"Failed to send email: {e}")
        messages.append(f"Error: Email notification Failed to sent for file: {body}.")
    return messages

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
                    # print(f"Error creating directory '{current_path}': {e}")
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
    
#MARK: DB File Exists
#Check already file exists in Database based on choose insert or update table
def check_file_in_db(cursor, conn, a_id, ftp_file_path, disp_id, article_id, file, file_path, retry_count, cust_id, journ_id):
    try:
        cursor.execute("SELECT id, status FROM xperttrack_ftp_upload_history WHERE inw_id = %s AND remote_path = %s", (a_id, ftp_file_path))
        result = cursor.fetchone()

        if result:
            upload_id = result[0]
            if result[1] == 'SUCCESS':
                return upload_id, 'Already Backuped'
            print(f"Record already exists in DB for file: {ftp_file_path}. Updating status.")
            update_file_status(cursor, conn, upload_id, "PENDING", 'Backup', retry_count)
        else:
            file_size = os.path.getsize(file_path) / (1024 * 1024)
            #Insert file details in database
            cursor.execute("INSERT INTO xperttrack_ftp_upload_history (inw_id, dispatch_id, cust_id, jour_id, file_extension, article_id, file_name, local_path, remote_path, local_file_size) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)", (a_id, disp_id, cust_id, journ_id, os.path.splitext(file)[-1].replace('.', ''), article_id, file, file_path, ftp_file_path, f'{file_size:.2f}MB'))
            conn.commit()
            upload_id = cursor.lastrowid

        return upload_id, "Start"
    except Exception as e:
        print(f'Error accure in "check_file_in_db" function {e}')
        return None, 'Error'
            
    
#MARK: Main
def main(a_id, retry_count):
    messages = []

    conn, cursor = database_connection()        #Check database connectivity
    if not cursor:
        print(f'Database Connection Failed.')
        messages.append("Error: Database Connection Failed.\n")
        subject = f'Database Connection Failed'
        body = f"""<pre>
Dear Team,<br><br>
\tDatabase Connection Failed in workflow<br>
\t<a href="file://">{e}</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None

    status, ftp = ftp_connection()      ##Check FTP connectivity
    if not status:
        print(f'FTP Connection Failed.')
        messages.append("Error: FTP Server Connection Failed.\n")
        subject = f'FTP Connection Failed'
        body = f"""<pre>
Dear Team,<br><br>
\tFTP Connection Failed<br>
\t<a href="file://">{e}</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None

    #Get path from "inw_inward_dtl" table
    cursor.execute('select id, work_dir, volume_folder, job_id, cust_id, j_id from inw_inward_dtl where id = %s', (a_id, ))
    rows1 = cursor.fetchall()
    if not rows1:
        print(f"No Article ID found in Database. Please enter the correct Article ID.")
        messages.append("Error: No Article ID found in Database. Please enter the correct Article ID.\n")
        subject = f'Article does not Exists'
        body = f"""<pre>
Dear Team,<br><br>
\tNo Article ID found in Database. Please enter the correct Article ID.<br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None
    
    #Get stage from "inw_dispatch_history" table
    cursor.execute("select stage, idh_id from inw_dispatch_history where a_id = %s", (rows1[0][0] ,))
    rows2 = cursor.fetchall()
    if not rows2:
        print(f"No stage found for Article {rows1[0][3]} in Database. Please enter the Valid id to file transfer.")
        messages.append(f"Error: No stage found for Article {rows1[0][3]} in Database. Please enter the Valid id to file transfer.\n")
        messages.append("Error: No Article ID found in Database. Please enter the correct Article ID.\n")
        subject = f'Article stage not fount in database'
        body = f"""<pre>
Dear Team,<br><br>
\tNo stage found for Article "{rows1[0][3]}" in Database. Please enter the Valid id to file transfer.<br>
\t<a href="file://">{rows1[0][3]}</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
        messages = send_email(subject, body, messages)
        log_message(messages)
        return None

    for row in rows2:
        stage = row[0]
        #MARK: Generate path
        cahnge_slash = rows1[0][1].replace('/', config.os_slash)
        wf_directory = os.path.join(cahnge_slash, rows1[0][2], rows1[0][3], stage, 'Delivery')
        remove_numbers = ''.join(char for char in row[0] if not char.isdigit())

        #handle trailing slash in work_dir
        base_direct = rows1[0][1][:-1] if rows1[0][1].endswith('/') else rows1[0][1]

        #Generate FTP Folder path
        ftp_dir_path = "/".join([remove_numbers, os.path.basename(os.path.dirname(base_direct)), os.path.basename(base_direct), rows1[0][3], stage, 'transforma-files'])
        
        if os.path.exists(wf_directory):    #Check the path exists in the machine
            for file in os.listdir(wf_directory):
                file_path = os.path.join(wf_directory, file)
                ftp_file_path = '/'.join([ftp_dir_path, os.path.basename(file_path)])
                if file in [f'{rows1[0][3]}.pdf', f'{rows1[0][3]}.zip']:    #Transfer only pdf, zip only

                    #Check the Database to file already exists or not
                    upload_id, messag = check_file_in_db(cursor, conn, a_id, ftp_file_path, rows2[0][1], rows1[0][3], file, file_path, retry_count, rows1[0][4], rows1[0][5])
                    if messag == 'Already Backuped':
                        print(f"File Already Transfered from workflow to FTP.")
                        messages.append("Error: File Already Transfered from workflow to FTP.\n")
                        subject = f'Article already Transfered'
                        body = f"""<pre>
Dear Team,<br><br>
\tFile Already Transfered from workflow to FTP.<br>
\t<a href="file://">"{wf_directory}"</a>.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                        messages = send_email(subject, body, messages)
                        continue

                    #Create FTP Folder and transfered the file
                    if ftp_folder_creation(ftp_dir_path, ftp):
                        try:
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

                                with open(local_path, "rb") as f:
                                    ftp.storbinary(f"STOR {remote_path}", f, blocksize, callback=handle_block)
                            # with open(file_path, 'rb') as f:
                            #     ftp.storbinary(f"STOR {ftp_file_path}", f)
                            print(f"File Uploading to FTP: {ftp_file_path}")
                            current_datetime = datetime.now()
                            cursor.execute('update xperttrack_ftp_upload_history set start_time = %s where id = %s', (current_datetime, upload_id, ))
                            upload_status = upload_with_progress(cursor, conn, ftp, file_path, ftp_file_path, upload_id)
                            file_size = os.path.getsize(file_path) / (1024 * 1024)
                            cursor.execute('update xperttrack_ftp_upload_history set ftp_file_size = %s where id = %s', (f'Size: {file_size:.2f} MB, Percentage: 100%', upload_id, ))
                            time_diffrence = datetime.now() - current_datetime
                            cursor.execute('update xperttrack_ftp_upload_history set end_time = %s, total_time = %s where id = %s', (datetime.now(), time_diffrence, upload_id, ))
                            conn.commit()

                            print(f"Successfully File uploading to FTP: {ftp_file_path}")
                            messages.append(f"Success: Uploaded file to FTP: {ftp_file_path}")

                            update_file_status(cursor, conn, upload_id, "SUCCESS", 'Backup', retry_count)
                            subject = f'File Upload to FTP'
                            body = f"""<pre>
Dear Team,<br><br>
\tThe file was successfully transferred from <a href="file://">{file_path}</a> in workflow 
to <a href="file://">{ftp_file_path}</a> in FTP.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                            messages = send_email(subject, body, messages)
                            print('\n---------------------------\n')
                        except Exception as e:
                            print(f'Error Uploading File in FTP: {ftp_file_path} ----- {e}')
                            messages.append(f'Error: Error Uploading File in FTP: {ftp_file_path} ----- {e}')
                            update_file_status(cursor, conn, upload_id, "FAILED", 'Backup', retry_count, str(e))
                            subject = f'File Upload to FTP Failed.'
                            body = f"""<pre>
Dear Team,<br><br>
\t⚠ The file transfer failed for: from <a href="file://">{file_path}</a> in workflow 
to <a href="file://">{ftp_file_path}</a> in FTP.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                            messages = send_email(subject, body, messages)
                            if retry_count < 3:
                                retry_count += 1
                                main(a_id, retry_count)
                    else:
                        print(f'Failed to Create FTP Path: {ftp_dir_path}.')
                        error_db = f'Failed to Create FTP Path: {ftp_dir_path}.'
                        update_file_status(cursor, conn, upload_id, "FAILED", 'Backup', error_db)
                        subject = f'File to Create FTP Path.'
                        body = f"""<pre>
Dear Team,<br><br>
\t⚠ The file transfer failed for creating <a href="file://">{ftp_dir_path}</a> the directory in FTP.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
                        messages = send_email(subject, body, messages)
                        messages.append(f'Error: Failed to Create FTP Path: {ftp_dir_path}.')
        else:
            print(f'Delivery folder Does not exists in the workflow {wf_directory}.')
            subject = f'Delivery Folder does not exists in workflow.'
            body = f"""<pre>
Dear Team,<br><br>
\t⚠ The file transfer failed <a href="file://">{wf_directory}</a> Delivery folder does not exists in workflow.<br><br>
\tNote: This is an auto generated mail, please do not reply to this.<br><br>
Thanks & Regards,<br>
FTP Virtual Assistant<br>
Transforma Pvt. Ltd.</pre>"""
            messages = send_email(subject, body, messages)
            messages.append(f'Error: Delivery folder Does not exists in the workflow {wf_directory}.')

    log_message(messages)


#MARK: Initial
if __name__ == "__main__":
    a_id = sys.argv[1]         #12220
    retry_count = 0

    main(a_id, retry_count)



# #Function to create nested folders in FTP
# def ftp_folder_creation(ftp_path, ftp):
#     # try:
#         directories = ftp_path.split("/")
#         current_path = ""
#         ftp.cwd("/")
#         for directory in directories:
#             current_path = f"{os.path.join(current_path, directory)}" if current_path else directory
#             try:
#                 ftp.cwd(directory)
#             except error_perm:
#                 try:
#                     ftp.mkd(current_path)
#                     print(f"Created directory: {current_path}")
#                     ftp.cwd(directory)
#                 except Exception as e:
#                     print(f"Error creating directory '{current_path}': {e}")
#     # except Exception as e:
#     #     print(f'Error accure in "ftp_folder_creation" function. {e}')
#         # return False, ''