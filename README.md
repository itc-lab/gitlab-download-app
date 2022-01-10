# GitLab Download App

Batch download web app using GitLab API powered by PHP

| ![GitLab Download App Demo](https://user-images.githubusercontent.com/76575923/148775077-a95f4382-750a-4a67-a075-794a786ddf27.gif) |
| :--------------------------------------------------------------------------------------------------------------------------------: |

## Features

- List all branches of a group / project, tag name, and commit timestamp
- Download past commits
- Batch download as .tar.gz or .zip
- Download only the difference from the previous commit
- Download select tag
- Commit messages can be displayed quickly
- Download the archive file by specifying the owner:group (in the case of .tar.gz)
- Archive file name can be specified
- Quick source code download with cache
- Sign out from GitLab

## User rights control

- User rights inherited from GitLab with OpenID Connect authentication

## Requirements

- GitLab
- PHP

_**Warning:** Tested only under a locally installed GitLab CE, PHP8 on Ubuntu 20.04.2 LTS._

## Deployment

Example for a deployment on the same server as GitLab is in [DEPLOY.md](DEPLOY.md).

## Getting started

### Get access token

`Preferences -> Access Tokens`  
**Name:** GitLab-Download-App  
**Expired at:** Blank (indefinite)  
**Scopes:** read_api  
| ![Access Tokens](https://user-images.githubusercontent.com/76575923/148753901-ed3e8fe3-0080-4ef2-847f-2bc5ec4e04de.png) |
| :-------------------------------------------------------------------------------------------------------------------: |

### Define access token

Assume that you have deployed GitLab-Download-App directly under /opt/gitlab-download-app/www/html/ and access token is "ADbHuxHKc2teKxyyJBNy".

```
# vi /opt/gitlab-download-app/www/html/function.inc
```

```
define("ACCESS_TOKEN", "ADbHuxHKc2teKxyyJBNy");
```

### Change config.json

```
# vi /opt/gitlab-download-app/www/html/config.json
```

```
{
  "url": <GitLab's URL>,
  "ignore_commit_message": <If the commit message contains this word, don't list it.>,
  "mark_commit_message": <If the commit message contains this word, a mark is displayed before the commit date and time.>,
  "ignore_file_name": <Do not include this file in the download target.>,
  "cache_projects": <If it is not 1, the cache is disabled.>,
  "max_message_length": <Maximum number of characters in commit message>,
  "group": <Permission group when archiving with .tar.gz>,
  "user": <Permission user when archiving with .tar.gz>,
  "session_cookie_name": <SSO Session Cookie Name>",
  "default_download_name_maxlength": <Maximum length of download file name for .zip/.tar.gz>,
  "default_download_name": <Download file name for .zip/.tar.gz>
}
```

### Register System Hook

`Admin Area -> System Hooks`  
**URL:** https://gitlab-download-app.itccorporation.jp/update_projects_json.php  
**Secret token:** ADbHuxHKc2teKxyyJBNy  
**Trigger:** Push events, Tag push events
| ![System Hooks](https://user-images.githubusercontent.com/76575923/148754112-6df3e076-ca1c-4915-b00c-f36e377c4f57.png) |
| :-------------------------------------------------------------------------------------------------------------------: |

### Register crontab

```
# crontab -u gitlab-www -e
```

In the following configuration, the user running the web app is gitlab-www, and the project list will be updated every hour.

```
0 * * * * cd /opt/gitlab-download-app/www/html && /usr/bin/php refresh_projects_json.php
```

## Technologies

- [php](https://www.php.net/)
- [jQuery](https://jquery.com/)
- [SlickGrid - jQuery plugin](https://slickgrid.net/)
- [blockUI - jQuery plugin](https://malsup.com/jquery/block/)

## License

MIT
