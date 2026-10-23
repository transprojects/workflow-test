#Define the os slash
os_slash = '\\'

automation_path = r'J:\AUTOMATION-SYSTEM\EDP-Sci'
# stage_path = r'J:\New_Workflow_Files\JOURNAL\downloads\JOURNALS'
stage_path = 'E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS'

#dDatabase details
db_host = '10.10.10.110'
db_user = 'root'
db_password = 'root@123'   #"root@123"
db_database = 'workflow'    #"workflow"

#Mail Details
SMTP_SERVER = "smtp.office365.com"
SMTP_PORT = 587
SENDER_EMAIL = "techsupport@transforma.in"
SENDER_PASSWORD = "Tr@n$!t2018"
#TO_EMAILS = ["uma@transforma.in","kannan@transforma.in","art@transforma.in","inward@transforma.in","jeyapriya@transforma.in","natraj@transforma.in","ezhilmathi@transforma.in"]
TO_EMAILS = ["marimuthu@transforma.in"]
CC_EMAILS = ["dhamodaran@transforma.in", "siva@transforma.in"]  # Optional CC

# SMTP_SERVER = "smtp.office365.com"
# SMTP_PORT = 587
# SENDER_EMAIL = "siva@transforma.in"
# SENDER_PASSWORD = "tpl@8903369255"
# TO_EMAILS = ["siva@transforma.in"]
# CC_EMAILS = ["siva@transforma.in"]

mail_subject = f'''EDP File Booked from FTP Successfully for : '''
mail_body = f'''Dear Team,<br/><br/> 
    The EDP File for the above mentioned article details are Downloaded to the workflow now. Please check and update.
    <br/><br/>
    Note: This is an Auto generated mail, please do not reply to this.<br/><br/>
    Regards,<br/><br/>
    TSP<br/>'''