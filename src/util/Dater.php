<?php
/**
 * 工具类
 * 日期时间 相关工具
 */

namespace Spf\util;

class Dater extends Util 
{
    //!! Spf 框架处理时间字符串的 默认格式
    public static $format = "Y-m-d H:i:s";

    //保留小数位数
    public static $presion = 2;

    //高精度浮点计算小数位数
    public static $bcscale = 4;

    //工作日参数
    public static $wds = 6;                 //每周6天工作日，1-7=周一~周日，周日休息
    public static $wdts = (8*60+30)*60;     //每个工作日开始时间，08:30，秒数，从每日 0 点开始计算
    public static $wdte = 18*60*60;         //每个工作日结束时间，18:00，秒数，从每日 0 点开始计算

    

    /**
     * 判断一个字符串是否有效的 日期字符串 
     * !! Spf 框架的 日期字符串格式统一为  Y-m-d H:i:s  2026-05-12 08:21:00
     * @param String $ds
     * @return Bool
     */
    public static function isStr($ds=null)
    {
        if (!Is::nemstr($ds)) return false;
        //去除外层可能存在的 '' ""
        $ds = Str::trimQuote($ds);
        //验证 0000-00-00 00:00:00 格式
        if (preg_match("/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/", $ds)!==1) return false;
        //使用 strtotime 方法验证是否有效的 日期时间格式 排除 19月 48日 67分 等情况
        return strtotime($ds)!==false;
    }

    /**
     * 判断一个 整数 是否有效的 时间戳
     * @param Int $ts
     * @return Bool
     */
    public static function isTs($ts=null)
    {
        //必须是 整数 或 整数字符串
        if (!is_numeric($ts) || (int)$ts!=$ts) return false;
        $ts = (int)$ts;
        //必须在 整数范围内
        if ($ts<PHP_INT_MIN || $ts>PHP_INT_MAX) return false;
        //必须能被正确转换
        $date = \DateTime::createFromFormat('U', (string)$ts);
        return $date && \DateTime::getLastErrors()['warning_count'] === 0;
    }

    /**
     * 工作时间段解析，返回 时，分，时长秒数
     * @param Int $st 工作日开始秒数，默认 self::$wdts
     * @param Int $et 工作日结束秒数，默认 self::$wdte
     * @return Array [ sh=>开始时, sm=>开始分, eh=>结束时, em=>结束分, dura=>时长秒数, durah=>时长小时数 ]
     */
    public static function parseWorkTime($st=0, $et=0)
    {
        //工作日
        $ws = $st;
        $we = $et;
        $ws = $ws<=0 ? self::$wdts : $ws;
        $we = $we<=0 ? self::$wdte : $we;
        $wsh = floor($ws/(60*60));
        $wsm = floor(($ws%(60*60))/60);
        $weh = floor($we/(60*60));
        $wem = floor(($we%(60*60))/60);
        $dura = strtotime("2024-01-01 $weh:$wem:00") - strtotime("2024-01-01 $wsh:$wsm:00");
        return [
            "st" => $ws,                //开始时间，秒数
            "et" => $we,                //结束时间，秒数
            "sh" => $wsh,               //开始时，8
            "sm" => $wsm,               //开始分，30
            "eh" => $weh,               //结束时，18
            "em" => $wem,               //结束分，0
            "dura" => $dura,            //工作时长，秒，34200
            "durah" => $dura/(60*60)    //工作时长，小时，9.5
        ];
    }

