<?php

class PrecurioStrings {
	const INVALIDUSERROLE = "Your user role does not exist";
	const CANNOTACCESSRESOURCE = "You dont have enough permissions";
	const UNKNOWNNOTIFICATIONCHANNEL = "Unknown notification channel, please make sure the Precurio Notification service is properly setup.";
	const CONFIGFILENOTFOUND = "An important configuration file or xml schema could not be found";
	const DATABASEERROROCCURED = "A database related error has occured";
	const MISSINGPROCESS = "The process you are trying to access is either corrupt or missing";
	const INVALIDPAGEACCESS = "The page you are trying to access does not exist";
	const SESSIONEXPIRED = 'We are sorry, you need to  <a href="#" onclick="gotoHome()">login</a> to view this page';
	const INVALIDPROCESSCONFIG = "This process has not been configured properly, please contact your administrator";
	const MISSINGCONTENT = "The content your are looking for has been removed";
	const NOSUCHUSER = "This user does not exist or is currently deactivated";
	const APPLICATION_LOGIC_ERROR = "Some semantic error just occured, please contact your administrator";
	const ERROR_PERFORMING_OPERATION = "Your last operation resulted in an error";
	const FORM_SESSION_EXPIRED = "You have spent too long filling the form, please fill the form again";
	const ATTENDINGEVENT = "You have confirmed your attendance";
	const NOTATTENDINGEVENT = "You are not attending this event";
	const UNSUREEVENT = "You are not sure about attending this event";
	const ADDSUCESS = "Add operation was successful";
	const TRANSFERSUCESS = "Transfer operation was successful";
	const EDITSUCESS = "Edit operation was successful";
	const DELETESUCCESS = 'Successfully deleted';
	const TASKCONTENTAPPROVAL = 'Content Approval';
	const WORKFLOWAPPROVAL = 'Workflow Approval';
	const INVALIDCONTENT = 'The content you are trying to access does not exist anymore.';
	
	const INVALIDLICENSE = 'Your license configuration has been tampered with, please contact support@kleindevort.com';
	const EXPIREDLICENSE  = 'Your license has expired, please purchase a new one';
	const ISPROFEATURE = "This feature is only available on the pro version <br/> <a href='http://www.precurio.com/commercial-services-precurio-pro.php' target='_blank'>Click here to purchase a PRO license</a>";
	const LICENSEFULL  = "You have exceeded your number of users licenses. Please purchase more user licenses";
	
	const DATABASECONNECTIONERROR = "There is been an error connecting to your database, please check your database settings.";
	
	const TODAY = "Today";
	const YESTERDAY = "Yesterday";
	const HOURSAGO = "hours ago";
	const HOURAGO = "hour ago";
	const DAYSAGO = "days ago";
	const MONTHSAGO = "months ago";
	const SOMEMINUTESAGO = "Some minutes ago";
	const FEWSECONDSAGO = "Few seconds ago";
	const FEWMINUTESAGO = "Few minutes ago";
	const ABOUT = "about";
	const AN = "an";
	
	
	const NO = "No";
	const YES = "Yes";
	
	const SIGNATURESTATEMENT = "I HEREBY INDICATE MY APPROVAL";
	
	const NOTENOUGHREPORTDATA = "Not enough data to build report";
	
	const MOD_EMPLOYEE = "Employee Directory";
	const MOD_EVENT = "Events";
	const MOD_CONTACT = "Contacts";
	const MOD_HELPDESK = "Service Centre";
	const MOD_TASK = "Task";
	const MOD_NEWS = "News";
	const MOD_VISITOR = "Visitors";
	const MOD_CMS = "Intranet Content";
	const MOD_ADMIN = "Admin";
	const MOD_WORKFLOW = "Workflow"; 
	const MOD_REPORT = "Report";
	const MOD_FORUM = "Forum";
	CONST CLICKHERE = "click here";
	
}

