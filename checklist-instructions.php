<?php include "includes/includes.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS -->
    <style>
      .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
}

.loader-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1001;
    display: none;
}

.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>
  <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Checklist</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>
		<?php 
		if($_COOKIE['department_id']==10)
		{
		?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
              <div class="container mt-4">
              <div class="formcont1heading">Pagination Checklist</div>
              <ul class="d-flex flex-column m-5 gap-4 addspacing">
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Run Validation Tool 
                 </h4>
                <p class="ps-4">Ensure there are no validation errors. </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Correction Template & Fonts
                 </h4>
                <p class="ps-4">Confirm the correct correction template and fonts are being used.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Artwork Links  
                 </h4>
                <p class="ps-4">Verify that all artworks are linked or copied from the corresponding job folder carefully.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Running Heads 
                 </h4>
                <p class="ps-4">Check that running heads are correctly fixed.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Figures, Tables, & Equations
                 </h4>
                <p class="ps-4">Ensure proper placement of figures, tables, and equations.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Text Formatting
                 </h4>
                <p class="ps-4">Avoid loose lines, widows, orphans, and text stacks. </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">PDF Generation
                 </h4>
                <p class="ps-4">Generate the PDF using the correct print and distiller settings.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Page Numbers 
                 </h4>
                <p class="ps-4">Ensure the correct page numbers are used. </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Blank Pages 
                 </h4>
                <p class="ps-4">Add blank pages where necessary.  </p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">File Organization
                 </h4>
                <p class="ps-4">Verify all files are placed in their respective folders.</p>
                </li>
              <li class="col-12 col-sm-6 col-lg-4">
                 <h4 class="mb-2">Queries
                 </h4>
                <p class="ps-4">If queries exist, confirm they are noted in the job card or raised to the client.</p>
                </li>
                </ul>
            </div>
           
    
        </div>
      </div>
	  <?php 
		}
		elseif($_COOKIE['department_id']==1 || $_COOKIE['department_id']==22)
		{
		?>
      <div class="container addarticleformouter">
    <div class="container addarticleforminner m-5">
        <div class="container mt-4">
            <div class="formcont1heading">Pre-editing Checklist</div>
            <ul class="d-flex flex-column m-5 gap-4 addspacing">
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Symbol Check</h4>
                    <p class="ps-4">Ensure that symbols are not replaced by keyboard characters (×, ±, °, <, >, ≤, ≥, ®, ©, ™).</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Space Restrictions</h4>
                    <p class="ps-4">Check and remove unwanted spaces: `.space(`, `,space(`, `space)`, `(space`, `space/`, `/space`, `space%`, `space-`, `-space`.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Double Characters</h4>
                    <p class="ps-4">Avoid double dots and double spaces.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Source Files & Instructions</h4>
                    <p class="ps-4">Verify that all source files and instructions are available.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Reference Citation Separator</h4>
                    <p class="ps-4">Ensure the correct use of citation separators (`,` or `;`).</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Uncited/Unlisted Citations</h4>
                    <p class="ps-4">Check for and query any uncited or unlisted citations.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Figure, Table, Equation Citations</h4>
                    <p class="ps-4">Verify the correct format for figure, table, equation, and box citations, raising queries if needed.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Supplemental Information</h4>
                    <p class="ps-4">Ensure that supplemental information queries are addressed.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Genus Species Italics</h4>
                    <p class="ps-4">Verify that genus and species names are italicized correctly.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Preamble Section Cross-check</h4>
                    <p class="ps-4">Ensure the preamble section matches the source file.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Heading Level Consistency</h4>
                    <p class="ps-4">Check that heading levels are correctly structured without unnecessary separation.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Author Citations</h4>
                    <p class="ps-4">Ensure author citations follow the correct format (`et al.`, `2019a, 2019b`, direct or indirect citation).</p>
                </li>
            </ul>

            <div class="formcont1heading">Copy-editing Checklist</div>
            <ul class="d-flex flex-column m-5 gap-4 addspacing">
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Reference Order</h4>
                    <p class="ps-4">Verify that references are in alphabetical, sequential, or chronological order (as per journal guidelines).</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Date Formatting</h4>
                    <p class="ps-4">Ensure consistency in date formatting (day, month, year, week, minutes, second, hour).</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Parenthesis Consistency</h4>
                    <p class="ps-4">Check that parentheses are set correctly.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">P-value Consistency</h4>
                    <p class="ps-4">Ensure the correct format for `P-value`, including italicization or roman type where applicable.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Latin Terms</h4>
                    <p class="ps-4">Confirm that all Latin terms are italicized.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Thousand Separators</h4>
                    <p class="ps-4">Check the consistency of thousand separators.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Repeated Units</h4>
                    <p class="ps-4">Ensure units are not repeated unnecessarily.</p>
                </li>
            </ul>
        </div>
    </div>
</div>

	  <?php 
		}
		elseif($_COOKIE['department_id']==14)
		{
		?>
   <div class="container addarticleformouter">
    <div class="container addarticleforminner m-5">
        <div class="container mt-4">
            <div class="formcont1heading">QC Checklist</div>
            <ul class="d-flex flex-column m-5 gap-4 addspacing">
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Template</h4>
                    <p class="ps-4">Ensure the correct template is used.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Trim</h4>
                    <p class="ps-4">Verify that the trim size is as per specifications.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Layout</h4>
                    <p class="ps-4">Confirm the layout adheres to the required structure.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Colour</h4>
                    <p class="ps-4">Ensure colours match the standard guidelines.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Alignment of Tables & Rule Thickness</h4>
                    <p class="ps-4">Check table alignment and ensure rule thickness consistency.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Representation of References</h4>
                    <p class="ps-4">Ensure references are formatted correctly as per journal standards.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Folio Arrangement</h4>
                    <p class="ps-4">Confirm folio arrangement is correct.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Zero-zone Parts</h4>
                    <p class="ps-4">Check and adjust zero-zone parts as required.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">DOI</h4>
                    <p class="ps-4">Ensure DOI is present and correctly formatted.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Article Type & Logos</h4>
                    <p class="ps-4">Verify article type, journal name, and logos.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Article Metadata</h4>
                    <p class="ps-4">Check title, author group, affiliation, history, corresponding author information, and copyright details.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Catch Line Information</h4>
                    <p class="ps-4">Ensure correct volume, issue, and year details.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Supplementary Information</h4>
                    <p class="ps-4">Confirm supplementary materials, open access statements, and logo information.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Highlights</h4>
                    <p class="ps-4">Verify the presence and correctness of highlights.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Running Head & Footer</h4>
                    <p class="ps-4">Check running heads and footers on recto and verso pages.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Section Headings</h4>
                    <p class="ps-4">Ensure proper section heading levels.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Figures, Tables, & Equations</h4>
                    <p class="ps-4">Confirm correct representation of charts, equations, figures, footnotes, tables, appendix, and references.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">In-line & Display Equations</h4>
                    <p class="ps-4">Verify proper placement and formatting of equations.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Citations</h4>
                    <p class="ps-4">Check correct citations for charts, equations, figures, tables, appendix, and references.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Sequential Order</h4>
                    <p class="ps-4">Ensure charts, equations, figures, tables, and references follow a sequential order as per journal standards.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">BM Footnotes</h4>
                    <p class="ps-4">Confirm footnotes are correctly formatted.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Avoid Bad Breaking</h4>
                    <p class="ps-4">Check for bad breaks, hyphenation issues, and base alignment.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">General Points</h4>
                    <p class="ps-4">Ensure all elements adhere to journal specifications.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Publication Frequency</h4>
                    <p class="ps-4">Verify the publication frequency is correct.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">ISSN Number</h4>
                    <p class="ps-4">Ensure ISSN numbers for both print and online versions are correct.</p>
                </li>
                <li class="col-12 col-sm-6 col-lg-4">
                    <h4 class="mb-2">Publication Mode</h4>
                    <p class="ps-4">Confirm the publication mode as per journal requirements.</p>
                </li>
            </ul>
        </div>
    </div>
</div>

	  <?php 
		}
		else
		{
		?>
		<div class="container mt-4" style="height:70vh; width: 100%">
			<div class="d-flex justify-content-center align-items-center h-100 w-100 text-align-center">
				<p style="color:red;font-weight:400;text-align:center">Department Checklist will be available for Users</p>
			</div>
		</div>
		<?php 
		}
		?>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
  </body>
</html>
