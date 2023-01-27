<?php

/*
Plugin Name: MarcatSearchBuild
Plugin URI: 
Description: Marcatが提供するジャンルのand検索ができる様になるプラグインです。
Version: 1.0.0
Author: MarcatCube
Author URI: https://www.marcatcube.com
License: MarcatCube
*/
/* 
Copyright (C) 2017 sunpla
*/
	

//ジャンルオプション設定
require_once(dirname(__FILE__) . '/library/settings/option/MarcatSearchBuildGenreOptionSettings.php');
//オプションに基づいてのカスタム分類登録
require_once(dirname(__FILE__) . '/library/settings/option/MarcatSearchBuildGenreSettings.php');

//apiについての設定
require_once(dirname(__FILE__) . '/library/settings/api/MarcatSearchBuildApiSettings.php');