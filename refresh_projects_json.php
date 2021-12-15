<?php
    require_once("function.inc");

    write_log("refresh_projects_json.log", "Start");
    $config = json_decode(file_get_contents("./config.json"), true);

    $headers = array(
        "private-token: " . ACCESS_TOKEN
    );

    // get projects info
    $ret = request_projects($config["url"], $headers);
    if ($ret === false) {
        write_log("refresh_projects_json.log", "End");
        exit;
    }

    $projects_arr = $ret;

    // exclude internal project
    for ($n = 0; $n < count($projects_arr); $n ++) {
        if ($projects_arr[$n]["visibility"]=="internal") {
            unset($projects_arr[$n]);
            $projects_arr = array_values($projects_arr);
        }
    }

    // get branches info
    $ret = request_branches($projects_arr, $config["url"], $headers);
    if ($ret === false) {
        write_log("refresh_projects_json.log", "End");
        exit;
    }

    // get tags info
    $ret = request_tags($projects_arr, $config["url"], $headers);
    if ($ret === false) {
        write_log("refresh_projects_json.log", "End");
        exit;
    }

    // get commits info
    $ret = request_commits($projects_arr, $config["url"], $headers);
    if ($ret === false) {
        write_log("refresh_projects_json.log", "End");
        exit;
    }

    write_json($projects_arr);

    write_log("refresh_projects_json.log", "End");

    ////////////////////////////////////////////////////////////////////////
    function request_projects($gitlab_url, $headers)
    {
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
                    write_log("refresh_projects_json.log", "request_projects : Error Unknown host");
                } else {
                    write_log("refresh_projects_json.log", "request_projects : Error Code : " . $rcode);
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

        return($projects);
    }

    ////////////////////////////////////////////////////////////////////////
    function request_branches(&$projects_arr, $gitlab_url, $headers)
    {
        foreach ($projects_arr as &$project) {
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
                if ($rcode != 200) {
                    if ($rcode == 0) {
                        write_log("refresh_projects_json.log", "request_branches : Error Unknown host");
                    } else {
                        write_log("refresh_projects_json.log", "request_branches : Error Code : " . $rcode);
                    }
                    return false;
                }
                if ($response == "[]") {
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

            if ($response == "[]") {
                for ($n = 0; $n < count($projects_arr); $n ++) {
                    if ($projects_arr[$n]["path_with_namespace"]==$path_with_namespace) {
                        write_log("refresh_projects_json.log", "request_branches : Exclusion project = $path_with_namespace");
                        unset($projects_arr[$n]);
                        $projects_arr = array_values($projects_arr);
                    }
                }
            } else {
                $project["branch"] = $branches;
            }
        }
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function request_tags(&$projects_arr, $gitlab_url, $headers)
    {
        foreach ($projects_arr as &$project) {
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
                if ($rcode != 200) {
                    if ($rcode == 0) {
                        write_log("refresh_projects_json.log", "request_tags : Error Unknown host");
                    } else {
                        write_log("refresh_projects_json.log", "request_tags : Error Code : " . $rcode);
                    }
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

            $project["tag"] = $tags;
        }
        return true;
    }

    ////////////////////////////////////////////////////////////////////////
    function request_commits(&$projects_arr, $gitlab_url, $headers)
    {
        foreach ($projects_arr as &$project) {
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
                if ($rcode != 200) {
                    if ($rcode == 0) {
                        write_log("refresh_projects_json.log", "request_commits : Error Unknown host");
                    } else {
                        write_log("refresh_projects_json.log", "request_commits : Error Code : " . $rcode);
                    }
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

            $project["commits"] = array_reverse($commits);
        }
        return true;
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
            write_log("refresh_projects_json.log", "File Write OK");
        } else {
            write_log("refresh_projects_json.log", "File Write Open Error");
        }
    }
