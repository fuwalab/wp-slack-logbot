[![Build Status](https://travis-ci.com/fuwalab/wp-slack-logbot.svg?branch=master)](https://travis-ci.com/fuwalab/wp-slack-logbot)
[![Maintainability](https://api.codeclimate.com/v1/badges/6a452504c6604a59f7c4/maintainability)](https://codeclimate.com/github/fuwalab/wp-slack-logbot/maintainability)
[![GitHub license](https://img.shields.io/github/license/fuwalab/wp-slack-logbot.svg)](https://github.com/fuwalab/wp-slack-logbot/blob/master/LICENSE)
# wp-slack-logbot

## Descriptions
- Contributors: [ryotsun](https://profiles.wordpress.org/ryotsun)
- Tags: WordPress, Slack, logs
- Requires at least: 4.9
- Tested up to: 5.1-alpha-20181015.143023
- Requires PHP: 5.3
- Stable tag: 1.0.0
- License: GPLv2 or later

Stores all messages of particular channels on slack. And able to see them.

## Specifications
- Save all posts on slack to the database in WordPress
- It will be made blog posts by each channel per day
- Create categories by teams and channels automatically

## Installation

### Install Plugin
1. Upload this repository to `plugins` directory, or install from admin page.
1. Activate `WP Slack Logbot`

### Create Slack App 
In order to complete installation, need to create a slack-bot user and issue the `Bot User OAuth Access Token`.

1. Visit [SlackAPI](https://api.slack.com/apps) page and click `Create New App` button on the top right.
	![create new app1](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/1.png)
1. Fill-out the following fields, and click `Create App` button.
	- `App Name`
	- `Development Slack Workspace`

		![create new app2](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/2.png)
1. Add Bot User
	1. Click `Bot User` link on the left side menu.
	1. Fill-out the following fields, and click `Add Bot User`
		![add bot user](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/3.png)
		
1. Enable Event Subscriptions
	1. Click `Event Subscriptions` link on the left side menu.
	1. Turn `Enable Events` ON.
		![enable events](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/4.png)
		
	1. Put `Request URL` like following URL.
		- `https://your-domain.com/wp-json/wp-slack-logbot/events/`
		- Then, it would be verified.
	1. Set the following `Subscribe to Workspace Events`
		- `message.channels`
		- `message.groups`
	1. Set `Subscribe to Bot Events` like below
		- `message.channels`
		- `message.groups`
	1. Click `Save Changes`
	1. After saving completed, follow the direction
		![after saving completed](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/5.png)
		
1. `OAuth & Permissions` 
	1. Click `OAuth & Permissions` link on the left side menu.
		![insall app to workspace](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/6.png)
	
	1. Click `Install App to Workspace` button
	1. Then it will be shown `OAuth Access Token` and `Bot User OAuth Access Token`
		![generated token](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/7.png)
		
		- Copy `Bot User OAuth Access Token`
			- This time, only use `Bot User OAuth Access Token`
	
### Plugin Settings
1. Go to Plugin Setting page
	1. Paste `Bot User OAuth Access Token` which is copied earlier.
		![insall app to workspace](https://github.com/fuwalab/wp-slack-logbot/blob/images/readme_images/8.png)
	1. Then click `Save Changes` button

### Invitation to the channel
1. Invite `logbot` to the channels
	- Both are allowed to invite logbot to the public channels and private channels.
	
That's all for installation.
