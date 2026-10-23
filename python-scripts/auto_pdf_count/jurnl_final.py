from PyPDF2 import PdfReader
import sys
import mysql.connector
import re
import os
from datetime import datetime
import smtplib
import zipfile
import glob
from docx import Document
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import win32com.client
import config_jrnl

def send_mail(subject, body, file_status):
    if file_status:
        to_emails = config_jrnl.crt_to
        cc_emails = config_jrnl.crt_cc
    else:
        to_emails = config_jrnl.wrg_to
        cc_emails = config_jrnl.wrg_cc
    # to_emails = ["arunkumar@transforma.in"]
    # cc_emails = ["arunkumar@transforma.in"]

    sender = config_jrnl.sender
    password = config_jrnl.password

    # Ensure both are lists
    if not isinstance(to_emails, list):
        to_emails = [to_emails]

    if not isinstance(cc_emails, list):
        cc_emails = [cc_emails]

    # Prepare the email
    msg = MIMEMultipart()
    msg["From"] = sender
    msg["To"] = ", ".join(to_emails)
    msg["Cc"] = ", ".join(cc_emails)
    msg["Subject"] = subject

    msg.attach(MIMEText(body, "html"))

    # Combine all recipients for sending
    # all_recipients = to_emails if isinstance(to_emails, list) else [to_emails]
    # all_recipients += cc_emails if isinstance(cc_emails, list) else [cc_emails]
    all_recipients = to_emails + cc_emails

    try:
        # Connect to SMTP server
        server = smtplib.SMTP("smtp.office365.com", 587)
        server.starttls()
        server.login(sender, password)
        server.sendmail(sender, all_recipients, msg.as_string())
        server.quit()
        print("Mail sent successfully.")
    except Exception as e:
        print("Mail failed:", e)

def check_query(text):

    # if Author Query text is present it is the query page
    pattern = re.compile(r"author\s*query", re.IGNORECASE)
    if pattern.search(text):
        return "present"
    
    pattern = re.compile(r"queries\s*for\s*the\s*author", re.IGNORECASE)
    if pattern.search(text):
        return "present"
    
    #Find the accurance of Quick Guidelines to Reviewing
    pattern = re.compile(r"quick\s*guidelines\s*to\s*reviewing", re.IGNORECASE)
    if pattern.search(text):
        return "present"
    
    value = []
    if "Query" not in text:
        pattern = r"^Q\d{1,3} \(p\.\d{1,3}\)"
        for line in text.split("\n"):
            if re.match(pattern, line):
                value.append("present")
        
    if value:
        return "present"
    else:
        return "Not present"

def pagecount(path):
    
    reader = PdfReader(path)
    count = 0
    for page in reader.pages:
        if page:
            text = page.extract_text()
            result = check_query(text)
            if result == "Not present":
                count += 1
    return count

def merge_docx(docx_files, output_file):
    merged_doc = Document()

    for index, file in enumerate(docx_files):

        sub_doc = Document(file)

        if index == 0:
            merged_doc = sub_doc
        else:
            merged_doc.add_page_break()

            for element in sub_doc.element.body:
                merged_doc.element.body.append(element)

    merged_doc.save(output_file)

def docx_page_count(file_path):
    word = win32com.client.Dispatch("Word.Application")
    word.Visible = False

    doc = word.Documents.Open(file_path)

    pages = doc.ComputeStatistics(2)

    doc.Close()
    word.Quit()

    return pages

#MARK:Connect Database  
def data_entry(row_id, rev_id):
    try:
        conn = mysql.connector.connect(
        host=config_jrnl.db_host,
        user=config_jrnl.db_user,
        password=config_jrnl.db_password,
        database=config_jrnl.db_database)

        # if conn.is_connected():
        #     print("Database connected successfully.")
        cursor = conn.cursor(dictionary=True)
