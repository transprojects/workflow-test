db_host = 'localhost'
db_user = 'root'
db_password = 'root@123'
db_name = 'workflow'


query = "SELECT cust_id, j_id, CASE WHEN stage LIKE 'REV%%' THEN 'REV' WHEN stage LIKE 'FIN%%' THEN 'FIN' ELSE stage END AS stage_group, COUNT(*) AS total_dispatches FROM inw_dispatch_history WHERE MONTH(sent_date) = %s AND YEAR(sent_date) = %s GROUP BY cust_id, j_id, stage_group"
month = 10
year = 2025

# SMTP_SERVER = "smtp.office365.com"
# SMTP_PORT = 587
# SENDER_EMAIL = "siva@transforma.in"
# SENDER_PASSWORD = "tpl@8903369255"

SMTP_SERVER = "smtp.office365.com"
SMTP_PORT = 587
SENDER_EMAIL = "techsupport@transforma.in"
SENDER_PASSWORD = "Tr@n$!t2018"

to_mail = ['marimuthu@transforma.in']
cc_mail = ['']
bcc_mail = ['']