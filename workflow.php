<?php

require_once('workflows-library.php');
require_once('helper-functions.php');

$wf = new Workflows();

$config = (require_once 'config.php');

if ($config['useLocalKeychain']) {
    $config = array_merge($config, getCredentialsFromLocalKeychain());
}

if (empty($config['username']) || empty($config['password'])) {
    $wf->result('jira-auth-error', '', 'Auth config incomplete', '', 'icon.png');
    echo $wf->toxml();
    die();
}

$options = array(
    CURLOPT_USERPWD => $config['username'] . ':' . $config['password']
);

// $input is given
$inputParts = explode(' ', $input);
$possibleFilter = $inputParts[0];
$selectedFilter = false;

$availableFilters = (require_once 'filters.php');

foreach ($availableFilters as $filter) {
    if ($possibleFilter === $filter['key']) {
        $selectedFilter = $filter;
        break;
    }
}

if ($selectedFilter === false) {
    if (!empty($possibleFilter)) {
        usort($availableFilters, function ($filterA, $filterB) use ($possibleFilter) {
            $similarToA = similar_text($filterA['key'], $possibleFilter);
            $similarToB = similar_text($filterB['key'], $possibleFilter);

            if ($similarToA === $similarToB) {
                return 0;
            }

            return ($similarToA > $similarToB) ? -1 : 1;
        });
    }

    foreach ($availableFilters as $filter) {
        $wf->result($filter['key'], $input, $filter['title'], '', 'icon.png', 'no', $filter['key']);
    }
} else {
    array_shift($inputParts);
    $searchWords = trim(implode(' ', $inputParts));
    $filter = '';

    if (!empty($searchWords)) {
        $filter .= 'text ~ "' . $searchWords . '" AND ';
    }

    $filter .= $selectedFilter['jql'];

    try {
        $response = $wf->request($config['hostUrl'] . '/rest/api/latest/search?maxResults=20&fields=id,key,summary,description,project&jql=' . urlencode($filter), $options);
        $jsonResponse = json_decode($response);

        if (isset($jsonResponse->errorMessages)) {
            foreach ($jsonResponse->errorMessages as $errorMessage) {
                $wf->result('jira-response-error', $input, 'Error message', $errorMessage, 'icon.png');
            }
        }

        if ($jsonResponse->total === 0) {
            $wf->result('jira-no-results', $input, 'No Suggestions', 'No search suggestions for "' . $searchWords . '" found', 'icon.png');
        }

        if ($jsonResponse->total > 0) {
            foreach ($jsonResponse->issues as $issue) {
                $avatarFilename = downloadProjectAvatar($issue->fields->project);

                $wf->result(microtime(true), $config['hostUrl'] . '/browse/' . $issue->key, sprintf('[%s] %s', $issue->key, strip_tags($issue->fields->summary)), strip_tags($issue->fields->description), $avatarFilename);
            }
        }
    } catch (Exception $e) {
        $wf->result('jira-request-error', $input, 'Search Request Error', $e->getMessage(), 'icon.png');
    }
}

echo $wf->toxml();
