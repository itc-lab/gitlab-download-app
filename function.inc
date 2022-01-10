<?php
    define("LOG_PATH", "/tmp/");
    define("LOG_FILE", "/tmp/gitlab-download-app.log");
    define("CACHE_DIR", "/opt/gitlab-download-app/cache/");
    define("RECV_FILE_CACHE", CACHE_DIR . "RECV_FILE/");
    define("PROJECTS_FILE", CACHE_DIR . "projects.json");
    define("LOCK_FILE", "/tmp/gitlab-download-app.lock");
    define("ACCESS_TOKEN", "XXXXXXXXXXXXXXXXXXXX");
    define("PER_PAGE", 100);
    define('TARGET_EVENTS', array( 'project_destroy',
                                   'project_rename',
                                   'project_update',
                                   'project_transfer',
                                   'tag_push',
                                   'repository_update',
                                   'push' ));

    ////////////////////////////////////////////////////////////////////////
    function put_status($file, $contents)
    {
        $fp = fopen($file, "c");
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, $contents);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    ////////////////////////////////////////////////////////////////////////
    function rmdir_r($path)
    {
        if (!file_exists($path)) {
            return true;
        }
        if (!is_dir($path)) {
            unlink($path);
            return true;
        }

        $fp = opendir($path);
        if ($fp !== false) {
            while (($file = readdir($fp)) !== false) {
                if ($file == "." || $file == "..") {
                    continue;
                }
                if (is_dir("$path/$file")) {
                    rmdir_r("$path/$file");
                } else {
                    unlink("$path/$file");
                }
            }
            closedir($fp);
        }
        rmdir($path);
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function get_status($file)
    {
        $fp = @fopen($file, "r");
        if (!$fp) {
            return false;
        }
        flock($fp, LOCK_SH);
        $contents = fread($fp, filesize($file));
        flock($fp, LOCK_UN);
        fclose($fp);
        $stat = json_decode($contents, true);
        return $stat;
    }

    ////////////////////////////////////////////////////////////////////////
    function write_log()
    {
        $num = func_num_args();
        if ($num < 1) {
            return false;
        }
        $fname = func_get_arg(0);
        $msg = "";
        for ($ct = 1; $ct < $num; $ct ++) {
            $arg = func_get_arg($ct);
            if (!is_array($arg)) {
                $msg .= $arg;
            } else {
                $msg .= var_export($arg, true) . "\n";
            }
        }
        $logpath = LOG_PATH;
        if (!is_dir($logpath)) {
            mkdir($logpath, 0777);
        }
        list($msec, $sec) = explode(" ", microtime());
        $time = "[" . strftime("%Y/%m/%d %H:%M:%S", $sec) . substr($msec, 1, 4) . "] ";
        error_log($time . "$msg\n", 3, $logpath . $fname);
    }
