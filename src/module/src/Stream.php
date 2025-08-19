<?php
/**
 * 框架 Src 资源处理模块
 * stream 视频流，音频流，或其他数据流 输出工具
 * 
 * 支持的视频格式  mp4,mov,m4v
 * 支持的音频格式  mp3,m4a,ogg
 * 
 * 支持远程文件读取并输出流，支持的格式与本地文件相同
 * 对于不支持的格式，以下载方式将文件发送到浏览器
 */

namespace Spf\module\src;

use Spf\Response;
use Spf\exception\BaseException;

ini_set("memory_limit", "1024M");   //脚本最大运行内存
set_time_limit(600);                //脚本超时时间 10min

class Stream
{

    private static $support = [
        "mp4","m4v","mov",
        "mp3","m4a","ogg"
    ];

    private $ext = "";
    private $mime   = ''; 

    private $stream = '';
    private $buffer = 102400;
    private $start  = -1;
    private $end    = -1;
    private $size   = 0;

    protected function __construct($file)
    {
        $this->path = $file;
        $this->ext = Mime::getExt($file);
        $this->mime = Mime::getMime($this->ext);
    }
    
    //supported
    private function supported()
    {
        return in_array($this->ext, self::$support);
    }

    /**
     * 工厂方法
     * @param Array $args 实例化参数
     * @return Stream 实例
     */
    public static function create(...$args)
    {
        return new Stream(...$args);
    }

    /**
     * 本地|远程 流式资源 处理入口
     * @param String $file 本地文件路径 或 远程资源 url
     * @return void
     */
    public static function play($file = "")
    {
        try {
            if (Resource::exists($file) !== true) {
                //资源不存在
                throw new SrcException("请求的资源不存在", "resource/getcontent");
            }
            //创建 Stream 实例
            $stream = self::create($file);
            //分别开始 本地|远程 资源的 流式输出
            if ($stream->isRemote()) {
                //读取远程资源，开始流式输出
                $stream->startRemoteStream();
            } else {
                //读取本地资源，开始流式输出
                $stream->startLocalStream();
            }
            exit;
        } catch (BaseException $e) {
            $e->handleException();
        }
    }

    /**
     * 判断当前资源 是否 远程 资源
     * @return Bool
     */
    public function isRemote()
    {
        return false !== strpos($this->path, "://");
    }



    /**
     * 处理本地文件
     */

    /**
     * 以 流形式 输出本地文件 入口
     * @return void
     */
    public function startLocalStream()
    {
        if ($this->supported()) {
            $this->open();
            $this->setHeader();
            $this->stream();
            $this->end();
        } else {
            $this->startDownload();
        }
    }

