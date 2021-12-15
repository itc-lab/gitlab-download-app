<?php
    require_once("function.inc");

    declare(ticks = 1);
    $config = json_decode(file_get_contents("./config.json"), true);
    $tmp = $argv[1];
    $pid = getmypid();
    file_put_contents("{$tmp}pid", $pid);
    $req = json_decode(file_get_contents("{$tmp}request.json"), true);
    if (!isset($req["files"])) {
        $req["files"] = array();
    }
    error_log("$_REQUEST: " . var_export($req, true) . "\n", 3, LOG_FILE);

    pcntl_signal(15 /*SIGTERM*/, function ($signo, $siginfo) {
        rmdir_r($GLOBALS["tmp"]);
        error_log("abort: {$GLOBALS["tmp"]}\n", 3, LOG_FILE);
        exit;
    });
    $headers = array(
        "private-token: " . ACCESS_TOKEN
    );
    $stat = array(
        "status" => "run"
    );
    $total = count($req["files"]);
    $stat["total"] = $total;
    for ($n = 0; $n < $total; $n ++) {
        $stat["current"] = $n + 1;
        put_status("${tmp}status.json", json_encode($stat));
        $url = $req["files"][$n]["url"];
        $file = "{$tmp}{$req["files"][$n]["name"]}";
        if (preg_match("/\/$/", $file)) {
            @mkdir($file, 0777, true);
        } else {
            @mkdir(dirname($file), 0777, true);
        }
        if ($url != "") {
            $rt = recvfile($url, $file, $headers);
            $size = @filesize($file);
            error_log("url: {$url} ({$rt}), file: {$file} ($size)\n", 3, LOG_FILE);
            if ($rt !== true) {
                $stat["status"] = "error";
                $ar = json_decode($rt, true);
                if ($ar) {
                    if (isset($ar["error"])) {
                        $stat["message"] = $ar["error"];
                    } elseif (isset($ar["message"])) {
                        $stat["message"] = $ar["message"];
                    } else {
                        $stat["message"] += $ar;
                    }
                } else {
                    $stat["message"] = $rt;
                }
                put_status("${tmp}status.json", json_encode($stat));
                exit;
            } else {
                touch($file, strtotime($req["files"][$n]["time"]));
            }
        }
    }
    $so = array();
    $rt = 0;
    if ($req["type"] == "tar.gz") {
        $target = "gitlab.tar.gz";
        $cmd = "cd ${tmp}gitlab && tar czf ../{$target} * --owner=${config["user"]} --group=${config["group"]}";
    } elseif ($req["type"] == "zip") {
        $target = "gitlab.zip";
        $cmd = "cd ${tmp}gitlab && zip -r ../{$target} *";
    }
    $rt = exec($cmd, $so, $rt);
    rmdir_r("{$tmp}gitlab");
    $stat["status"] = "comp";
    put_status("${tmp}status.json", json_encode($stat));
    exit;

    ///////////////////////////////////////////////////////////////////////////
    function recvfile($url, $file = "", $headers = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if (!strcmp($file, "php://output")) {
        } elseif (strlen($file) != 0) {
            $fp = fopen($file, "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
        } else {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        }
        $reply = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (strlen($file) == 0) {
            if ($code != 200) {
                return false;
            }
            return $reply;
        }
        if (isset($fp)) {
            fclose($fp);
        }
        if ($code == 0) {
            return "Unknown host";
        }
        if ($code >= 400) {
            if (strlen($file) != 0) {
                $reply = file_get_contents($file);
                if (file_exists($file)) {
                    unlink($file);
                }
                return $reply;
            } else {
                return "Unknown error";
            }
        }
        return true;
    }
