import sys, os
import utils, config
import zipfile
import tarfile
import shutil
import traceback
from datetime import datetime, timedelta
import PyPDF2
import win32com.client
import time
import random, re
import subprocess
import xml.etree.ElementTree as ET
import glob


conn, cursor= utils.database_connection(config.db_host, config.db_username, config.db_password, config.db_database)

#MARK: EXTRACT .zip or .tar.bz2
def extract_tar_bz2(tar_path, extract_dir, messages):
    try:
        os.makedirs(extract_dir, exist_ok=True)
        with tarfile.open(tar_path, 'r:bz2') as tar:
            tar.extractall(extract_dir)
        messages.append(f"Successfully extracted tar.bz2: {tar_path}")
        return True, messages

    except Exception as e:
        full_traceback = traceback.format_exc()
        print(f"Error extracting tar.bz2 {tar_path}: {full_traceback}")
        return False, messages


def unzip_nested_zip(zip_path, temp_dir, messages):
    try:
        # 1. Normalize the base directory to the Extended Path format
        # This allows 32,000+ characters and forces Backslashes
        safe_temp_dir = os.path.abspath(temp_dir).replace('/', '\\')
        if not safe_temp_dir.startswith("\\\\?\\"):
            safe_temp_dir = "\\\\?\\" + safe_temp_dir
            
        os.makedirs(safe_temp_dir, exist_ok=True)

        # Ensure the zip_path itself is normalized for the OS to find it
        zip_path = os.path.abspath(zip_path).replace('/', '\\')
        if not zip_path.startswith("\\\\?\\"):
            zip_path = "\\\\?\\" + zip_path

        with zipfile.ZipFile(zip_path, 'r') as outer_zip:
            for member in outer_zip.namelist():
                if '__MACOSX' in member:
                    continue

                # 2. Get the original member name but fix the slashes
                # Zip internal paths ALWAYS use forward slashes '/', which WinError 123 hates.
                safe_member_name = member.replace('/', '\\')
                target_disk_path = os.path.join(safe_temp_dir, safe_member_name)

                # 3. Process Directory vs File
                if member.endswith('/'):
                    os.makedirs(target_disk_path, exist_ok=True)
                else:
                    # Ensure the parent directory exists
                    os.makedirs(os.path.dirname(target_disk_path), exist_ok=True)
                    
                    # 4. Extract with original name
                    with outer_zip.open(member) as source, open(target_disk_path, "wb") as target:
                        shutil.copyfileobj(source, target)

                    # 5. Handle Nested Zip Recursively
                    if target_disk_path.lower().endswith('.zip'):
                        # Create a folder for the nested zip using its original name
                        nested_extract_dir = os.path.splitext(target_disk_path)[0]
                        
                        success, messages = unzip_nested_zip(target_disk_path, nested_extract_dir, messages)
                        if not success: 
                            return False, messages
                        
                        # Cleanup the nested zip after extraction
                        try:
                            os.remove(target_disk_path)
                        except:
                            pass
                            
        return True, messages

    except Exception as e:
        # Error handling as you had it
        full_traceback = traceback.format_exc()
        messages.append(f"Error unzipping {zip_path}: {full_traceback}")
        # (Email logic remains the same)
        return False, messages


def get_quick_page_count(path):
    abs_path = os.path.abspath(path)
    # print(abs_path)
    ext = abs_path.lower()

    if ext.endswith('.pdf'):
        try:
            with open(path, 'rb') as f:
                return len(PyPDF2.PdfReader(f).pages)
        except: return 0

    if ext.endswith(('.docx', '.doc', '.rtf', '.odt', '.docm')):
        max_retries = 2
        for attempt in range(max_retries):
            word = None
            doc = None
            try:
                word = win32com.client.DispatchEx("Word.Application")
                word.Visible = False
                word.DisplayAlerts = 0
                word.Options.ConfirmConversions = False
                
                doc = word.Documents.Open(abs_path, ReadOnly=True, OpenAndRepair=True)
                p = doc.ComputeStatistics(2)
                doc.Close(False)
                word.Quit()
                return p
            except Exception as e:
                if "Command failed" in str(e) and attempt < max_retries - 1:
                    time.sleep(random.uniform(2, 4))
                    continue
                return 1 # Fallback
            finally:
                if word:
                    try: word.Quit()
                    except: pass
    return 0


