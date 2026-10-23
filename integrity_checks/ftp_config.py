ftp_host = "xpertxpress.transforma.in"
ftp_port = 21
ftp_username = "uploads@xpertxpress.transforma.in"
ftp_password = "XSzmL7w=c)JlsjGp"

db_host = "68.178.166.2"
db_user = "xpertxpresstrans_integrity_chec"
db_name = "xpertxpresstrans_integrity_checks"
db_password = "!^Ua#Ywj3C(aqzt)"

SMTP_SERVER = "smtp.office365.com"
SMTP_PORT = 587
SENDER_EMAIL = "techsupport@transforma.in"
SENDER_PASSWORD = "Tr@n$!t2018"
TO_EMAILS = ["marimuthu@transforma.in"]
CC_EMAILS = ["dhamodaran@transforma.in", "bala@transforma.in", "siva@transforma.in"]  # Optional CC

query = 'select id, article_name from article where status = "0"'
upload_path_110 = r"\\10.10.10.110\\new_workflow_files\\AUTOMATION-SYSTEM\\TSP-INTEGRITY-CHECKS"
CHECK_INTERVAL = 20