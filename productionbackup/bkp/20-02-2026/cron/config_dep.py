#Backup folder directory
change_directory = r"\\10.10.10.22\\Backup"

#Define the database details
host = 'localhost'
user = 'root'
password = 'root@123'   #"root@123"
database = 'workflow'    #"workflow"

#Query to get valid folder path in database
query = """SELECT
    inw.work_dir, inw_t.project_id, inw_t.current_stage, inw_t.dept, inw_t.id, inw.volume_folder,inw.pub_id,dept.dept_name
    FROM
    `inw_transactions` AS inw_t
    INNER JOIN inw_inward_dtl AS inw
    ON
    inw.id = inw_t.project_id
    INNER JOIN adm_dept_master AS dept
    ON
    dept.dept_code = inw_t.dept
    WHERE
    inw_t.`file_backedup`=1 AND(
    inw_t.current_status = "Completed"
    ) AND DATE(inw_t.created_dt) = CURDATE();"""

#Config smb client details
username = ".\highmax"    #".\\highmax"
user_pwd = "112233"  #"112233"
