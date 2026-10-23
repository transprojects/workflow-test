import os, sys
import zipfile
import memo_file
import time
import random
import config
from docx import Document
import pymysql
from datetime import date, datetime
import win32com.client


#MARK: Log
#Function to write log messages
def log_message(messages):
    print("Log Messages: ", messages)
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
            database = db_database,
            port=3306,
            cursorclass=pymysql.cursors.DictCursor
        )
        cursor = conn.cursor()
        print("Database Connected Successfully.")
        return conn, cursor, messages
    except Exception as e:
        print(f'Error in "database_connection" function. {e}')

        subject = f'Database Connection Failed'
        body = f"""\tDatabase Connection Failed<br>
\t<a href="file://">{e}</a>.<br>"""
        # messages = utils.send_email(subject, body, messages)

        return None, None, messages

# #MARK: Page Count
def get_docx_metrics(path):
    abs_path = os.path.abspath(path)
    pages, words, tables, figures = 0, 0, 0, 0
    manuscript_count, prgh_count, char_count = 0, 0, 0  # <-- ADDED
    
    max_retries = 3
    wait_time = 2

    for attempt in range(max_retries):
        word = None
        doc_com = None
        try:
            word = win32com.client.DispatchEx("Word.Application")
            word.Visible = False
            word.DisplayAlerts = 0 
            word.Options.ConfirmConversions = False

            doc_com = word.Documents.Open(abs_path, ReadOnly=True, OpenAndRepair=True)
            doc_com.Repaginate()
            
            pages = doc_com.ComputeStatistics(2) # wdStatisticPages
            words = doc_com.ComputeStatistics(0) # wdStatisticWords
            tables = doc_com.Tables.Count
            figures = doc_com.InlineShapes.Count + doc_com.Shapes.Count
            prgh_count = doc_com.ComputeStatistics(4)   # wdStatisticParagraphs
            char_count = doc_com.ComputeStatistics(5)   # wdStatisticCharactersWithSpaces
            manuscript_count = pages
            
            doc_com.Close(False)
            word.Quit()
            return {"fig": figures, "tab": tables, "manuscript_count": manuscript_count, "page_count": pages, "prgh_count": prgh_count,"word_count": words, "char_count": char_count}

        except Exception as e:
            error_str = str(e)
            if "Command failed" in error_str or "-2147352567" in error_str:
                
                # Cleanup current failed instance
                if doc_com: 
                    try: doc_com.Close(False)
                    except: pass
                if word: 
                    try: word.Quit()
                    except: pass
                
                # Wait with Jitter: If two scripts collide, they will restart at different times
                time.sleep(wait_time + random.uniform(1, 3))
                wait_time *= 2 # Double the wait for next attempt
                continue 
            else:
                print(f"Error in getting docx metrics for : {e}")
        finally:
            # Final safety cleanup
            if word:
                try: word.Quit()
                except: pass

    # return pages, words, tables, figures
    return {"fig": figures, "tab": tables, "manuscript_count": manuscript_count, "page_count": pages, "prgh_count": prgh_count,"word_count": words, "char_count": char_count}

def get_title(doc_path, docx_values):
    doc = Document(doc_path)

    line_count = 0

    for para in doc.paragraphs:

        para_text = para.text.strip()

        # skip empty paragraphs
        if not para_text:
            continue

        # check word count
        if len(para_text.split()) > 3:
            docx_values['chapter_title'] = para_text
            break

        # stop after first 3 matching paragraphs
        if line_count == 3:
            break

    return docx_values

