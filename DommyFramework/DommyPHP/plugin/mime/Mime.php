<?php
/*
 *  DommyFramework 插件
 *  Mime  文件类型
 * 
 */

namespace dp\plugin;
use dp\Plugin as Plugin;

class Mime extends Plugin {

    public $default = "application/octet-stream";

    public static $types = array(
		// text
		"txt" => "text/plain",
		"asp" => "text/plain",
		"aspx" => "text/plain",
		"jsp" => "text/plain",
		"vue" => "text/plain",
        "htm" => "text/html",
        "html" => "text/html",
		"tpl" => "text/html",
        "php" => "text/html",
        "css" => "text/css",
        "csv" => "text/csv",
        "js" => "application/javascript",
        "json" => "application/json",
        "xml" => "application/xml",
        "swf" => "application/x-shockwave-flash",

        // images
        "png" => "image/png",
        "jpe" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "gif" => "image/gif",
        "bmp" => "image/bmp",
        "ico" => "image/vnd.microsoft.icon",
        "tiff" => "image/tiff",
        "tif" => "image/tiff",
        "svg" => "image/svg+xml",
        "svgz" => "image/svg+xml",
        "dwg" => "image/vnd.dwg",

        // archives
        "zip" => "application/zip",
        "rar" => "application/x-rar-compressed",
        "7z" => "application/x-7z-compressed",
        "exe" => "application/x-msdownload",
        "msi" => "application/x-msdownload",
        "cab" => "application/vnd.ms-cab-compressed",

        // audio
        "aac" => "audio/x-aac",
        "flac" => "audio/x-flac",
        "mid" => "audio/midi",
        "mp3" => "audio/mpeg",
        "m4a" => "audio/mp4",
        "ogg" => "audio/ogg",
        "wav" => "audio/x-wav",
        "wma" => "audio/x-ms-wma",

        // video
        "3gp" => "video/3gpp",
        "avi" => "video/x-msvideo",
        "flv" => "video/x-flv",
        "mkv" => "video/x-matroska",
        "mov" => "video/quicktime",
        "mp4" => "video/mp4",
        "qt" => "video/quicktime",
        "wmv" => "video/x-ms-wmv",

        // adobe
        "pdf" => "application/pdf",
        "psd" => "image/vnd.adobe.photoshop",
        "ai" => "application/postscript",
        "eps" => "application/postscript",
        "ps" => "application/postscript",

        // ms office
        "doc" => "application/msword",
        "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "rtf" => "application/rtf",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "ppt" => "application/vnd.ms-powerpoint",
        "pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",

        // open office
        "odt" => "application/vnd.oasis.opendocument.text",
        "ods" => "application/vnd.oasis.opendocument.spreadsheet",
	);

	public function get($fext="js"){
		$fext = strtolower($fext);
		if(!isset(self::$types[$fext])){
			return $this->default;
		}else{
			return self::$types[$fext];
		}
	}

	public function is_supported($fext=""){
		if(!empty($fext)){
			$fext = strtolower($fext);
			return array_key_exists($fext, self::$types);
		}
		return FALSE;
	}

	public function support_types(){
		return array_keys(self::$types);
    }

    //检查是否纯文本文件
    public function is_plain($fext=""){
        $fext = strtolower($fext);
        if(in_array($fext,["js","json","xml"])) return TRUE;
        if(strpos($mime,"text/")!==FALSE) return TRUE;
        return FALSE;
    }

    //检查是否支持直接输出
    public function can_export($fext=""){
        $fext = strtolower($fext);
        if(in_array($fext,["js","json","xml","swf"])) return TRUE;
        if(in_array($fext,["csv","psd"])) return FALSE;
        $mime = $this->get($fext);
        if($mime==$this->default) return FALSE;
        if(strpos($mime,"text/")!==FALSE) return TRUE;
        if(strpos($mime,"image/")!==FALSE) return TRUE;
        if(strpos($mime,"audio/")!==FALSE) return TRUE;
        if(strpos($mime,"video/")!==FALSE) return TRUE;
        return FALSE;
    }



    /*
     *  export
     */
    public function export($file=""){
        //var_dump($file);
        if(!_res_exists_($file)) _export_404_();
        $fi = _res_path_($file);
        //return $fi;
        $content = file_get_contents($file);
        $this->export_header($fi["ext"],$fi["fullname"]);
        $m = "export_".$fi["ext"];
        //return $m;
        if(method_exists($this,$m)){
            return call_user_func_array([$this,$m],[$file]);
        }else{
            //return "foobar";
            echo $content;
            die();
        }
    }

    //根据mime输出header
    public function export_header($fext="", $fn=""){
        $mime = $this->get($fext);
        _header_("Content-type:".$mime.($this->is_plain($fext) ? "; charset=utf-8" : ""));
        if(!$this->can_export($fext)){
            _header_("Content-Disposition: attachment; filename=\"".$fn."\"");
        }
    }
    
    
    /**
     * 根据不同的文件类型，输出文件
     */

    //mp4
    public function export_mp4($file){
        $size = filesize($file);
        //$size = $size*-1;
        //var_dump($size);die();
        header("Content-type: video/mp4"); 
        header("Accept-Ranges: bytes"); 
        if(isset($_SERVER['HTTP_RANGE'])){ 
            header("HTTP/1.1 206 Partial Content"); 
            list($name, $range) = explode("=", $_SERVER['HTTP_RANGE']); 
            list($begin, $end) =explode("-", $range); 
            if($end == 0){ 
                $end = $size - 1; 
            } 
        }else { 
            $begin = 0; $end = $size - 1; 
        } 
        header("Content-Length: " . ($end - $begin + 1)); 
        header("Content-Disposition: filename=".basename($file)); 
        header("Content-Range: bytes ".$begin."-".$end."/".$size); 
        $fp = fopen($file, 'rb'); 
        fseek($fp, $begin); 
        while(!feof($fp)) { 
            $p = min(1024, $end - $begin + 1); 
            $begin += $p; 
            echo fread($fp, $p); 
        } 
        fclose($fp);
    }
}