?>
<?php 
return;
/*
 * The content below is not used, it is simply to make my translation tool read the translation
 * strings above. Which means the content below is a translation tool friendly format of the one above.
 * 
 * To get the content below, copy the strings above in a regular expressions friendly editor
 * then find "const [A-Z_]*[[:space:]]*=[[:space:]]*" and replace it with "$tr->translate(".
 * Also find ";$" and replace it with ");"
 * 
 */
	$tr->translate("Your user role does not exist");
    $tr->translate("You dont have enough permissions");
    $tr->translate("Unknown notification channel, please make sure the Precurio Notification service is properly setup.");
    $tr->translate("An important configuration file or xml schema could not be found");
    $tr->translate("A database related error has occured");
    $tr->translate("The process you are trying to access is either corrupt or missing");
    $tr->translate("The page you are trying to access does not exist");
    $tr->translate('We are sorry, you need to  <a href="#" onclick="gotoHome()">login</a> to view this page');
    $tr->translate("This process has not been configured properly, please contact your administrator");
    $tr->translate("The content your are looking for has been removed");
    $tr->translate("This user does not exist or is currently deactivated");
    $tr->translate("Some semantic error just occured, please contact your administrator");
    $tr->translate("Your last operation resulted in an error");
    $tr->translate("You have spent too long filling the form, please fill the form again");
    $tr->translate("You have confirmed your attendance");
    $tr->translate("You are not attending this event");
    $tr->translate("You are not sure about attending this event");
    $tr->translate("Add operation was successful");
    $tr->translate("Transfer operation was successful");
    $tr->translate("Edit operation was successful");
    $tr->translate('Successfully deleted');
    $tr->translate('Content Approval');
    $tr->translate('Workflow Approval');
    $tr->translate('The content you are trying to access does not exist anymore.');
    
    $tr->translate('Your license configuration has been tampered with, please contact support@kleindevort.com');
    $tr->translate('Your license has expired, please purchase a new one');
    $tr->translate("This feature is only available on the pro version <br/> <a href='http://www.precurio.com/commercial-services-precurio-pro.php' target='_blank'>Click here to purchase a PRO license</a>");
    $tr->translate("You have exceeded your number of users licenses. Please purchase more user licenses");
    
    $tr->translate("There is been an error connecting to your database, please check your database settings.");
    
    $tr->translate("Today");
    $tr->translate("Yesterday");
    $tr->translate("hours ago");
    $tr->translate("hour ago");
    $tr->translate("days ago");
    $tr->translate("months ago");
    $tr->translate("Some minutes ago");
    $tr->translate("Few seconds ago");
    $tr->translate("Few minutes ago");
    $tr->translate("about");
    $tr->translate("an");
    
    
    $tr->translate("No");
    $tr->translate("Yes");
    
    $tr->translate("I HEREBY INDICATE MY APPROVAL");
    
    $tr->translate("Not enough data to build report");
    
    $tr->translate("Employee Directory"); 
    $tr->translate("Events");
    $tr->translate("Contacts");
    $tr->translate("Service Centre");
    $tr->translate("Task");
    $tr->translate("News");
    $tr->translate("Visitors");
    $tr->translate("Intranet Content");
    $tr->translate("Admin");
    $tr->translate("Workflow"); 
    $tr->translate("Report");
    $tr->translate("Forum");
    $tr->translate("click here");
   
?>
<?php 
/*
 * This one is for the config file
 */
return;
	$tr->translate("An internal directory for your organization, contains phone numbers and contact details of every person in your company. It allows employees to easily locate anyone in your organization."); 
    $tr->translate("Events Management");
    $tr->translate("A company-wide calendar to maintain company events, public holidays, pay days, employee birthdays and other company-wide relevant dates.");
    $tr->translate("Contacts Management");
    $tr->translate("Allows employees maintain, manage and share contacts.");
    $tr->translate("Help Desk");
    $tr->translate("Manage support requests");
    $tr->translate("Task Management");
    $tr->translate("Organize and manage your tasks. It also allows you assign tasks to others.");
    $tr->translate("Publish important company news items. It also allows you connect to international/external news sources via RSS"); 
    $tr->translate("Enterprise Content Management");
    $tr->translate("Creation, management, distribution, publishing, and searching of corporate information.");
    $tr->translate("Intranet Contents");
    $tr->translate("Intranet Administration"); 
    $tr->translate("Setup basic configuration data, create and manage user access levels, manage notifications, backup and restore; these are just part of the many administration tools available for you.");
    $tr->translate("Streamline your business processes with a comprehensive forms builder and automatic approval routing");
    $tr->translate("Reports and Analytics");
    $tr->translate("Measure your ROI with reports such as Top Contents, Top Contributors, Workflow Report, Portal Activity Report, Module Report etc");
    $tr->translate("Visitor Management");
    $tr->translate("Track all visitors to your company from arrival to departure. Also includes a telephone message management system.");
    $tr->translate("Allows employees discuss ideas and challenges.");
    $tr->translate("Forums");
    $tr->translate("Document Management"); 
    $tr->translate("Documents");
    $tr->translate("Organize your documents in a central repository for fast and efficient retrieval.");
    $tr->translate("Instant Messaging");
    $tr->translate("Allows employees chat with each other."); 
    $tr->translate("Quick Poll");
    $tr->translate("Find out what matters most to your colleagues with an informal polling tool. Create single or multi-question polls and view live results.");
    $tr->translate("Featured Employee");
    $tr->translate("Staff of the Day");
    $tr->translate("Uses an algorithm to pick an employee to be featured on the intranet for the day");
    $tr->translate("Company Links");
    $tr->translate("Allows you link to company related resources.");
    $tr->translate("Recent Contents");
    $tr->translate("Displays a list of recently added contents."); 
    $tr->translate("Featured Article");
    $tr->translate("Displays the featured article which has set by your content administrator");
    $tr->translate("Portal Updates");
    $tr->translate("Know what your colleagues are up. Gives you an aggregated feed of all activities happening around your intranet"); 
    $tr->translate("My Profile");
    $tr->translate("Displays your profile picture and a few other details");
    $tr->translate("Suggested Content");
    $tr->translate("Analyses your entire intranet content and presents you with those you will most likely be interested in.");
    $tr->translate("Group Resources");
    $tr->translate("Quick access to group content and documents");
    $tr->translate("Intranet Ads");
    $tr->translate("View important advertisements circulating around your workplace");
    $tr->translate("Reminders"); 
    $tr->translate("Never miss something important anymore");
?>