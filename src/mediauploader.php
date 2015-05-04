<?php
/**
 * Media uploader class
 */
class WhatsMediaUploader
{
    protected static function sendData($url, $header, $hBAOS, $filepath, $mediafile, $fBAOS)
    {
        $host = parse_url($url, PHP_URL_HOST);
    	$ar=array(
		    	"ssl"=>array(
        			"cafile"		=> __DIR__."/ca-certificates.crt",
        			"verify_peer"	=> false,
        			"verify_peer_name"=> false,
    			),
    			"http"=>array(
    					"method"	=> 'POST',
    					"header"	=> $header,
    					"content"	=> $hBAOS.file_get_contents($filepath).$fBAOS
    			)
		);
    	$context = stream_context_create($ar);
    	$body = file_get_contents($url,false,$context);

        $json = json_decode($body);
        if ( ! is_null($json)) {
            return $json;
        }
        return false;
    }

    public static function pushFile($uploadResponseNode, $messageContainer, $mediafile, $selfJID)
    {
        //get vars
        $url      = $uploadResponseNode->getChild("media")->getAttribute("url");
        $filepath = $messageContainer["filePath"];
        $to       = $messageContainer["to"];
        return self::getPostString($filepath, $url, $mediafile, $to, $selfJID);
    }

    protected static function getPostString($filepath, $url, $mediafile, $to, $from)
    {
        $host = parse_url($url, PHP_URL_HOST);

        //filename to md5 digest
        $cryptoname    = md5($filepath) . "." . $mediafile['fileextension'];
        $boundary      = "zzXXzzYYzzXXzzQQ";

        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $hBAOS = "--" . $boundary . "\r\n";
        $hBAOS .= "Content-Disposition: form-data; name=\"to\"\r\n\r\n";
        $hBAOS .= $to . "\r\n";
        $hBAOS .= "--" . $boundary . "\r\n";
        $hBAOS .= "Content-Disposition: form-data; name=\"from\"\r\n\r\n";
        $hBAOS .= $from . "\r\n";
        $hBAOS .= "--" . $boundary . "\r\n";
        $hBAOS .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . $cryptoname . "\"\r\n";
        $hBAOS .= "Content-Type: " . $mediafile['filemimetype'] . "\r\n\r\n";

        $fBAOS = "\r\n--" . $boundary . "--\r\n";

        $contentlength = strlen($hBAOS) + strlen($fBAOS) + $mediafile['filesize'];

        $header = "Content-Type: multipart/form-data; boundary=" . $boundary . "\r\n";
        $header .= "Host: " . $host . "\r\n";
        $header .= "User-Agent: " . WhatsProt::WHATSAPP_USER_AGENT . "\r\n";
        $header .= "Content-Length: " . $contentlength . "\r\n\r\n";

        return self::sendData($url, $header, $hBAOS, $filepath, $mediafile, $fBAOS);
    }
}