#MARK: FIND FILES
def find_correct_files(folder_path, pub_id, messages):
    candidates = []
    tex_exists = False
    tex_extensions = ('.tex', '.bib')
    word_extensions = ('.docx', '.doc', '.rtf', '.odt', '.docm')
    #print(folder_path)
    for root, dirs, files in os.walk(folder_path):
        for file in files:
            if file.lower().endswith(tex_extensions):
                tex_exists = True
                break
        if tex_exists:
            break

    for root, dirs, files in os.walk(folder_path):
        for file in files:
            #print(file)
            file_path = os.path.join(root, file)
            file_lower = file.lower()

            if file.startswith("~$"):
                continue

            if tex_exists and file_lower.endswith(".pdf"): 
                p = get_quick_page_count(file_path)
                ext= 'PDF'
                if p > 0:
                    candidates.append({'p': p, 'path': file_path, 'root': root, 'ext': ext})
            elif not tex_exists and file_lower.endswith(word_extensions):
                p = get_quick_page_count(file_path)
                ext= 'DOCX'
                if p > 0:
                    candidates.append({'p': p, 'path': file_path, 'root': root, 'ext': ext})
    # print(candidates)

    if tex_exists and not candidates:
#         messages.append(f'Tex exists and PDF not found for {pub_id}')
#         subject = f'Tex file exists and PDF not found in Page count processing for {pub_id}'
#         body = f"""<pre>Dear Team,
# While page count processing for {pub_id}, the Tex file exists in the zip but, <b>PDF</b> not found.
# So, fig_count and total_fig_count will be set to 1, in inw_inward_dtl table.
# Note: This is an auto-generated email.
# Thanks & Regards,
# Virtual Assistant
# Transforma Pvt. Ltd.</pre>"""
        # utils.send_email(subject, body, messages)
        return None

    if not candidates:
        messages.append(f'No files found for {pub_id}')
        subject = f'No files found for {pub_id} in Journal Booking'
        body= f"""<pre>Dear Team,
No files found in zip for {pub_id}.
Please check the file.
Note: This is an auto-generated email.
Thanks & Regards,
Virtual Assistant
Transforma Pvt. Ltd.</pre>
"""   
        utils.send_email(subject, body, messages)
        return None

    candidates.sort(key=lambda x: x['p'], reverse=True)
    winner = candidates[0]

    return winner


def send_critical_alert(subject, error_msg, path, messages):
    full_body = f"""<pre>
Dear Team,

An error occurred during Journal Booking for: {os.path.basename(path)}

ERROR DETAILS:
{error_msg}

Thanks & Regards,
Virtual Assistant
Transforma Pvt. Ltd.
</pre>"""
    utils.send_email(subject, full_body, messages)
    messages.append(f"ERROR: {subject}")


