<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "course_qcat_export".
 *
 * Auto generated 14-10-2016 11:38
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Open Qcat Export',
	'description' => 'Exports records from t3events_course to Open Qcat.',
	'category' => 'module',
	'author' => 'Dirk Wenzel',
	'author_email' => 'dirk.wenzel@cps-it.de',
	'author_company' => 'CPS-IT GmbH Berlin',
	'state' => 'beta',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'version' => '0.1.0',
	'constraints' => 
	array (
		'depends' => 
		array (
			'typo3' => '6.2.0-7.99.99',
			'php' => '5.4.0-0.0.0',
			't3import_export' => '0.6.2-0.0.0',
			't3events' => '0.29.0-0.0.0',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
	'_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"d5db";s:9:"README.md";s:4:"09d9";s:13:"composer.json";s:4:"93ef";s:57:"Classes/Component/PreProcessor/PerformanceToQcatArray.php";s:4:"be1e";s:64:"Tests/Unit/Component/PreProcessor/PerformanceToQcatArrayTest.php";s:4:"de48";}',
);

