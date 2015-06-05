<?php

require_once('workflows-library.php');

$config = (require_once 'config.php');

if ($config['useLocalKeychain']) {
	exec('security find-internet-password -j "' . $_ENV['alfred_workflow_bundleid'] . '" -g 2>&1; echo $?', $keychainData);

	$protocol = '';
	$server = '';
	foreach ($keychainData as $singleLine) {
		if (stripos($singleLine, '"acct"') !== FALSE) {
			$config['username'] = preg_replace('/^.*"([^"]+)"\w*$/', '$1', $singleLine);
			continue;
		}
		if (stripos($singleLine, 'password:') !== FALSE) {
			$config['password'] = preg_replace('/^.*"([^"]+)"\w*$/', '$1', $singleLine);
			continue;
		}
		if (stripos($singleLine, '"ptcl"') !== FALSE) {
			$protocol = preg_replace('/^.*"([^"]+)"\w*$/', '$1', $singleLine);
			continue;
		}
		if (stripos($singleLine, '"srvr"') !== FALSE) {
			$server = preg_replace('/^.*"([^"]+)"\w*$/', '$1', $singleLine);
			continue;
		}
	}
	$config['hostUrl'] = ($protocol === 'htps' ? 'https://' : 'http://') . $server;
}

$options = array(
	CURLOPT_USERPWD => $config['username'] . ':' . $config['password']
);

$wf = new Workflows();

// $input is given
$inputParts = explode(' ', $input);

if ($inputParts === FALSE) {
	$inputParts = array('');
}

$availableFilters = array(
	'my-open-issues',
	'recently-viewed'
);

$selectedFilterKey = array_search($inputParts[0], $availableFilters);

if ($selectedFilterKey === FALSE) {

	$wf->result('my-open-issues', $input, 'My open issues', '', 'icon.png', 'no', 'my-open-issues');
	$wf->result('recently-viewed', $input, 'Recently viewed issues', '', 'icon.png', 'no', 'recently-viewed');

} else {

	$selectedFilter = array_shift($inputParts);
	$searchWords = trim(implode(' ', $inputParts));
	$filter = '';

	if (!empty($searchWords)) {
		$filter .= 'text ~ "' . $searchWords . '" AND ';
	}

	if ($selectedFilter === 'my-open-issues') {
		$filter .= 'assignee = currentUser() AND resolution = Unresolved ORDER BY updatedDate DESC';
	}

	if ($selectedFilter === 'recently-viewed') {
		$filter .= 'issuekey in issueHistory() ORDER BY lastViewed DESC';
	}

	try {
		$response = $wf->request($config['hostUrl'] . '/rest/api/latest/search?maxResults=20&fields=id,key,summary,description,project&jql=' . urlencode($filter), $options);
		$jsonResponse = json_decode($response);

		if ($jsonResponse->errorMessages) {
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

				$wf->result($selectedFilter . $issue->id, $config['hostUrl'] . '/browse/' . $issue->key, strip_tags($issue->fields->summary), strip_tags($issue->fields->description), $avatarFilename);
			}
		}
	} catch (Exception $e) {
		$wf->result('jira-request-error', $input, 'Search Request Error', 'Error when searching for "' . $searchWords, 'icon.png');
	}
}

echo $wf->toxml();


function downloadProjectAvatar($project) {
	if (empty($project->id)) {
		return;
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

?>