    /**
     * 将给定的时间戳，转换为 最近的 工作时间段内的 时间戳
     * @param Int $time 给定时间戳
     * @param Bool $calcDay 是否计算工作日，默认 false，表示 不计算时间戳所在日期的星期数是否在工作日
     * @param Int $shift 当确定计算工作日时，此参数确定日期偏移方向，如果给定时间戳正好不在工作日，则 shift > 0 => 向后偏移，shift < 0 => 向前偏移
     * @return Int 转换后的时间戳
     */
    public static function toWorkTime($time, $calcDay = false, $shift = 1)
    {
        //获取当前默认的 工作时间段
        $wt = self::parseWorkTime();
        $wet = $wt["et"];   //结束 秒数
        $wst = $wt["st"];   //开始 秒数

        $h = date("H", $time)*1;
        $m = date("i", $time)*1;
        $ss = ($h*60+$m)*60;

        //计算时间
        $tstr = date("H:i:s", $time);
        if ($ss>=$wet || $ss<$wst) {
            $ds = abs($ss-$wst);    //离 开始 的 距离，秒数
            $de = abs($ss-$wet);    //离 结束 的 距离，秒数
            if ($ds<=$de) {
                //时间调整为 工作时间段 开始时间
                $tstr = $wt["sh"].":".$wt["sm"];
            } else {
                //时间调整为 工作时间段 结束时间
                $tstr = $wt["eh"].":".$wt["em"];
            }
            $tstr .= ":".date("s", $time);
        }

        //计算日期
        $dstr = date("Y-m-d", $time);
        if ($calcDay) {
            //如果要求计算 工作日
            $shift = $shift<0 ? -1 : 1;
            //计算日期是否在 工作日
            $wd = self::$wds;
            $td = date("N", $time);
            if ($td>$wd) {
                //不在工作日，根据 shift 计算调整的天数，正负表示调整方向
                $shift = $shift * ($td-$wd);
                $nt = $time + $shift*24*60*60;
                $dstr = date("Y-m-d", $nt);
            }
        }

        //返回计算后的 时间戳
        $timestr = $dstr." ".$tstr;
        return strtotime($timestr);
    }

    /**
     * 判断给定的日期是否当月的最后 $n 天
     * @param Mixed $d 给定日期，可以是 时间戳，日期字符串
     * @param Int $n 每月最后天数，用以判断
     * @return Bool
     */
    public static function isMonthEnd($n=1, $d=null)
    {
        if (is_null($d)) {
            $t = time();
        } else if (is_numeric($d)) {
            $t = $d*1;
        } else if (self::isStr($d)) {
            $t = strtotime($d);
        } else {
            $t = time();
        }
        $dt = date("j",$t);
        $t2 = $t+$n*24*3600;
        $dt2 = date("j",$t2);

        return $dt2<$dt;
    }

    /**
     * 计算保质期至 生产日期+保质期天数
     * @param String $start 开始日期(生产日期)，"2023-07-30"  or  timestamp
     * @param Int $days 要增加的天数(保质期天数) 1月=30天，1年=360天
     * @return Int timestamp
     */
    public static function warranty($start, $days)
    {
        $st = is_numeric($start) ? $start : strtotime($start);
        $sd = date("Y-m-d", $st);
        $ds = (int)$days-1;
        $ys = floor($ds/360);
        $ds = $ds%360;
        $ms = floor($ds/30);
        $ds = $ds%30;
        $add = [];
        if ($ys>0) $add[] = "+$ys year";
        if ($ms>0) $add[] = "+$ms month";
        if ($ds>0) $add[] = "+$ds day";
        $as = implode(" ", $add);
        $et = strtotime($as, $st);
        return $et;
    }

