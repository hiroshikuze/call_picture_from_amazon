<?php

/*
 * call_picture_from_amazon
 * Copyright (c) 2017-2019 Hiroshi Kuze.
 */
/*
 * Parameters($_GET):
 *   'Keyword' - keyword.
 *   'SearchIndex' - category[ex All(Default), Books, Default].
 */

include('aws_signed_request.php');
include('call_picture_from_amazon__config.php');

if(! isset($_GET["Keyword"])) exit;

$keyword = htmlspecialchars($_GET["Keyword"], ENT_QUOTES, "UTF-8");
$keyword_hash_xml_file = TEMP_FOLDER.md5($keyword).".xml";

// ケースに応じて対応を変える
if(file_exists($keyword_hash_xml_file)
  && time() < filemtime($keyword_hash_xml_file) + 24*60*60
  && filesize($keyword_hash_xml_file) > 0) {
	// 期限内のキャッシュが存在する＝キャッシュを返す
	$return = file_get_contents($keyword_hash_xml_file);
} else {
	// キャッシュが存在しないか、期限外のキャッシュが存在する
	$temp = getAwsSignedRequest($keyword, $keyword_hash_xml_file);
	if($temp !== null) {
		// PA-APIに接続できる＝全キャッシュクリア(1日1回のみ)、PA-APIを返してキャッシュとして残す
		deleteOldFiles();
		$return = $temp;
	}
	if(file_exists($keyword_hash_xml_file)
	  && filesize($keyword_hash_xml_file) > 0) {
		// PA-APIに接続できないが、キャッシュは存在する＝キャッシュを返す
		$return = file_get_contents($keyword_hash_xml_file);
	} else {
		// PA-APIに接続できないし、キャッシュも存在しない＝あきらめてnullを返す
		$return = null;
	}
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

	if($handle = opendir(TEMP_FOLDER)) {
		while(($file = readdir($handle)) !== false) {
			$dir_file = TEMP_FOLDER . $file;
			if(filetype($dir_file) !== "file") continue;
			if(time() - 24*60*60 < filemtime($dir_file)) continue;
			if(pathinfo($dir_file, PATHINFO_EXTENSION) !== "xml") continue;
			unlink($dir_file);
		}
	}
	unlink(TEMP_FOLDER . TEMP_FILE);
	touch(TEMP_FOLDER . TEMP_FILE);
	chmod(TEMP_FOLDER . TEMP_FILE, 0666);
}

/**
 * aws_signed_requestを取得
 * @param string キーワード
 * @param string ファイルエクスポート先
 * @return string aws_signed_requestを取得した結果
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

	if($response = @file_get_contents($request)) {
		if($response !== FALSE) {
			$pxml = simplexml_load_string($response);
			if(isset($pxml) && $pxml !== FALSE) {
				$result = json_encode($pxml);
				file_put_contents($keyword_hash_xml, $result);
				chmod($keyword_hash_xml, 0666);
				return $result;
			}
		}
	} else {
		//PA-APIから30日経過したときのエラー処理？
		if(count($http_response_header) > 0) {
			$status_code = explode(' ', $http_response_header[0]);
			switch($status_code[1]) {
				case 503:
					break;
			}
		}
	}

	return null;
}
