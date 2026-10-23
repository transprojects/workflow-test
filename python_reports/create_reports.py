import pymysql
from collections import defaultdict
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import sys
import config



#MARK: Database
#Function to connect the databse
def database_connection(messages):
    try:
        #Establish the connection to the database
        conn = pymysql.connect(
            host = config.db_host,
            user = config.db_user,
            password = config.db_password,
            database = config.db_name
        )
        cursor = conn.cursor()
        print("Database Connected Successfully.")
        return conn, cursor, messages
    except Exception as e:
        print(f'Error in "database_connection" function. {e}')
#         subject = f'Database Connection Failed'
#         body = f"""\tDatabase Connection Failed in workflow<br>
# \t<a href="file://">{e}</a>.<br>"""
        # messages = utils.send_email(subject, body, messages)
        return None, None, messages
    
#MARK: Email Function
#Send email functionalitys
def send_email(subject, body):
    
    def parse_emails(emails):
        if not emails:
            return []
        if isinstance(emails, str):
            return [e.strip() for e in emails.split(",") if e.strip()]
        return emails  # already a list

    to_emails = parse_emails(config.to_mail)
    cc_emails = parse_emails(config.cc_mail)
    bcc_emails = parse_emails(config.bcc_mail)

    msg = MIMEMultipart()
    msg["From"] = config.SENDER_EMAIL
    msg["To"] = ", ".join(to_emails)
    msg["Cc"] = ", ".join(cc_emails)
    if config.bcc_mail:
        msg["Bcc"] = ", ".join(config.bcc_mail)
    msg["Subject"] = subject
    msg.attach(MIMEText(body, "html"))

    try:
        with smtplib.SMTP(config.SMTP_SERVER, config.SMTP_PORT) as server:
            server.starttls()
            server.login(config.SENDER_EMAIL, config.SENDER_PASSWORD)
            all_recipients = to_emails + cc_emails + bcc_emails
            server.sendmail(config.SENDER_EMAIL, all_recipients, msg.as_string())
        print(f"Email notification sent for file: successfully.")
        # messages.append(f"Success: Email notification sent for file: successfully.")
        return True
    except Exception as e:
        print(f"Failed to send email: {e}")
        # messages.append(f"Error: Email notification Failed to sent: {e}.")
        return False
    
#MARK: Create HTML
def generate_html(whole_json):
    try:
        html = """
<html>
<head>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <table>
        <tr>
            <th>Customer</th>
            <th>Journal Code</th>
            <th>FP</th>
            <th>REV</th>
            <th>FIN</th>
            <th>Total Dispatches</th>
        </tr>
"""
        for cust, journals in whole_json.items():
            cust_rowspan = len(journals)
            cust_printed = False
            for journ, stages in journals.items():
                html += "<tr>"

                if not cust_printed:
                    html += f'<td rowspan="{cust_rowspan}">{cust}</td>'
                    cust_printed = True

                html += f"<td>{journ}</td>"

                fp_count = rev_count = fin_count = 0
                for stage, count in stages.items():

                    if stage.startswith("FP"):
                        fp_count += count
                    elif stage.startswith("REV"):
                        rev_count += count
                    elif stage.startswith("FIN"):
                        fin_count += count
                total = fp_count + rev_count + fin_count

                # Add table cells
                html += f"""
                    <td>{fp_count}</td>
                    <td>{rev_count}</td>
                    <td>{fin_count}</td>
                    <td>{total}</td>
                </tr>
                """
        html += """
    </table>
</body>
</html>
"""
        return html, ''
    except Exception as e:
        print(f'Error occur in generate_html function. {e}')
        return None, e

#MARK: Create Report
def create_report():
    try:
        messages = []
        conn, cursor, messages = database_connection(messages)
        if not conn:
            print(f'Database connection failed in workflow.')
            return {'status': False, 'message': 'Database connection failed in workflow.'}
        
        cursor.execute(config.query, (config.month, config.year))
        
        # cursor.execute("SELECT cust_id, j_id, stage, COUNT(*) AS total_dispatches FROM inw_dispatch_history WHERE sent_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) group by cust_id, j_id, stage")
        rows = cursor.fetchall()
        if not rows:
            return {'status': False, 'message': 'No record found for this query or month.'}

        columns = [desc[0] for desc in cursor.description]
        dict_rows = [dict(zip(columns, row)) for row in rows]
        # print(dict_rows)
        whole_json = defaultdict(lambda: defaultdict(dict))

        for row in dict_rows:
            cursor.execute("""SELECT adm_c.cust_name, adm_j.j_code FROM adm_customer_master AS adm_c JOIN adm_journals AS adm_j ON adm_j.j_id = %s WHERE adm_c.id = %s""", (row['j_id'], row['cust_id']))
            get_cust = cursor.fetchall()
            
            if get_cust:
                customer_name = get_cust[0][0]
                journal_name = get_cust[0][1]
            
            whole_json[customer_name][journal_name][row['stage_group']] = row['total_dispatches']

        if whole_json:
            html_report, message = generate_html(whole_json)

        if not html_report:
            return {'status': False, 'message': f'Error occur in generate_html function. {message}'}
        
        # with open('report.html', 'w')as f:
        #     f.write(html_report)

        subject = 'Dispatch Reports.'

        #Email callinf funtion
        email_result = send_email(subject, html_report)
        if not email_result:
            return {'status': False, 'message': f'Email send failed.'}
        
        print(f'Success...!')
        return {'status': True, 'message': 'Success...!'}
    except Exception as e:
        print(f'Error occur in create_report function. {e}')
        return {'status': False, 'message': f'Error occur in create_report function. {e}.'}

#MARK: Init
if __name__ == '__main__':
    
    result = create_report()
    if not result['status']:
        print(result)