<?php

/*
 * call_picture_from_amazon
 * Copyright (c) 2017 Hiroshi Kuze.
 */
/*
 * Parameters($_GET):
 *   'Keyword' - keyword.
 *   'SearchIndex' - category[ex All(Default), Books, Default].
 */

include('aws_signed_request.php');
include('call_picture_from_amazon__config.php');

deleteOldFiles(TEMP_FOLDER);

if(! isset($_GET["Keyword"])) exit;

$keyword = htmlspecialchars($_GET["Keyword"], ENT_QUOTES, "UTF-8");
$keyword_hash_xml_file = TEMP_FOLDER.md5($keyword).".xml";

if(! file_exists($keyword_hash_xml_file)
  || filemtime($keyword_hash_xml_file)+24*60*60 < time()
  || filesize($keyword_hash_xml_file) === 0) {
	// キャッシュが存在しないなら生成
	$return = getAwsSignedRequest($keyword, $keyword_hash_xml_file);
} else {
	// キャッシュをロード
	$return = file_get_contents($keyword_hash_xml_file);
}
print $return;
exit();

/**
 * 古いファイルの削除
 */
function deleteOldFiles()
{
	if(! is_dir(TEMP_FOLDER)
	  || (file_exists(TEMP_FOLDER . TEMP_FILE) &&  time() < filemtime(TEMP_FOLDER . TEMP_FILE)+24*60*60)) return;
	
	$handle = opendir(TEMP_FOLDER);
	while(($file = readdir($handle)) !== false) {
		$dir_file = TEMP_FOLDER . $file;
		if(filetype($dir_file) !== "file") continue;
		if(time() < filemtime($dir_file)+24*60*60) continue;
		if(pathinfo($dir_file, PATHINFO_EXTENSION) !== "xml") continue;
		unlink($dir_file);
	}
	touch(TEMP_FOLDER . TEMP_FILE);
}

/**
 * aws_signed_requestを取得
 * @import キーワード
 * @import ファイルエクスポート先
 * @return aws_signed_requestを取得した結果
 */
function getAwsSignedRequest($keyword, $keyword_hash_xml)
{
	if(isset($_GET["SearchIndex"])) {
		$SearchIndex = htmlspecialchars($_GET["SearchIndex"], ENT_QUOTES, "UTF-8");
	} else {
		$SearchIndex = "All";
	}

	$params = array(
	  'Operation' => 'ItemSearch',
	  'SearchIndex' => $SearchIndex,
	  'Keywords' => $keyword,
	  'ResponseGroup' => 'Images,ItemAttributes'
	);

	if($SearchIndex !== "All") {
		array_merge($params, array('Sort' => '-releasedate'));
	}

	$request = aws_signed_request(
	  'co.jp',
	  $params,
	  PUBLIC_KEY, 
	  PRIVATE_KEY, 
	  ASSOCIATE_TAG
	);

	$response = @file_get_contents($request);
	if($response !== FALSE) {
		$pxml = simplexml_load_string($response);
		if(isset($pxml) && $pxml !== FALSE) {
			$result = json_encode($pxml);
			file_put_contents($keyword_hash_xml, $result);
			return $result;
		}
	}

	return null;
}
