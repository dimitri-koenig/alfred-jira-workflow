# Jira Search Suggest Workflow for Alfred 2

Uses Jira's REST Api for issue searching, and your local mac keychain for credentials

## Installation

1. Clone this repo into your Alfred workflows directory `~/Library/Application Support/Alfred 2/Alfred.alfredpreferences/workflows/`

2. Go into your mac keychain program, and either search for your jira safari login data, or create such an item (more later)

3. Add `com.alfredapp.dimitrikoenig.jirasuggest` to the comment field

4. Ready

## Usage and available filters

Using `js` you can trigger this workflow. Then you get two available filters.

### my-open-issues

If you just enter that you get a list of all your open issues. If you enter more text the whole search will be filtered for that text.

### recently-viewed

If you just enter that you get a list of all your recently viewed issues. If you enter more text the whole search will be filtered for that text.

## Modify filters

Within `filters.php` you can modify, add or remove filters. It's not linked to your saved filters on your jira host.

## TODOS

* Refactoring
* Explain keychain usage and alternative