<?php
    require_once("function.inc");

    if (!isset($_REQUEST["command"])) {
        echo "invalid access";
        exit;
    }
    $result = array(
        "result" => "UNKNOWN"
    );

    $config = json_decode(file_get_contents("./config.json"), true);

    if ($_REQUEST["command"] == "cache_check") {
        $seed = json_decode($_REQUEST["seed"], true);
        $seed = json_encode($seed, JSON_UNESCAPED_UNICODE);
        $hash = hash("sha256", $seed);
        $result["result"] = "OK";
        $result["hash"] = $hash;
        $tmp = CACHE_DIR . $hash . "/";
        $fp = fopen(LOCK_FILE, "c");
        if ($fp) {
            flock($fp, LOCK_EX);
            $stat = get_status("{$tmp}status.json");
            if ($stat === false) {
                $result["status"] = "new";
                @mkdir($tmp);
                $stat = array(
                    "status" => "wait"
                );
                file_put_contents("${tmp}status.json", json_encode($stat));
                file_put_contents("${tmp}seed.json", $seed);
            } else {
                if ($seed == file_get_contents("${tmp}seed.json")) {
                    $result["status"] = $stat["status"] == "comp" ? "ready" : "busy";
                } else {
                    $result["status"] = "error";
                }
            }
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
        }
    } elseif ($_REQUEST["command"] == "put_projects") {
        $fp = fopen(PROJECTS_FILE, "c");
        if ($fp) {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            fwrite($fp, $_REQUEST["projects"]);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            $result["result"] = "OK";
        } else {
            $result["result"] = "File Write Error";
        }
    } elseif ($_REQUEST["command"] == "get_projects") {
        $fp = @fopen(PROJECTS_FILE, "r");
        if ($fp) {
            flock($fp, LOCK_SH);
            $json = fread($fp, filesize(PROJECTS_FILE));
            flock($fp, LOCK_UN);
            fclose($fp);
            $result["result"] = "OK";
            $result["projects"] = json_decode($json, true);
        } else {
            $result["result"] = "OK";
            $result["projects"] = json_decode("[]", true);
        }
        $result["cookies"] = !empty($_COOKIE) ? $_COOKIE : array();
    } elseif ($_REQUEST["command"] == "recvfile") {
        if (isset($_REQUEST["hash"]) && $_REQUEST["hash"] != "") {
            $tmp = CACHE_DIR . $_REQUEST["hash"] . "/";
            @mkdir($tmp);
            $_REQUEST["files"] = json_decode($_REQUEST["files"], true);
            $json = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
            file_put_contents("{$tmp}request.json", $json);
            exec("nohup php -f recvfile.php {$tmp}  > /dev/null &", $so);
            $result["result"] = "OK";
            $result["id"] = $_REQUEST["hash"];
        } else {
            $result["result"] = "not found hash";
        }
    } elseif ($_REQUEST["command"] == "status") {
        if (isset($_REQUEST["id"]) && file_exists(CACHE_DIR . "{$_REQUEST["id"]}/status.json")) {
            $result["result"] = "OK";
            $stat = get_status(CACHE_DIR . "{$_REQUEST["id"]}/status.json");
            if ($stat !== false) {
                $result += $stat;
            }
        }
    } elseif ($_REQUEST["command"] == "cancel") {
        $fp = fopen(LOCK_FILE, "c");
        flock($fp, LOCK_EX);
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $tmp = CACHE_DIR . $_REQUEST["id"] . "/";
            $result["result"] = "OK";
            if (file_exists("{$tmp}pid")) {
                $result["result"] = "OK";
                $pid = intval(file_get_contents("{$tmp}pid"));
                $result["pid"] = $pid;
                posix_kill($pid, 15 /*SIGTERM*/);
            }
            rmdir_r($tmp);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    } elseif ($_REQUEST["command"] == "download") {
        if (isset($_REQUEST["id"]) && file_exists(CACHE_DIR . "{$_REQUEST["id"]}/request.json")) {
            $tmp = CACHE_DIR . $_REQUEST["id"] . "/";
            $req = json_decode(file_get_contents("{$tmp}request.json"), true);
            $file = "{$tmp}". $req["download_file_name"] . "." . $req["type"];
            $new_file = "{$tmp}". $_REQUEST["download_file_name"] . "." . $req["type"];
            if ($file != $new_file) {
                rename($file, $new_file);
                $file = $new_file;
                $req["download_file_name"] = $_REQUEST["download_file_name"];
                $json = json_encode($req, JSON_UNESCAPED_UNICODE);
                unlink("{$tmp}request.json");
                file_put_contents("{$tmp}request.json", $json);
            }
            header("Content-type: application/gzip");
            header("Content-Disposition: attachment; filename=" . basename($file));
            header("Content-Length: " . filesize($file));
            readfile($file);
            $pid = intval(file_get_contents("{$tmp}pid"));
            posix_kill($pid, 15 /*SIGTERM*/);
            exit;
        }
    } elseif ($_REQUEST["command"] == "logout") {
        setcookie($config["session_cookie_name"], "", time() - 3600, "/");
        $result["result"] = "OK";
    }
    $text = json_encode($result, JSON_UNESCAPED_UNICODE);
    header("Content-Type: application/json; charset=utf-8");
    header("Content-Length: " . strlen($text));
    echo $text;
