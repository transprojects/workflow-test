<?php include_once("includes/includes-sw2.php");?>
<?php
// Fetch Event Details

 $event_query = "SELECT * FROM wf_tme WHERE inw_id='".$_REQUEST['aid']."'";

$event_result = $db->query($event_query);

$event = $event_result->fetch_assoc();
 
$tme_id=$event['tme_id'];
// Fetch Author Details

$authors = $db->query("SELECT * FROM wf_tme_authordetails WHERE author_tme_id='$tme_id'");
 
// Fetch Funding Details
$fundings = $db->query("SELECT * FROM wf_tme_fundingdetails WHERE fund_tme_id='$tme_id'");
 
// Fetch Images

$images = $db->query("SELECT * FROM wf_tme_images WHERE img_tme_id='$tme_id'");
 
?>

<script>
function addRow(section) {
    let container = document.getElementById(section);
    let newRow = container.children[0].cloneNode(true);
    let inputs = newRow.getElementsByTagName("input");

    for (let input of inputs) {
        if (input.type === "text" || input.type === "email" || input.type === "number") {
            input.value = ""; // Clear the input fields
        } else if (input.type === "checkbox") {
            input.checked = false; // Uncheck checkboxes
        }
    }

    let deleteBtn = newRow.querySelector(".delete-btn");
    if (!deleteBtn) {
        deleteBtn = document.createElement("button");
        deleteBtn.innerText = "Delete";
        deleteBtn.classList.add("btn", "btn-danger", "mt-2", "delete-btn");
        deleteBtn.type = "button";
        deleteBtn.onclick = function() {
            deleteRow(this);
        };
        let colDiv = document.createElement("div");
        colDiv.classList.add("col-12");
        colDiv.appendChild(deleteBtn);
        newRow.appendChild(colDiv);
    }

    container.appendChild(newRow);
}

function deleteRow(button) {
    let row = button.parentNode.parentNode;
    row.remove();
}
</script>
<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">-->
<link rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/richtext.min.css" />
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtext.min.js"></script>
<div class="container mt-4">
    <?php
    $fm_html_file_path = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/" . $app_res['pub_id'] . "-fm.html";

    $file_content = ''; // Initialize variable to store the file content

    // Check if the file exists
    if (file_exists($fm_html_file_path)) {
        // Read the content of the file
        $file_content = file_get_contents($fm_html_file_path);
    } else {
        // Optionally handle the case where the file doesn't exist
        $file_content = "<p>The specified HTML file does not exist.</p>";
    }
