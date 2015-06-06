<?php

function getCredentialsFromLocalKeychain()
{
	$keychainData = shell_exec('security find-internet-password -j "' . $_ENV['alfred_workflow_bundleid'] . '" -g 2>&1; echo $?');

	$protocol = findValue('/"ptcl".*"([^"]+)"\W*/Uis', $keychainData);
	$server = findValue('/"srvr".*"([^"]+)"\W*/Uis', $keychainData);

	$config = [
		'hostUrl'  => ($protocol === 'htps' ? 'https://' : 'http://') . $server,
		'username' => findValue('/"acct".*"([^"]+)"\W*/Uis', $keychainData),
		'password' => findValue('/password:\W*"([^"]+)"/', $keychainData)
	];

	return $config;
}

function findValue($pattern, $haystack)
{
	$matches = [];
	preg_match($pattern, $haystack, $matches);

	return count($matches) ? trim(array_pop($matches)) : null;
}

function downloadProjectAvatar($project)
{
	if (empty($project->id))
	{
		return '';
	}

	$filename = $GLOBALS['wf']->cache() . '/project-avatar-' . $project->id . '.png';

	if (!file_exists($filename) && !empty($project->avatarUrls->{'48x48'}))
	{
		$response = $GLOBALS['wf']->request($project->avatarUrls->{'48x48'}, $GLOBALS['options']);

		if ($response)
		{
			file_put_contents($filename, $response);
		}
	}

	return $filename;
}
