<?php
/**
 * Auto-subscribe own topics
 *
 * @copyright (c) 2026 bennybernaer
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bennybernaer\autowatchowntopics\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected \phpbb\db\driver\driver_interface $db;
	protected \phpbb\user $user;
	protected \phpbb\request\request_interface $request;
	protected \phpbb\template\template $template;
	protected \phpbb\config\config $config;
	protected \phpbb\auth\auth $auth;
	protected \phpbb\notification\manager $notification_manager;
	protected string $settings_table;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\user $user,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\config\config $config,
		\phpbb\auth\auth $auth,
		\phpbb\notification\manager $notification_manager,
		string $table_prefix
	)
	{
		$this->db = $db;
		$this->user = $user;
		$this->request = $request;
		$this->template = $template;
		$this->config = $config;
		$this->auth = $auth;
		$this->notification_manager = $notification_manager;
		$this->settings_table = $table_prefix . 'bennybernaer_autowatch_users';
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'core.ucp_prefs_post_data'              => 'ucp_prefs_post_data',
			'core.ucp_prefs_post_update_data'       => 'ucp_prefs_post_update_data',
			'core.posting_modify_submit_post_after' => 'posting_modify_submit_post_after',
			'core.delete_post_after'                => 'delete_post_after',
		];
	}

	/**
	 * Add the preference to UCP > Board preferences > Edit posting defaults.
	 */
	public function ucp_prefs_post_data($event): void
	{
		if (!$this->user->data['is_registered'])
		{
			return;
		}

		$this->user->add_lang_ext('bennybernaer/autowatchowntopics', 'common');

		$enabled = $this->is_enabled((int) $this->user->data['user_id']);
		$data = $event['data'];

		$data['autowatch_own_topics'] = $event['submit']
			? $this->request->variable('autowatch_own_topics', false)
			: $enabled;

		$event['data'] = $data;
		$this->template->assign_var('S_AUTOWATCH_OWN_TOPICS', (bool) $data['autowatch_own_topics']);
	}

	/**
	 * Save the preference.
	 *
	 * Existing own topics are imported only when the preference changes from
	 * disabled to enabled. This intentionally preserves later manual
	 * unsubscribes when the UCP page is saved again while still enabled.
	 */
	public function ucp_prefs_post_update_data($event): void
	{
		if (!$this->user->data['is_registered'])
		{
			return;
		}

		$user_id = (int) $this->user->data['user_id'];
		$was_enabled = $this->is_enabled($user_id);
		$data = $event['data'];
		$enabled = !empty($data['autowatch_own_topics']);

		if ($enabled)
		{
			if (!$was_enabled)
			{
				$this->enable_for_user($user_id);

				if (!empty($this->config['allow_topic_notify']))
				{
					$this->subscribe_existing_own_topics($user_id);
				}
			}

			return;
		}

		if ($was_enabled)
		{
			$this->disable_for_user($user_id);
		}
	}

	/**
	 * Subscribe the author after a new topic has been created successfully.
	 */
	public function posting_modify_submit_post_after($event): void
	{
		if ($event['mode'] !== 'post' || !$this->user->data['is_registered'])
		{
			return;
		}

		if (empty($this->config['allow_topic_notify']))
		{
			return;
		}

		$user_id = (int) $this->user->data['user_id'];
		if (!$this->is_enabled($user_id))
		{
			return;
		}

		$forum_id = (int) $event['forum_id'];
		if ($forum_id && !$this->auth->acl_get('f_subscribe', $forum_id))
		{
			return;
		}

		// For a newly created topic, submit_post() stores the new topic ID in data.
		// The separate topic_id event value can still be 0 in mode=post.
		$data = $event['data'];
		$topic_id = !empty($data['topic_id']) ? (int) $data['topic_id'] : (int) $event['topic_id'];

		if ($topic_id)
		{
			$this->subscribe_topic($user_id, $topic_id);
		}
	}

	/**
	 * Remove stale board notifications when a post or topic is deleted.
	 *
	 * phpBB notifications for replies, mentions, quotes, bookmarks and forum
	 * watches use the post ID as their item ID. When an entire topic is
	 * deleted, topic-level notifications use the topic ID instead.
	 */
	public function delete_post_after($event): void
	{
		$post_id = (int) $event['post_id'];
		$topic_id = (int) $event['topic_id'];
		$post_mode = (string) $event['post_mode'];

		if ($post_id)
		{
			$this->notification_manager->delete_notifications_by_types([
				'notification.type.post',
				'notification.type.mention',
				'notification.type.quote',
				'notification.type.bookmark',
				'notification.type.approve_post',
				'notification.type.forum',
			], $post_id);
		}

		if ($post_mode === 'delete_topic' && $topic_id)
		{
			$this->notification_manager->delete_notifications_by_types([
				'notification.type.topic',
				'notification.type.approve_topic',
			], $topic_id);
		}
	}

	protected function is_enabled(int $user_id): bool
	{
		$sql = 'SELECT user_id
			FROM ' . $this->settings_table . '
			WHERE user_id = ' . $user_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$enabled = (bool) $this->db->sql_fetchfield('user_id');
		$this->db->sql_freeresult($result);

		return $enabled;
	}

	protected function enable_for_user(int $user_id): void
	{
		if ($this->is_enabled($user_id))
		{
			return;
		}

		$sql = 'INSERT INTO ' . $this->settings_table . ' ' . $this->db->sql_build_array('INSERT', [
			'user_id' => $user_id,
		]);
		$this->db->sql_query($sql);
	}

	protected function disable_for_user(int $user_id): void
	{
		$sql = 'DELETE FROM ' . $this->settings_table . '
			WHERE user_id = ' . $user_id;
		$this->db->sql_query($sql);
	}

	protected function subscribe_existing_own_topics(int $user_id): void
	{
		$sql = 'SELECT t.topic_id, t.forum_id
			FROM ' . TOPICS_TABLE . ' t
			LEFT JOIN ' . TOPICS_WATCH_TABLE . ' tw
				ON tw.topic_id = t.topic_id
				AND tw.user_id = ' . $user_id . '
			WHERE t.topic_poster = ' . $user_id . '
				AND t.topic_moved_id = 0
				AND tw.topic_id IS NULL';
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$forum_id = (int) $row['forum_id'];

			// Global topics can have forum_id 0. Regular topics honour f_subscribe.
			if ($forum_id && !$this->auth->acl_get('f_subscribe', $forum_id))
			{
				continue;
			}

			$this->subscribe_topic($user_id, (int) $row['topic_id']);
		}

		$this->db->sql_freeresult($result);
	}

	protected function subscribe_topic(int $user_id, int $topic_id): void
	{
		if (!$topic_id)
		{
			return;
		}

		$sql = 'SELECT topic_id
			FROM ' . TOPICS_WATCH_TABLE . '
			WHERE user_id = ' . $user_id . '
				AND topic_id = ' . $topic_id;
		$result = $this->db->sql_query_limit($sql, 1);
		$exists = (bool) $this->db->sql_fetchfield('topic_id');
		$this->db->sql_freeresult($result);

		if ($exists)
		{
			return;
		}

		$sql = 'INSERT INTO ' . TOPICS_WATCH_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
			'user_id'       => $user_id,
			'topic_id'      => $topic_id,
			'notify_status' => NOTIFY_YES,
		]);
		$this->db->sql_query($sql);
	}
}
