<?php

include "standart.php";

$JUDEX_HOME = getenv('JUDEX_HOME');

function upload($submission_id, $language) {
    global $JUDEX_HOME;
    $submission_dir = $JUDEX_HOME . '/' . "Submissions" . '/' . "$submission_id";
    mkdir($submission_dir, 0777, TRUE);	

    $output_dir = $submission_dir . '/' . "output";
    mkdir($output_dir, 0777, TRUE);

    $result_file = $submission_dir . '/' . "result.json";
    $submission_result = array();
    $submission_result["status"] = "IQ";
    $fp = fopen($submission_result, 'w');
    fwrite($fp, '{"status": "IQ"}');
    fclose($fp);
    chmod($submission_dir, 0777);
    chmod($result_file, 0777);
    $lang_conf = parse_ini_file($JUDEX_HOME."/conf.d/language.conf", true);
    $uploaded = move_uploaded_file($_FILES['uploading_file']['tmp_name'], $submission_dir . '/' . "$submission_id" . $lang_conf[$language]["extension"]);

    chmod($submission_dir . '/' . "$submission_id" . $lang_conf[$language]["extension"], 0777);
    return $uploaded;
}

function getCurrentUserId($link, $token){
    $query = "select user_id from auth where token = '$token'";
    $result = mysqli_query($link, $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row['user_id'];
}


function submit() {
    global $path_to_judge_root;
    global $link;

    if(!isset($_POST)) {
        die("Nothing in POST query");
    }

    $user_id = getCurrentUserId($link, $_COOKIE["token"]);

    echo "userId = $user_id";
    $problem_id = $_POST["problem_id"];
    $language = $_POST["language"];

    $sql = "INSERT INTO submissions (problem_id, user_id, time, language) VALUES ($problem_id, $user_id, now(), '$language');";
    $inserted = mysqli_query($link, $sql);
    if(!$inserted) {
        mysqli_error($link);
    	die("Error while inserting into submissions");
    }

    $sql = "SELECT LAST_INSERT_ID()";
    $submission_id = mysqli_fetch_array(mysqli_query($link, $sql))[0];
    if(!$submission_id) {
    	die("Error while getting submission_id");
    }

    if(!upload($submission_id, $language)) {
        die("Error while uploading file");
    }

    $sql = "INSERT INTO testing_queue (id, problem_id, language) VALUES ($submission_id, $problem_id, '$language')";
    $inserted = mysqli_query($link, $sql);
    if(!$inserted) {
        die(mysqli_error($link));
    } 

    setcookie("submission_id", $submission_id, time() + 100);
    header("Location: /task.php?id=$problem_id");
}

submit();

?>