    /**
     * 计算截止时间 timestamp
     * 计算 $timestamp 加上 $seconds 秒数后的 新 timestamp
     * 需要计算每日工作时长，并跳过休息日
     * @param Int $timestamp 时间戳，开始时间
     * @param Int $seconds 增加的秒数，限定时长，秒数
     * @param Int $st 每个工作日的开始时间，默认 self::$wdts
     * @param Int $et 每个工作日的结束时间，默认 self::$wdte
     * @param Int $workingdays 每周工作天数，默认 self::$wds
     * @return Timestamp
     */
    public static function deadline($timestamp, $seconds, $st=0, $et=0, $workingdays=0)
    {
        $st = $st<=0 ? self::$wdts : $st;
        $et = $et<=0 ? self::$wdte : $et;
        $workingdays = $workingdays<=0 ? self::$wds : $workingdays;

        $tc = $timestamp;
        if (date("N",$tc)>$workingdays) {   //如果起始时间在休息日，则将起始时间设置为之后的第一个工作日的工作开始时间
            $_tc = $tc+((7-$workingdays)*24*60*60);
            $_t0 = strtotime(date("Y-m-d", $_tc)." 00:00:00");
            $tc = $_t0+$st;
        }
        //现在 $tc 肯定不是休息日
        $t0 = strtotime(date("Y-m-d",$tc)." 00:00:00");  //今日 0时
        if ($tc<$t0+$st) {
            $tc = $t0+$st;
        } else if ($tc>=$t0+$et) {
            if (date("N",$tc)==$workingdays) {
                $tc = $t0+$st+((7-$workingdays+1)*24*60*60);
            } else {
                $tc = $t0+$st+(24*60*60);
            }
        }
        //现在 $tc 作为今日，肯定不是休息日，肯定在工作时间段
        $t0 = strtotime(date("Y-m-d",$tc)." 00:00:00");  //今日 0时
        $dw = $et-$st;              //每日工作时长，秒数
        $dp = ceil($seconds/$dw);   //增加的秒数折合几个工作日，向上取整，整除则+1
        if ($dp==$seconds/$dw) {
            $dp += 1;
        }
        $tp = $tc+$seconds;         //加上秒数后的 timestamp
        $ts = $t0+$st;  //今日 工作开始时间
        $te = $t0+$et;  //今日 工作结束时间
        $wc = date("N", $tc);   //今日周几，1-7
        
        if ($tp<=$te) return $tp;       //如果加上秒数后仍在今日工作时间范围内，直接返回
        if ($dp<=1) {                   // 1    如果增加的秒数折合工作日不超过 1 天   
            $tte = $te-$tc;
            $_tp = $t0+(24*60*60)+$st+($seconds-$tte);
            if (date("N",$_tp)>$workingdays) {  //如果计算后为休息日，则向后顺延 (7-workingdays)*24*60*60 秒
                return $_tp+((7-$workingdays)*24*60*60);
            } else {
                return $_tp;
            }
        } else if ($dp>=7) {               // 2    如果增加秒数折合工作日超过 7 天
            $tw = $workingdays-$wc;
            $dpws = floor(($dp-$tw)/7);
            $ds = $dp-1+(($dpws+1)*(7-$workingdays));
            //var_dump("实际过了 ".$ds." 天，含 ".$dpws." 个休息日");
            $ss = ($dp-1)*$dw;
            $scs = $seconds-$ss;
            $_tc = $tc+($ds*24*60*60);
            return self::deadline($_tc, $scs, $st, $et, $workingdays);
        } else {                        // 3    如果增加秒数折合工作日天数在 1-7 之间
            if (7-$wc>$dp) {            // 3.1  如果增加秒数折合天数<今日到休息日的天数
                $_te = $seconds-($te-$tc)-(($dp-1)*$dw);
                return $ts+($dp*24*60*60)+$_te;
            } else  {                   // 3.2  如果增加秒数后，日期超过了休息日日期
                $tw = 7-$wc;
                $pw = $dp-$tw;
                $ds = $dp+(7-$workingdays);
                //var_dump("实际需要天数 ".($ds-1)." 天，24h");
                $_tsc = $te-$tc;
                $scs = $seconds-(($dp-2)*$dw)-$_tsc;
                $_ts = $ts+($ds-1)*24*60*60;
                return $_ts+$scs;
            }
        }
    }

    /**
     * 计算 $timestamp 所在月份的 最后 $len 个工作日（日期数组）
     * 需要跳过休息日
     * @param Int $timestamp 给定的日期时间戳
     * @param Int $len 要获取的工作日长度，天数
     * @param Int $workingdays 每周工作日天数，默认 self::$wds
     * @return Array like [ 28, 29, 30, ... ]
     */
    public static function lastwds($timestamp, $len=3, $workingdays=0) 
    {
        $workingdays = $workingdays<=0 ? self::$wds : $workingdays;
        //$timestamp 月份有多少天，format="t"
        $ld = date("t", $timestamp);
        //$timestamp 月份最后一天是周几，数字 1-7 mon-sun，format="N"
        $lw = date("N", strtotime(date("Y-m", $timestamp)."-".$ld." 12:00:00"));
        if ($lw<$workingdays+1){
            if ($lw<$len) {
                $sd = $ld-(7-$workingdays)-($len-1);
            } else {
                $sd = $ld-($len-1);
            }
        } else {
            $sd = $ld-($lw-$workingdays)-($len-1);
        }
        $ds = [];
        for ($i=$sd;$i<=$ld;$i++) {
            if (date("N", strtotime(date("Y-m", $timestamp)."-".$i." 12:00:00"))<($workingdays+1)){
                $ds[] = $i;
            }
        }
        return $ds;
    }

