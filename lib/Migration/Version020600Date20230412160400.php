<?php

declare(strict_types=1);

namespace OCA\Collectives\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version020600Date20230412160400 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array   $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('collectives_page_trash')) {
			$table = $schema->createTable('collectives_page_trash');
			$table->addColumn('trash_id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 250,
			]);
			$table->addColumn('original_location', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('deleted_time', Types::BIGINT, [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('collective_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('file_id', Types::BIGINT, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['trash_id']);
			$table->addUniqueIndex(['collective_id', 'name', 'deleted_time'], 'collectives_page_trash_index');
			return $schema;
		}

		return null;
	}
}
