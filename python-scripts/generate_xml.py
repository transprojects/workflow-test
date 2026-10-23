from bs4 import BeautifulSoup
import sys
from datetime import datetime
import re
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

query_text = []
query_tag = ["ArticleId", "Title", "AcceptedDate", "ReceivedDate", "ArticleType", "FirstName", "Affiliation", "City", "Country", "Email"]

# Function to safely extract values with warnings
def get_value(rows, key):
    value = rows.get(key)
    if (value or value==0 or value is None) and key in query_tag:
        if key == "Email" and (rows.get("CorrespondingAuthor") == "N" or rows.get("CorrespondingAuthor") is None):
            pass
        else:
            query_text.append({'type': f"{key}Query", 'desc': f'This is some warning;'})
    return value or ""

#Get the corresponding article details
cursor.execute("select * from wf_tme where inw_id = %s", (sys.argv[1], ))
row = cursor.fetchall()
columns = [desc[0] for desc in cursor.description]
dict_rows = [dict(zip(columns, row)) for row in row]
# print(dict_rows)
for main in dict_rows:
    xml_data = f"""<?xml version="1.0" encoding="UTF-8"?>
<EventMessage>
    <Event>
        <EventId>{get_value(main, "EventId")}</EventId>
        <EventCreationDate>{get_value(main, "EventCreationDate")}</EventCreationDate>
        <NotificationDate>{get_value(main, "NotificationDate")}</NotificationDate>
        <Source>{get_value(main, "Source")}</Source>
        <Vendor>{get_value(main, "Vendor")}</Vendor>
    </Event>  

    <Content>
        <Manuscript>
            <ArticleId>{get_value(main, "ArticleId")}</ArticleId>
            <EntryStatus>{get_value(main, "EntryStatus")}</EntryStatus>
            <Details>
                <Title>{get_value(main, "Title")}</Title>
                <SecondaryArticleId>{get_value(main, "SecondaryArticleId")}</SecondaryArticleId>
                <ArticleHistoryDate>
                    <AcceptedDate>{get_value(main, "AcceptedDate")}</AcceptedDate>
                    <ReceivedDate>{get_value(main, "ReceivedDate")}</ReceivedDate>
                    <RevisedDate>{get_value(main, "RevisedDate")}</RevisedDate>
                </ArticleHistoryDate>
                <SectionHeading>{get_value(main, "SectionHeading")}</SectionHeading>
                <ArticleType>{get_value(main, "ArticleType")}</ArticleType>
                <OpenScienceBadges>
                    <OpenDataBadge>{get_value(main, "OpenDataBadge")}</OpenDataBadge>
                    <OpenMaterialsBadge>{get_value(main, "OpenMaterialsBadge")}</OpenMaterialsBadge>
                    <PreRegisteredDataBadge>{get_value(main, "PreRegisteredDataBadge")}</PreRegisteredDataBadge>
                </OpenScienceBadges>
            </Details>
            <ImageDetails>
                <NumberOfFigures>{get_value(main, "numberOfFigures")}</NumberOfFigures>
                <NumberOfTables>{get_value(main, "numberOfTables")}</NumberOfTables>"""

#Get the Image detail
cursor.execute("select * from wf_tme_images where img_tme_id = %s", (dict_rows[0]['tme_id'], ))
row2 = cursor.fetchall()
columns2 = [desc[0] for desc in cursor.description]
dict_rows2 = [dict(zip(columns2, row)) for row in row2]
# print(dict_rows2)
xml_data += f"""\n                <Images>"""
for img in dict_rows2:
    xml_data += f"""\n                    <Image>
                        <ImageReference>{get_value(img, "ImageReference")}</ImageReference>
                        <IsColor>{get_value(img, "IsColor")}</IsColor>
                        <TypesetPageNumber>{get_value(img, "TypesetPageNumber")}</TypesetPageNumber>
                        <PrintInColor>{get_value(img, "PrintInColor")}</PrintInColor>
                    </Image>"""

