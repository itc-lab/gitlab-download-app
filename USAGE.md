# Usage

## Sign in

Sign in with your GitLab account.
| ![Sign in](https://user-images.githubusercontent.com/76575923/150266414-2c4877bc-0e71-4c35-a608-c81f7664e5bb.gif) |
| :-------------------------------------------------------------------------------------------------------------: |

## Refresh project list

Forces the list to be up to date. If the WebHook is set up properly, it will be updated automatically, so this operation is not required.

| ![Refresh project list](https://user-images.githubusercontent.com/76575923/150266481-15f491ad-8742-4f45-aed5-0d37783e5ec9.gif) |
| :----------------------------------------------------------------------------------------------------------------------------: |

## Show commit message

When you hover over a commit, you'll see a commit message.

| ![Show commit message](https://user-images.githubusercontent.com/76575923/150266583-0de017a7-8206-4f74-b733-dd1679714165.gif) |
| :---------------------------------------------------------------------------------------------------------------------------: |

## Sort by group

You can sort by group name.

| ![Sort by group](https://user-images.githubusercontent.com/76575923/150267498-2b928796-3954-4774-986c-24cb7d8c6c03.gif) |
| :---------------------------------------------------------------------------------------------------------------------: |

## Sort by project

You can sort by project name.
| ![Sort by project](https://user-images.githubusercontent.com/76575923/150267026-45951aa1-d30e-4d13-b984-390fddd79e23.gif) |
| :------------------------------------------------------------------------------------------------------------------------: |

## Narrow down by group

You can select and narrow down the group.

| ![Narrow down by group](https://user-images.githubusercontent.com/76575923/150267180-9aade448-9258-48a1-81ed-859a75516da1.gif) |
| :----------------------------------------------------------------------------------------------------------------------------: |

## Switch to tag list

You can change from the branch / commit list to the tag list. If you select a tag, the corresponding commit date and time will be displayed.

| ![Switch to tag list](https://user-images.githubusercontent.com/76575923/150266762-e4b7436e-da09-4f79-b10b-93bd414ff1f0.gif) |
| :--------------------------------------------------------------------------------------------------------------------------: |

## Basic download

You can download all files of the selected commit as .tar.gz or .zip.  
The time stamps of the files in the archive are touched at each commit date and time.  
In the case of .tar.gz, the files in the archive are chowned according to the setting of config.json.  
If you press the button below the "All" button to download, only the committed files/folders will be downloaded.

| ![Basic download](https://user-images.githubusercontent.com/76575923/150267605-ae72e228-985f-4768-b49a-82f5a5f3d12b.gif) |
| :----------------------------------------------------------------------------------------------------------------------: |

## Download including project name

You can download it including the folder with the project name.  
e.g. `gitlab.zip/<project name>/<project root files/folders>...`

| ![Download including project name](https://user-images.githubusercontent.com/76575923/150268400-e8770bc7-5006-4e7e-b18b-32b5d51a6762.gif) |
| :---------------------------------------------------------------------------------------------------------------------------------------: |

## Specify download file name

You can change the download file name (_.tar.gz/_.zip).  
The default is gitlab.zip/gitlab.tar.gz.

| ![Specify download file name](https://user-images.githubusercontent.com/76575923/150268871-04cfaa64-93d8-4d3d-9782-19437860d160.gif) |
| :----------------------------------------------------------------------------------------------------------------------------------: |

## Download with the latest default branch

You can download it with the latest default branch (often it's "main" or "master").  
Projects with the latest default branch selected will not be downloaded.  
The selected commits will be downloaded to the "selected/" folder and the latest default branch will be downloaded to the "latest_main/" folder.

| ![Download with the latest default branch](https://user-images.githubusercontent.com/76575923/150269100-f7547d20-3f29-4811-9e71-d8b02be62eb7.gif) |
| :-----------------------------------------------------------------------------------------------------------------------------------------------: |

## Download with the previous commit

You can download it with the previous commit.  
The selected commits will be downloaded to the "selected/" folder and the previous commit will be downloaded to the "previous/" folder.

| ![Download with the previous commit](https://user-images.githubusercontent.com/76575923/150269210-fd77d96a-b7af-4d8a-816a-0b011e32d365.gif) |
| :-----------------------------------------------------------------------------------------------------------------------------------------: |

## Diff(Comparing changes)

You can see the latest default branch or the diff with the previous commit.  
This app uses the diff command without using the GitLab API.  
<strong>GitLab diff has a limit on the number of lines, but this screen has no limit.</strong>  
Instead, a large number of lines may delay the display.

| ![Diff(Comparing changes)](https://user-images.githubusercontent.com/76575923/150269297-b3d5d574-5f0e-4310-ba7c-d51c338c15e6.gif) |
| :-------------------------------------------------------------------------------------------------------------------------------: |

## Sign out

You can sign out from GitLab and GitLab-Download-App.

| ![Sign out](https://user-images.githubusercontent.com/76575923/150269351-c0862633-dca4-4b9c-9428-84925da226df.gif) |
| :----------------------------------------------------------------------------------------------------------------: |
