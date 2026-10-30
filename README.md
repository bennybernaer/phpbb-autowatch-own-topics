# Auto-subscribe own topics

A phpBB 4.0+ extension that lets each registered user automatically subscribe to topics they create.

## Features

- Adds **Automatically subscribe me to my own topics** to:
  **UCP → Board preferences → Edit posting defaults**.
- When enabled, existing topics created by that user are subscribed once.
- New topics created afterwards are subscribed automatically.
- Existing phpBB topic subscriptions are reused; no duplicate subscription is created.
- A user may manually unsubscribe from an individual topic at any time.
- Saving the UCP preference again while it remains enabled does not re-subscribe manually unsubscribed topics.
- Turning the preference off stops automatic subscriptions for future topics but does not remove existing subscriptions.
- Turning it off and deliberately on again imports any currently missing subscriptions for existing own topics again.
- Respects phpBB's global topic-notification setting and `f_subscribe` forum permission.

## Requirements

- phpBB 4.0.0 or newer
- PHP 8.2 or newer

## Installation

1. Upload the extension to:
   `ext/bennybernaer/autowatchowntopics/`
2. In the ACP, open **Customise → Manage extensions**.
3. Enable **Auto-subscribe own topics**.
4. Users can configure it in **UCP → Board preferences → Edit posting defaults**.

## Updating

Disable the extension before replacing its files when performing a manual update. Do not delete extension data unless you intentionally want to reset all user preferences.

## Uninstallation

Disable the extension first. Use **Delete data** only if you want to remove the extension's preference table. Existing normal phpBB topic subscriptions are not owned by the extension and are therefore not removed.

## Author

bennybernaer

## License

GNU General Public License, version 2 (GPL-2.0-only)