    /**
     * 根据 stimestamp 和 etimestamp 计算耗时，返回 秒数
     * 仅计算在每日工作时间内的 耗时，不考虑每周工作几天，适用于连续事件，例如 采购耗时
     * @param Int $stime 开始时间戳
     * @param Int $etime 结束时间戳
     * @param Array $workHours 工作日开始，结束的时间（从0点开始的秒数），默认：[self::$wdts, self::$wdte]
     * @return Float 实际耗时数，秒
     */
    public static function timespend($stime, $etime, $workHours = [0,0])
    {
        //开始时间
        $sy = date("Y", $stime)*1;
        $sm = date("m", $stime)*1;
        $sd = date("d", $stime)*1;
        $sh = date("H", $stime)*1;
        $si = date("i", $stime)*1;
        $ss = date("s", $stime)*1;
        //结束时间
        $ey = date("Y", $etime)*1;
        $em = date("m", $etime)*1;
        $ed = date("d", $etime)*1;
        $eh = date("H", $etime)*1;
        $ei = date("i", $etime)*1;
        $es = date("s", $etime)*1;
        //工作日
        $ws = $workHours[0];   //explode(":", $workHours[0]);
        $we = $workHours[1];   //explode(":", $workHours[1]);
        $ws = $ws<=0 ? self::$wdts : $ws;
        $we = $we<=0 ? self::$wdte : $we;
        $wsh = floor($ws/(60*60));
        $wsm = floor(($ws%(60*60))/60);
        $weh = floor($we/(60*60));
        $wem = floor(($we%(60*60))/60);
        //开始时间到开始当天下班的间隔时长，秒数
        $sdura = strtotime("$sy-$sm-$sd $weh:$wem:00")-$stime;
        $sdura = $sdura<0 ? 0 : $sdura;
        //结束时间到结束当天上班时间的间隔时长，秒数
        $edura = $etime-strtotime("$ey-$em-$ed $wsh:$wsm:00");
        $edura = $edura<0 ? 0 : $edura;
        //开始时间下一天 0 点的 timestamp
        $snext = strtotime("$sy-$sm-$sd 23:59:59")+1;
        //结束时间上一天 23.59.59 的 timestamp
        $eprev = strtotime("$ey-$em-$ed 00:00:00")-1;
        //开始到结束，中间经过了几个完整的 天
        $ddura = round(($eprev-$snext)/(24*60*60));
        $ddura = $ddura<0 ? 0 : $ddura;
        //每个工作日时长，秒数
        $wdura = strtotime("2024-01-01 $weh:$wem:00") - strtotime("2024-01-01 $wsh:$wsm:00");
        //最终耗时，秒数
        $spend = $sdura + $edura + $ddura*$wdura;
        return $spend;
    }
    //返回耗时，小时数
    public static function timespendHours($stime, $etime, $workHours = [0,0])
    {
        $spend = self::timespend($stime, $etime, $workHours);
        return round($spend/(60*60));
    }

