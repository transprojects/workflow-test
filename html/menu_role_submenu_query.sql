-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2023 at 04:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trans_workflow_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_role_master`
--

CREATE TABLE `user_role_master` (
  `user_role_id` int(11) NOT NULL,
  `user_role_name` varchar(45) DEFAULT NULL,
  `user_role_description` varchar(250) DEFAULT NULL,
  `menu_id` text DEFAULT NULL,
  `smenu_id` text DEFAULT NULL,
  `crud_access` text DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_role_master`
--

INSERT INTO `user_role_master` (`user_role_id`, `user_role_name`, `user_role_description`, `menu_id`, `smenu_id`, `crud_access`, `created_on`, `created_by`) VALUES
(1, 'Portal Admin', 'Portal Admin', NULL, NULL, NULL, '0000-00-00 00:00:00', 0),
(2, 'Manager', 'Manager', NULL, NULL, NULL, '0000-00-00 00:00:00', 0),
(3, 'Employee', 'Employee', NULL, NULL, NULL, '0000-00-00 00:00:00', 0),
(4, 'Vendor', 'Vendor', NULL, NULL, NULL, '0000-00-00 00:00:00', 0),
(5, 'Co-ordinator', 'Co-ordinator', NULL, NULL, NULL, '2019-11-20 07:34:33', 54),
(6, 'Team Leader (PE/XML)', 'Team Leader (PE/XML)', NULL, NULL, NULL, '2021-11-05 15:22:09', 190),
(7, 'Team Leader (PAG)', 'Team Leader (PAG)- Tex, 3B2', NULL, NULL, NULL, '2023-04-25 17:11:06', 192),
(9, 'Tech Lead', 'IT', NULL, NULL, NULL, '2023-06-01 12:17:31', 54);

-- --------------------------------------------------------

--
-- Table structure for table `wf_menu`
--

