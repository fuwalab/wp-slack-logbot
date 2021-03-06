=== WP Slack Logbot ===
Contributors: ryotsun
Donate link:
Tags: Slack, log, post
Requires at least: 4.9
Tested up to: 5.1-alpha-20181015.143023
Requires PHP: 5.3
Stable tag: 1.6.0
License: GPLv2 or later
License URI: LICENSE

Stores all messages of particular channels on slack. And able to see them.

== Description ==
* Save all posts on slack to the database in WordPress
* It will be made blog posts by each channel per day
* Create categories by teams and channels automatically

== Installation ==
Follow the steps below.

= Install Plugin =
1. Upload this repository to `plugins` directory, or install from admin page.
1. Activate `WP Slack Logbot`

= Create Slack App =
In order to complete installation, need to create a slack-bot user and issue the `Bot User OAuth Access Token`.

1. Visit [SlackAPI](https://api.slack.com/apps) page and click `Create New App` button on the top right.
1. Fill-out the following fields, and click `Create App` button.
	- `App Name`
	- `Development Slack Workspace`

1. Add Bot User
	1. Click `Bot User` link on the left side menu.
	1. Fill-out the following fields, and click `Add Bot User`

1. Enable Event Subscriptions
	1. Click `Event Subscriptions` link on the left side menu.
	1. Turn `Enable Events` ON.

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

1. `OAuth & Permissions`
	1. Click `OAuth & Permissions` link on the left side menu.

	1. Click `Install App to Workspace` button
	1. Then it will be shown `OAuth Access Token` and `Bot User OAuth Access Token`

		- Copy `Bot User OAuth Access Token`
			- This time, only use `Bot User OAuth Access Token`

= Plugin Settings =
1. Go to Plugin Setting page
	1. Paste `Bot User OAuth Access Token` which is copied earlier.
	1. Then click `Save Changes` button

= Invitation to the channel =
1. Invite `logbot` to the channels
	- Both are allowed to invite logbot to the public channels and private channels.

== Frequently Asked Questions ==
No information

== Screenshots ==
No information

== Changelog ==

= 1.6 =
* Minor bug fixes

= 1.5 =
* Minor bug fixes

= 1.4 =
* Minor bug fixes

= 1.3 =
* Minor bug fixes

= 1.2 =
* Move setting link into `Settings`

= 1.1 =
* Enabled uninstall hook to delete option value and drop log table

= 1.0 =
* First release

== Upgrade Notice ==
No information
