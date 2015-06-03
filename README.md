# Jira Search Suggest Workflow for Alfred 2

Uses Jira's REST Api for issue searching

## Installation

1. Clone this repo into your Alfred workflows directory `~/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows/`

2. Go into the cloned repo directory and copy the `config.example.php` to `config.php`

3. Insert your jira credentials into that `config.php` file

4. Ready

## Usage and available filters

Using `js` you can trigger this workflow. Then you get two available filters.

### my-open-issues

If you just enter that you get a list of all your open issues. If you enter more text the whole search will be filtered for that text.

### recently-viewed

If you just enter that you get a list of all your recently viewed issues. If you enter more text the whole search will be filtered for that text.

## TODOS

* Make filteres configurable
* Add project avatars as search result item icons