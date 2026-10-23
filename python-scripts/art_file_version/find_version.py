import os, sys
import zipfile
from datetime import datetime


#MARK: Find Version
def detect_office_source_and_version(file_path):
    ext = os.path.splitext(file_path)[1].lower()

    # -------------------------
    # Binary MS Office (97-2003)
    # -------------------------
    if ext in [".doc", ".xls", ".ppt"]:
        return "MS Office 97-2003 Binary", "97-2003"

    # -------------------------
    # OpenDocument (LibreOffice / OpenOffice)
    # -------------------------
    if ext in [".odt", ".ods", ".odp"]:
        try:
            with zipfile.ZipFile(file_path, 'r') as z:
                if "meta.xml" in z.namelist():
                    meta_content = z.read("meta.xml").decode("utf-8", errors="ignore").lower()
                    version = "Unknown"
                    # Look for libreoffice version string in meta.xml
                    if "libreoffice" in meta_content:
                        import re
                        m = re.search(r'<appversion>([\d\.]+)</appversion>', meta_content)
                        if m:
                            version = m.group(1)
                        return "LibreOffice OpenDocument", version
                    elif "openoffice" in meta_content:
                        return "Apache OpenOffice OpenDocument", "Unknown version"
                    else:
                        return "OpenDocument", "Unknown generator"
                else:
                    return "OpenDocument", "No meta.xml"
        except Exception as e:
            return "Error reading file", str(e)

    # -------------------------
    # OOXML (MS Office 2007+ or LibreOffice generated OOXML)
    # -------------------------
    if ext in [".docx", ".xlsx", ".pptx"]:
        try:
            with zipfile.ZipFile(file_path, 'r') as z:
                app_data = ""
                core_data = ""

                if "docProps/app.xml" in z.namelist():
                    app_data = z.read("docProps/app.xml").decode("utf-8", errors="ignore")
                if "docProps/core.xml" in z.namelist():
                    core_data = z.read("docProps/core.xml").decode("utf-8", errors="ignore")

                metadata = (app_data + core_data).lower()

                # LibreOffice detection
                if "libreoffice" in metadata:
                    import re
                    version = "Unknown"
                    m = re.search(r'<appversion>([\d\.]+)</appversion>', metadata)
                    if m:
                        version = m.group(1)
                    return "LibreOffice", version

                # OpenOffice detection
                if "openoffice" in metadata:
                    return "Apache OpenOffice", "Unknown version"

                # Microsoft Office detection
                if "microsoft" in metadata:
                    version = "Unknown"
                    if "16.0" in metadata:
                        version = "2016 or later"
                    elif "15.0" in metadata:
                        version = "2013"
                    elif "14.0" in metadata:
                        version = "2010"
                    elif "12.0" in metadata:
                        version = "2007"
                    return "Microsoft Office", version

                return "OOXML Document", "Unknown generator"

        except Exception as e:
            return "Error reading file", str(e)

    return "Unsupported file type", ""

#MARK: Report
def generate_report(folder, report_path):
    lines = []

    header = (
        "Office File Version Report\n"
        "Generated On : {}\n"
        "Folder       : {}\n\n"
        "{}\n"
        "{:<35} {:<25} {:<20}\n"
        "{}"
    ).format(
        datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        folder,
        "-" * 85,
        "File Name",
        "Generator",
        "Version",
        "-" * 85
    )

    lines.append(header)

    count = 0

    for root, dirs, files in os.walk(folder):
        for file in files:
            if file.lower().endswith((".doc",".docx",".xls",".xlsx",".ppt",".pptx",".odt",".ods",".odp")):
                path = os.path.join(root, file)

                generator, version = detect_office_source_and_version(path)

                lines.append("{:<35} {:<25} {:<20}".format(
                    file,
                    generator,
                    version
                ))

                count += 1

    lines.append("-" * 85 + "\n")
    lines.append(f"Total Files Scanned : {count}")

    with open(report_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))

    print("Report created:", report_path)


#MARK: Init
if __name__ == "__main__":
    production_folder = sys.argv[1]     #"input"  # Replace with your folder path
    report_name = f"version.txt"
    report_path = os.path.join(production_folder, report_name)
    generate_report(production_folder, report_path)