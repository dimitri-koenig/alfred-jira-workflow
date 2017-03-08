# Jira Search Suggest Workflow for Alfred 3

Uses Jira's REST Api for issue searching, and your local mac keychain for credentials

## Installation

1. [Download the master branch as zip file](https://github.com/dimitri-koenig/alfred-jira-workflow/archive/master.zip) and double click on the `jira.search.suggest` workflow package file

2. Configure your ENV Variables

3. Ready

## Usage and available filters

Using `js` you can trigger this workflow. Then you get two available filters.

As a third parameter you can specify a project token to filter your results. E.g.:

`js my-open-issues MYPROJECT`

### my-open-issues

If you just enter that you get a list of all your open issues. If you enter more text the whole search will be filtered for that text.

### recently-viewed

If you just enter that you get a list of all your recently viewed issues. If you enter more text the whole search will be filtered for that text.

## Modify filters

Within `filters.php` you can modify, add or remove filters. It's not linked to your saved filters on your jira host.