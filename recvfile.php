    define("DIFF_SWITCH_FILE", "-durtN --tabsize=4");
    define("DIFF_SWITCH_FOLDER", "-durtq");

        $cmd = "cd ${tmp}${download_file_name} && tar czf ../{$target} * --owner=${config["user"]} --group=${config["group"]} --hard-dereference";
        exec($cmd, $so, $rt);
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
    rmdir_r("{$tmp}{$download_file_name}");