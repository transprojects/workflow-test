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
    
    log_file_path = os.path.join(r'D:/Localserver/Apache/htdocs/workflow-new/python_ftp_download/EDP/logs', f"log_file_{datetime.now().strftime('%Y-%m-%d')}.txt")
    log_file_path = PureWindowsPath(log_file_path)
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

def list_zip_files_recursive(ftp, path, journal_list):
    zip_files = []

    def walk(current_path):
        try:
            ftp.cwd(current_path)
        except Exception as e:
            print(f"Cannot access {current_path}: {e}")
            return

        items = []
        ftp.retrlines('LIST', items.append)

        for item in items:
            parts = item.split()

            # Detect folder or file
            name = parts[-1]
            if name.lower() not in journal_list:
                continue
            is_dir = item.startswith('d')

            if not is_dir:
                continue  # skip files in base path

            full_path = f"{current_path}/{name}"

            try:
                ftp.cwd(name)
            except:
                print(f"Cannot access {full_path} in journal folder: {e}")
                continue

            files = []
            ftp.retrlines('LIST', files.append)

            for f in files:
                f_parts = f.split()
                fname = f_parts[-1]
                is_file = not f.startswith('d')

                if is_file and fname.lower().endswith('.zip'):
                    up_full_path = f"{full_path}/{fname}"
                    zip_files.append(up_full_path)

            # if is_dir:
            #     #🔁 Recurse into directory
            #     walk(name)
            # else:
            #     if name.lower().endswith('.zip'):
            #         zip_files.append(full_path)

        # Go back to parent directory
        ftp.cwd("..")

    walk(path)
    return zip_files

#MARK: Download File
def download_with_progress_1(ftp, local_path, remote_file_path, messages, blocksize=65536):
    try:
        directory, filename = os.path.split(remote_file_path)
        ftp.cwd(directory)

        ftp.voidcmd('TYPE I')
    except Exception as e:
        messages = add_messages('Error: ', f'⚠ Folder does not exists in FTP. {directory}', messages)
        log_message(messages)
        subject = f'⚠ Folder does not exists in FTP. While Download EDP Files.'
        body = f"""\t⚠ Folder does not exists in FTP. for \"{directory}\".<br>"""
        messages = utils.send_email(subject, body, messages)
        return False, messages

    files = ftp.nlst()
    if filename not in files:
        # cursor12.execute("""UPDATE submission_tracking_system SET download_error=%s WHERE id=%s""", (f'⚠ File does not exist on FTP. {remote_file_path}', row['id']))
        messages = add_messages('Error: ', f'⚠ File does not exist on FTP. {remote_file_path}', messages)
        log_message(messages)
        subject = f'⚠ File does not exist on FTP. While Download EDP Files.'
        body = f"""\t⚠ File does not exist on FTP. for \"{remote_file_path}\".<br>"""
        messages = utils.send_email(subject, body, messages)
        return False, messages
    
    # cursor12.execute("""UPDATE submission_tracking_system SET file_download_status=%s, start_time=%s WHERE id=%s""", ('PENDING', datetime.now(), row['id']))
    # conn12.commit()

    current_datetime = datetime.now()

    # for name, facts in ftp.mlsd():
    #     if name == filename:
    #         print("File size:", facts.get("size"))

    try:
        file_size = ftp.size(filename)
    except Exception:
        file_size = None
    # file_size = ftp.size(filename)
    downloaded = 0
    last_print = time.time()

    def handle_block(block):
        nonlocal downloaded, last_print
        f.write(block)
        downloaded += len(block)
        now = time.time()
        if now - last_print >= 1:  # print every 1 second
            # percent = (downloaded / file_size) * 100
            size_in_mb = downloaded / (1024 * 1024)
            # print(f"Download progress: {percent:.2f}% - {size_in_mb:.2f} MB downloaded")
            if file_size:  # ✅ Only calculate % if size exists
                percent = (downloaded / file_size) * 100
                print(f"Download progress: {percent:.2f}% - {size_in_mb:.2f} MB downloaded")
            else:
                print(f"Downloaded: {size_in_mb:.2f} MB")
            # cursor12.execute('update submission_tracking_system set download_zipsize = %s where id = %s', (f'Size: {size_in_mb:.2f} MB, Percentage: {percent:.2f}%', row["id"], ))
            # conn12.commit()
            last_print = now

    # Ensure local folder exists
    os.makedirs(os.path.dirname(local_path), exist_ok=True)

    with open(local_path, 'wb') as f:
        ftp.retrbinary(f'RETR {filename}', handle_block, blocksize=blocksize)

    time_diffrence = datetime.now() - current_datetime
    if file_size:
        size_in_mb = file_size / (1024 * 1024)
    else:
        size_in_mb = downloaded / (1024 * 1024)
    # size_in_mb = file_size / (1024 * 1024)
    # cursor12.execute('UPDATE submission_tracking_system SET download_zipsize=%s, total_time_taken=%s, file_download_status=%s, end_time=%s WHERE id=%s', (f'Size: {size_in_mb:.2f} MB, Percentage: 100%', time_diffrence, 'SUCCESS', datetime.now(), row['id']))
    # conn12.commit()

    print(f"Download complete: {local_path} ")
    return True, messages

