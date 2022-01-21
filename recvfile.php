<?php
    require_once("function.inc");

    define("DIFF_SWITCH_FILE", "-durtN --tabsize=4");
    define("DIFF_SWITCH_FOLDER", "-durtq");

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
        $recv_file = RECV_FILE_CACHE . "{$req["files"][$n]["recv_path"]}/{$req["files"][$n]["commit"]}/" . basename("{$req["files"][$n]["name"]}");
        if (preg_match("/\/$/", $file)) {
            @mkdir($file, 0777, true);
        } else {
            @mkdir(dirname($file), 0777, true);
            @mkdir(dirname($recv_file), 0777, true);
        }
        if ($url != "") {
            if (! file_exists("$recv_file")) {
                $rt = recvfile($url, $recv_file, $headers);
                $size = @filesize($recv_file);
                error_log("url: {$url} ({$rt}), file: {$recv_file} ($size)\n", 3, LOG_FILE);
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
                    touch($recv_file, strtotime($req["files"][$n]["time"]));
                    link($recv_file, $file);
                }
            } else {
                link($recv_file, $file);
            }
        }
    }
    $so = array();
    $rt = 0;
    $download_file_name = $req["download_file_name"];
    if ($req["type"] == "tar.gz") {
        $target = $download_file_name . ".tar.gz";
        $cmd = "cd ${tmp}${download_file_name} && tar czf ../{$target} * --owner=${config["user"]} --group=${config["group"]} --hard-dereference";
        exec($cmd, $so, $rt);
    } elseif ($req["type"] == "zip") {
        $target = $download_file_name . ".zip";
        $cmd = "cd ${tmp}${download_file_name} && zip -r ../{$target} *";
        exec($cmd, $so, $rt);
    } elseif ($req["type"] == "diff") {
        $target = $download_file_name . ".diff";
        if (file_exists("${tmp}${download_file_name}/${config["download_name_latest_commit_of_main"]}")) {
            rename(
                "${tmp}${download_file_name}/${config["download_name_latest_commit_of_main"]}",
                "${tmp}${download_file_name}/a"
            );
        } else {
            rename(
                "${tmp}${download_file_name}/${config["download_name_previous_commit"]}",
                "${tmp}${download_file_name}/a"
            );
        }
        rename(
            "${tmp}${download_file_name}/${config["download_name_selected_commit"]}",
            "${tmp}${download_file_name}/b"
        );

        $cmd = "cd ${tmp}${download_file_name};" .
                "diff ". DIFF_SWITCH_FOLDER . " a/ b/|grep \"Only in \"";
        $dir_diff = "";
        unset($so);
        exec($cmd, $so, $rt);
        foreach ($so as $str) {
            if (preg_match("/^Only in ([ab])\/: (.+)$/", $str, $match)) {
                if (is_dir("${tmp}${download_file_name}/{$match[1]}/{$match[2]}")) {
                    if ($match[1] == "a") {
                        $dir_diff .= "diff --git a/{$match[2]} b/{$match[2]}\ndeleted file mode 100644\n";
                    } else {
                        $dir_diff .= "diff --git a/{$match[2]} b/{$match[2]}\nnew file mode 100644\n";
                    }
                }
            }
        }

        $cmd = "cd ${tmp}${download_file_name};" .
                "diff ". DIFF_SWITCH_FILE . " a/ b/";
        unset($so);
        exec($cmd, $so, $rt);
        $file_diff = implode("\n", $so) . "\n";
        file_put_contents("{$tmp}{$target}.org", $file_diff);
        $rt = preg_replace("/^Binary files (.+) differ$/m", "diff \$1\nBinary files \$1 differ", $file_diff);
        if (!is_null($rt)) {
            $file_diff = $rt;
        }
        file_put_contents("{$tmp}{$target}", $dir_diff . $file_diff);
    }
    rmdir_r("{$tmp}{$download_file_name}");
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