#         else:
#             subject = f'⚠ JOURNAL - AUTO PDF COUNT UPDATE ERROR'
#             body = f"""<pre>
# Dear Team,<br>
# ⚠ Database : "workflow" connection failed.<br>
# \tkindly inform the concerned team for quick resolution.<br>
# <b>Note: This is an auto generated email. Please do not reply to this.</b><br>
# Thanks & Regards,
# FTP Virtual Assistant
# Transforma Pvt. Ltd.</pre>"""
#             file_status = False
#             send_mail(subject, body, file_status)
#             return "failed", None, None, None

        # Parameterized query
        query = "SELECT * FROM inw_inward_dtl WHERE id = %s"
        cursor.execute(query, (row_id,))
        row = cursor.fetchone()

        if not row:
            print("No row found with that ID.")
            subject = f'⚠ JOURNAL - AUTO PDF COUNT UPDATE ERROR'
            body = f"""<pre>
Dear Team,<br>
⚠ id : "{row_id}" does not matched in inw_inward_dtl table <br>
\tPlease verify the input id<br>
<b>Note: This is an auto generated email. Please do not reply to this.</b><br>
Thanks & Regards,
FTP Virtual Assistant
Transforma Pvt. Ltd.</pre>"""
            file_status = False
            send_mail(subject, body, file_status)
            return "failed", None, None, None
        
        #Insert values in inw_inward_dtl table
        path = row['work_dir'].replace('/', '\\')
        test_path = row['work_dir'].replace("E:/New_Workflow_Files/JOURNAL/", "J:/JOURNAL/").replace('/', '\\')
        
        stage = row['stage']
        status = 'PRQC'      #row['status']
        jurnl_name = row['pub_id']
        
        normalized_path = path.rstrip("\\/")
        test_normalized_path = test_path.rstrip("\\/")
        
        # file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, row['status'], row['pub_id'] + ".pdf")
        # test_file_path = os.path.join(test_normalized_path, row['volume_folder'], row['pub_id'], stage, row['status'], row['pub_id'] + ".pdf")
        file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
        test_file_path = os.path.join(test_normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
        if not os.path.exists(file_path):
            file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC-ELX')
        new_pdf_path = row['pub_id'] + ".pdf"
        if row['cust_id'] == ('57'):
            if stage == 'FP':
                new_pdf_path = f"{row['pub_id']}_1.pdf"
            elif 'PMC' in stage:
                # fol_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
                if os.path.exists(file_path):
                    pattern = rf'^{re.escape(row["pub_id"])}[-_]\d\.pdf$'
                    matched_files = [f for f in os.listdir(file_path) if re.match(pattern, f)]
                    if matched_files:
                        new_pdf_path = matched_files[0]
                
                # new_pdf_path = f"{row['pub_id']}-2.pdf"
            elif 'REV' in stage:
                numbers = ''.join(c for c in stage if c.isdigit())
                new_pdf_path = f"{row['pub_id']}_1-{int(numbers) + 1}.pdf"
                if not os.path.exists(os.path.join(file_path, new_pdf_path)):
                    new_pdf_path = f"{row['pub_id']}_2.pdf"
                    if not os.path.exists(os.path.join(file_path, new_pdf_path)):
                        new_pdf_path = f"{row['pub_id']}-2.pdf"
            elif 'ISSUE' in stage:
                # fol_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
                if os.path.exists(file_path):
                    pattern = rf'^\d+_{re.escape(row["pub_id"])}_print\.pdf$'
                    matched_files = [f for f in os.listdir(file_path) if re.match(pattern, f)]
                    if matched_files:
                        new_pdf_path = matched_files[0]
        #If Customer is "ASP" then add the issueorgroup_folder column after the volume folder
        elif row['cust_id'] == '37':
            group_folder = row['issueorgroup_folder'].replace('/', '')
            group_file_path = os.path.join(normalized_path, row['volume_folder'], group_folder)
            if os.path.exists(os.path.join(group_file_path)):
                file_path = os.path.join(group_file_path, row['pub_id'], stage, 'PRQC')
                if not os.path.exists(file_path):
                    file_path = os.path.join(group_file_path, row['pub_id'], stage, 'PRQC-ELX')
            else:
                file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
                if not os.path.exists(file_path):
                    file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC-ELX')
            # if not os.path.exists(file_path):
            #     file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'PRQC')
            if os.path.exists(os.path.join(file_path, f"{row['pub_id']}.docx")):
                new_pdf_path = f"{row['pub_id']}.docx"

        file_path = os.path.join(file_path, new_pdf_path)
        test_file_path = os.path.join(test_file_path, new_pdf_path)

        if row['cust_id'] == '58':
            file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'Delivery')
            if os.path.exists(file_path):
                zip_file = os.path.join(file_path, f"{row['pub_id']}.zip")
                if os.path.exists(zip_file):
                    extract_folder = os.path.join(file_path, row['pub_id'])
                    os.makedirs(extract_folder, exist_ok=True)

                    with zipfile.ZipFile(zip_file, 'r') as z:
                        z.extractall(extract_folder)

                    docx_files = glob.glob(os.path.join(extract_folder, "**", "*.docx"), recursive=True)
                    if docx_files:
                        docx_files.sort()

                        file_path = os.path.join(extract_folder, "merged.docx")
                        merge_docx(docx_files, file_path)

                        # 5️⃣ get page count
                        # pages = get_page_count(merged_doc)
                        test_file_path = os.path.join(normalized_path, row['volume_folder'], row['pub_id'], stage, 'Delivery', row['pub_id'], "merged.docx")

        # print("\n", file_path, "====\n", test_file_path)
        
        if not os.path.exists(file_path):
            print("PDF does not exist in folder")
            subject = f'⚠ JOURNAL - AUTO PDF COUNT UPDATE ERROR'
            jurnl = f"""<pre>
Dear Team,<br>
⚠ File Path : "{file_path}" does not have any (.pdf) file for "{row['pub_id']}" journal<br>
\tPlease verify this file path.<br>

Check this id's for testing ==> id : {row_id} in inw_inward_dtl in workflow database(10.10.110)
local_path : {test_file_path}

<b>Note: This is an auto generated email. Please do not reply to this.</b><br>
Thanks & Regards,
FTP Virtual Assistant
Transforma Pvt. Ltd.</pre>"""
            file_status = False
            send_mail(subject, jurnl, file_status)
            return "failed", None, None, None
        if 'pdf' in os.path.splitext(file_path)[-1]:
            pg_count = pagecount(file_path)
        else:
            pg_count = docx_page_count(file_path)

        update_query = "UPDATE inw_inward_dtl SET auto_pdf_page_count = %s WHERE id = %s"
        cursor.execute(update_query, (pg_count, row_id))
        conn.commit()

        #Insert values in inw_revisions_dtl table
        query2 = "SELECT * FROM inw_inward_dtl WHERE id = %s"
        cursor.execute(query2, (row_id,))
        rev_row = cursor.fetchone()
        
        #stage
        stage = rev_row['stage']
        match = re.match(r"([A-Za-z]+)(\d+)", stage)

        if match:
            typ = match.group(1)
            revision = int(match.group(2))
        else:
            typ = stage
            revision = 1 

        #Filepath
        filepath = os.path.join(rev_row['work_dir'], rev_row['volume_folder'], rev_row['pub_id'], rev_row['stage']).replace("production", "downloads").replace('\\', '/')

        inw_id = row_id
        revision_count = revision
        received_date = rev_row['recv_dt']
        due_date = rev_row['due_dt']
        correction_pages = 0
        page_count = 0
        auto_pdf_page_count = pg_count
        comments = "Initial revision"
        file_name = filepath
        created_by = 54
        created_on = datetime.now().date()
        type_value = typ 

        # Check if row exists in inw_revisions_dtl
        check_query = """
        SELECT r_id FROM inw_revisions_dtl
        WHERE inw_id = %s AND type = %s AND revision_count = %s
        """
        cursor.execute(check_query, (inw_id, type_value, revision_count))
        existing_row = cursor.fetchone()

        if existing_row:
            update_query = """
            UPDATE inw_revisions_dtl
            SET auto_pdf_page_count = %s
            WHERE r_id = %s
            """
            cursor.execute(update_query, (auto_pdf_page_count, existing_row['r_id']))
            conn.commit()
            print(f"Updated auto_pdf_page_count for row ID {existing_row['r_id']}")
        else:
            insert_query = """
            INSERT INTO inw_revisions_dtl
            (inw_id, revision_count, received_date, due_date, correction_pages,
            page_count, auto_pdf_page_count, comments, file_name, created_by, created_on, type)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            values = (
                inw_id, revision_count, received_date, due_date, correction_pages,
                page_count, auto_pdf_page_count, comments, file_name, created_by, created_on, type_value
            )
            #cursor.execute(insert_query, values)
            #conn.commit()
            #print(f"Inserted new revision row for inw_id {inw_id}")

        cursor.execute("UPDATE inw_dispatch_history SET page_count_status = %s WHERE idh_id = %s", (1, rev_id))
        conn.commit()
        return "success", jurnl_name, stage, status

    except mysql.connector.Error as db_err:
        print(f"MySQL error: {db_err}")
    except FileNotFoundError as fnf_err:
        print(f"File not found: {fnf_err}")
    except Exception as e:
        print(f"An error occurred: {e}")
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

if __name__ == '__main__':
    
    # id = sys.argv[1]
    # filestatus, jurnl_name, stage, status = data_entry(id)

    conn = mysql.connector.connect(
    host=config_jrnl.db_host,
    user=config_jrnl.db_user,
    password=config_jrnl.db_password,
    database=config_jrnl.db_database)

    cursor = conn.cursor(dictionary=True, buffered=True)

    cursor.execute('select * from inw_dispatch_history where DATE(sent_date) = CURDATE() and page_count_status = 0')
    rows = cursor.fetchall()
 
    if not rows:
        print({"message": "There are no records found in the database to process."})
        sys.exit()

    for row in rows:
        # print(row)
        filestatus, jurnl_name, stage, status = data_entry(row['a_id'], row['idh_id'])

        if filestatus == "success":
            subject = f'Auto page count updated successfully for {jurnl_name}'
            body = f"""
    <html>
    <head>
    <style>
        body {{
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f7f6;
            padding: 20px;
        }}
        .container {{
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 8px;
            border: 1px solid #d9e6d8;
            max-width: 650px;
            margin: auto;
            line-height: 1.6;
            font-size: 14px;
            color: #333;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }}
        .success-title {{
            font-size: 18px;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 10px;
        }}
        .success-box {{
            background: #e8f5e9;
            border-left: 4px solid #2e7d32;
            padding: 12px 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #2e7d32;
            font-weight: bold;
        }}
        .note {{
            margin-top: 25px;
            font-size: 12px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }}
    </style>
    </head>

    <body>
    <div class="container">

        <div class="success-title">Process Completed Successfully</div>

        <div class="success-box">
            The auto page count updated for <b>"{jurnl_name}" - {stage} - {status}</b>.
        </div>

        <p>
            If you notice any discrepancies or require further assistance, kindly inform the concerned team for quick resolution.
        </p>

        <p class="note">
            <b>Note:</b> This is an auto-generated email. Please do not reply to this notification.
        </p>

        <p>
            Thanks & Regards,<br>
            FTP Virtual Assistant<br>
            Transforma Pvt. Ltd.
        </p>

    </div>
    </body>
    </html> 
    """
            file_status = True 
            
            # send_mail(subject, body, file_status)
        else:
            print("An error occured during inserting")

