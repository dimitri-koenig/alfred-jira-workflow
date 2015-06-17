<?php

return array(
    array(
        'title' => 'My open issues',
        'key'   => 'my-open-issues',
        'jql'   => 'assignee = currentUser() AND resolution = Unresolved ORDER BY updatedDate DESC'
    ),
    array(
        'title' => 'My recently viewed issues',
        'key'   => 'recently-viewed',
        'jql'   => 'issuekey in issueHistory() ORDER BY lastViewed DESC'
    )
);