# MARK: GET METADATA
def extract_metadata(folder_path, winner_path, messages):
    meta = {'author': '', 'email': '', 'instructions': ''}
    
    def get_first_author(full_string):
        if not full_string:
            return ""
        delimiters = [',', ';', ' and ', '&']
        first_author = full_string
        for sep in delimiters:
            if sep in first_author:
                first_author = first_author.split(sep)[0]
        return first_author.strip()

    for root, dirs, files in os.walk(folder_path):
        for file in files:
            if file.lower().endswith('.xml'):
                try:
                    xml_full_path = os.path.join(root, file)
                    tree = ET.parse(xml_full_path)
                    root_node = tree.getroot()
                    content_text = ET.tostring(root_node, encoding='utf-8', method='text').decode('utf-8')
                    
                    author_match = re.search(r'<given-names>(.*?)</given-names>.*?<surname>(.*?)</surname>', content_text, re.S)
                    email_match = re.search(r'([\w\.-]+@[\w\.-]+\.\w+)', content_text)
                    
                    if author_match:
                        raw_name = f"{author_match.group(1)} {author_match.group(2)}".strip()
                        meta['author'] = get_first_author(raw_name)
                    
                    if email_match:
                        meta['email'] = email_match.group(1)
                    
                    if meta['author'] or meta['email']:
                        messages.append(f"Metadata extracted from XML: {file}")
                        return meta
                except:
                    continue

    for root, dirs, files in os.walk(folder_path):
        for file in files:
            if file.lower().endswith('.txt') and "readme" in file.lower():
                try:
                    with open(os.path.join(root, file), 'r', encoding='utf-8', errors='ignore') as f:
                        meta['instructions'] = f.read()
                except:
                    continue

    if winner_path:
        ext = winner_path.lower()
        if ext.endswith(('.docx', '.doc')):
            try:
                word = win32com.client.DispatchEx("Word.Application")
                word.Visible = False
                word.DisplayAlerts = 0
                doc = word.Documents.Open(os.path.abspath(winner_path), ReadOnly=True)
                
                if not meta['author']:
                    try:
                        raw_val = doc.BuiltInDocumentProperties("Author").Value
                        meta['author'] = get_first_author(raw_val)
                    except:
                        pass
                
                if not meta['email']:
                    text_sample = doc.Range(0, 3000).Text
                    e_m = re.search(r'([\w\.-]+@[\w\.-]+\.\w+)', text_sample)
                    if e_m:
                        meta['email'] = e_m.group(1)
                
                doc.Close(False)
                word.Quit()
            except:
                if 'word' in locals() and word:
                    word.Quit()
            
        elif ext.endswith('.pdf'):
            try:
                with open(winner_path, 'rb') as f:
                    reader = PyPDF2.PdfReader(f)
                    info = reader.metadata
                    if info and info.author:
                        meta['author'] = get_first_author(info.author)
                    
                    if not meta['email']:
                        page1_text = reader.pages[0].extract_text()
                        e_m = re.search(r'([\w\.-]+@[\w\.-]+\.\w+)', page1_text)
                        if e_m:
                            meta['email'] = e_m.group(1)
            except:
                pass

    meta['author'] = get_first_author(meta['author'])
    return meta

def get_pub_id(path):
    basename = os.path.basename(path)
    if basename.lower().endswith('.tar.bz2'):
        basename = basename[:-len('.tar.bz2')]
        return basename.split('-')[0]
    else:
        basename = os.path.splitext(basename)[0]
        return basename.replace('-typesetting', '').replace('-updated', '')

#MARK: DUE DATE
def get_due_date(add_days, holidays=None, skip_saturday='0', skip_sunday='0'):
    if holidays is None:
        holidays = []
    
    current_date = datetime.now()
    added_days = 0
    
    while added_days < add_days:
        current_date += timedelta(days=1)
        
        if skip_sunday == '1' and current_date.weekday() == 6:
            continue
            
        if skip_saturday == '1' and current_date.weekday() == 5:
            continue
            
        if current_date.strftime('%Y-%m-%d') in holidays:
            continue
            
        added_days += 1
        
    return current_date.strftime('%Y-%m-%d')


def get_extended_path(path):
    abs_path = os.path.abspath(path)
    normalized = abs_path.replace('/', '\\')
    if not normalized.startswith('\\\\?\\'):
        return '\\\\?\\' + normalized
    return normalized

#MARK: VERSION FINDER
def run_version_finder(ext_path, messages):
    temp_flatten_dir = os.path.join(ext_path, "version_temp_flatten")
    os.makedirs(temp_flatten_dir, exist_ok=True)
    
    try:
        for root, dirs, files in os.walk(ext_path):
            if "version_temp_flatten" in root: continue 
            for file in files:
                source_file = os.path.join(root, file)
                shutil.copy2(source_file, temp_flatten_dir)

        version_script_path = r".\find_version.py"
        subprocess.check_output(['py', version_script_path, temp_flatten_dir], text=True)

        temp_txt_path = os.path.join(temp_flatten_dir, "version.txt")
        
        if os.path.exists(temp_txt_path):
            with open(temp_txt_path, 'r') as f:
                content = f.read()

            corrected_content = content.replace(temp_flatten_dir, ext_path)

            final_txt_path = os.path.join(ext_path, f"version.txt")
            with open(final_txt_path, 'w') as f:
                f.write(corrected_content)
            
            messages.append(f"Version metadata corrected and moved to: {final_txt_path}")
        else:
            messages.append("Warning: version.txt was not generated by the find_version script.")

    except Exception as e:
        messages.append(f"Error in version flattening process: {str(e)}")
    
    finally:
        if os.path.exists(temp_flatten_dir):
            shutil.rmtree(temp_flatten_dir)


