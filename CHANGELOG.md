# Changelog

All notable changes to this extension are documented here.

## 1.0.1 - 2026-11-01

### Fixed
- Removes stale phpBB notifications when the related post is deleted.
- Cleans reply, mention, quote, bookmark, forum-watch and post-approval notifications linked to the deleted post.
- Also removes topic-level notifications when an entire topic is deleted.

## 1.0.0 - 2026-10-30

### Added
- UCP preference under **Board preferences → Edit posting defaults**.
- Automatic subscription to every newly created own topic.
- One-time import of existing own topics when the preference is enabled.
- English and Dutch language support.
- Universal `styles/all` template event support.

### Behaviour
- Uses phpBB's native `topics_watch` subscriptions, so subscriptions remain visible and manageable through phpBB itself.
- Prevents duplicate subscriptions when a topic is already watched.
- Respects the global topic-notification setting and the `f_subscribe` permission.
- Does not remove existing subscriptions when the preference is disabled.
- Respects manual per-topic unsubscribe choices while the preference remains enabled.

### Compatibility
- First public release targeted at phpBB 4.0.0-a1 and newer.
- Requires PHP 8.2 or newer.
