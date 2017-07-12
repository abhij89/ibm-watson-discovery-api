<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DiscoveryApi {

	private $url = "https://gateway.watsonplatform.net/discovery/api/v1";
	private $username = "Your-username";
	private $password = "Your-password";
	private $collection_id = "Your-collection-id";
	private $environment_id = "Your-environment-id";
	private $version = "2017-06-25";

	public function queryCollection($query, $sort = "relevancy", $date = "7 days", $next = 0, $language = "english") {
		date_default_timezone_set("UTC");
		$size = 15;
		if ($next > 0) {
			$offset = $next * $size;
		} else {
			$offset = "0";
		}
		$query = $query . ",language:$language";

		//Retrieving cached data
		$data = $this->getContent($query . $sort . $date . $next);
		if (!empty($data)) {
			return unserialize($data);
		}
		$now = time();
		if (!empty($date)) {
			$ts = strtotime(" - " . $date);
		} else {
			$ts = strtotime(" - 7 days");
		}
		if ($sort == "relevancy") {
			$sortby = "-_score";
		} else {
			$sortby = "";
		}

		$params = array();
		$params['version'] = $this->version;
		$params['query'] = urlencode($query);
		$params['offset'] = $offset;
		$params['sort'] = $sortby;
		$params['count'] = $size;
		$params['return'] = urlencode("author,alchemyapi_text,host,language,text,url,title,score,publicationDate.date");
		$params['filter'] = urlencode("blekko.hostrank>10,blekko.chrondate>$ts,blekko.chrondate<$now"); // This filters hosts with rank less than 10 and content published between $ts and $now

		$apiurl = $this->url . "/environments/" . $this->environment_id . "/collections/" . $this->collection_id . "/query?" . http_build_query($params);
		$curl = curl_init($apiurl);
		//$header[] = 'Content-type: application/json';
		//curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "$this->username:$this->password"); //Your credentials goes here
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate

		$curl_response = curl_exec($curl);
		$response = json_decode($curl_response);
		curl_close($curl);
		$content = array();
		if (!empty($response->results)) {
			foreach ($response->results as $key => $post) {
				$content[$key]['title'] = $post->title;
				$content[$key]['url'] = $post->url;
				$content[$key]['site_full'] = $post->host;
				$content[$key]['feed_url'] = '';
				$content[$key]['site_type'] = '';
				$content[$key]['social'] = array();
				$content[$key]['image'] = '';
				$publishedDate = new DateTime($post->publicationDate->date);
				$datePost = $publishedDate->format('Y-m-d H:i:s');
				$content[$key]['published'] = $datePost;
				$content[$key]['author'] = $post->author;
				$content[$key]['language'] = $post->language;
				$content[$key]['description'] = $post->alchemyapi_text;
				$content[$key]['performance_score'] = $post->score;
			}
		}
		//Caching data to avoid repetitive api calls
		$this->saveContent($query . $sort . $date . $next, serialize($content));
		return $content;
	}

	function getContent($file) {
		// cache files are created like cache/abcdef123456...
		$cacheFile = '../cache' . DIRECTORY_SEPARATOR . md5($file);
		if (file_exists($cacheFile)) {
			$fh = fopen($cacheFile, 'r') or die("Unable to open file!");
			$timeElapsed = time() - filemtime($cacheFile);
			// if data was cached recently, return cached data
			if ($timeElapsed < 86400) {
				return fread($fh, filesize($cacheFile));
			}

			// else delete cache file
			fclose($fh);
			unlink($cacheFile);
		}
		return;
	}

	function saveContent($file, $json) {
		$cacheFile = '../cache' . DIRECTORY_SEPARATOR . md5($file);
		$fh = fopen($cacheFile, 'w');
		fwrite($fh, $json);
		fclose($fh);
	}

}

?>