CREATE TABLE `wf_menu` (
  `menu_id` int(11) NOT NULL,
  `menu_type` varchar(50) NOT NULL,
  `menu_order` int(11) NOT NULL,
  `menu_item` varchar(100) NOT NULL,
  `menu_title` text NOT NULL,
  `menu_class` varchar(255) NOT NULL,
  `menu_link` varchar(100) NOT NULL,
  `menu_link_demo` text NOT NULL,
  `menu_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `menu_show` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `wf_menu`
--

INSERT INTO `wf_menu` (`menu_id`, `menu_type`, `menu_order`, `menu_item`, `menu_title`, `menu_class`, `menu_link`, `menu_link_demo`, `menu_status`, `menu_show`) VALUES
(1, 'ADMIN', 1, 'Accounts', 'Accounts', '', 'onlinecopyediting', 'onlinecopyediting', 'Active', 1),
(2, 'ADMIN', 2, 'Customers', 'Customers', '', 'articlesubmission', 'articlesubmission', 'Active', 1),
(3, 'ADMIN', 4, 'Journals', 'Journals', '', 'finaldelivery', 'finaldelivery', 'Active', 1),
(4, 'ADMIN', 3, 'Departments', 'Departments', '', 'productionprocess', 'productionprocess', 'Active', 1),
(5, 'ADMIN', 5, 'Settings', 'Settings', '', 'productionprocess', 'productionprocess', 'Active', 1),
(6, 'ADMIN', 6, 'Users', 'Users', '', 'users', 'users', 'Active', 1),
(7, 'ADMIN', 7, 'Reports', 'Reports', '', 'reports', 'reports', 'Active', 1),
(8, 'ADMIN', 8, 'Books', 'Books', '', 'books', 'books', 'Inactive', 1),
(9, 'ADMIN', 9, 'Articles', 'Articles', '', 'articles', 'articles', 'Active', 1),
(10, 'ADMIN', 10, 'Issues', 'Issues', '', 'issues', 'issues', 'Active', 1),
(11, 'ADMIN', 11, 'Profile', 'Profile', '', 'profile', 'profile', 'Active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `wf_smenu`
--

CREATE TABLE `wf_smenu` (
  `smenu_id` int(11) NOT NULL,
  `menu_id` int(11) DEFAULT NULL,
  `smenu_type` varchar(50) NOT NULL,
  `smenu_order` int(11) NOT NULL,
  `smenu_item` varchar(100) NOT NULL,
  `smenu_title` text NOT NULL,
  `smenu_class` varchar(255) NOT NULL,
  `smenu_link` varchar(100) NOT NULL,
  `smenu_link_demo` text NOT NULL,
  `smenu_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `smenu_show` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `wf_smenu`
--

INSERT INTO `wf_smenu` (`smenu_id`, `menu_id`, `smenu_type`, `smenu_order`, `smenu_item`, `smenu_title`, `smenu_class`, `smenu_link`, `smenu_link_demo`, `smenu_status`, `smenu_show`) VALUES
(1, 1, 'ADMIN', 2, 'Create Invoice XLS', 'Create Invoice XLS', '', 'createinvoicexls', 'createinvoicexls', 'Active', 1),
(2, 1, 'ADMIN', 1, 'Create Invoice PDF', 'Create Invoice PDF', '', 'createinvoicepdf', 'createinvoicepdf', 'Active', 1),
(3, 1, 'ADMIN', 4, 'Submit Invoice', 'Submit Invoice', '', 'submitinvoice', 'submitinvoice', 'Inactive', 0),
(7, 1, 'ADMIN', 3, 'Submitted Invoice', 'Submitted Invoice', '', 'submittedinvoice', 'submittedinvoice', 'Active', 1),
(8, 1, 'ADMIN', 3, 'Company Details Config', 'Company Details Config', '', 'companydetailsconfig', 'companydetailsconfig', 'Active', 1),
(9, 1, 'ADMIN', 3, 'Customer Invoice Config', 'Customer Invoice Config', '', 'customerinvoiceconfig', 'customerinvoiceconfig', 'Active', 1),
(10, 2, 'ADMIN', 3, 'Customers', 'Customers', '', 'customers', 'customers', 'Active', 1),
(11, 2, 'ADMIN', 3, 'Customers Alert Email', 'Customers Alert Email', '', 'customersalertemail', 'customersalertemail', 'Inactive', 1),
(12, 3, 'ADMIN', 3, 'Journal Type', 'Journal Type', '', 'journaltype', 'journaltype', 'Active', 1),
(13, 3, 'ADMIN', 3, 'Manage Journals', 'Manage Journals', '', 'managejournals', 'managejournals', 'Active', 1),
(14, 3, 'ADMIN', 3, 'Manage Folder', 'Manage Folder', '', 'managefolder', 'managefolder', 'Active', 1),
(15, 3, 'ADMIN', 3, 'File Configuration', 'File Configuration', '', 'fileconfiguration', 'fileconfiguration', 'Inactive', 1),
(16, 3, 'ADMIN', 3, 'Dispatch File Configuration', 'Dispatch File Configuration', '', 'dispatchfileconfiguration', 'dispatchfileconfiguration', 'Inactive', 1),
(17, 4, 'ADMIN', 3, 'Manage Departments', 'Manage Departments', '', 'managedepartments', 'managedepartments', 'Active', 1),
(18, 4, 'ADMIN', 3, 'Manage Move Files', 'Manage Move Files', '', 'managemovefiles', 'managemovefiles', 'Active', 1),
(19, 4, 'ADMIN', 3, 'Manage Book Service', 'Manage Book Service', '', 'managebookservice', 'managebookservice', 'Inactive', 1),
(20, 5, 'ADMIN', 3, 'Workflow', 'Workflow', '', 'workflow', 'workflow', 'Active', 1),
(21, 5, 'ADMIN', 3, 'Bookflow', 'Bookflow', '', 'bookflow', 'bookflow', 'Inactive', 1),
(22, 5, 'ADMIN', 3, 'Pages', 'Pages', '', 'pages', 'pages', 'Inactive', 1),
(23, 5, 'ADMIN', 3, 'Checklist', 'Checklist', '', 'checklist', 'checklist', 'Active', 1),
(24, 5, 'ADMIN', 3, 'Emails', 'Emails', '', 'emails', 'emails', 'Active', 1),
(25, 5, 'ADMIN', 3, 'Client Feedback', 'Client Feedback', '', 'clientfeedback', 'clientfeedback', 'Active', 1),
(26, 5, 'ADMIN', 3, 'Article Query', 'Article Query', '', 'articlequery', 'articlequery', 'Active', 1),
(27, 5, 'ADMIN', 3, 'Article Type', 'Article Type', '', 'articletype', 'articletype', 'Active', 1),
(28, 5, 'ADMIN', 3, 'Schedule Mail details', 'Schedule Mail details', '', 'schedulemaildetails', 'schedulemaildetails', 'Active', 1),
(29, 5, 'ADMIN', 3, 'Archive Config', 'Archive Config', '', 'archiveconfig', 'archiveconfig', 'Active', 1),
(30, 6, 'ADMIN', 3, 'Award Users', 'Award Users', '', 'awardusers', 'awardusers', 'Inactive', 1),
(31, 6, 'ADMIN', 3, 'Shift Masters', 'Shift Masters', '', 'shiftsettings', 'shiftsettings', 'Active', 1),
(32, 6, 'ADMIN', 3, 'Members', 'Members', '', 'employees', 'employees', 'Active', 1),
(33, 6, 'ADMIN', 3, 'Roles', 'Roles', '', 'roles', 'roles', 'Active', 1),
(34, 6, 'ADMIN', 3, 'Add Users', 'Add Users', '', 'addusers', 'addusers', 'Inactive', 1),
(35, 6, 'ADMIN', 3, 'Assign Shift', 'Assign Shift', '', 'assignshift', 'assignshift', 'Inactive', 1),
(36, 6, 'ADMIN', 3, 'My Account', 'My Account', '', 'myaccount', 'myaccount', 'Inactive', 1),
(37, 7, 'ADMIN', 3, 'Master Status Report', 'Master Status Report', '', 'masterstatusreport', 'masterstatusreport', 'Active', 1),
(38, 7, 'ADMIN', 3, 'User Target Report', 'User Target Report', '', 'usertargetreport', 'usertargetreport', 'Active', 1),
(39, 7, 'ADMIN', 3, 'Daywise User Target Report', 'Daywise User Target Report', '', 'daywiseusertargetreport', 'daywiseusertargetreport', 'Active', 1),
(40, 7, 'ADMIN', 3, 'Customerwise Report', 'Customerwise Report', '', 'customerwisereport', 'customerwisereport', 'Active', 1),
(41, 7, 'ADMIN', 3, 'Dispatch History', 'Dispatch History', '', 'dispatchhistory', 'dispatchhistory', 'Active', 1),
(42, 7, 'ADMIN', 3, 'WIP Report', 'WIP Report', '', 'wipreport', 'wipreport', 'Active', 1),
(43, 7, 'ADMIN', 3, 'Received  Report', 'Received  Report', '', 'receivedreport', 'receivedreport', 'Active', 1),
(44, 7, 'ADMIN', 3, 'Dispatch  Report', 'Dispatch  Report', '', 'dispatchreport', 'dispatchreport', 'Active', 1),
(45, 8, 'ADMIN', 3, 'Add Book', 'Add Book', '', 'addbook', 'addbook', 'Active', 1),
(46, 8, 'ADMIN', 3, 'Added Books', 'Added Books', '', 'addedbooks', 'addedbooks', 'Active', 1),
(47, 8, 'ADMIN', 3, 'Current Work', 'Current Work', '', 'currentwork', 'currentwork', 'Active', 1),
(48, 8, 'ADMIN', 3, 'Overdue Books', 'Overdue Books', '', 'overduebooks', 'overduebooks', 'Active', 1),
(49, 8, 'ADMIN', 3, 'High Priority Books', 'High Priority Books', '', 'highprioritybooks', 'highprioritybooks', 'Active', 1),
(50, 8, 'ADMIN', 3, 'Books Under Dispatch', 'Books Under Dispatch', '', 'booksunderdispatch', 'booksunderdispatch', 'Active', 1),
(51, 8, 'ADMIN', 3, 'Chapters Under Dispatch', 'Chapters Under Dispatch', '', 'chaptersunderdispatch', 'chaptersunderdispatch', 'Active', 1),
(52, 8, 'ADMIN', 3, 'Services Under Dispatch', 'Services Under Dispatch', '', 'servicesunderdispatch', 'servicesunderdispatch', 'Active', 1),
(53, 8, 'ADMIN', 3, 'Books Under Client Review', 'Books Under Client Review', '', 'booksunderclientreview', 'booksunderclientreview', 'Active', 1),
(54, 8, 'ADMIN', 3, 'Completed Books', 'Completed Books', '', 'completedbooks', 'completedbooks', 'Active', 1),
(55, 9, 'ADMIN', 3, 'Added Articles', 'Added Articles', '', 'addedarticles', 'addedarticles', 'Active', 1),
(56, 9, 'ADMIN', 3, 'Current Work', 'Current Work', '', 'currentwork', 'currentwork', 'Active', 1),
(57, 9, 'ADMIN', 3, 'Assign Due Date', 'Assign Due Date', '', 'assignduedate', 'assignduedate', 'Active', 1),
(58, 9, 'ADMIN', 3, 'Assign to User', 'Assign to User', '', 'assigntouser', 'assigntouser', 'Active', 1),
(59, 9, 'ADMIN', 3, 'Re-assign to User', 'Re-assign to User', '', 're-assigntouser', 're-assigntouser', 'Active', 1),
(60, 9, 'ADMIN', 3, 'Queried/Hold Article', 'Queried/Hold Article', '', 'queried/holdarticle', 'queried/holdarticle', 'Active', 1),
(61, 9, 'ADMIN', 3, 'High Priority Articles', 'High Priority Articles', '', 'highpriorityarticles', 'highpriorityarticles', 'Active', 1),
(62, 9, 'ADMIN', 3, 'ASP Article History', 'ASP Article History', '', 'asparticlehistory', 'asparticlehistory', 'Active', 1),
(63, 9, 'ADMIN', 3, 'Under Dispatch Articles', 'Under Dispatch Articles', '', 'underdispatcharticles', 'underdispatcharticles', 'Active', 1),
(64, 9, 'ADMIN', 3, 'Under Client Review', 'Under Client Review', '', 'underclientreview', 'underclientreview', 'Active', 1),
(65, 9, 'ADMIN', 3, 'Author Proofing Completed Files', 'Author Proofing Completed Files', '', 'authorproofingcompletedfiles', 'authorproofingcompletedfiles', 'Inactive', 1),
(66, 9, 'ADMIN', 3, 'GoDaddy Test-Under Dispatch Articles', 'GoDaddy Test-Under Dispatch Articles', '', 'godaddytest-underdispatcharticles', 'godaddytest-underdispatcharticles', 'Inactive', 1),
(67, 9, 'ADMIN', 3, 'AWS Test-Under Dispatch Articles', 'AWS Test-Under Dispatch Articles', '', 'awstest-underdispatcharticles', 'awstest-underdispatcharticles', 'Inactive', 1),
(68, 9, 'ADMIN', 3, 'AWS REV1 Manual Booking Click here', 'AWS REV1 Manual Booking Click here', '', 'awsrev1manualbookingclickhere', 'awsrev1manualbookingclickhere', 'Inactive', 1),
(69, 9, 'ADMIN', 3, 'AWS Delete FP Files List(Rebooking)', 'AWS Delete FP Files List(Rebooking)', '', 'awsdeletefpfileslist(rebooking)', 'awsdeletefpfileslist(rebooking)', 'Inactive', 1),
(70, 10, 'ADMIN', 3, 'Manage Issues', 'Manage Issues', '', 'manageissues', 'manageissues', 'Active', 1),
(71, 10, 'ADMIN', 3, 'Under Dispatch Issues', 'Under Dispatch Issues', '', 'underdispatchissues', 'underdispatchissues', 'Active', 1),
(72, 10, 'ADMIN', 3, 'Under Client Issues', 'Under Client Issues', '', 'underclientissues', 'underclientissues', 'Active', 1),
(73, 10, 'ADMIN', 3, 'Completed Issues', 'Completed Issues', '', 'completedissues', 'completedissues', 'Active', 1),
(74, 10, 'ADMIN', 3, 'View All Issues', 'View All Issues', '', 'viewallissues', 'viewallissues', 'Active', 1),
(75, 10, 'ADMIN', 3, 'Volume Archive', 'Volume Archive', '', 'volumearchive', 'volumearchive', 'Active', 1),
(76, 10, 'ADMIN', 3, 'Individual Archive', 'Individual Archive', '', 'individualarchive', 'individualarchive', 'Active', 1),
(77, 10, 'ADMIN', 3, 'Upload Excel', 'Upload Excel', '', 'uploadexcel', 'uploadexcel', 'Active', 1),
(78, 10, 'ADMIN', 3, 'Assign Sequence numbers to Issue', 'Assign Sequence numbers to Issue', '', 'assignsequencenumberstoissue', 'assignsequencenumberstoissue', 'Active', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_role_master`
--
ALTER TABLE `user_role_master`
  ADD PRIMARY KEY (`user_role_id`);

--
-- Indexes for table `wf_menu`
--
ALTER TABLE `wf_menu`
  ADD PRIMARY KEY (`menu_id`);

--
-- Indexes for table `wf_smenu`
--
ALTER TABLE `wf_smenu`
  ADD PRIMARY KEY (`smenu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_role_master`
--
ALTER TABLE `user_role_master`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wf_menu`
--
ALTER TABLE `wf_menu`
  MODIFY `menu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `wf_smenu`
--
ALTER TABLE `wf_smenu`
  MODIFY `smenu_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
