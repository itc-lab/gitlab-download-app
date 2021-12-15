<?php
    require_once("function.inc");

    write_log("update_projects_json.log", "Start");

    $header = var_export(getallheaders(), true);
    write_log("update_projects_json.log", "main :header  = " . var_export($header, true));
    $body = json_decode(file_get_contents("php://input"), true);
    write_log("update_projects_json.log", "main :body    = " . var_export($body, true));

    $tabet_event = false;
    foreach (TARGET_EVENTS as $event) {
        if ($event == $body["event_name"]) {
            $tabet_event = true;
            break;
        }
    }
    if ($tabet_event == false) {
        write_log("update_projects_json.log", "main : Not Target Event =  " . $body["event_name"]);
        write_log("update_projects_json.log", "End");
        exit;
    } else {
        write_log("update_projects_json.log", "main : Target Event =  " . $body["event_name"]);
    }

    $config = json_decode(file_get_contents("./config.json"), true);

    $headers = array(
        "private-token: " . ACCESS_TOKEN
    );

    if (!file_exists(PROJECTS_FILE)) {
        $ret = create_projects_json($config, $headers);
        if ($ret == false) {
            write_log("update_projects_json.log", "main : call create_projects_json error");
            exit;
        }
    } else {
        if (isset($body["project"]["path_with_namespace"])) {
            $path_with_namespace = $body["project"]["path_with_namespace"];
        } elseif (isset($body["path_with_namespace"])) {
            $path_with_namespace = $body["path_with_namespace"];
        } else {
            write_log("update_projects_json.log", "main : Key Search error");
            exit;
        }

        if ($body["event_name"] == "project_destroy") {
            $ret = delete_projects_json($path_with_namespace);
            if ($ret == false) {
                write_log("update_projects_json.log", "main : call delete_projects_json error");
                exit;
            }
        } elseif ($body["event_name"] == "project_transfer" ||
                  $body["event_name"] == "project_rename") {
            $ret = delete_projects_json($body["old_path_with_namespace"]);
            if ($ret == false) {
                write_log("update_projects_json.log", "main : call delete_projects_json error");
                exit;
            }
            $ret = update_projects_json($path_with_namespace, $config, $headers);
            if ($ret == false) {
                write_log("update_projects_json.log", "main : call update_projects_json error");
                exit;
            }
        } else {
            $ret = update_projects_json($path_with_namespace, $config, $headers);
            if ($ret == false) {
                write_log("update_projects_json.log", "main : call update_projects_json error");
                exit;
            }
        }
    }

    write_log("update_projects_json.log", "End");
    exit;

    ////////////////////////////////////////////////////////////////////////
    function create_projects_json($config, $headers)
    {
        write_log("update_projects_json.log", "create_projects_json : start");
        // get projects info
        $ret = request_projects($config["url"], $headers);
        if ($ret === false) {
            write_log("update_projects_json.log", "create_projects_json : call request_projects error");
            return false;
        }

        $projects_arr = $ret;

        // exclude internal project
        for ($n = 0; $n < count($projects_arr); $n ++) {
            if ($projects_arr[$n]["visibility"]=="internal") {
                unset($projects_arr[$n]);
                $projects_arr = array_values($projects_arr);
            }
        }

        foreach ($projects_arr as &$project) {
            // get branches info
            $ret = request_branch($project, $config["url"], $headers);
            if ($ret === false) {
                write_log("update_projects_json.log", "create_projects_json : call request_branch error");
                return false;
            }

            if ($ret == "[]") {
                for ($n = 0; $n < count($projects_arr); $n ++) {
                    if ($projects_arr[$n]["path_with_namespace"]==$project["path_with_namespace"]) {
                        write_log("update_projects_json.log", "create_projects_json : Exclusion project = " . $project["path_with_namespace"]);
                        unset($projects_arr[$n]);
                        $projects_arr = array_values($projects_arr);
                        break;
                    }
                }
            }

            $project["branch"] = $ret;

            // get tags info
            $ret = request_tag($project, $config["url"], $headers);
            if ($ret === false) {
                write_log("update_projects_json.log", "create_projects_json : call request_tag error");
                return false;
            }

            $project["tag"] = $ret;

            // get commits info
            $ret = request_commit($project, $config["url"], $headers);
            if ($ret === false) {
                write_log("update_projects_json.log", "create_projects_json : call request_commit error");
                return false;
            }

            $project["commits"] = array_reverse($ret);
        }

        $ret = write_json($projects_arr);
        if ($ret === false) {
            write_log("update_projects_json.log", "create_projects_json : call write_json error");
            return false;
        }

        write_log("update_projects_json.log", "create_projects_json : end");
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function update_projects_json($path_with_namespace, $config, $headers)
    {
        write_log("update_projects_json.log", "update_projects_json : start[project=" . $path_with_namespace . "]");

        $url = $config["url"] . 'api/v4/projects/' . urlencode($path_with_namespace) ;

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($rcode == 0) {
            write_log("update_projects_json.log", "update_projects_json : Error Unknown host");
            return false;
        }
        if ($rcode >= 400) {
            write_log("update_projects_json.log", "update_projects_json : Error Code : " . $rcode);
            return false;
        }

        $new_project_arr = json_decode($response, true);

        // get branches info
        $ret = request_branch($new_project_arr, $config["url"], $headers);
        if ($ret === false) {
            write_log("update_projects_json.log", "update_projects_json : call request_branch error");
            return false;
        }

        if ($ret == "[]") {
            for ($n = 0; $n < count($projects_arr); $n ++) {
                if ($projects_arr[$n]["path_with_namespace"]==$project["path_with_namespace"]) {
                    write_log("update_projects_json.log", "create_projects_json : Exclusion project = " . $project["path_with_namespace"]);
                    unset($projects_arr[$n]);
                    $projects_arr = array_values($projects_arr);
                    break;
                }
            }
        }

        $new_project_arr["branch"] = $ret;

        // get tags info
        $ret = request_tag($new_project_arr, $config["url"], $headers);
        if ($ret === false) {
            write_log("update_projects_json.log", "update_projects_json : call request_tag error");
            return false;
        }

        $new_project_arr["tag"] = $ret;

        // get commits info
        $ret = request_commit($new_project_arr, $config["url"], $headers);
        if ($ret === false) {
            write_log("update_projects_json.log", "update_projects_json : call request_commit error");
            return false;
        }

        $new_project_arr["commits"] = array_reverse($ret);

        $fp = @fopen(PROJECTS_FILE, "r");
        if ($fp) {
            flock($fp, LOCK_SH);
            $projects_json = fread($fp, filesize(PROJECTS_FILE));
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            write_log("update_projects_json.log", "update_projects_json : File Read Open error");
            return false;
        }

        $projects_arr = json_decode($projects_json, true);

        $update_flag = false;
        foreach ($projects_arr as &$project_arr) {
            if ($project_arr["path_with_namespace"] == $new_project_arr["path_with_namespace"]) {
                $project_arr = $new_project_arr;
                $update_flag = true;
                break;
            }
        }

        if ($update_flag == false) {
            $projects_arr[] = $new_project_arr;
        }

        $ret = write_json($projects_arr);
        if ($ret == false) {
            write_log("update_projects_json.log", "update_projects_json : call write_json error");
            return false;
        }

        write_log("update_projects_json.log", "update_projects_json : end");
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function delete_projects_json($path_with_namespace)
    {
        write_log("update_projects_json.log", "delete_projects_json : start[project=" . $path_with_namespace . "]");

        $fp = @fopen(PROJECTS_FILE, "r");
        if ($fp) {
            flock($fp, LOCK_SH);
            $projects_json = fread($fp, filesize(PROJECTS_FILE));
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            write_log("update_projects_json.log", "delete_projects_json : File Read Open error");
            return false;
        }

        $projects_arr = json_decode($projects_json, true);

        for ($n = 0; $n < count($projects_arr); $n ++) {
            if ($projects_arr[$n]["path_with_namespace"] == $path_with_namespace) {
                unset($projects_arr[$n]);
                $projects_arr = array_values($projects_arr);
                break;
            }
        }

        $ret = write_json($projects_arr);
        if ($ret == false) {
            write_log("update_projects_json.log", "delete_projects_json : call write_json error");
            return false;
        }

        write_log("update_projects_json.log", "delete_projects_json : end");
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function request_projects($gitlab_url, $headers)
    {
        write_log("update_projects_json.log", "request_projects : start");

        $projects = array();
        $url = $gitlab_url . 'api/v4/projects/';

        for ($n = 1; ; $n ++) {
            $params = [
                'order_by' => 'name',
                'simple' => 'false',
                'sort' => 'asc',
                'd_after' => 0,
                'per_page' => PER_PAGE,
                'page' => $n
            ];

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($rcode != 200) {
                if ($rcode == 0) {
                    write_log("update_projects_json.log", "request_projects : Error Unknown host");
                } else {
                    write_log("update_projects_json.log", "request_projects : Error Code : " . $rcode);
                }
                return false;
            }
            $projects_arr = json_decode($response, true);
            if (0 < count($projects_arr)) {
                $projects = array_merge_recursive($projects, $projects_arr);
            }
            if (count($projects_arr) < PER_PAGE) {
                break;
            }
        }

        write_log("update_projects_json.log", "request_projects : end");
        return($projects);
    }

    ////////////////////////////////////////////////////////////////////////
    function request_branch(&$project, $gitlab_url, $headers)
    {
        write_log("update_projects_json.log", "request_branch : start[project=" . $project["path_with_namespace"] . "]");

        $branches = array();
        $path_with_namespace = $project["path_with_namespace"];
        $url = $gitlab_url . 'api/v4/projects/' . urlencode($path_with_namespace) . '/repository/branches';

        for ($n = 1; ; $n ++) {
            $params = [
                'per_page' => PER_PAGE,
                'page' => $n
            ];

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($rcode == 0) {
                write_log("update_projects_json.log", "request_branch : Error Unknown host");
                return false;
            }
            if ($rcode >= 400) {
                write_log("update_projects_json.log", "request_branch : Error Code : " . $rcode);
                return false;
            }
            if ($response == "[]") {
                $branches = $response;
                break;
            }
            $branches_arr = json_decode($response, true);
            if (0 < count($branches_arr)) {
                $branches = array_merge_recursive($branches, $branches_arr);
            }
            if (count($branches_arr) < PER_PAGE) {
                break;
            }
        }

        write_log("update_projects_json.log", "request_branch : end");
        return $branches;
    }

    ////////////////////////////////////////////////////////////////////////
    function request_tag(&$project, $gitlab_url, $headers)
    {
        write_log("update_projects_json.log", "request_tag : start[project=" . $project["path_with_namespace"] . "]");

        $tags = array();
        $path_with_namespace = $project["path_with_namespace"];
        $url = $gitlab_url . 'api/v4/projects/' . urlencode($path_with_namespace) . '/repository/tags';

        for ($n = 1; ; $n ++) {
            $params = [
                'per_page' => PER_PAGE,
                'page' => $n
            ];

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($rcode == 0) {
                write_log("update_projects_json.log", "request_tag : Error Unknown host");
                return false;
            }
            if ($rcode >= 400) {
                write_log("update_projects_json.log", "request_tag : Error Code : " . $rcode);
                return false;
            }
            $tags_arr = json_decode($response, true);
            if (0 < count($tags_arr)) {
                $tags = array_merge_recursive($tags, $tags_arr);
            }
            if (count($tags_arr) < PER_PAGE) {
                break;
            }
        }

        write_log("update_projects_json.log", "request_tag : end");
        return $tags;
    }

    ////////////////////////////////////////////////////////////////////////
    function request_commit(&$project, $gitlab_url, $headers)
    {
        write_log("update_projects_json.log", "request_commit : start[project=" . $project["path_with_namespace"] . "]");

        $commits = array();
        $path_with_namespace = $project["path_with_namespace"];
        $url = $gitlab_url . 'api/v4/projects/' . urlencode($path_with_namespace) . '/repository/commits';

        for ($n = 1; ; $n ++) {
            $params = [
                'per_page' => PER_PAGE,
                'page' => $n,
                'all' => 'true'
            ];

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curl);
            $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($rcode == 0) {
                write_log("update_projects_json.log", "request_commit : Error Unknown host");
                return false;
            }
            if ($rcode >= 400) {
                write_log("update_projects_json.log", "request_commit : Error Code : " . $rcode);
                return false;
            }
            $commits_arr = json_decode($response, true);
            if (0 < count($commits_arr)) {
                $commits = array_merge_recursive($commits_arr, $commits);
            }
            if (count($commits_arr) < PER_PAGE) {
                break;
            }
        }

        write_log("update_projects_json.log", "request_commit : end");
        return $commits;
    }

    ////////////////////////////////////////////////////////////////////////
    function write_json(&$projects_arr)
    {
        $id = array_column($projects_arr, 'id');
        $name  = array_column($projects_arr, 'name');

        $name_low = array_map('strtolower', $name);

        array_multisort($name_low, SORT_ASC, SORT_FLAG_CASE, $id, SORT_ASC, SORT_NUMERIC, $projects_arr);

        $projects = json_encode($projects_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $fp = fopen(PROJECTS_FILE, "c");
        if ($fp) {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            fwrite($fp, $projects);
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            write_log("update_projects_json.log", "write_json : File Write OK");
        } else {
            write_log("update_projects_json.log", "write_json : File Write Open error");
            return false;
        }
        write_log("update_projects_json.log", "write_json : end");
        return true;
    }
