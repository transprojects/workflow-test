#Import nesseccery libraries
import os, sys
import pymysql
import config
import shutil
import re
import zipfile
from xml.dom import minidom
from pathlib import Path
from pathlib import PureWindowsPath


# Database connection
db = pymysql.connect(
    host = config.db_host,
    user = config.db_user,
    password = config.db_password,
    database = config.db_database
)


def file_exists(path):
    return os.path.isfile(path)

def delete_file(path):
    if os.path.exists(path):
        os.remove(path)

def create_zip(zip_path, files):
    zip_path = PureWindowsPath(zip_path)
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
        for file_path, arcname in files:
            if os.path.isfile(file_path):
                zf.write(file_path, arcname)

def process_issue(v_id):
    # try:
        with db.cursor(pymysql.cursors.DictCursor) as cursor:
            #Get volume issue
            cursor.execute("SELECT * FROM inw_volume_issues WHERE vi_id = %s", (v_id,))
            volume_dtl = cursor.fetchone()
            if not volume_dtl:
                print(f"v_id: {v_id} was not found in inw_volume_issues table. Volume issue not found.")
                return {'status': False, 'message': f"v_id: {v_id} was not found in inw_volume_issues table. Volume issue not found."}
            
            #Get customer details
            cursor.execute("SELECT * FROM adm_customer_master WHERE id = %s", (volume_dtl['cust_id'],))
            crow = cursor.fetchone()
            if not crow:
                print(f"Customer ID not found for {volume_dtl['cust_id']} in adm_customer_master table. Where {v_id}")
                return {'status': False, 'message': f"Customer ID not found for {volume_dtl['cust_id']}. Where {v_id}"}
            
            if crow['cust_name'] == "TSP":
                #Get journal details
                cursor.execute("SELECT * FROM adm_journals WHERE j_id = %s", (volume_dtl['j_id'],))
                jninfo = cursor.fetchone()

                dest_path = volume_dtl['dispatch_dir']
                filename = f"{crow['cust_name']}_{jninfo['j_code']}-{int(volume_dtl['volume_number'])}-{int(volume_dtl['issue_number'])}"

                print_package = PureWindowsPath(os.path.join(dest_path, f"{filename}-Print-pdf.zip"))
                web_package = PureWindowsPath(os.path.join(dest_path, f"{filename}-Online-pdf.zip"))
                all_files_package = PureWindowsPath(os.path.join(dest_path, f"{filename}.zip"))

                # local_path = PureWindowsPath(local_path)
                delete_file(print_package)
                delete_file(web_package)
                delete_file(all_files_package)

                #Get articles (except FM, BM, Hyperlink)
                cursor.execute("""SELECT * FROM inw_inward_dtl WHERE v_issue_id=%s AND pub_id NOT LIKE '%%-FM%%' AND pub_id NOT LIKE '%%-BM%%' AND pub_id NOT LIKE '%%-Hyperlink%%' AND v_issue_sequence_id >= 1 ORDER BY v_issue_sequence_id ASC""", (volume_dtl['vi_id'],))
                articles = cursor.fetchall()
                total_count = len(articles)

                if total_count == 0:
                    print(f"No articles found for {volume_dtl['vi_id']} in inw_inward_dtl table.")
                    return {'status': False, 'message': f"No articles found for {volume_dtl['vi_id']} in inw_inward_dtl table."}

                print_files = []
                web_files = []
                all_files = []

                for article in articles:
                    v_issue_seq_id = str(article['v_issue_sequence_id']).zfill(len(str(total_count)))
                    work_dir = article['work_dir']
                    volume_folder = article['volume_folder']
                    pub_id = article['pub_id']
                    stage = article['stage']

                    if volume_folder in work_dir:
                        src_path = os.path.join(work_dir, pub_id, stage)
                    else:
                        src_path = os.path.join(work_dir, volume_folder, pub_id, stage)
                    
                    pdf_file = PureWindowsPath(os.path.join(src_path, "PRQC", f"{pub_id}.pdf"))
                    pdf_res_file = PureWindowsPath(os.path.join(src_path, "PRQC", f"{pub_id}-highres.pdf"))
                    xml_file = PureWindowsPath(os.path.join(src_path, "XML", f"{pub_id}.xml"))
                    graphics_path = PureWindowsPath(os.path.join(src_path, "Graphics"))
                    graphics_web_path = PureWindowsPath(os.path.join(src_path, "Graphics", "Web"))
                    xhtml_path = PureWindowsPath(os.path.join(src_path, "ePub", pub_id))
                    xhtml_file = PureWindowsPath(os.path.join(xhtml_path, f"{pub_id}.xhtml"))
                    
                    #PDF validation logic
                    pdf_file_check = False
                    if stage != "ISSCOR1":
                        if os.path.isfile(pdf_file):
                            pdf_file_check = True
                    else:
                        pdf_file_check = True
                    
                    if not pdf_file_check:
                        continue
                    
                    #Mandatory PDF checks
                    if not os.path.isfile(pdf_file):
                        return {'status': False, 'message': f"{v_id} - {pub_id}.pdf file not available in PRQC folder, please check it. ({pdf_file})"}
                        # raise FileNotFoundError(f"{v_id} - {pub_id}.pdf file not available in PRQC folder, please check it. ({pdf_file})")

                    if not os.path.isfile(pdf_res_file):
                        return {'status': False, 'message': f"{v_id} - {pub_id}-highres.pdf file not available in PRQC folder, please check it. ({pdf_res_file})"}
                        raise FileNotFoundError(f"{v_id} - {pub_id}-highres.pdf file not available in PRQC folder, please check it. ({pdf_res_file})")

                    #XML validation
                    if not os.path.isfile(xml_file):
                        return {'status': False, 'message': f"{pub_id}.xml file not available in XML folder, please check it. ({xml_file})"}
                        raise FileNotFoundError(f"{pub_id}.xml file not available in XML folder, please check it. ({xml_file})")

                    #Add files to zip lists
                    web_files.append((pdf_file, f"{pub_id}.pdf"))
                    all_files.append((pdf_file, os.path.join(pub_id, f'{pub_id}.pdf')))
                    if os.path.isfile(pdf_res_file):
                        if article["v_issue_sequence_id"] > 0:
                            print_files.append((pdf_res_file, f"{article['v_issue_sequence_id']}. {pub_id}.pdf"))
                        else:
                            print_files.append((pdf_res_file, f"{pub_id}.pdf"))

                    #XML & Graphics
                    if file_exists(xml_file):
                        all_files.append((xml_file, os.path.join(pub_id, f'{pub_id}.xml')))
                        doc = minidom.parse(xml_file)
                        for fig in doc.getElementsByTagName("fig"):
                            for graphic in fig.getElementsByTagName("graphic"):
                                img_name = graphic.getAttribute('xlink:href')
                                img_path = os.path.join(graphics_web_path, img_name)
                                if not os.path.exists(img_name):
                                    return {'status': False, 'message': f"{pub_id}.xml file {img_name} not available in {graphics_web_path} folder, please check it. ({img_path})"}
                                if file_exists(img_path):
                                    all_files.append((img_path, os.path.join(pub_id, img_name)))
                        for inline in doc.getElementsByTagName("inline-graphic"):
                            img_name = inline.getAttribute("xlink:href")
                            img_path = os.path.join(graphics_web_path, img_name)
                            # if not os.path.exists(img_name):
                            #     return {'status': False, 'message': f"{pub_id}.xml file {img_name} not available in {graphics_web_path} folder, please check it. ({img_path})"}
                            if os.path.isfile(img_path):
                                all_files.append((img_path, os.path.join(pub_id, img_name)))

                    #XHTML
                    if file_exists(xhtml_file):
                        all_files.append((xhtml_file, os.path.join(pub_id, pub_id, f'{pub_id}.xhtml')))

                        css_path = os.path.join(xhtml_path, "css-template.css")
                        if os.path.isfile(css_path):
                            all_files.append((css_path, os.path.join(pub_id, pub_id, 'css-template.css"')))

                        doc = minidom.parse(xhtml_file)
                        for img in doc.getElementsByTagName("img"):
                            img_name = img.getAttribute("src")
                            img_path = os.path.join(xhtml_path, img_name)
                            if os.path.isfile(img_path):
                                all_files.append((img_path, os.path.join(pub_id, pub_id, img_name)))
                    else:
                        return {'status': False, 'message': f"{pub_id}.xhtml file not available in ePub folder, please check it. ({xhtml_file})"}
                # print(print_files)
                # Create zip files
                create_zip(print_package, print_files)
                create_zip(web_package, web_files)
                create_zip(all_files_package, all_files)

                #Update volume issue status
                cursor.execute("UPDATE inw_volume_issues SET status='Delivery' WHERE vi_id=%s", (volume_dtl['vi_id'],))
                db.commit()
                print("Successfully Data set created.")
                
            elif crow['cust_name'] == "ASP":
                #ASP logic: copy files to Delivery folder
                cursor.execute("""SELECT * FROM inw_inward_dtl WHERE v_issue_id=%s AND pub_id NOT LIKE '%%-FM%%' AND pub_id NOT LIKE '%%-BM%%' AND pub_id NOT LIKE '%%-Hyperlink%%' ORDER BY v_issue_sequence_id ASC""", (volume_dtl['vi_id'],))
                articles = cursor.fetchall()

                for article in articles:
                    v_issue_seq_id = str(article['v_issue_sequence_id']).zfill(len(str(len(articles))))

                    cursor.execute("SELECT * FROM adm_customer_master WHERE id=%s", (article['cust_id'],))
                    crow = cursor.fetchone()
                    cursor.execute("SELECT * FROM adm_journals WHERE j_id=%s", (article['j_id'],))
                    jninfo = cursor.fetchone()
                    cursor.execute("SELECT * FROM adm_dept_master WHERE dept_code=%s", (article['department_id'],))
                    dptinfo = cursor.fetchone()

                    work_dir = PureWindowsPath(article['work_dir'])
                    src_path = PureWindowsPath(os.path.join(work_dir, article['volume_folder'], article['pub_id'], article['stage'], dptinfo['dept_name']))
                    dest_path = PureWindowsPath(os.path.join(work_dir, article['volume_folder'], "Delivery", article['stage']))
                    src_pdf = PureWindowsPath(os.path.join(src_path, f"{article['pub_id']}.pdf"))

                    pub_suffix = re.sub(r"(.*)-(.*)", r"\2", article["pub_id"])
                    dest_pdf = PureWindowsPath(os.path.join(dest_path, f"{v_issue_seq_id}_{volume_dtl['volume_number']}{jninfo['j_code']}{volume_dtl['issue_number']}-{pub_suffix}.pdf"))

                    os.makedirs(dest_path, exist_ok=True)
                    if file_exists(src_pdf):
                        shutil.copy(src_pdf, dest_pdf)

                cursor.execute("""SELECT * FROM inw_inward_dtl WHERE v_issue_id = %s AND (pub_id LIKE '%%-FM%%' OR pub_id LIKE '%%-BM%%' OR pub_id LIKE '%%-Hyperlink%%') ORDER BY v_issue_sequence_id ASC""", (volume_dtl["vi_id"],))
                articles = cursor.fetchall()

                for article in articles:
                    cursor.execute("SELECT * FROM adm_journals WHERE j_id = %s", (article["j_id"],))
                    jninfo = cursor.fetchone()
                    cursor.execute("SELECT * FROM adm_dept_master WHERE dept_code = %s", (article["department_id"],))
                    dptinfo = cursor.fetchone()

                    work_dir = PureWindowsPath(article["work_dir"])

                    src_path = PureWindowsPath(os.path.join(work_dir, article["volume_folder"], article["pub_id"], article["stage"],dptinfo["dept_name"]))

                    dest_path = PureWindowsPath(os.path.join(work_dir, article["volume_folder"], "Delivery", article["stage"]))

                    pub_suffix = re.sub(r"(.*)-(.*)", r"\2", article["pub_id"])

                    dest_base = (f"00_{volume_dtl['volume_number']}"f"{jninfo['j_code']}"f"{volume_dtl['issue_number']}-{pub_suffix}")

                    src_pdf = os.path.join(src_path, f"{article['pub_id']}.pdf")
                    src_docx = os.path.join(src_path, f"{article['pub_id']}.docx")

                    if "Hyperlink" in article["pub_id"]:
                        if os.path.exists(src_docx):
                            os.makedirs(dest_path, exist_ok=True)
                            shutil.copy(src_docx, os.path.join(dest_path, f"{dest_base}.docx"))
                    else:
                        if os.path.exists(src_pdf):
                            os.makedirs(dest_path, exist_ok=True)
                            shutil.copy(src_pdf, os.path.join(dest_path, f"{dest_base}.pdf"))

                cursor.execute("UPDATE inw_volume_issues SET status = 'Delivery' WHERE vi_id = %s", (volume_dtl["vi_id"],))
                db.commit()
                print("Successfully added ASP files.")
            else:
                cursor.execute("UPDATE inw_volume_issues SET status = 'Delivery' WHERE vi_id = %s", (volume_dtl["vi_id"],))
                db.commit()
        
        return {'status': True, 'message': 'Success...!'}
    # except Exception as e:
    #     print(f'Error Occur in process_issue function in issue_collection file. {e}')
        # return {'status': False, 'message': 'Error...!'}

if __name__ == "__main__":
    v_id = sys.argv[1]   #137

    result = process_issue(v_id)

    print(result, "---")