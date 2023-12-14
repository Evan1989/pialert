<?php

use EvanPiAlert\Util\Text;

$texts = array(

    'change' => 'Change',
    'pageGenerated' => 'Page generated',
    'surnameName' => 'Surname Name',
    'avatar' => 'Avatar',
    'status' => 'Status',
    'actions' => 'Actions',
    'user' => 'User',
    'responsibleEmployee' => 'Responsible employee',
    'addUser' => 'Add user',
    'save' => 'Save',
    'delete' => 'Delete',
    'summary' => 'Summary',
    'pieces' => 'pcs.',
    'msecs' => 'ms.',
    'perDay' => 'per day',
    'alertCount' => 'Alert count',
    'alertPercent' => 'Alert percent',
    'today' => 'Today',
    'name' => 'Name',
    'search' => 'Search',
    'date' => 'Date',
    'sender' => 'Sender',
    'receiver' => 'Receiver',
    'object' => 'Object',
    'error' => 'Error',
    'comment' => 'Comment',
    'requestList' => 'Request list',
    'last' => 'Last',
    'reloadPage' => 'Reload page',
    'complete' => 'Complete',
    'back' => 'Back',
    'or' => 'or',

    'statusNew' => 'New',
    'statusIgnore' => 'Ignore',
    'statusManual' => 'Manual steps',
    'statusWait' => 'On the fix',
    'statusClose' => 'Close',
    'statusReopen' => 'Reopen',

    'dayNameArray' => array(1 => 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
    'yearsArray' => array('year', 'years', 'years'),
    'monthsArray' => array('month', 'months', 'months'),
    'daysArray' => array('day', 'days', 'days'),
    'hoursArray' => array('hour', 'hours', 'hours'),
    'minutesArray' => array('minute', 'minutes', 'minutes'),
    'secondsArray' => array('second', 'seconds', 'seconds'),
    'immediately' => 'less then a second',

    'menuGroupSettings' => 'Settings',
    'menuGroupAnalytics' => 'Analytics',
    'menuDashboard' => 'Dashboard',
    'menuAlerts' => 'Alerts',
    'menuRights' => 'User rights',
    'menuUsers' => 'Users',
    'menuStatistics' => 'Statistics',
    'menuSettings' => 'Settings',
    'menuReports' => 'Reports',
    'menuRules' => 'Support rules',
    'menuProfile' => 'Profile',
    'menuOnline' => 'Support online',
    'menuMassAlerts' => 'Mass Incidents',
    'menuSystems' => 'Systems information',

    'usersPageHeader' => 'All users',
    'usersAddUserSuccess' => 'For user '.Text::REPLACE_PATTERN.' created login link: '.Text::REPLACE_PATTERN,
    'usersLastActionTime' => 'Was '.Text::REPLACE_PATTERN.' ago',

    'profilePageHeader' => 'My account',
    'profileLanguage' => 'Language',
    'profileAlertCount' => 'Alert count',
    'profileChangePassword' => 'Change password',
    'profileNewPassword' => 'New password',
    'profilePasswordChangeSuccess' => 'Password change successfully',
    'profilePasswordChangeFail' => 'Password change failed!',
    'profileTotalOnline' => 'Total online',
    'profileAvatar' => 'Link to avatar',

    'settingsUpdateSuccess' => 'Settings updated.',
    'settingsUpdateFail' => 'Settings update failed!',
    'settingsInstallButton' => 'Installer',
    'settingsCommonSettings' => 'Common settings',
    'settingsHostname' => 'System host',
    'settingsCodeVersion' => 'Local code version',
    'settingsDataBaseVersion' => 'Database version',
    'settingsGithubVersion' => 'Github version',
    'settingsGroupCompany' => 'Company settings',
    'settingsGroupSystem' => 'Systems settings',
    'settingsGroupOther' => 'Other settings',

    'statisticAllSystems' => 'All systems',
    'statisticAlertGroupCount' => 'Alert group count',
    'statisticAlert24HourCount' => 'Alerts, per 24 hour',
    'statisticAlertTodayChart' => 'Alert chart, for today',
    'statisticAlertWeekCount' => 'Alerts, per week',
    'statisticAlertMonthCount' => 'Alerts, per month',
    'statisticAlertMonthChart' => 'Alert chart, for month',
    'statisticAlertPercentMonthChart' => 'Alert percent chart, for month',
    'statisticAlertTotalCount' => 'Total alerts',

    'statisticMessageWeekCount' => 'Message count, per week',
    'statisticMessageMonthCount' => 'Message count, per month',

    'statisticMessageWeekTimeProc' => 'Message processing time, per week',
    'statisticMessageMonthTimeProc' => 'Message processing time, per month',
    'statisticMessageTimeProc' => 'Message processing time, total',

    'statisticAlertWeekPercent' => 'Error percent, per week',
    'statisticAlertMonthPercent' => 'Error percent, per month',
    'statisticAlertTotalPercent' => 'Error percent, total',
    'statistic4ExtSystem' => 'Statistic for external system',

    'chartsNormalForDay' => 'Normal for '.Text::REPLACE_PATTERN,
    'chartsAverageDay' => 'Average day ',

    'exportPageHeader' => 'Reports from PiAlert',
    'exportLoadOnSystem' => 'Load on the system while downloading',
    'exportMainAlertGroupBase' => 'Knowledge base about all alert groups',
    'exportLow' => 'Low',
    'exportErrorOnGenerateReport' => 'Error generating document',

    'alertsPageHeader' => 'Last 1000 alerts',

    'dashboardPageHeader' => 'Aggregated alerts from SAP PI',
    'dashboardCommentPlaceholder' => 'Steps that have already been taken or that need to be completed if alert repeated',
    'dashboardAlertLinkPlaceholder' => 'List of created requests',
    'dashboardCheckAlertGroupAsCompleteButton' => 'The group received a new alert. Click if required action is completed',
    'dashboardCheckAlertGroupAsCompleteFail' => 'It is necessary to appoint a responsible person or change the status.',
    'dashboardShowOnlyNewAlerts' => 'Only show new alerts (not older than two weeks)',
    'dashboardShowOnlyImportantAlerts' => 'Show only alerts that require attention',
    'dashboardNoConnectToPiSystem' => 'No calls from systems for a long time, check the logs in SAP PI',
    'dashboardSupportOnline' => 'There are several employees is online in the system',
    'dashboardTitleToTopBillCounter' => 'Total actual alerts per page',
    'dashboardRequisites' => 'Requisites',
    'dashboardMaskOrError' => 'Error or mask text',
    'dashboardMaskOrErrorTitle' => 'A mask is a generalization of similar error texts. * (asterisk) = 100 any characters',
    'dashboardMaskAfterUnion' => 'Error text mask in case of merging',
    'dashboardUnionGroupButtonStep2' => 'merge all alerts from the alert group in this line with',
    'dashboardThisGroup' => 'this group',
    'dashboardFirstAlert' => 'First alert',
    'dashboardLastAlert' => 'Last alert',
    'dashboardShowAlertButton' => 'Show alerts',
    'dashboardShowStatisticButton' => 'Alert group statistics',
    'dashboardUnionGroupButton' => 'The alert group can be union with another one',
    'dashboardFindSameErrors' => 'Find similar errors in PiAlert',
    'dashboardUnionSuccess' => 'Alert groups merged successfully.',
    'dashboardUnionFail' => 'Failed to merge alert group data.',
    'dashboardUnionFatalError' => 'Unable to merge alert group data.',
    'dashboardLegend' => 'Legend',
    'dashboardLegendActualAlert' => 'actual alert: it is relatively fresh (came after office hours or today) and no actions have been taken on it yet',
    'dashboardLegendNew' => 'new alert group detected',
    'dashboardLegendIgnore' => 'the next time this type of error occurs, nothing needs to be done',
    'dashboardLegendManual' => 'for such an error, it is necessary to perform manual steps by the integration employee',
    'dashboardLegendWait' => 'we are waiting for the error correction within the framework of the task indicated in the comments',
    'dashboardLegendClose' => 'the problem is resolved',
    'dashboardLegendReopen' => 'the error type has been fixed, but a new alert has arrived',
    'dashboardShareLinkButton' => 'Link to this alert group',
    'dashboardLastUserId' => 'Last responsible employee',
    'dashboardEditDate' => 'Last edit: '.Text::REPLACE_PATTERN,
    'dashboardAvgBigCount' => 'The value is greater than the observed averages!',

    'authorizationPageTitle' => 'Login to PiAlert',
    'authorizationPageHeader' => 'Requires authorization',
    'authorization403Error' => 'This page is not available for your user, please contact the server administrator.',

    'installPageHeader' => 'Install and update PiAlert',
    'installNextStep' => 'Next step',
    'installError' => 'Step failed: '.Text::REPLACE_PATTERN,
    'installStep1Header' => 'System install. Step 1',
    'installStep1Body' => 'It is necessary to create a configuration file /src/<b>config.php</b>, in which to fill in the key fields: system host and database access parameters. The base must be created in advance, empty.
        <br><br>
        Example:',
    'installDatabaseUpdateError' => 'Script to update database in folder /src/install/ to version '.Text::REPLACE_PATTERN.' not found.',
    'installStep2Header' => 'System install. Step 2',
    'installStep2Body' => 'You need to create a tables in database. It might take some time.',
    'installStep2Success' => 'Database created successfully',
    'installUpdateHeader' => 'Upgrade system',
    'installUpdateBody' => 'Version of system PHP code: '.Text::REPLACE_PATTERN.'
        <br>
        You need to upgrade the version of the database structures to the same level.',
    'installUpgradeBody' => '1. Download archive from <a href="'.Text::REPLACE_PATTERN.'" target="_blank">'.Text::REPLACE_PATTERN.'</a>
        <br>
        2. Unpack it to PiAlert folder with replace
        <br>
        3. Reload this page',
    'installAutoUpgradeBody' => 'The system will automatically try to download a new version of the code from Github and install it on the server.',
    'installAutoUpgradeSuccess' => 'System source code updated successfully.',
    'installAutoUpgradeFail' => '⚠️There were errors during the upgrade: <pre>'.Text::REPLACE_PATTERN.'</pre> You can update the system manually:',
    'installUpdateSuccess' => 'Database structures successfully upgraded to version '.Text::REPLACE_PATTERN,
    'installFinish' => 'Installation complete successfully',
    'installTrySystem' => 'Try PiAlert',

    'apiCBMAServiceInfo' => "<h1>Service for receiving alerts (CBMA) from SAP PI</h1>
        <p>
            Access is via HTTP Basic Auth (any login, password change in admin settings) at ".Text::REPLACE_PATTERN."
        </p>
        <p>
            This service is designed to receive alerts (CBMA) from SAP PI and process them.
            It is expected that the data will get into it through the integration scenario JMS → Passthrogh → REST.
        </p>
        <p>
            The service expects a POST request with json as input. The message format is standard, generated via the AlertRule in SAP PI 7.4/7.5:
        </p>
        <pre>".
        '{
    "ToParty": "",
    "ScenarioId": "dir://ICO/c49519aec1213061b74c7e3c04e5ee64",
    "RuleId": "67824a2f4d5630ebb4cb5920a474d6f6",
    "AdapterType": "XI_J2EE_MESSAGING_SYSTEM",
    "ErrCat": "Mapping",
    "ToService": "SystemB_P",
    "ScenarioName": "|SystemA_P|Interface_Sync_Out||",
    "Severity": "HIGH",
    "Timestamp": "2022-03-14T17:36:02Z",
    "MonitoringUrl": "https://pip.example.com:8000/webdynpro/resources/sap.com/tc~lm~itsam~ui~mainframe~wd/FloorPlanApp?applicationID=com.sap.itsam.mon.xi.msg&msgid=770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "MsgId": "770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "FromService": "SystemA_P",
    "Namespace": "http://namespace/example",
    "ErrCode": "RESOURCE_EXCEPTION",
    "ErrLabel": "1220",
    "ErrText": "Could not determine mapping steps for message 770a5a44-a39b-11ec-8f7e-000000cacaa6",
    "FromParty": "",
    "Component": "af.pip.pip-db",
    "Interface": "Interface_Sync_Out"
}'.
        "</pre>
         <p>
            The response is an empty HTTP Body and HTTP code 200 or 500.
        </p>",

    'apiNetworkCheckServiceInfo' => "<h1>Service for canary certificate</h1>
        <p>
            Access is via HTTP Basic Auth (any login, password change in admin settings) at ".Text::REPLACE_PATTERN."
        </p>
        <p>
            In SAP PI, the script REST polling → Dynamic Reciever Determination is configured (always giving false + selected ignore in ICO) → Any,
            which calls this service every 5 minutes. If there was no call, then PiAlert will assume problems
            with a network between SAP PI and PiAlert and owe this to the support person in the Desktop section.
        </p>
        <p>
            At the input, the service expects a GET request with one parameter:
        </p>
        <ul>
            <li>system - a string containing the name of the systems, similar to how it is generated in the Component field from the alert (CBMA) SAP PI</li>
        </ul>
        <p>
            The response is HTTP 200 and empty json.
        </p>",

    'onlinePageHeader' => 'Support online statistic',
    'onlineModeUsually' => 'Usually',
    'onlineModeThisWeek' => 'Last week',

    'massAlertsPageHeader' => 'Dashboard for mass incidents',
    'massAlertsSearchBlockHeader' => 'Search for mass incidents',
    'massAlertsFoundedBlockHeader' => 'Bulk editing of found incidents',
    'massAlertsFoundedCount' => 'Records found',
    'massAlertsReplace' => 'Replace',
    'massAlertsRemoveIcon' => 'Remove icon',

    'genericExceptionTitle' => 'An error of type Genetic Exception does not require a reaction usually. Is occurs simultaneously with other alert containing more information (message_id, etc.) generally. Either in response to a one-time error (message in Waiting status, not in System Error)',

    'systemsPageHeader' => 'Systems information',
    'systemsCode' => 'SLD code',
    'systemsName' => 'System name',
    'systemsContact' => 'System contacts',
    'systemsComment' => 'Comments',
    'systemCard' => 'System Card',
    'messageAlertCountErr' => 'Error: message count',
    'messageAlertCount' => 'Error: message count per interval is greater than average for interface: '.Text::REPLACE_PATTERN.PHP_EOL.
                            'Average message count: '.Text::REPLACE_PATTERN.PHP_EOL.
                            'Message count per last interval: '.Text::REPLACE_PATTERN,
    'messageAlertProcTimeErr' => 'Error: message processing time',
    'messageAlertProcTime' => 'Error: message processing time per interval is high for interface: '.Text::REPLACE_PATTERN.PHP_EOL.
                            'Average message processing time (ms): '.Text::REPLACE_PATTERN.PHP_EOL.
                            'Message processing time (ms) per last interval: '.Text::REPLACE_PATTERN,
    'externalSystems' => 'External systems',

);