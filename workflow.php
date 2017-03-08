<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');

require_once('workflows-library.php');
$wf = new Workflows();

if (empty($_ENV['hostUrl']) || empty($_ENV['username']) || empty($_ENV['password'])) {
    $wf->result('jira-auth-error', '', 'ENV Variables not filled', '', 'icon.png');
    echo $wf->toxml();
    die('');
}

$options = [
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_USERPWD => $_ENV['username'] . ':' . $_ENV['password']
];

function downloadProjectAvatar($project)
{
    if (empty($project->id)) {
        return '';
    }

    $filename = $GLOBALS['wf']->cache() . '/project-avatar-' . $project->id . '.png';

    if (!file_exists($filename) && !empty($project->avatarUrls->{'48x48'})) {
        $response = $GLOBALS['wf']->request($project->avatarUrls->{'48x48'}, $GLOBALS['options']);

        if ($response) {
            file_put_contents($filename, $response);
        }
    }

    return $filename;
}

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
    $projectCode = array_shift($inputParts);
    $textFilter = trim(implode(' ', $inputParts));
    $filter = '';

    if (!empty($projectCode)) {
        $filter .= 'project = "' . $projectCode . '" AND ';
    }

    if (!empty($textFilter)) {
        $filter .= 'text ~ "' . $textFilter . '" AND ';
    }

    $filter .= $selectedFilter['jql'];

    try {
        $response = $wf->request($_ENV['hostUrl'] . '/rest/api/latest/search?maxResults=50&fields=id,key,summary,description,project&jql=' . urlencode($filter), $options);
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

                $wf->result(microtime(true), $_ENV['hostUrl'] . '/browse/' . $issue->key, sprintf('[%s] %s', $issue->key, strip_tags($issue->fields->summary)), strip_tags($issue->fields->description), $avatarFilename);
            }
        }
    } catch (Exception $e) {
        $wf->result('jira-request-error', $input, 'Search Request Error', $e->getMessage(), 'icon.png');
    }
}

echo $wf->toxml();
