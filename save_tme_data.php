<?php
include_once("includes/includes-sw2.php");

    $tme_id = $_POST['tme_id'] ?? null;
    $EventId = $_POST['EventId'];
    $EventCreationDate = $_POST['EventCreationDate'];
    $NotificationDate = $_POST['NotificationDate'];
    $Source = $_POST['Source'];
    $ArticleId = $_POST['ArticleId'];
    $EntryStatus = $_POST['EntryStatus'];
    $Title = $_POST['Title'];
    $SecondaryArticleId = $_POST['SecondaryArticleId'];
    $AcceptedDate = $_POST['AcceptedDate'];
    $ReceivedDate = $_POST['ReceivedDate'];
    $RevisedDate = $_POST['RevisedDate'];
    $SectionHeading = $_POST['SectionHeading'];
    $ArticleType = $_POST['ArticleType'];
    $OpenDataBadge = $_POST['OpenDataBadge'];
    $OpenMaterialsBadge = $_POST['OpenMaterialsBadge'];
    $PreRegisteredDataBadge = $_POST['PreRegisteredDataBadge'];
    $NumberOfFigures = $_POST['numberOfFigures'];
    $NumberOfTables = $_POST['numberOfTables'];

    // If tme_id exists, update; otherwise, insert
    if ($tme_id) {
        // UPDATE query
        $query = "UPDATE wf_tme SET 
            EventId = ?, EventCreationDate = ?, NotificationDate = ?, Source = ?, ArticleId = ?, 
            EntryStatus = ?, Title = ?, SecondaryArticleId = ?, AcceptedDate = ?, ReceivedDate = ?, 
            RevisedDate = ?, SectionHeading = ?, ArticleType = ?, OpenDataBadge = ?, 
            OpenMaterialsBadge = ?, PreRegisteredDataBadge = ?, numberOfFigures = ?, numberOfTables = ? 
            WHERE tme_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssssisssssssssssiii", 
            $EventId, $EventCreationDate, $NotificationDate, $Source, $ArticleId, 
            $EntryStatus, $Title, $SecondaryArticleId, $AcceptedDate, $ReceivedDate, 
            $RevisedDate, $SectionHeading, $ArticleType, $OpenDataBadge, 
            $OpenMaterialsBadge, $PreRegisteredDataBadge, $NumberOfFigures, $NumberOfTables, $tme_id);
        $stmt->execute();
    } else {
        // INSERT query
        $query = "INSERT INTO wf_tme (EventId, EventCreationDate, NotificationDate, Source, ArticleId, 
            EntryStatus, Title, SecondaryArticleId, AcceptedDate, ReceivedDate, RevisedDate, 
            SectionHeading, ArticleType, OpenDataBadge, OpenMaterialsBadge, PreRegisteredDataBadge, 
            numberOfFigures, numberOfTables) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ssssisssssssssssii", 
            $EventId, $EventCreationDate, $NotificationDate, $Source, $ArticleId, 
            $EntryStatus, $Title, $SecondaryArticleId, $AcceptedDate, $ReceivedDate, 
            $RevisedDate, $SectionHeading, $ArticleType, $OpenDataBadge, 
            $OpenMaterialsBadge, $PreRegisteredDataBadge, $NumberOfFigures, $NumberOfTables);
        $stmt->execute();
        $tme_id = $stmt->insert_id; // Get newly inserted ID
    }

    // Handle Author Details
    $db->query("DELETE FROM wf_tme_authordetails WHERE author_tme_id = '$tme_id'");
    if (!empty($_POST['FirstName'])) {
        foreach ($_POST['FirstName'] as $key => $FirstName) {
            $LastName = $_POST['LastName'][$key];
            $Email = $_POST['Email'][$key];
            $SecondaryEmail = $_POST['SecondaryEmail'][$key];
            $Affiliation = $_POST['Affiliation'][$key];
            $AddressLine1 = $_POST['AddressLine1'][$key];
            $AddressLine2 = $_POST['AddressLine2'][$key];
            $AddressLine3 = $_POST['AddressLine3'][$key];
            $AddressLine4 = $_POST['AddressLine4'][$key];
            $City = $_POST['City'][$key];
            $Country = $_POST['Country'][$key];
            $PostalCode = $_POST['PostalCode'][$key];
            $Telephone1 = $_POST['Telephone1'][$key];
            $Telephone2 = $_POST['Telephone2'][$key];
            $Orcid = $_POST['Orcid'][$key];
            $CorrespondingAuthor = $_POST['CorrespondingAuthor'][$key];
            $RingGoldId = $_POST['RingGoldId'][$key];
            $query = "INSERT INTO wf_tme_authordetails (author_tme_id, FirstName, LastName, Email, SecondaryEmail, Affiliation,AddressLine1,AddressLine2,AddressLine3,AddressLine4,City,Country,PostalCode,Telephone1,Telephone2,Orcid,CorrespondingAuthor,RingGoldId) 
                      VALUES (?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("issssssssssssssssi", $tme_id, $FirstName, $LastName, $Email, $SecondaryEmail, $Affiliation, $AddressLine1, $AddressLine2, $AddressLine3, $AddressLine4, $City, $Country, $PostalCode, $Telephone1, $Telephone2, $Orcid, $CorrespondingAuthor, $RingGoldId);
            $stmt->execute();
        }
    }

    // Handle Funding Details
    $db->query("DELETE FROM wf_tme_fundingdetails WHERE fund_tme_id = '$tme_id'");
    if (!empty($_POST['FundRefDoi'])) {
        foreach ($_POST['FundRefDoi'] as $key => $FundRefDoi) {
            $GrantId = $_POST['GrantId'][$key];
            $Description = $_POST['Description'][$key];
            $query = "INSERT INTO wf_tme_fundingdetails (fund_tme_id, FundRefDoi, GrantId, Description) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("isss", $tme_id, $FundRefDoi, $GrantId, $Description);
            $stmt->execute();
        }
    }

    // Handle Image Details
    $db->query("DELETE FROM wf_tme_images WHERE img_tme_id = '$tme_id'");
    if (!empty($_POST['ImageReference'])) {
        foreach ($_POST['ImageReference'] as $key => $ImageReference) {
            $IsColor = $_POST['IsColor'][$key];
            $TypesetPageNumber = $_POST['TypesetPageNumber'][$key];
            $PrintInColor = $_POST['PrintInColor'][$key];
            $query = "INSERT INTO wf_tme_images (img_tme_id, ImageReference, IsColor, TypesetPageNumber, PrintInColor) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("issss", $tme_id, $ImageReference, $IsColor, $TypesetPageNumber, $PrintInColor);
            $stmt->execute();
        }
    }

				$pbkp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python-scripts/generate_xml.py";
				//$dest_pbkp_dept_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/"; // destination folder or file 
				$python_tf_article_tme ='C:\\Windows\\py.exe "'.$pbkp_python_file.'" '.$_REQUEST['id'].'';
				system($python_tf_article_tme);
?>
