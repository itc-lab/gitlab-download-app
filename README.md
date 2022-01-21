# GitLab Download App

Batch download / Diff(Comparing changes) web app using GitLab API powered by PHP

| ![GitLab Download App Demo](https://user-images.githubusercontent.com/76575923/150256013-ef659c55-efa0-4588-a699-cd1b21f632ee.gif) |
| :--------------------------------------------------------------------------------------------------------------------------------: |

## Features

- List all branches of a group / project, tag name, and commit timestamp
- Quickly show Diff(Comparing changes)
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

_**Warning:** Tested only under a locally installed GitLab CE, PHP8 on Ubuntu 20.04.2 LTS / PHP8 and openresty installed on Docker._

## Usage

How to use is explained in the movie gif at [USAGE.md](USAGE.md).

## Deployment

Example for a deployment on the same server as GitLab is in [DEPLOY.md](DEPLOY.md).

### Docker

If you use Docker, you can get started quickly.
_**Warning:** GitLab must be up and running and ready in advance._

Install the SSL KEY and certificate.

```
./docker/openresty/certs/server.crt
./docker/openresty/certs/server.key
```

Change the host name to the host name of GitLab to be linked.

```
./.env
./config.json
./docker/openresty/conf.d/default.conf
```

Change the IP address to the IP address of GitLab to be linked.

```
./.env
```

Change appropriately. See "Getting started" below for more information.

```
./function.inc
./config.json
```

```
docker-compose up
```

or

```
docker-compose up -d
```

to start it.

Access `https://localhost/`.

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
  "session_cookie_name": <SSO Session Cookie Name>,
  "default_download_name_maxlength": <Maximum length of download file name for .zip/.tar.gz>,
  "default_download_name": <Download file name for .zip/.tar.gz>,
  "download_name_selected_commit": <When "Download with the latest default branch" is checked,
                                    or "Download with the previous commit" is checked,
                                    the selected commit will be downloaded to the "selected/" folder.>
  "download_name_latest_commit_of_main": <When "Download with the latest default branch" is checked,
                                          the latest default branch commit will be downloaded to the "latest_main/" folder.>
  "download_name_previous_commit": <When "Download with the previous commit" is checked,
                                    the previous commit will be downloaded to the "previous/" folder.>
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

refresh_projects_json.php refreshes the project list cache. This is not necessary if you use Docker.

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
- [diff2html](https://diff2html.xyz/)

## License

MIT