def ensure_ftp_dir(ftp, path):
    dirs = path.strip("/").split("/")
    current = ""

    for d in dirs:
        current += f"/{d}"
        try:
            ftp.mkd(current)
        except Exception:
            pass  # already exists

#MARK: Move Files
def move_file_to_backup(ftp, remote_file_path):
    directory, filename = os.path.split(remote_file_path)

    # 📅 current date folder
    date_folder = datetime.now().strftime("%Y-%m-%d")

    # 📁 target path
    backup_base = f"{directory}/bkp"
    backup_date_path = f"{backup_base}/{date_folder}"

    # ✅ Ensure folders exist
    ensure_ftp_dir(ftp, backup_base)
    ensure_ftp_dir(ftp, backup_date_path)

    # 🎯 Final destination
    new_path = f"{backup_date_path}/{filename}"

    ftp.cwd("/")

    try:
        ftp.rename(remote_file_path, new_path)
        print(f"Moved to backup: {new_path}")
        return True
    except Exception as e:
        print(f"Failed to move file: {e}")
        return False

#MARK: Main
def main():
    messages = []

    #Check the Database Connection
    conn, cursor, messages = utils.database_connection(messages)
    if not cursor:
        messages = add_messages('Error: ', f'Database Connection Failed in workflow.', messages)
        return log_message(messages), None
    
    #Default email was send or not. Get the status in database "wf_settings" table
    cursor.execute('select set_download_email_auto, set_success_email_auto from wf_settings where set_id = %s', (1, ))
    default_email_status = cursor.fetchone()
    default_email_send = True
    success_email_send = False
    if default_email_status and default_email_status[0] != 'Active':
        default_email_send = False
    if default_email_status and default_email_status[1] == 'Active':
        success_email_send = True

    #Check the customer credentials are present in the database using customer ID  #MARK: Customer credentials
    cursor.execute("select * from ftp_credentials where cust_id = %s", (82 ,))
    crede = cursor.fetchone()
    if not crede:
        error_msg1 = f'⚠ FTP and Database Credential Missing for the cutomer ID: \"77\" present in "ftp_credentials" table in workflow database.'
        messages = add_messages('Error: ', error_msg1, messages)
        subject = f'⚠ FTP and Database credentials Missing in Database for customer ID: 77. While Download EDP Files.'
        body = f"""\t{error_msg1}<br>"""
        messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    creden_columns = [desc[0] for desc in cursor.description]
    creden_dict_rows = dict(zip(creden_columns, crede))

    #Make FTP Connection Check based on each customer
    status, ftp, messages = ftp_connection(creden_dict_rows['host'], creden_dict_rows['port'], creden_dict_rows['username'], creden_dict_rows['password'], messages)
    if not status:
        error_msg10 = f'⚠ FTP was not connected for \"{creden_dict_rows["host"]}\" this host.'
        messages = add_messages('Error: ', error_msg10, messages)
        subject = f'⚠ FTP Connectivity Failed for: \"{creden_dict_rows["host"]}\". While Download EDP Files.'
        body = f"""\t{error_msg10}<br>"""
        messages = utils.send_email(subject, body, messages)
        return log_message(messages), None
    
    cursor.execute('select j_code from adm_journals where j_cust_id = %s', ('82', ))
    jorn_result = cursor.fetchall()
    j_code_list = [row[0].lower() for row in jorn_result]
    origi_j_code = [row[0] for row in jorn_result]
    
    ftp_file_path_1 = f'{creden_dict_rows["download_path"]}'

    zip_files = list_zip_files_recursive(ftp, ftp_file_path_1, j_code_list)
    
    customer_name = 'EDP-Sci'
    # automation_path = f'/home/user/Siva/python/program/marimuthu/ftp_to_workflow_download/ACG/automation'
    automation_path = os.path.join(config.automation_path)
    work_dir_1 = f'{config.stage_path}/{customer_name}'

    if not zip_files:
        print(f'⚠ No File present to download from FTP (EDP) to Inhouse Journal Workflow Automation Folder.')
        messages.append('No File present to download from FTP (EDP) to Inhouse Journal Workflow Automation Folder.')
    for file in zip_files:
        journ_name = os.path.basename(os.path.dirname(file))
        for jou in origi_j_code:
            if jou.lower() == journ_name.lower():
                jou_name = jou
                break
            else:
                jou_name = journ_name
        file_name = os.path.basename(file)
        article_no = os.path.splitext(file_name)[0]

        journal_name = journ_name.upper()   #'M2AN'
        work_dir = f'{work_dir_1}/{jou_name}/'

        if 'correct' in file_name:
            rev_type = 'REV'
            name, ext = os.path.splitext(file_name)
            base = name.split("_")[0]
            new_name = base
        else:
            rev_type = 'FP'
            new_name = article_no

        cursor.execute('select * from inw_inward_dtl where pub_id=%s', (new_name))
        # cursor.execute('select * from inw_inward_dtl where pub_id=%s', (dd))
        check_already = cursor.fetchone()

        if check_already:
            if rev_type == "FP":
                print(f'File was already Exists in workflow. {new_name}')
                continue
            else:
                workflow_id = check_already[0]
        else:
            if rev_type == 'FP':
                workflow_id = 1
            else:
                print(f'File was not present in the Database. Filename: {file}')
                continue

        cursor.execute('select * from inw_revisions_dtl where inw_id=%s', (workflow_id, ))
        get_revisi = already_exists = cursor.fetchall()
        revision_count = len(get_revisi)
        if rev_type != 'FP':
            fi_stage = f'{rev_type}{revision_count + 1}'
            automation_path = f'{config.stage_path}/{customer_name}/{journ_name}/Vol00000/{new_name}/{fi_stage}'
            automation_path = PureWindowsPath(automation_path)
            os.makedirs(automation_path, exist_ok=True)
        else:
            fi_stage = rev_type

        automation_path = PureWindowsPath(automation_path)

        ftp_file_path = f'{ftp_file_path_1}/{file}'
        ftp_file_path = f'{file}'
        ftp.cwd("/")

        download_result, messages = download_with_progress_1(ftp, os.path.join(automation_path, file_name), ftp_file_path, messages)      #MARK: Download
        if not download_result:
            continue

        print(f"Successfully Download file from FTP: {ftp_file_path}")
        messages.append(f"Success: Download file from FTP: {ftp_file_path}")
        
        move_status = move_file_to_backup(ftp, ftp_file_path)

        if not move_status:
            print(f"Warning: File downloaded but not moved → {ftp_file_path}")
            messages.append('Warning: File downloaded but not moved → {ftp_file_path}')

    # for row in zip_files:
        
        #Get customer name and journal code from "adm_customer_master" and "adm_journal" tabals
        query = """SELECT adm_cus.id, adm_jou.j_id, adm_jou.j_name FROM adm_customer_master adm_cus join adm_journals adm_jou on adm_jou.j_cust_id = adm_cus.id where adm_cus.cust_name = %s and adm_jou.j_code = %s"""
        cursor.execute(query, (customer_name, journal_name, ))
        result = cursor.fetchone()
        if not result:
            error_msg4 = f"Missing data for customer ID: {customer_name} and journal ID: {journ_name}"
            messages = add_messages('Error: ', error_msg4, messages)
            continue
        cust_id = result[0]
        journ_id = result[1]
        journal_title = result[2]

        #Check the current customer was active or inactive in database
        if not creden_dict_rows['status'] == 'Active':
            error_msg12 = f'⚠ The current customer \"{cust_id}\" was not active. Inactive in database \"ftp_credentialas\" table. Please activate it in database.'
            messages = add_messages('Error: ', error_msg12, messages)
            return log_message(messages), None

        #Get the due date count from the database
        cursor.execute('select due_days, wf_id, skip_dept, add_dept, d_email_id, skip_saturday, skip_sunday from ftp_customer_stage where cust_id=%s AND FIND_IN_SET(%s, stage)', (cust_id, fi_stage))  #MARK: Due days
        due_date_count = cursor.fetchone()
        if not due_date_count:
            error15 = f'⚠ Due days count was not found in ftp_customer_stage table in workflow for {article_no}.'
            messages = add_messages('Error: ', {error15}, messages)
            log_message(messages)
            subject = f'⚠ Due days count missing in ftp_customer_stage table in workflow database. While Download EDP Files.'
            body = f"""\t '{error15}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue
            
        #Get the holidays list from the database
        cursor.execute('select wf_hdate from wf_holidays where wf_status="0"')
        holidays_list = cursor.fetchall()
        if not holidays_list:
            error_msg13 = f'⚠ Holidays table was not present in the workflow database.'
            messages = add_messages('Error: ', error_msg13, messages)
            log_message(messages)
            subject = f'⚠ Holidays table does not exists in the workflow database. While Download EDP Files.'
            body = f"""\t{error_msg13}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue
        
        holiday_dates = {d[0] for d in holidays_list}

        due_date = datetime.now()
        added_days = 0
        while added_days < due_date_count[0]:
            due_date += timedelta(days=1)
            if due_date_count[6] == '1' and due_date.weekday() == 6:  # Sunday
                continue
            if due_date_count[5] == '1' and due_date.weekday() == 5:  # Saturday
                continue
            if due_date.date() in holiday_dates:
                continue
            added_days += 1

        if rev_type == 'FP':
            file_size = os.path.getsize(automation_path)
            #MARK: Update Query
            cursor.execute('select * from inw_inward_dtl where job_id=%s', (article_no))
            check_already = cursor.fetchone()
            if check_already is None:
                cursor.execute('insert into inw_inward_dtl (cust_id, j_id, job_id, journal_title, article_title, recv_dt, accpt_dt, due_dt, pub_id, created_dt, stage, wf_id, department_id, status, work_dir, volume_folder, inw_type, approved_by, zip_size, fig, xperttrack_sync) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', (cust_id, journ_id, article_no, journal_title, article_no, datetime.now(), datetime.now(), due_date, article_no, datetime.now(), "FP", 145, 1, "CE", work_dir.replace('downloads', 'production'), "Vol00000", "NULL", 54, file_size, 0, '1'))   #os.path.join(automation_path, file_name)
                conn.commit()
                pri_id = cursor.lastrowid

                workflow_id = pri_id
            else:
                messages = add_messages('Error: ', f'File was already present in the workflow database. {automation_path} --- {article_no}', messages)
                log_message(messages)
                subject = f'File already present in workflow Database. While Download EDP Files.'
                body = f"""\t⚠ File already present in workflow database. for \"{automation_path}\".<br>"""
                messages = utils.send_email(subject, body, messages)
                continue
        
        if rev_type != 'FP':
            #Check the table for already the file present in the database
            cursor.execute('select * from inw_revisions_dtl where inw_id=%s and revision_count=%s and type=%s', (workflow_id, revision_count + 1, rev_type))
            already_exists = cursor.fetchall()
            if already_exists:
                error_msg14 = f'⚠ The file was already present in the database for {workflow_id} and {rev_type}. {article_no}'
                messages = add_messages('Error: ', error_msg14, messages)
                log_message(messages)
                subject = f'⚠ File already present in the database. {article_no}. While Download EDP Files.'
                body = f"""\t{error_msg14}<br>"""
                messages = utils.send_email(subject, body, messages)
                continue
            
            work_dir = f'{work_dir}/Vol00000/{article_no}/{fi_stage}'

            #Insert the new entry in database for file download
            cursor.execute('insert into inw_revisions_dtl (inw_id, correction_pages, revision_count, received_date, due_date, file_name, created_by, created_on, type, comments) values (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)', (workflow_id, "0", str(revision_count + 1), datetime.now(), due_date, work_dir, "54", datetime.now(), rev_type, "No Commends"))
            conn.commit()
            print(f'SUCCESS: Insert value in inw_revisions_dtl table successfully. for {workflow_id}')

        #Update the table before check it is present or not
        cursor.execute('select * from inw_inward_dtl where id=%s', (workflow_id, ))
        get_present = cursor.fetchone()
        if not get_present:
            error_msg15 = f'⚠ {workflow_id} was does not present in the inw_inward_dtl table. for {article_no}'
            messages = add_messages('Error: ', error_msg15, messages)
            log_message(messages)
            subject = f'⚠ {workflow_id} value does not exists in the workflow database. for {article_no}. While Download EDP Files.'
            body = f"""\t{error_msg15}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue

        #MARK: Query updates
        journ_tab_key = f'j_{rev_type.lower()}_wf'
        
        cursor.execute(f"select adm_jour.{journ_tab_key} as journal_value, adm_wf_mas.wf_id, adm_wf_mas.wf_code, adm_dept_mas.dept_name from adm_journals adm_jour join adm_workflow_master adm_wf_mas on adm_wf_mas.wf_name = adm_jour.{journ_tab_key} join adm_dept_master adm_dept_mas on adm_dept_mas.dept_code = SUBSTRING_INDEX(adm_wf_mas.wf_code, ',', 1) where adm_jour.j_id=%s", (journ_id, ))
        get_journal_table = cursor.fetchone()
        # print(journal_value, wf_id, wf_code, dept_name)
        
        if due_date_count[1]:
            wf_id = due_date_count[1]
        
        if not get_journal_table:
            error_msg16 = f'⚠ {journ_id} was not present in adm_journals table in workflow database while updating inw_inward_dtl table. for {article_no}'
            messages = add_messages('Error: ', error_msg16, messages)
            log_message(messages)
            subject = f'⚠ {journ_id} not present in adm_journals table in workflow database. for {article_no}. While Download EDP Files.'
            body = f"""\t{error_msg16}<br>"""
            messages = utils.send_email(subject, body, messages)
            continue

        journal_value, wf_id, wf_code, dept_name = get_journal_table

        cursor.execute('update inw_inward_dtl set stage =%s, prev_stage =%s, status=%s, department_id=%s, wf_id=%s where id=%s', (f'{fi_stage}', "", dept_name, wf_code.split(',')[0], wf_id, workflow_id, ))
        conn.commit()
        print(f'SUCCESS: Update value in inw_inward_dtl table successfully.')

        if fi_stage != 'FP':
            if fi_stage == 'REV1':
                prev_stage = 'FP'
            elif 'REV' in fi_stage:
                prev_stage = rev_type + str(revision_count)
            elif fi_stage == 'FIN1':
                prev_stage = fi_stage
            else:
                prev_stage = fi_stage

            prev_stage_path = str(automation_path).replace(fi_stage, prev_stage)
            prev_stage_path = prev_stage_path.replace('downloads', 'production')
            prev_stage_path = PureWindowsPath(prev_stage_path)
            if not os.path.exists(prev_stage_path):
                error_msg17 = f'⚠ {prev_stage_path} is does not exists in the workflow. previou stage folder not present in workflow. So does not change files from previous stage to current stage.'
                messages = add_messages('Error: ', error_msg17, messages)
                log_message(messages)
                subject = f'⚠ {prev_stage_path} Path does not exists in the workflow. for {article_no}. While Download EDP Files.'
                body = f"""\t{error_msg17}<br>"""
                messages = utils.send_email(subject, body, messages)
                continue
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
                    if folder_list_to_change_all and rel_path in [folder_list_to_change_all[0]]:
                        continue
                    if any(nuw in rel_path for nuw in folder_list_to_change_all[1]):
                        dest_root = os.path.join(automation_path, rel_path)
                        dest_root = str(dest_root).replace('downloads', 'production')
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
        cursor.execute('select from_email, from_password, host, port, mail_to, mail_cc, mail_bcc from customer_email_settings where id = %s', (due_date_count[4], ))
        emails_detail = cursor.fetchall()
        if not emails_detail:
            error_msg6 = f'⚠ No email credentials for \"{creden_dict_rows["email_id"]}\" ID in \"email_settings\" tabel. While Download EDP Files.'
            messages = add_messages('Error: ', error_msg6, messages)
            subject = error_msg6
            body = error_msg6 + '</br>'
            messages = utils.send_email(subject, body, messages)
            return log_message(messages), None
        
        from_mail = emails_detail[0][0]
        from_password = emails_detail[0][1]
        mail_host = emails_detail[0][2]
        mail_port = emails_detail[0][3]
        mail_to = emails_detail[0][4]
        mail_cc = emails_detail[0][5]
        mail_bcc = emails_detail[0][6]
        email_status, messages = send_email(from_mail, from_password, mail_to, mail_cc, mail_bcc, config.mail_subject + article_no, config.mail_body, mail_host, mail_port, messages)
        if not email_status:
            email_status, messages = send_email(from_mail, from_password, mail_to, mail_bcc, mail_bcc, config.mail_subject + article_no, config.mail_body, mail_host, mail_port, messages)

        if not email_status:
            error_msg7 = f'⚠ Email notification sent to client is Failed for {article_no}. While Download EDP Files.'
            messages = add_messages('Error: ', error_msg7, messages)
            subject = error_msg7
            body = f"""\t⚠ Email notification sent to client is Failed. <br>\"{messages[-2]}\".<br>"""
            messages = utils.send_email(subject, body, messages)

        print(f'SUCCESS: Email sent successfully for client.\n\n------------------\n\n')

    return log_message(messages), "Success"



#MARK: Start
if __name__ == '__main__':
    dd = main()
    # print(dd)
