<?php
define('MERGE_FILE_URL','http://192.168.1.22/transforma/home/edit_file/');
define('VOLUME_FOLDER_NAME','Vol00000');
define('WF_DIR','E:/New_Workflow_Files/JOURNAL');
define('CLONE_WF_DIR','K:/Workflow_Archive');
define('TVL_WF_DIR','T:/Workflow_TVL/JOURNAL');
define('BOOKS_WF_DIR','E:/New_Workflow_Files/BOOKS');
define('TOOL_DIR','J:/JOURNAL');
define('BOOKS_TOOL_DIR','J:/BOOKS');
define('EDITOR_INPUT_FILE_DIR','//fileserver/Trans_Production/EDITORIAL/Preediting/Tools/IN');
define('EDITOR_OUTPUT_FILE_DIR','//192.168.1.22/rootDir/OUT');
define('AWARDS_IMAGE_FILE_PATH','C:/LAMP/Apache/htdocs/workflow/assets/images/award/');
define('STATUS_LOG_FILE_PATH','dasd');
define('REV_INPUT_PATH','E:/New_Workflow_Files/AUTOMATION-SYSTEM/REVISES/');
define('PRE_EDITING_URL','http://192.168.1.22/transforma/home/edit_file/');
define('VOLUME_PREFIX','Vol');
define('LATEX_EDITOR_SOURCE_DIR','L:/Latex_Editor/Editor_Files/');
define('LaTeX2HTM_URL','http://192.168.0.4/Transforma/Latex_Editor/xpertproof/wf_In_file.php');
define('LaTeX_Editor_URL','http://192.168.0.4/Transforma/Latex_Editor/xpertproof');
define('BACKUP_DIR','K:\New_Workflow_Files');
define('Proofing_Path','//192.168.0.4/Transforma/Proofing/Files');
define('Proofing_URL','http://transforma.co:8005/workflow/proofing');
define('WORK_TYPE','FM,Chapters,Parts,BM,Covers');
define('XML_to_HTML_perl_Path','E:/Transforma/Proofing/APF.pl');
define('Proof_Viewing_URL','http://10.10.10.110/transforma/trans_editor/home/edit_file/');
define('AWS_Proofing_URL','https://xpertproof.transforma.in/xpertproofing/');
define('Cloud_Proofing_URL','https://xpertproof.com/online-correction-tool/oct/');
define('Linux_Proofing_URL','https://xperttrack.transforma.in/editorproofing/');
define('EPROOFING_URL','http://xpertproof.transforma.in/xpertproofing/EProofing/EProofing.php');
define('GODADDY_Proofing_URL','http://132.148.148.211/Transforma_2021/xproof/author_xpertproof/');
define('EPUB_GENERATION_PERL_PATH','E:/Transforma/TSP_EPUB/TSP_EPUB.pl');
define('SCHEDULE_STATUS_FROM_MAIL','coordinator@transforma.in');
define('SCHEDULE_STATUS_FROM_MAIL_PASSWORD','Fuc83450');
define('CHN_SCHEDULE_STATUS_TO_MAIL','coordinator@transforma.in,tvloffice@transforma.in,chnconversion@transforma.co,chnpag@transforma.co,chnqc@transforma.co,chnce@transforma.co,tt101@transforma.co,production@transforma.in,rebana@transforma.in');
define('CHN_SCHEDULE_STATUS_CC_MAIL','ganeshkoumar@transforma.in,murali@transforma.in,jeyakumar@transforma.in,bosco@transforma.in,tec0174@transforma.co');
define('CHN_SCHEDULE_STATUS_BCC_MAIL','');
define('TVL_SCHEDULE_STATUS_TO_MAIL','coordinator@transforma.in,tvloffice@transforma.in,rebana@transforma.in,pm@transforma.in');
define('TVL_SCHEDULE_STATUS_CC_MAIL','ganeshkoumar@transforma.in,murali@transforma.in,jeyakumar@transforma.in,bosco@transforma.in,tec0174@transforma.co');
define('TVL_SCHEDULE_STATUS_BCC_MAIL','');
define('ASP_DISTILLER_PATH','//10.10.10.104/latex_cloud/Distiller/ASP/');
define('TSP_DISTILLER_HIGH_RES_PATH','//10.10.10.104/latex_cloud/Distiller/TSP_TeX-HighRes/');
define('TSP_DISTILLER_LOW_RES_PATH','//10.10.10.104/latex_cloud/Distiller/TSP_TeX-LowRes/');
define('INVOICE_FILE_PATH','INVOICE_FILE_PATH');
define('GODADDY_DB_IP','68.178.166.2');


