<?php
/**
 * Auto-subscribe own topics migration
 *
 * @copyright (c) 2026 bennybernaer
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace bennybernaer\autowatchowntopics\migrations;

class v10x_m1_initial extends \phpbb\db\migration\migration
{
	public function effectively_installed(): bool
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bennybernaer_autowatch_users');
	}

	public static function depends_on(): array
	{
		return ['\\phpbb\\db\\migration\\data\\v400\\v400a1'];
	}

	public function update_schema(): array
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'bennybernaer_autowatch_users' => [
					'COLUMNS' => [
						'user_id' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'user_id',
				],
			],
		];
	}

	public function revert_schema(): array
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'bennybernaer_autowatch_users',
			],
		];
	}
}