    //打开文件流
    private function open()
    {
        try {
            $this->stream = fopen($this->path, 'rb');
            if ($this->stream === false) {
                throw new SrcException("无法开启流输出", "resource/getcontent");
            }
        } catch (BaseException $e) {
            $e->handleException();
        }
    }
    //设置header头
    private function setHeader()
    {
        ob_get_clean();
        //header("Content-Type: video/mp4");
        header("Content-Type: " . $this->mime);
        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );
        $this->start = 0;
        $this->size  = filesize($this->path);
        $this->end   = $this->size - 1;
        header("Accept-Ranges: 0-".$this->end);

        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_end = $this->end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            if ($range == '-') {
                $c_start = $this->size - substr($range, 1);
            } else {
                $range = explode('-', $range);
                $c_start = $range[0];

                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $this->end) ? $this->end : $c_end;
            if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $this->start-$this->end/$this->size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream,$this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$this->size);
        } else {
            header("Content-Length: ".$this->size);
        }
    }
    //执行计算范围的流式处理
    private function stream()
    {
        $i = $this->start;
        set_time_limit(0);
        while(!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $this->buffer;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = fread($this->stream,$bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }
    //关闭文件流
    private function end()
    {
        fclose($this->stream);
        exit;
    }



    /**
     * 处理远程资源
     */

    /**
     * 读取远程资源，以 流形式 输出 入口
     * @return void
     */
    public function startRemoteStream()
    {
        if ($this->supported()) {
            $this->remoteStream();
        } else {
            $this->startDownload();
        }
    }

    //读取远程文件，输出流
    private function remoteStream()
    {
        $videoUrl = $this->path;
        //获取视频大小
        $header_array = get_headers($videoUrl, true);
        $sizeTemp = $header_array['Content-Length'];
        if (is_array($sizeTemp)) {
            $size = $sizeTemp[count($sizeTemp) - 1];
        } else {
            $size = $sizeTemp;
        }
    
        //初始参数
        $start = 0;
        $end = $size - 1;
        $length = $size;
        $buffer = 1024 * 1024 * 5; // 输出的流大小 5m
        
        //计算 Range
        $ranges_arr = array();
        if (isset($_SERVER['HTTP_RANGE'])) {
            
            if (!preg_match('/^bytes=\d*-\d*(,\d*-\d*)*$/i', $_SERVER['HTTP_RANGE'])) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
            }
            $ranges = explode(',', substr($_SERVER['HTTP_RANGE'], 6));
            foreach ($ranges as $range) {
                $parts = explode('-', $range);
                $ranges_arr[] = array($parts[0], $parts[1]);
            }
            $ranges = $ranges_arr[0];
            $start = (int)$ranges[0];
            if ($ranges[1] != '') {
                $end = (int)$ranges[1];
            }
            $length = min($end - $start + 1, $buffer);
            $end = $start + $length - 1;
        }else{
            
            // php 文件第一次浏览器请求不会携带 RANGE 为了提升加载速度 默认请求 1 个字节的数据
            $start=0;
            $end=1;
            $length=2;
        }
    
        //添加 Range 分段请求
        $header = array("Range:bytes={$start}-{$end}");
        #发起请求
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $videoUrl);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $header);
        //设置读取的缓存区大小
        curl_setopt($ch2, CURLOPT_BUFFERSIZE, $buffer);
        // 关闭安全认证
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        //追踪返回302状态码，继续抓取
        curl_setopt($ch2, CURLOPT_HEADER, false);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch2, CURLOPT_NOBODY, false);
        curl_setopt($ch2, CURLOPT_REFERER, $videoUrl);
        //模拟来路
        curl_setopt($ch2, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.83 Safari/537.36 Edg/85.0.564.44");
        $content = curl_exec($ch2);
        curl_close($ch2);
        #设置响应头
        header('HTTP/1.1 206 PARTIAL CONTENT');
        header("Accept-Ranges: bytes");
        header("Connection: keep-alive");
        //header("Content-Type: video/mp4");
        header("Content-Type: " . $this->mime);
        header("Access-Control-Allow-Origin: *");
        //为了兼容 ios UC这类浏览器 这里加个判断 UC的 Content-Range 是 起始值-总大小减一
        if($end!=1){
            $end=$size-1;
        }
        header("Content-Range: bytes {$start}-{$end}/{$size}");
        //设置流的实际大小
        header("Content-Length: ".strlen($content));
        //清空缓存区
        ob_clean();
        //输出视频流
        echo $content;
        //销毁内存
        unset($content);

        exit;
    }



    /**
     * 对于不支持的 文件格式 转为 下载
     * @return void
     */
    public function startDownload()
    {
        //以只读和二进制模式打开文件  
        $file = fopen($this->path, "rb");
        
        //告诉浏览器这是一个文件流格式的文件   
        header("Content-type: application/octet-stream");
        //请求范围的度量单位 
        header("Accept-Ranges: bytes"); 
        //Content-Length是指定包含于请求或响应中数据的字节长度   
        header("Accept-Length: " . filesize($this->path)); 
        //用来告诉浏览器，文件是可以当做附件被下载，下载后的文件名称为$file_name该变量的值。
        header("Content-Disposition: attachment; filename=" . basename($this->path));
        
        //特殊格式文件
        if (in_array(strtolower($this->ext), ["ttf","woff","woff2"])) {
            //针对 图标字体 文件，增加 header 解决跨域问题
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: *");
            header("Access-Control-Allow-Methods: POST,GET,OPTIONS");
        }
    
        //读取文件内容并直接输出到浏览器   
        echo fread($file, filesize($this->path));   
        fclose($file);   
        exit();
    }


}