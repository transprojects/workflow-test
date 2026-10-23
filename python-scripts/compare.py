import sys
import pymysql
import os


#Establish the connection to the database
conn = pymysql.connect(
    host = 'localhost', 
    user = 'root', 
    password = 'root@123', 
    database = 'workflow'
)
cursor = conn.cursor()

inw_id = sys.argv[1]

cursor.execute("select * from wf_tme where inw_id = %s and cats_type = 1", (inw_id, ))
row = cursor.fetchall()
columns = [desc[0] for desc in cursor.description]
dict_rows = [dict(zip(columns, row)) for row in row]

cursor.execute("select * from wf_tme where inw_id = %s and cats_type = 2", (inw_id, ))
row1 = cursor.fetchall()
columns1 = [desc[0] for desc in cursor.description]
dict_rows1 = [dict(zip(columns1, row)) for row in row1]

xml= ''
if dict_rows:
    for key in dict_rows[0]:
        if key not in ["tme_id", "cats_type"]:
            if dict_rows[0][key] != dict_rows1[0][key]:
                xml += f"""<{key}>{dict_rows1[0][key]}</{key}>\n"""
                # print(dict_rows[0][key], key)
#print(xml)

cursor.execute("select pub_id,work_dir,status,volume_folder,stage from inw_inward_dtl where id = %s", (sys.argv[1], ))
row = cursor.fetchall()

if row:
    # Extract values
    pub_id = row[0][0]  # pub_id
    work_dir = row[0][1]  # work_dir
    status = row[0][2]  # status
    volume_folder = row[0][3]  # volume_folder
    stage = row[0][4]  # stage

    # Replace 'production' with 'downloads' in work_dir
    work_dir = work_dir.replace(
        "E:/New_Workflow_Files/JOURNAL/production/",
        "E:/New_Workflow_Files/JOURNAL/downloads/"
    )

    # Construct the updated path
    path = os.path.join(work_dir, volume_folder, pub_id, stage, status)
    
    # Print the updated path
    #print(path)

else:
    print("No data found for the given ID.")


with open(os.path.join(path,"compare.xml"), "w")as f:
    f.write(xml)