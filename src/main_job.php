<?php

/**
 * Данный файл можно добавить в cron на стороне сервера
 */

use EvanPiAlert\Util\jobs\JobsUtil;

require_once(__DIR__."/autoload.php");

JobsUtil::executeNeededJobs();