#MARK: Extract ZIP
def main(zip_path):
    # try:
        messages = []

        conn, cursor, messages = database_connection(config.db_host, config.db_user, config.db_password, config.db_database, messages)
        if not cursor:
            messages.append(f'Database was not Connected to the {config.db_host}')
            return None, messages
        
        cursor.execute("SELECT cust_name FROM adm_customer_master WHERE id = %s", (89,))
        cust_row = cursor.fetchone()
        if cust_row:
            cust_name = cust_row['cust_name']
        else:
            cust_name = None
            print("No customer found with this ID")

        extract_to = os.path.join(os.path.dirname(zip_path))
        manu_path = os.path.join(os.path.splitext(zip_path)[0], 'Manuscript')
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_to)

        # memo_docx = 'samples/Apgar_37340_Bachelors_EG_ASSIGNMENT MEMO_05-06.docx'
        # response = memo_file.get_book_details(memo_docx)
        response = ''
        for root, dirs, files in os.walk(extract_to):
            for file in files:
                if "memo" in file.lower() and file.endswith('.docx'):
                    file_path = os.path.join(root, file)
                    response = memo_file.get_book_details(file_path)
        if not response:
            messages.append(f'Memo file was not present in the zip file. {zip_path}')
            return None, messages
        print(response, "-----------")

        if os.path.exists(manu_path):
            # work_dir = os.path.join("E:\\", config.book_path, config.production_path, cust_name, doi_text).replace("\\","/") + "/"
            work_dir = os.path.join("E:\\", config.book_path, config.production_path, cust_name, os.path.splitext(os.path.basename(zip_path))[0]).replace("\\","/") + "/"
            book_detail = {'book_name': os.path.splitext(os.path.basename(zip_path))[0], 'book_title': response['title_/_subtitle'], 'total_fig': 0, 'total_table': 0, 'total_pages': 0, 'chapter_count': 0, 'total_words': 0, 'total_para': 0, 'isbn': response['isbn_(print)'], 'work_dir': work_dir}

            cursor.execute("SELECT 1 FROM inw_book_dtl WHERE book_name = %s", (book_detail['book_name'],))
            exists = cursor.fetchone()

            if exists:
                print(f"Book \"{book_detail['book_name']}\" already exists. Skipping insert.")
                sys.exit()

            chapter_details = []
            # Loop through all files and folders
            for item in os.listdir(manu_path):
                full_path = os.path.join(manu_path, item)

                if os.path.isfile(full_path):
                    docx_values = {}
                    docx_details = get_docx_metrics(full_path)
                    print(docx_details, "=============")
                    # sys.exit()
                    book_detail['total_fig'] += docx_details['fig']
                    book_detail['total_table'] += docx_details['tab']
                    book_detail['total_pages'] += docx_details['page_count']
                    book_detail['total_words'] += docx_details['word_count']
                    book_detail['total_para'] += docx_details['prgh_count']

                    docx_values['fig_count'] = docx_details['fig']
                    docx_values['tab_count'] = docx_details['tab']
                    docx_values['page_count'] = docx_details['page_count']
                    docx_values['prgh_count'] = docx_details['prgh_count']
                    docx_values['word_count'] = docx_details['word_count']
                    docx_values['char_count'] = docx_details['char_count']
                    docx_values['chapter_name'] = os.path.splitext(item)[0]

                    book_detail['chapter_count'] += 1
                    # print(f"File: {full_path}")

                    docx_values = get_title(full_path, docx_values)
                    
                    chapter_details.append(docx_values)
                elif os.path.isdir(full_path):
                    print(f"Folder: {full_path}")

            if book_detail['chapter_count'] > 1:
                book_detail['book_work_type'] = "split"
            else:
                book_detail['book_work_type'] = "Combine"

            print(book_detail)
            print(chapter_details)
            # sys.exit()
            now = datetime.now()
            formatted_datetime = now.strftime("%Y-%m-%d %H:%M:%S")
            
            cursor.execute("""INSERT INTO inw_book_dtl 
                (cust_id, book_name, book_short_name, isbn_no, book_folder_name, chapter_file_name, color_details, platform, additional_services1, additional_services2, recv_dt, due_dt , fig, tab, manuscript_count, file_name, stage,status, front_matter, chapters, created_by, created_dt, modified_by, modified_dt, approved_by, approved_on, department_id, sub_department_id, assign_department_id, wf_id, instructions, issueorgroup_folder,highpriority, graphics, inw_type, complexity, work_dir, fig_complete_status, volume_folder, v_issue_id, prev_stage, prev_workflow, prev_status, prev_dept_id, present_user_id, temp_volume_folder, temp_v_issue_id, type, page_count,notes,parts,img_redraw_count,img_relabel_count,img_color_figures,article_type,back_matter,covers,book_work_type,book_flow_id,remarks,aq_status,hardback_isbn,paperback_isbn,ebookGeneral_isbn,epub_isbn,webpdf_isbn,set_isbn, auth_notes, usd_count, eqn_count, aud_count, video_count, word_count, para_count,chp_count, binding, reading, format_used, family, face_font, doc, lic_items, lic_res, lic_doc, count_page)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s,%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)  #
                """, (89, book_detail['book_name'], book_detail['book_name'], book_detail['isbn'], book_detail['book_name'], None, "BW", "InDesign", "", "", date.today(), 'final_text', book_detail['total_fig'], book_detail['total_table'], book_detail['total_pages'], None, "JOBANALYSIS", "JOBANALYSIS", None, book_detail['chapter_count'], None, formatted_datetime, None, None, None, formatted_datetime, "32", None, None, wf_id, None, None, None, None, None, None, book_detail['work_dir'], 0, "Vol00000", 0, "", 0, "", 0, 0, "", 0, "", 1, "", None, None, None, 0 ,0, 0, 0, book_detail['book_work_type'], 0, None, None, None, None, None, None, None, None, None, 'usd_text',  'equn_text', 'aud_text', 'vid_text', book_detail['total_words'], book_detail['total_para'], book_detail['chapter_count'], None, None, None, None, None, None, None, None, None, None))

            last_id = cursor.lastrowid

            #Insert chapter details
            cursor.execute("""INSERT INTO inw_chapter_dtl
                (cust_id, b_id, chapter_title, chapter_file_name, title, recv_dt, due_dt, fig, tab, manuscript_count, stage, status, allocate_to, allocate_time, created_by, created_dt, modified_by, modified_dt, approved_by, approved_on, department_id, sub_department_id, assign_department_id, wf_id, instructions, issueorgroup_folder, highpriority, graphics, inw_type, complexity, work_dir, fig_complete_status, volume_folder, v_issue_id, prev_stage, prev_workflow, prev_status, prev_dept_id, present_user_id, temp_volume_folder, temp_v_issue_id, type, page_count, notes, img_disk_count, img_redraw_count, img_relabel_count, img_color_figures, article_type, sequecne_no, remarks, aq_status, assigned_od_user_id, od_due_date, is_active, equn_count, audio_count, vid_count, prgh_count, word_count, char_count, doi)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                """,
                (89, last_id, "BOOK", f"{book_detail['book_name']}_BOOK", "", date.today(), 'final_text', book_detail['total_fig'], book_detail['total_table'], book_detail['total_pages'], "JOBANALYSIS", "JOBANALYSIS", "", None, "--", datetime.now(), "", None,54, None, "32", None, 0, wf_id, "", "", "1", "1", "", "Regular", book_detail['work_dir'], 0, "Vol0000", 0, "", 0, "", 0, 0, "", 0, "", 0, "", None, None, None, 0, "", None, "", "", None, None, 1,None, None, None, None, None, None, None))
        
            for chap in chapter_details:
                cursor.execute("""INSERT INTO inw_chapter_dtl
                    (cust_id, b_id, chapter_title, chapter_file_name, title, recv_dt, due_dt, fig, tab, manuscript_count, stage, status, allocate_to, allocate_time, created_by, created_dt, modified_by, modified_dt, approved_by, approved_on, department_id, sub_department_id, assign_department_id, wf_id, instructions, issueorgroup_folder, highpriority, graphics, inw_type, complexity, work_dir, fig_complete_status, volume_folder, v_issue_id, prev_stage, prev_workflow, prev_status, prev_dept_id, present_user_id, temp_volume_folder, temp_v_issue_id, type, page_count, notes, img_disk_count, img_redraw_count, img_relabel_count, img_color_figures, article_type, sequecne_no, remarks, aq_status, assigned_od_user_id, od_due_date, is_active, equn_count, audio_count, vid_count, prgh_count, word_count, char_count, doi)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    (89, last_id, chap['chapter_title'], 'chapter', "", date.today(), 'final_text', chap['fig_count'], chap['tab_count'], chap['page_count'], "JOBANALYSIS", "JOBANALYSIS", "", None, "--", datetime.now(), "", None, 54, None, "32", None, 0, wf_id, "", "", "1", "1", "", "Regular", book_detail['work_dir'], 0, "Vol0000", 0, "", 0, "", 0, 0, "", 0, "", 0, "", None, None, None, 0, "", None, "", "", None, None, 1, 'chapter_equation', 'chapter_audio', 'chapter_video', chap['prgh_count'], chap['word_count'], chap['char_count'], 'chapter_doi'))

                last_c_id = cursor.lastrowid
        conn.commit()

        #MARK:inw_book_revisions_dtl
        # Check if revision already exists
        last_b_r_id = None
        cursor.execute("""SELECT 1 FROM inw_book_revisions_dtl WHERE b_id=%s AND stage=%s AND batch_label=%s""", (last_id, "JOBANALYSIS", 'BATCH1'))
        revision_exists = cursor.fetchone()
        if revision_exists:
            print(f"Revision for book_id {last_id} with stage 'FP' and batch 1 already exists. Skipping insert.")
        else:
            cursor.execute("""INSERT INTO inw_book_revisions_dtl(b_id, revision_count, stage, batch_label, batch_count, stage_type, status, department_id, wf_id, received_date, due_date, correction_pages, comments, file_name, created_by, created_on, type)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""", (last_id, 0, "JOBANALYSIS", "BATCH1", 1, "Main", "JOBANALYSIS", 32, wf_id, date.today(), '', 1, "", "f_n", 212, formatted_datetime, "JOBANALYSIS"))
            last_b_r_id = cursor.lastrowid
            conn.commit()

        #MARK:inw_chapter_revisions_dt
        if last_b_r_id is not None:
            cursor.execute("""INSERT INTO chapter_revisions_dtl(b_id, b_rev_id, c_id, revision_count, received_date, due_date, stage, department_id, wf_id, correction_pages, created_by, created_on, type) VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""", (last_id, last_b_r_id, last_c_id, 0, date.today(), '', "JOBANALYSIS",32, wf_id, 1, 212, formatted_datetime, "FP"))
            conn.commit()

        print("inserted")
        #get department name
        # query1 = "SELECT wf_code FROM adm_workflow_master WHERE wf_id = %s"
        # cursor.execute(query1, (wf_id,))
        # row1 = cursor.fetchone()
        # if row1:
        #     wf_code = row1['wf_code']

        #     # Step 2 — Use dept_code to get dept_name
        #     query2 = "SELECT dept_name FROM adm_dept_master WHERE dept_code = %s"
        #     cursor.execute(query2, (wf_code,))
        #     row2 = cursor.fetchone()

        #     if row2:
        #         dept_name = row2['dept_name']
        #     else:
        #         print("Department not found")
        #         sys.exit()

        # else:
        #     print("tandf_workflow not found")
        #     sys.exit()

        # output_path = os.path.join("E:", config.book_path, config.folder_path, cust_name, 'isbn_number', config.stage)
        # if not os.path.exists(output_path):
        #     os.makedirs(output_path)
        #     #Extract the zip
        #     with zipfile.ZipFile(zip_path, 'r') as zip_ref:
        #         zip_ref.extractall(output_path)

        # output_path2 = os.path.join("E:", config.book_path, config.production_path, cust_name, 'isbn_number', config.stage, dept_name, config.empty_folder)
        # if not os.path.exists(output_path2):
        #     os.makedirs(output_path2)


        return "Success...!", messages
    # except Exception as e:
    #     print(f'Error occur in main function. {e}')
        # return None, messages

#MARK: Init
if __name__ == '__main__':
    file_path = sys.argv[1]     # f'input/51629_Leik_FNP_6E.zip'      #sys.argv[1]
    wf_id = 293

    resp, msg = main(file_path)
    if msg:
        log_message(msg)
    print("pppppppp")