    /**
     * 根据 开始时间戳stime 和 预计耗时小时数，计算 预计完成时间戳
     * 耗时数仅计算每日工作时间段内的时长，不考虑每周工作几天，适用于连续事件，例如 采购耗时
     * @param Int $stime 开始时间
     * @param Float $spend 预计耗时，小时数，可以为负数
     * @param Array $workHours 工作日开始，结束的时间（从0点开始的秒数），默认：[self::$wdts, self::$wdte]
     * @return Int 预计完成时间戳
     */
    public static function timeexpect($stime, $spend, $workHours = [0,0])
    {
        if ($spend==0) return $stime;
        //耗时 秒数
        $spend = $spend*60*60;
        //工作时间段
        $wp = self::parseWorkTime(...$workHours);
        $wsh = $wp["sh"];
        $wsm = $wp["sm"];
        $weh = $wp["eh"];
        $wem = $wp["em"];
        $wdura = $wp["dura"];
        //开始时间
        $sy = date("Y", $stime)*1;
        $sm = date("m", $stime)*1;
        $sd = date("d", $stime)*1;
        $sh = date("H", $stime)*1;
        $si = date("i", $stime)*1;
        $ss = date("s", $stime)*1;

        if ($spend>0) {
            //常规，从 $stime 向后计算耗时

            //开始时间到开始当天下班时间的 间距，秒数
            $sdura = strtotime("$sy-$sm-$sd $weh:$wem:00")-$stime;
            $sdura = $sdura<0 ? 0 : $sdura;
            if ($spend<=$sdura) return $stime+$spend;
            //剩余耗时可以划分为几个完整的 天，每天只计算工作时长
            $days = floor(($spend-$sdura)/$wdura);
            //去掉完整的天后，剩余的 秒数
            $spleft = floor(($spend-$sdura)%$wdura);
            //结束时间到结束当天 0 点的秒数，需要算上上班时间
            $et = $wp["st"]+$spleft;
            //最终结束时间的 时间戳
            $etime = strtotime("$sy-$sm-$sd 23:59:59")+1+($days*24*60*60)+$et;
        } else {
            //反向计算，即 $stime 为耗时 $spend 后得到的时间戳，向前计算开始时间戳

            $spend = $spend*-1;
            //结束时间到开始当天上班时间的 间距，秒数
            $sdura = $stime-strtotime("$sy-$sm-$sd $wsh:$wsm:00");
            $sdura = $sdura<0 ? 0 : $sdura;
            if ($spend<=$sdura) return $stime-$spend;
            //剩余耗时可以划分为几个完整的 天，每天只计算工作时长
            $days = floor(($spend-$sdura)/$wdura);
            //去掉完整的天后，剩余的 秒数
            $spleft = floor(($spend-$sdura)%$wdura);
            //开始时间到开始当天 24 点的秒数，需要算上上班时间
            $st = $wp["et"]-$spleft;
            //最终开始时间的 时间戳
            $etime = strtotime("$sy-$sm-$sd 00:00:00")-($days*24*60*60)-$st;
        }
        return $etime;
    }

    /**
     * 将给定的时间戳，转换为时间戳所在日期的 0点-24点 的时间戳区间
     * 用于判断是否在 stime ~ etime 所在日期区间内
     * @param Int $stime 开始时间戳
     * @param Int $etime 结束时间戳，不指定则与 stime 相同
     * @return Array [00:00:00时间戳， 23:59:59时间戳]
     */
    public static function timeInDura($stime, $etime = 0)
    {
        $etime = $etime==0 ? $stime : $etime;
        $st = $etime>=$stime ? $stime : $etime;
        $et = $etime>=$stime ? $etime : $stime;
        $ststr = date("Y-m-d 00:00:00", $st);
        $etstr = date("Y-m-d 23:59:59", $et);
        $stt = strtotime($ststr);
        $ett = strtotime($etstr);
        return [$stt, $ett];
    }

    /**
     * 计算给定日期的 本周、本月 开始00点 到 结束24点 的 timedura
     * @param Int $t 指定时间戳
     * @return Array [00:00:00时间戳， 23:59:59时间戳]
     */
    public static function timeInWeekMonthDura($t)
    {
        $w = date("w", $t);     //0~6 给定时间是周几
        $wst = $t - $w*(24*60*60);      //一周开始的时间
        $wet = $t + (6-$w)*(24*60*60);  //一周结束的时间
        $weekDura = [
            strtotime(date("Y-m-d 00:00:00", $wst)),
            strtotime(date("Y-m-d 23:59:59", $wet))
        ];

        $m = date("t", $t);     //所在月份的天数 28~31
        $monthDura = [
            strtotime(date("Y-m-01 00:00:00", $t)),
            strtotime(date("Y-m-".$m." 23:59:59", $t))
        ];

        return [
            "week" => $weekDura,
            "month" => $monthDura
        ];
    }

    /**
     * 判断给定的时间戳，是否属于同一天
     * @param Int $timestamps 给定时间戳，至少 2 个
     * @return Bool 
     */
    public static function timeInSameDay(...$timestamps)
    {
        if (count($timestamps)<2) return true;
        $stime = array_shift($timestamps);
        $dura = self::timeInDura($stime);
        $flag = true;
        for ($i=0;$i<count($timestamps);$i++) {
            $ti = $timestamps[$i];
            if ($ti>$dura[1] || $ti<$dura[0]) {
                $flag = false;
                break;
            }
        }
        return $flag;
    }

}