<?php

// Please don't edit this file! Copy it as config.php and then edit it.

// PRODUCTION configuration for WMF Toolforge.

// Imports DB_USER, DB_PASSWORD credentials from external file.
require_once("../external_includes/mysql_pw.inc");

// Host and DB for meta_p.wiki table.
define('META_HOST', 'enwiki.web.db.svc.wikimedia.cloud');
define('META_DB', 'meta_p');

?>