# MARK: MAIN 
def main(path, check_path, cust_name, cust_id, prod_base_path):
    messages=[]
    download_dir= os.path.join(prod_base_path, 'downloads', 'JOURNALS')
    production_dir= os.path.join(prod_base_path, 'production', 'JOURNALS')  #r'D:\Abishek_projects\booking_process\JOURNAL\production\JOURNALS'


    log_path= os.path.join(config.log_base_path, cust_name)
    os.makedirs(log_path, exist_ok=True)
    log_path= os.path.join(log_path, f'{os.path.splitext(os.path.basename(path))[0]}_{datetime.now().strftime("%Y-%m-%d")}.log')
    messages.append(f"Process starts at {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    messages.append("-" * 150)
    messages.append(f"Starting process for path: {path}")
    
    
    try:
        cursor.execute("SELECT wf_hdate FROM wf_holidays")
        holidays = [row[0].strftime('%Y-%m-%d') if hasattr(row[0], 'strftime') else str(row[0]) 
            for row in cursor.fetchall()]
        # print(holidays)
        zip_dir= os.path.join(check_path, 'ZIP-FILES')
        os.makedirs(zip_dir, exist_ok=True)
        shutil.copy(path, zip_dir)
        
        pub_id= get_pub_id(path)
        volume_folder= 'Vol00000'

        cursor.execute("""SELECT j_id, j_code, j_name, j_fp_wf FROM adm_journals WHERE %s LIKE CONCAT('%%', j_code, '%%') AND j_cust_id = %s ORDER BY CHAR_LENGTH(j_code) DESC LIMIT 1""", (pub_id, cust_id))
        journal_row = cursor.fetchone()
        # print(j_id)
        if not journal_row:
            send_critical_alert(f"Unregistered Journal in Journal Booking: {pub_id}", "Journal ID not found in adm_journals table.\nPlease verify", path, messages)
            return
        j_id, j_type, j_name, j_fp_wf = journal_row
        # print(j_id, j_type)
        messages.append(f"Journal ID: {j_id}")
        messages.append(f"Journal Type: {j_type}")
        messages.append(f"Journal Workflow ID: {j_fp_wf}")

        cursor.execute("SELECT id FROM inw_inward_dtl WHERE pub_id = %s", (pub_id,))
        existing_record = cursor.fetchone()
        if existing_record:
            messages.append(f"Record already exists for {pub_id}. Skipping...")
            subject = f'Record already exists for {pub_id} in Journal Booking'
            body = f"""<pre>Dear Team, 
Journal Booking for {pub_id}, 
record already exists in `inw_inward_dtl` table.

Please update the existing record.
Note: This is an auto-generated email.
Thanks & Regards,
Virtual Assistant
Transforma Pvt. Ltd.</pre>"""
            utils.send_email(subject, body, messages)

        work_id = existing_record[0] if existing_record else None
        # print(work_id)

    
        # cursor.execute("""SELECT wf_id, wf_code, wf_name FROM `adm_workflow_master` WHERE wf_name = %s and w_cust_id = %s""", (j_fp_wf, cust_id))
        cursor.execute("""SELECT wf_id, wf_code, wf_name FROM `adm_workflow_master` WHERE wf_name = %s""", (j_fp_wf))
        wf_result = cursor.fetchone()
        if not wf_result:
            send_critical_alert(f"Unregistered Workflow in Journal Booking: {j_fp_wf}", f"Workflow ID not found in adm_workflow_master table.\nDetails:\n\tJournal_workflow_id: {j_fp_wf},\n\tCustomer id: {cust_id}\nPlease verify", path, messages)
            return
        wf_id, wf_code, wf_name = wf_result
        messages.append(f"Workflow ID: {wf_id}")
        messages.append(f"Workflow Code: {wf_code}")

    
        ext_path=  get_extended_path(os.path.join(download_dir, cust_name, j_type, volume_folder, pub_id, 'FP'))
        os.makedirs(ext_path, exist_ok=True)
        try:
            shutil.copy(path, ext_path)
        except Exception as e:
            messages.append(f"Failed to copy source to ext_path: {str(e)}")

        
        prod_path= os.path.join(production_dir, cust_name, j_type, volume_folder, pub_id, 'FP')
        os.makedirs(prod_path, exist_ok=True)
        
        success = False

        
        if path.lower().endswith('.zip'):
            success, messages = unzip_nested_zip(path, ext_path, messages)
        elif path.lower().endswith('.tar.bz2'):
            success, messages = extract_tar_bz2(path, ext_path, messages)

        if not success:
            send_critical_alert(f"Extraction Failed in Journal Booking for {pub_id}", "The ZIP or TAR file is corrupted to extract.\n ", path, messages)
            return

        
        prod_graphics_path= os.path.join(prod_path, 'Graphics')
        prod_graphics_web_path= os.path.join(prod_graphics_path, 'Web')
        prod_graphics_print_path= os.path.join(prod_graphics_path, 'Print')
        os.makedirs(prod_graphics_path, exist_ok=True)  
        os.makedirs(prod_graphics_web_path, exist_ok=True)
        os.makedirs(prod_graphics_print_path, exist_ok=True)

        
        # print(success)
        if success:
            print(f'Calling version finder for {pub_id}')
            run_version_finder(ext_path, messages)
            print(f'Version finder completed for {pub_id}')
            winner= find_correct_files(ext_path, pub_id, messages)
            count, file_path, root, ext= 0, None, None, None
            if winner:
                count, file_path, root, ext= winner.get('p'), winner.get('path'), winner.get('root'), winner.get('ext')
            inwd_type = ""
            if ext.upper() == 'PDF':
                inwd_type = 'Latex'
            elif ext.upper() == 'DOCX':
                inwd_type = 'docx'
            fp_entry_dept = ""
            cursor.execute("""SELECT fp_entry_dept from adm_customer_master where id= %s""", (cust_id,))
            fp_entry_dept= cursor.fetchone()[0]

            # due_days= config.due_date.get(int(cust_id), 2)
            cursor.execute("""SELECT due_days, skip_saturday, skip_sunday from ftp_customer_stage WHERE cust_id = %s and stage = 'FP'""", (cust_id,))
            row = cursor.fetchone()

            if row:
                due_days, skip_saturday, skip_sunday = row
            else:
                messages.append(f"Warning: No 'FP' stage config found for cust_id {cust_id} in tp_customer_stage for due date calculation. Using defaults. (2 days, 0, 1)")
                due_days, skip_saturday, skip_sunday = 2, '0', '1'

            due_date = get_due_date(due_days, holidays, skip_saturday, skip_sunday) 
            # print(due_date)

            metadata = extract_metadata(ext_path, file_path, messages)
            # print(metadata)

            dept_ids = fp_entry_dept.split(',')
            # print(dept_ids, inwd_type)
            target_dept_id =0
            if inwd_type.lower() == 'latex':
                target_dept_id = dept_ids[1].strip()
            elif inwd_type.lower() == 'docx':
                target_dept_id = dept_ids[0].strip()
            # print(target_dept_id)
            
            target_dept_name = None
            if target_dept_id != 0:
                cursor.execute("""SELECT dept_name from adm_dept_master where dept_code= %s""", (target_dept_id,))
                target_dept_name= cursor.fetchone()[0]
            # print(target_dept_name)
            # print(target_dept_name)
            if target_dept_name is not None:
                prod_dept_path= os.path.join(prod_path, target_dept_name)
                os.makedirs(prod_dept_path, exist_ok=True)
            # print(due_date)
            if not work_id:
                work_dir = os.path.join(r'E:/New_Workflow_Files/JOURNAL', 'production', 'JOURNALS', cust_name, j_type)
                work_dir = re.sub(r'\\', '/', work_dir)
                # work_dir = re.split(fr'.*{j_name}', work_dir, 1)
                # print(work_dir)
                cursor.execute("""
                    INSERT INTO inw_inward_dtl (cust_id, j_id, job_id, journal_title, pub_id, accpt_dt, recv_dt, due_dt, stage, wf_id, department_id, work_dir, status, approved_by, volume_folder, complexity, graphics, inw_type, xperttrack_sync, page_count, corresponding_author, corresponding_mail) 
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """, (cust_id, j_id, pub_id, j_name, pub_id, datetime.now(), datetime.now(), due_date, 'FP', wf_id, target_dept_id, work_dir, target_dept_name, '1', volume_folder, 'Regular', '1', inwd_type, '1', count, metadata.get('author'), metadata.get('email')))
                work_id = cursor.lastrowid
                conn.commit()
                messages.append(f'Records inserted successfully for {pub_id} in inw_inward_dtl table')
                messages.append(f'ID: {work_id}')
                messages.append(f'Inserted attributes: \n\t\t\t\t\t\t- cust_id: {cust_id}\n\t\t\t\t\t\t- j_id: {j_id}\n\t\t\t\t\t\t- pub_id: {pub_id}\n\t\t\t\t\t\t- journal_title: {j_name}\n\t\t\t\t\t\t- due_dt: {due_date}\n\t\t\t\t\t\t- wf_id: {wf_id}\n\t\t\t\t\t\t- department_id: {target_dept_id}\n\t\t\t\t\t\t- status: {target_dept_name}\n\t\t\t\t\t\t- inw_type: {inwd_type}\n\t\t\t\t\t\t- page_count: {count}')
            print(f"Processed {pub_id} (WorkID: {work_id})")
            messages.append(f"Processed {pub_id} (WorkID: {work_id})")
            
            print(f'Calling page count for {pub_id}')
            page_count_path = config.page_count_path
            try:
                output = subprocess.check_output(
                    ['py', page_count_path, path], 
                    stderr=subprocess.STDOUT, 
                    text=True
                )
                print(output) 
                if "Process completed" in output:
                    messages.append("Page count finished successfully.")
                else:
                    messages.append(f"Page count script did not report completion for {pub_id} ")
            
            except subprocess.CalledProcessError as e:
                messages.append(f"Error running page count script: {e.output}")
            except Exception as e:
                messages.append(f"General error during sub-process call: {e}")

        os.remove(path)

    except Exception as e:
        full_traceback = traceback.format_exc()
        print(f"Error processing {path}: {full_traceback}")

    finally:
        messages.append(f"Process completed at {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        messages.append("-" * 150)
        messages.append('\n')
        with open(log_path, 'a', encoding='utf-8') as log_file_handle:
            for message in messages:
                log_file_handle.write(message + '\n')
        
        print('Booking Process Completed')


#MARK: START
if __name__ == '__main__':
    if conn:
        cursor.execute("""SELECT automation_path, journal_auto_cust, production_path FROM wf_settings WHERE set_id = 1""")
        base_path, cust_ids, prod_base_path = cursor.fetchone()
        cust_ids= cust_ids.split(',')
        # cust_ids=[33]
        for cust_id in cust_ids:
            cust_id= int(cust_id)
            cursor.execute("""SELECT cust_name, automation_folder from adm_customer_master WHERE id = %s""", (cust_id))
            cust_name, automation_folder= cursor.fetchone()
            check_path=os.path.join(base_path, automation_folder)
            if os.path.exists(check_path):
                for file in os.listdir(check_path):
                    if file.endswith('.zip') or file.endswith('.tar.bz2'):
                        process_path = os.path.join(check_path, file)
                        temp_rename_path = process_path + ".sc"
                        try:
                            os.rename(process_path, temp_rename_path)
                            os.rename(temp_rename_path, process_path)
                            
                            print(f"Processing: {file}")
                            main(process_path, check_path, cust_name, cust_id, prod_base_path)
                            
                        except OSError:
                            print(f"Skipping (File Busy): {file}")
                            continue