?>
    <div class="row">
        <textarea name="tme_message" id="tme_message" class="form-control" rows="10" placeholder="Comments"
            style=" min-height: 100px;"><?php echo $file_content; ?></textarea>        
    </div>

    <div class="formcont1heading" style="left: -72px; margin-bottom: 20px;">Typesetter Manuscript Entry</div>
    <input type="hidden" name="tme_id" value="<?php echo  $event['tme_id']; ?>">
    <div class="row border p-3 mb-3 bg-light">
        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Event ID</label>
            <input type="text" class="form-control" name="EventId" value="<?php echo $event['EventId']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Event Creation Date</label>
            <input type="text" class="form-control" name="EventCreationDate"
                value="<?php echo $event['EventCreationDate']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Notification Date</label>
            <input type="text" class="form-control" name="NotificationDate"
                value="<?php echo $event['NotificationDate']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Source</label>
            <input type="text" class="form-control" name="Source" value="<?php echo $event['Source']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Article ID</label>
            <input type="text" class="form-control" name="ArticleId" value="<?php echo $event['ArticleId']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Entry Status</label>
            <input type="text" class="form-control" name="EntryStatus" value="<?php echo $event['EntryStatus']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Title</label>
            <input type="text" class="form-control" name="Title" value="<?php echo $event['Title']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Secondary Article ID</label>
            <input type="text" class="form-control" name="SecondaryArticleId"
                value="<?php echo $event['SecondaryArticleId']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Accepted Date</label>
            <input type="text" class="form-control" name="AcceptedDate" value="<?php echo $event['AcceptedDate']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Received Date</label>
            <input type="text" class="form-control" name="ReceivedDate" value="<?php echo $event['ReceivedDate']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Revised Date</label>
            <input type="text" class="form-control" name="RevisedDate" value="<?php echo $event['RevisedDate']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Section Heading</label>
            <input type="text" class="form-control" name="SectionHeading"
                value="<?php echo $event['SectionHeading']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Article Type</label>
            <input type="text" class="form-control" name="ArticleType" value="<?php echo $event['ArticleType']; ?>">
        </div>

        <div class="col-12">
            <label class="mb-2">Open Science Badges</label>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Open Data Badge</label>
            <input type="text" class="form-control" name="OpenDataBadge" value="<?php echo $event['OpenDataBadge']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Open Materials Badge</label>
            <input type="text" class="form-control" name="OpenMaterialsBadge"
                value="<?php echo $event['OpenMaterialsBadge']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Pre-Registered Data Badge</label>
            <input type="text" class="form-control" name="PreRegisteredDataBadge"
                value="<?php echo $event['PreRegisteredDataBadge']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Number of Figures</label>
            <input type="number" class="form-control" name="numberOfFigures"
                value="<?php echo $event['numberOfFigures']; ?>">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="mb-2">Number of Tables</label>
            <input type="number" class="form-control" name="numberOfTables"
                value="<?php echo $event['numberOfTables']; ?>">
        </div>
    </div>

    <h3 class="formcont1heading" style="left: -72px; margin-bottom: 20px;">Author Details</h3>
    <div id="authors">
        <?php while ($author = $authors->fetch_assoc()) { ?>
        <div class="row border p-3 mb-3 bg-light">

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">First Name</label>
                <input type="text" class="form-control" name="FirstName[]" value="<?php echo $author['FirstName']; ?>"
                    placeholder="First Name">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Last Name</label>
                <input type="text" class="form-control" name="LastName[]" value="<?php echo $author['LastName']; ?>"
                    placeholder="Last Name">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Email</label>
                <input type="email" class="form-control" name="Email[]" value="<?php echo $author['Email']; ?>"
                    placeholder="Email">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Secondary Email</label>
                <input type="email" class="form-control" name="SecondaryEmail[]"
                    value="<?php echo $author['SecondaryEmail']; ?>" placeholder="Secondary Email">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Affiliation</label>
                <input type="text" class="form-control" name="Affiliation[]"
                    value="<?php echo $author['Affiliation']; ?>" placeholder="Affiliation">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Address Line 1</label>
                <input type="text" class="form-control" name="AddressLine1[]"
                    value="<?php echo $author['AddressLine1']; ?>" placeholder="Address Line 1">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Address Line 2</label>
                <input type="text" class="form-control" name="AddressLine2[]"
                    value="<?php echo $author['AddressLine2']; ?>" placeholder="Address Line 2">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">City</label>
                <input type="text" class="form-control" name="City[]" value="<?php echo $author['City']; ?>"
                    placeholder="City">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Country</label>
                <input type="text" class="form-control" name="Country[]" value="<?php echo $author['Country']; ?>"
                    placeholder="Country">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Postal Code</label>
                <input type="text" class="form-control" name="PostalCode[]" value="<?php echo $author['PostalCode']; ?>"
                    placeholder="Postal Code">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Telephone 1</label>
                <input type="text" class="form-control" name="Telephone1[]" value="<?php echo $author['Telephone1']; ?>"
                    placeholder="Telephone 1">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Telephone 2</label>
                <input type="text" class="form-control" name="Telephone2[]" value="<?php echo $author['Telephone2']; ?>"
                    placeholder="Telephone 2">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Orcid</label>
                <input type="text" class="form-control" name="Orcid[]" value="<?php echo $author['Orcid']; ?>"
                    placeholder="Orcid">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Ring Gold ID</label>
                <input type="text" class="form-control" name="RingGoldId[]" value="<?php echo $author['RingGoldId']; ?>"
                    placeholder="Ring Gold ID">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Number of Tables</label>
                <input type="number" class="form-control" name="numberOfTables[]"
                    value="<?php echo $event['numberOfTables']; ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Number of Figure</label>
                <input type="number" class="form-control" name="numberOfFigures[]"
                    value="<?php echo $event['numberOfFigures']; ?>">
            </div>
            <div class="col-12">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="CorrespondingAuthor[]" value="1">
                    <label class="form-check-label">Corresponding Author</label>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <button type="button" class="btn btn-danger mt-2 delete-btn" onclick="deleteRow(this)">Delete</button>
            </div>

        </div>
        <?php } ?>
    </div>
    <button type="button" class="btn btn-success mt-3" onclick="addRow('authors')">Add Author</button>


    <h3 class="formcont1heading" style="left: -72px; margin-bottom: 20px; margin-top: 20px">Funding Details</h3>
    <div id="fundings">
        <?php if ($fundings && $fundings->num_rows > 0) { 
        while ($funding = $fundings->fetch_assoc()) { ?>
        <div class="row border p-3 mb-3 bg-light">

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Fund Ref DOI</label>
                <input type="text" class="form-control" name="FundRefDoi[]"
                    value="<?php echo $funding['FundRefDoi']; ?>" placeholder="Fund Ref DOI">
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <label class="mb-2">Grant ID</label>
                <input type="text" class="form-control" name="GrantId[]" value="<?php echo $funding['GrantId']; ?>"
                    placeholder="Grant ID">
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Description</label>
                <input type="text" class="form-control" name="Description[]"
                    value="<?php echo $funding['Description']; ?>" placeholder="Description">
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label class="p-4"></label>
                <button type="button" class="btn btn-danger mt-2 delete-btn" onclick="deleteRow(this)">Delete</button>
            </div>

        </div>
        <?php } } ?>
    </div>
    <button type="button" class="btn btn-success mt-3" onclick="addRow('fundings')">Add Funding</button>

    <h3 class="formcont1heading" style="left: -72px; margin-bottom: 20px;margin-top: 20px">Image Details</h3>
    <div id="images">
        <?php if ($images && $images->num_rows > 0) { 
        while ($image = $images->fetch_assoc()) { ?>
        <div class="row border p-3 mb-3 bg-light">

            <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Image Reference</label>
                <input type="text" class="form-control" name="ImageReference[]"
                    value="<?php echo $image['ImageReference']; ?>" placeholder="Image Reference">
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label class="mb-2">Is Color</label>
                <input type="text" class="form-control" name="IsColor[]" value="<?php echo $image['IsColor']; ?>"
                    placeholder="Is Color">
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label class="mb-2">Page Number</label>
                <input type="text" class="form-control" name="TypesetPageNumber[]"
                    value="<?php echo $image['TypesetPageNumber']; ?>" placeholder="Page Number">
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label class="mb-2">Print In Color</label>
                <input type="text" class="form-control" name="PrintInColor[]"
                    value="<?php echo $image['PrintInColor']; ?>" placeholder="Print In Color">
            </div>

            <div class="col-12 col-sm-6 col-lg-2">
                <label class="p-4"></label>
                <button type="button" class="btn btn-danger mt-2 delete-btn" onclick="deleteRow(this)">Delete</button>
            </div>

        </div>
        <?php } } ?>
    </div>
    <button type="button" class="btn btn-success mt-3" onclick="addRow('images')">Add Image</button>
</div>
<script>
    $('#tme_message').richText();
</script>