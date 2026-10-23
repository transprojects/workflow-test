#Define the os slash
os_slash = '\\'

automation_path = r'E:\New_Workflow_Files\AUTOMATION-SYSTEM\EDP-Sci'
# stage_path = r'E:\New_Workflow_Files\JOURNAL\downloads\JOURNALS'
stage_path = 'E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS'

#dDatabase details
db_host = 'localhost'
db_user = 'root'
db_password = 'root@123'   #"root@123"
db_database = 'workflow'    #"workflow"

#Mail Details
# SMTP_SERVER = "smtp.office365.com"
# SMTP_PORT = 587
# SENDER_EMAIL = "techsupport@transforma.in"
# SENDER_PASSWORD = "Tr@n$!t2018"
# TO_EMAILS = ["marimuthu@transforma.in"]
# CC_EMAILS = ["dhamodaran@transforma.in", "bala@transforma.in", "siva@transforma.in"]  # Optional CC

SMTP_SERVER = "smtp.office365.com"
SMTP_PORT = 587
SENDER_EMAIL = "siva@transforma.in"
SENDER_PASSWORD = "tpl@8903369255"
TO_EMAILS = ["siva@transforma.in"]
CC_EMAILS = ["siva@transforma.in"]


mail_subject = f'''TSP Revised PDF & other attachment Submitted Successfully for : '''
mail_body = f'''Dear Team,<br/><br/> 
    The Revised PDF & other attachments for the above mentioned article details are uploaded to the portal now. Please check and update.
    <br/><br/>
    Note: This is an Auto generated mail, please do not reply to this.<br/><br/>
    Regards,<br/><br/>
    TSP<br/>'''