//define ("ADMIN_NAME", "admin"); // sp

/// Portal Proect Status Types
//$project_status_array =array(12=>'Correction Done',0=>'Inprogress',1=>'Completed',2=>'Pending',3=>'Lunch',4=>'Break',5=>'Need Clarification',6=>'Assign',7=>'Query',8=>'Training',9=>'Meeting',10=>'Cross Checking',11=>'Cross Checking Done');
$project_status_array =array(12=>'Correction Done',1=>'Completed',2=>'Pending',4=>'Break',5=>'Need Clarification',6=>'Assign',7=>'Query',10=>'To Cross Check',11=>'Cross Checking Done',0=>'Others',13=>'Inprogress');
$project_status_others_array =array(12=>'Correction Done',4=>'Break',5=>'Need Clarification',7=>'Query',10=>'To Cross Check',11=>'Cross Checking Done');

$platform_journal_array =array(1=>'3B2',2=>'LATEX',3=>'INDESIGN',4=>'WORD');
$division_array =array(1=>'Regular Division',2=>'Computer Division');
$j_complexity_array =array(1=>'Regular',2=>'Complex');

//$proofing_system_server=array('GODADDY'=>"GODADDY SERVER",'AWS1'=>"AWS PROOFING SYSTEM-LIVE",'AWS2'=>"AWS PROOFING SYSTEM-XPRO ");
$proofing_system_server=array('AWS2'=>"AWS PROOFING SYSTEM-XPRO ");


/// Poject stages array
$project_status_types =array('FP'=>'FirstProof','REV'=>'Revises','FIN'=>'Final','PMC'=>'PMC','ISSCOR'=>'Issue','ISSFIN'=>'Issue Final','JSTAGE'=>'JSTAGE','Sample'=>'Sample','Draft'=>'Draft','TME'=>'TME','AMO'=>'AMO');

/// Stages shown in REPORTS ($report_stage_types): defined in includes/report_stages.php (also used by the report APIs)
include_once __DIR__.'/report_stages.php';

$target_stage=array('CE'=>'CE','Preclean'=>'Preclean','FP'=>'FirstProof','REV'=>'Revises','Corrections'=>'Corrections','ISSCOR'=>'Issue','JSTAGE'=>'JSTAGE','Others'=>'Others');

/// Graphics queries stages array
$graphic_query_status =array('1'=>'Poor text quality','2'=>'Low-Resolution','3'=>'Unknown format','4'=>'Image missing','5'=>'None');

define('BOOK_SERVICE', 'BS'); // salt for password

$project_status_types_bs =array('FP'=>'FirstProof','REV'=>'Revises','FIN'=>'Final','PMC'=>'PMC','Sample'=>'Sample','TME'=>'TME','AMO'=>'AMO');

/// complexity_types array ///
$complexity_types =array('Regular','Complex');
$work_loactions =array('CHN'=>"Chennai",'TVL'=>"Tirunelveli");

/// Color Details array ///
$book_color_types =array('4C', '2C', 'BW');

$user_status =array('1'=>'Active','0'=>'Inactive');


/// Platform Details array ///
$book_platform_types =array('InDesign', 'TeX', '3B2','Others');

//// Global Book File movement ////
define ("GLOBAL_BOOK_FILE_MOVEMENT", "BOOK");
define ("GLOBAL_BOOK_FILE_MOVEMENT_LABEL", "Global Book");


/* Specify user levels */
define ("ADMIN_LEVEL", 1);
define ("MANAGER_LEVEL", 2);
define ("CONTRACTER_LEVEL", 3);
define ("EMPLOYEE_LEVEL", 5);
define ("USER_LEVEL", 4);
define ("GUEST_LEVEL", 0);

/// Portal User Types
$user_types =array(0=>'All',1=>'Portal Admin', 2=>'Manager',3=>'Contractor',4=>'Employee');
$work_loactions =array('CHN'=>"Chennai",'TVL'=>"Tirunelveli");

// Online journls list
$ONLINE_JOURNAL_LIST = [
    'TSP' => ['EE', 'JRM', 'SDHM', 'OR', 'Oncologie', 'CHD', 'IJMHP', 'SV', 'MCB', 'Phyton', 'FDMP','BIOCELL','CSSE','IASC'],
    'Transforma' => ['TEST-01', 'TEST-02', 'TEST-03', 'TEST-06', 'TEST-07']
];
$OFFLINE_JOURNAL_LIST = [
    'TSP' => ['CMES','CMC'],
    'Transforma' => ['TEST-05']
];


?>
