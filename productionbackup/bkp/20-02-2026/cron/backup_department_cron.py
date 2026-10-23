import os
import sys
import pymysql
from ftplib import FTP, error_perm
from datetime import datetime

# Database and FTP Configuration
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "root@123",
    "database": "workflow",
}

FTP_CREDENTIALS = {
            "host": "workflow.transformainc.com",
            "port": 21,
            "username": "production@workflow.transformainc.com",
            "password": "N^]%Ehn+C-M1"
        }

# Backup folder directory
BACKUP_DIRECTORY = r"\\10.10.10.22\\Backup"

# SQL query to fetch records for FTP upload
SQL_QUERY = """
SELECT
    inw.work_dir,
    inw_t.project_id,
    inw_t.current_stage,
    inw_t.dept,
    inw_t.id,
    inw.volume_folder,
    inw.pub_id,
    dept.dept_name
FROM
    `inw_transactions` AS inw_t
INNER JOIN inw_inward_dtl AS inw
    ON inw.id = inw_t.project_id
INNER JOIN adm_dept_master AS dept
    ON dept.dept_code = inw_t.dept
WHERE
    inw_t.`file_backedup` = 1
    AND DATE(inw_t.`created_dt`) = CURDATE() limit 10;
"""

# SQL query to update records after successful upload
UPDATE_QUERY = """
UPDATE inw_transactions
SET file_backedup = 3
WHERE id = %s;
"""

# Function to log messages
def log_message(messages):
    log_file = os.path.join(BACKUP_DIRECTORY, f"log_{datetime.now().strftime('%Y%m%d')}.txt")
    with open(log_file, "a") as log:
        log.writelines(messages)

# Function to upload files to FTP
def upload_to_ftp(records, ftp_credentials, cursor, connection):
    messages = []
    try:
        ftp = FTP()
        ftp.connect(ftp_credentials["host"], ftp_credentials["port"])
        ftp.login(ftp_credentials["username"], ftp_credentials["password"])
        print("Connected to FTP server.")
        messages.append("Connected to FTP server.\n")

        for record in records:
            source_directory = os.path.join(
                record["work_dir"], record["volume_folder"],
                record["pub_id"], record["current_stage"], record["dept_name"]
            )
            source_directory = source_directory.replace("/", "\\")

            # Replace backslashes for FTP paths
            source_dir = source_directory.replace("\\", "/")
            directories = source_dir.split("/")
            current_path = ""

            # Create directories on FTP
            for directory in directories:
                current_path = f"{current_path}/{directory}" if current_path else directory
                try:
                    ftp.mkd(current_path)
                    print(f"Created directory: {current_path}")
                except error_perm as e:
                    if "550" in str(e):
                        print(f"Directory '{current_path}' already exists.")
                    else:
                        print(f"Error creating directory '{current_path}': {e}")
                except Exception as e:
                    print(f"Unexpected error: {e}")

            # Upload files to FTP
            for root, dirs, files in os.walk(source_directory):
                for file_name in files:
                    file_path = os.path.join(root, file_name)
                    file_paths = file_path.replace("\\", "/")
                    try:
                        with open(file_path, 'rb') as f:
                            ftp.storbinary(f"STOR {file_paths}", f)
                        print(f"Uploaded file to FTP: {file_paths}")
                        messages.append(f"Uploaded file to FTP: {file_paths}\n")
                    except Exception as e:
                        print(f"Error uploading {file_paths}: {e}")
                        messages.append(f"Error uploading {file_paths}: {e}\n")

            # Mark the record as processed in the database
            try:
                cursor.execute(UPDATE_QUERY, (record["id"],))
                connection.commit()
                print(f"Updated record ID {record['id']} as processed.")
                messages.append(f"Updated record ID {record['id']} as processed.\n")
            except Exception as e:
                print(f"Error updating record ID {record['id']}: {e}")
                messages.append(f"Error updating record ID {record['id']}: {e}\n")

        ftp.quit()
    except Exception as e:
        print(f"FTP upload failed: {e}")
        messages.append(f"FTP upload failed: {e}\n")

    log_message(messages)
    return messages

# Main script logic
def main():
    try:
        # Connect to the database
        connection = pymysql.connect(
            host=DB_CONFIG["host"],
            user=DB_CONFIG["user"],
            password=DB_CONFIG["password"],
            database=DB_CONFIG["database"],
        )
        cursor = connection.cursor(pymysql.cursors.DictCursor)

        # Execute the query to fetch records
        cursor.execute(SQL_QUERY)
        records = cursor.fetchall()

        # If no records are found
        if not records:
            print({"message": "No records found in the database to process or backup."})
            sys.exit()

        # Process records and upload to FTP
        upload_to_ftp(records, FTP_CREDENTIALS, cursor, connection)

    except Exception as e:
        print(f"Error: {e}")
    finally:
        if connection:
            connection.close()

# Entry point
if __name__ == "__main__":
    main()
