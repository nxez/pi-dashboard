<?php
/*!
* Pi Dashboard (http://www.nxez.com)
* Copyright 2017 NXEZ.com.
* Licensed under the GPL v3.0 license.
*/

@header("content-Type: text/html; charset=utf-8");
ob_start();
date_default_timezone_set('Asia/Shanghai');

$D = Array();
$D['page']['time']['start'] = explode(' ', microtime());
get_info();

if (isset($_GET['ajax']) && $_GET['ajax'] == "true"){
    echo json_encode($D);
    exit;
}

$D['version'] = '1.0.0';
$D['model'] = get_device_model();
$D['user'] = @get_current_user();
$D['hostname'] = gethostname();
$D['hostip'] = ('/'==DIRECTORY_SEPARATOR) ? $_SERVER['SERVER_ADDR'] : @gethostbyname($_SERVER['SERVER_NAME']);
$D['yourip'] = $_SERVER['REMOTE_ADDR'];
$D['uname'] = @php_uname();
$D['os'] = explode(" ", php_uname());

if (($str = @file("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq")) !== false){
    $D['cpu']['freq'] = $str[0];
}
else{
    $D['cpu']['freq'] = 0;
}


if (($str = @file("/proc/cpuinfo")) !== false){
    $str = implode("", $str);
    @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);
    @preg_match_all("/BogoMIPS\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/", $str, $bogomips);

    if (false !== is_array($model[1])){
        $D['cpu']['count'] = sizeof($model[1]);
        $bogomips[1][0] = ' | Bogomips:'.$bogomips[1][0];
        if($D['cpu']['count'] == 1){
            $D['cpu']['model'] = $model[1][0].$bogomips[1][0];
        }
        else{
            $D['cpu']['model'] = $model[1][0].$bogomips[1][0].' ×'.$D['cpu']['count'];
        }
    }
}
else{
    $D['cpu']['count'] = 1;
    $D['cpu']['model'] = '';
}

function get_device_model(){
    return ['name' => 'Raspberry Pi', 'id' => 'raspberry-pi'];
}

function get_info(){
    global $D;

    $D['time'] = time();

    if (($str = @file("/proc/uptime")) !== false){
        $str = explode(" ", implode("", $str));
        $D['uptime'] = trim($str[0]);
    }
    else{
        $D['uptime'] = 0;
    }

    // CPU Core
    if (($str = @file("/proc/stat")) !== false){
        $str = str_replace("  ", " ", $str);
        $info = explode(" ", implode("", $str));
        $D['cpu']['stat'] = array('user'=>$info[1],
            'nice'=>$info[2],
            'sys' => $info[3],
            'idle'=>$info[4],
            'iowait'=>$info[5],
            'irq' => $info[6],
            'softirq' => $info[7]
        );
    }
    else{
        $D['cpu']['stat'] = array('user'=>0,
            'nice'=>0,
            'sys' => 0,
            'idle'=> 0,
            'iowait'=> 0,
            'irq' => 0,
            'softirq' => 0
        );
    }


    if (($str = @file("/sys/class/thermal/thermal_zone0/temp")) !== false){
        $D['cpu']['temp'] = $str;
    }
    else{
        $D['cpu']['temp'] = 0;
    }


    if (($str = @file("/proc/meminfo")) !== false){
        $str = implode("", $str);

        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s", $str, $buffers);

        $D['mem']['total'] = round($buf[1][0]/1024, 2);
        $D['mem']['free'] = round($buf[2][0]/1024, 2);
        $D['mem']['buffers'] = round($buffers[1][0]/1024, 2);
        $D['mem']['cached'] = round($buf[3][0]/1024, 2);
        $D['mem']['cached_percent'] = (floatval($D['mem']['cached'])!=0)?round($D['mem']['cached']/$D['mem']['total']*100,2):0;
        $D['mem']['used'] = $D['mem']['total']-$D['mem']['free'];
        $D['mem']['percent'] = (floatval($D['mem']['total'])!=0)?round($D['mem']['used']/$D['mem']['total']*100,2):0;
        $D['mem']['real']['used'] = $D['mem']['total'] - $D['mem']['free'] - $D['mem']['cached'] - $D['mem']['buffers'];
        $D['mem']['real']['free'] = round($D['mem']['total'] - $D['mem']['real']['used'],2);
        $D['mem']['real']['percent'] = (floatval($D['mem']['total'])!=0)?round($D['mem']['real']['used']/$D['mem']['total']*100,2):0;
        $D['mem']['swap']['total'] = round($buf[4][0]/1024, 2);
        $D['mem']['swap']['free'] = round($buf[5][0]/1024, 2);
        $D['mem']['swap']['used'] = round($D['mem']['swap']['total']-$D['mem']['swap']['free'], 2);
        $D['mem']['swap']['percent'] = (floatval($D['mem']['swap']['total'])!=0)?round($D['mem']['swap']['used']/$D['mem']['swap']['total']*100,2):0;
    }
    else{
        $D['mem']['total'] = 0;
        $D['mem']['free'] = 0;
        $D['mem']['buffers'] = 0;
        $D['mem']['cached'] = 0;
        $D['mem']['cached_percent'] = 0;
        $D['mem']['used'] = 0;
        $D['mem']['percent'] = 0;
        $D['mem']['real']['used'] = 0;
        $D['mem']['real']['free'] = 0;
        $D['mem']['real']['percent'] = 0;
        $D['mem']['swap']['total'] = 0;
        $D['mem']['swap']['free'] = 0;
        $D['mem']['swap']['used'] = 0;
        $D['mem']['swap']['percent'] = 0;
    }


    if (($str = @file("/proc/loadavg")) !== false){
        $str = explode(" ", implode("", $str));
        $str = array_chunk($str, 4);
        $D['load_avg'] = $str[0];
    }
    else{
        $D['load_avg'] = array(0,0,0,'0/0');
    }

    $D['disk']['total'] = round(@disk_total_space(".")/(1024*1024*1024),3);
    $D['disk']['free'] = round(@disk_free_space(".")/(1024*1024*1024),3);
    $D['disk']['used'] = $D['disk']['total'] - $D['disk']['free'];
    $D['disk']['percent'] = (floatval($D['disk']['total'])!=0)?round($D['disk']['used']/$D['disk']['total']*100,2):0;


    if (($strs = @file("/proc/net/dev")) !== false){
        $D['net']['count'] = count($strs) - 2;

        for ($i = 2; $i < count($strs); $i++ )
        {
            preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
            $D['net']['interfaces'][$i-2]['name'] = $info[1][0];
            $D['net']['interfaces'][$i-2]['total_in'] = $info[2][0];
            $D['net']['interfaces'][$i-2]['total_out'] = $info[10][0];
        }
    }
    else{
        $D['net']['count'] = 0;
    }
}
?>