xml_data += f"""\n                </Images>
            </ImageDetails>"""

#Get the Funding detail
cursor.execute("select * from wf_tme_fundingdetails where fund_tme_id = %s", (dict_rows[0]['tme_id'], ))
row1 = cursor.fetchall()
columns1 = [desc[0] for desc in cursor.description]
dict_rows1 = [dict(zip(columns1, row)) for row in row1]
# print(dict_rows1)
xml_data += f"""\n            <FundingDetails>"""
for fund in dict_rows1:
    xml_data += f"""\n                <FundingBody>
                    <FundRefDoi>{get_value(fund, "FundRefDoi")}</FundRefDoi>
                    <GrantId>{get_value(fund, "GrantId")}</GrantId>
                    <Description>{get_value(fund, "Description")}</Description>
                </FundingBody>"""

xml_data += f"""\n            </FundingDetails>"""

#Get the Author detail
cursor.execute("select * from wf_tme_authordetails where author_tme_id = %s", (dict_rows[0]['tme_id'], ))
row3 = cursor.fetchall()
columns3 = [desc[0] for desc in cursor.description]
dict_rows3 = [dict(zip(columns3, row)) for row in row3]
# print(dict_rows3)
xml_data += f"""\n            <AuthorDetails>"""
for auth in dict_rows3:
    xml_data += f"""\n                <Author>
                    <FirstName>{get_value(auth, "FirstName")}</FirstName>
                    <LastName>{get_value(auth, "LastName")}</LastName>
                    <Email>{get_value(auth, "Email")}</Email>
                    <SecondaryEmail>{get_value(auth, "SecondaryEmail")}</SecondaryEmail>
                    <Affiliation>{get_value(auth, "Affiliation")}</Affiliation>
                    <AddressLine1>{get_value(auth, "AddressLine1")}</AddressLine1>
                    <AddressLine2>{get_value(auth, "AddressLine2")}</AddressLine2>
                    <AddressLine3>{get_value(auth, "AddressLine3")}</AddressLine3>
                    <AddressLine4>{get_value(auth, "AddressLine4")}</AddressLine4>
                    <City>{get_value(auth, "City")}</City>
                    <Country>{get_value(auth, "Country")}</Country>
                    <PostalCode>{get_value(auth, "PostalCode")}</PostalCode>
                    <Telephone1>{get_value(auth, "Telephone1")}</Telephone1>
                    <Telephone2>{get_value(auth, "Telephone2")}</Telephone2>
                    <Orcid>{get_value(auth, "Orcid")}</Orcid>
                    <CorrespondingAuthor>{get_value(auth, "CorrespondingAuthor")}</CorrespondingAuthor>
                    <RingGoldId>{get_value(auth, "RingGoldId")}</RingGoldId>
                </Author>"""

xml_data += f"""\n            </AuthorDetails>"""
print(query_text)
if query_text:
    xml_data += f"""\n            <Warnings>"""
for war in query_text:
    xml_data += f"""\n                <Warning>
                    <Type>{war["type"]}</Type>
                    <Description>{war["desc"]}</Description>
                </Warning>"""

xml_data += f"""\n            </Warnings>
        </Manuscript>
    </Content>
</EventMessage>"""

# # Parse and prettify the XML
# soup = BeautifulSoup(xml_data, "xml")
# formatted_xml = soup.prettify()
cursor.execute("select pub_id,work_dir,status,volume_folder,stage from inw_inward_dtl where id = %s", (sys.argv[1], ))
row = cursor.fetchall()

path=os.path.join(row[0][1],row[0][3],row[0][0],row[0][4],row[0][2])
print(path)
with open(os.path.join(path,"cats.xml"), "w")as f:
    f.write(xml_data)
    
    
    
## <EventId>{dict_rows[0]["EventId"] if dict_rows[0]["EventId"] is not None else query_text.append({'type': 'eventid', 'desc': 'This is some warning;'})}</EventId>