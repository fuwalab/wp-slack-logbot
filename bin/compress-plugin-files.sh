#!/bin/bash
zip -r `date "+%Y-%m-%d"`-wp-slack-logbot.zip admin includes languages class-wp-slack-logbot.php readme.txt -x *.DS_Store
