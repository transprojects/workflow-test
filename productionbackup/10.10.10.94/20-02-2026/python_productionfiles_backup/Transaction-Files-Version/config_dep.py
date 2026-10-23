#Backup folder directory
change_directory = r"\\10.10.10.22\\Backup"

#Define the database details
host = '10.10.10.110'
user = 'root'
password = 'root@123'   #"root@123"
database = 'workflow'    #"workflow"

page_count_python_file = '../python-scripts/auto_pdf_page_count.py'

query = """SELECT
    inw.work_dir, inw_t.project_id, inw_t.current_stage, inw_t.dept, inw_t.id, inw.volume_folder,inw.pub_id,dept.dept_name, inw_t.created_dt
    FROM
    `inw_transactions` AS inw_t
    INNER JOIN inw_inward_dtl AS inw
    ON
    inw.id = inw_t.project_id
    INNER JOIN adm_dept_master AS dept
    ON
    dept.dept_code = inw_t.dept
    WHERE
    (inw_t.`file_backedup`=0 or inw_t.`file_backedup` is NULL) AND current_status NOT IN ('Editing','Inprogress') AND DATE(inw_t.created_dt) = CURDATE();
"""

#Config smb client details
username = ".\highmax"    #".\\highmax"
user_pwd = "112233"  #"112233"