<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version16000Date20190215125511 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if(!$schema->hasTable('filecache_extended')) {
			$table = $schema->createTable('filecache_extended');
			$table->addColumn('fileid', 'integer', [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('metadata_etag', 'string', [
				'notnull' => false,
				'length' => 40,
			]);
			$table->addColumn('creation_time', 'datetime', [
				'notnull' => false,
			]);
			$table->addColumn('upload_time', 'datetime', [
				'notnull' => false,
			]);
			$table->addUniqueIndex(['fileid']);
			$table->addIndex(['creation_time']);
			$table->addIndex(['upload_time']);
		}

		return $schema;
	}
}
