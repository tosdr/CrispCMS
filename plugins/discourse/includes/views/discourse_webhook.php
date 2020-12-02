<?php

/** @var crisp\core\Plugin $this */
header("Content-Type: application/json");

$EnvFile = parse_ini_file(__DIR__ . "/../../.env");
$Error = false;

if (empty($EnvFile["DISCOURSE_WEBHOOK_SECRET"])) {
    $Error[] = "DISCOURSE_WEBHOOK_SECRET is not set in .env file";
}

if (empty($EnvFile["DISCOURSE_HOSTNAME"])) {
    $Error[] = "DISCOURSE_HOSTNAME is not set in .env file";
}

if (empty($EnvFile["DISCOURSE_API_KEY"])) {
    $Error[] = "DISCOURSE_API_KEY is not set in .env file";
}


if (array_key_exists('HTTP_X_DISCOURSE_EVENT_SIGNATURE', $_SERVER) && $_SERVER["HTTP_X_DISCOURSE_EVENT"] == "post_created" && !$Error) {
    $PayLoadRaw = file_get_contents('php://input');
    $PayLoadHash = substr($_SERVER['HTTP_X_DISCOURSE_EVENT_SIGNATURE'], 7);
    $PayLoad = json_decode($PayLoadRaw);

    $Regex = "/^(?=.*has been added)|(?=.*i have added)|(?=.*added to)|(?=.*is up)|(?=.*for review)/i";

    if (hash_hmac('sha256', $PayLoadRaw, $EnvFile["DISCOURSE_WEBHOOK_SECRET"]) == $PayLoadHash) {
        if (preg_match_all($Regex, $PayLoad->post->raw) > 0 && ($PayLoad->post->primary_group_name == "Team" || $PayLoad->post->primary_group_name == "curators")) {

            $responses = [];

            $Discourse = new \pnoeric\DiscourseAPI($EnvFile["DISCOURSE_HOSTNAME"], $EnvFile["DISCOURSE_API_KEY"]);

            $Discourse->setDebugPutPostRequest(true);

            $responses[] = $Discourse->closeTopic($PayLoad->post->topic_id);
            $responses[] = $Discourse->addTagsToTopic(["service-added"], "-/" . $PayLoad->post->topic_id);




            $Response = (array("error" => $Error, "message" => "Match!", "responses" => $responses));
        } else {

            if ($PayLoad->post->post_number == 1) {
                $responses = [];

                $Discourse = new \pnoeric\DiscourseAPI($EnvFile["DISCOURSE_HOSTNAME"], $EnvFile["DISCOURSE_API_KEY"]);

                $responses[] = $Discourse->createPost("Hello!\nThanks for contributing to ToS;DR!\n\nA curator will soon add your service to our database.", $PayLoad->post->topic_id, "system", new DateTime());
                $Response = (array("error" => $Error, "message" => "Success!", "post" => $PayLoad->post->raw, "responses" => $responses));
            } else {
                $Response = (array("error" => $Error, "message" => "No match!", "post" => $PayLoad->post->raw, "regex" => preg_match_all($Regex, $PayLoad->post->raw)));
            }
        }
    } else {
        $Error[] = "Failed to verify payload";
        $Response = (array("error" => $Error, "message" => "Failed to authenticate webhook request!"));
    }
} else {
    if(!array_key_exists('HTTP_X_DISCOURSE_EVENT_SIGNATURE', $_SERVER)){
        $Error[] = "HTTP_X_DISCOURSE_EVENT_SIGNATURE missing";
    }
    if(!$_SERVER["HTTP_X_DISCOURSE_EVENT"] == "post_created"){
        $Error[] = "HTTP_X_DISCOURSE_EVENT is not post_created";
    }
    $Response = (array("error" => $Error, "message" => "Not a valid webhook request!"));
}


$_vars = array("response" => $Response);
