<?php
abstract class Model
{

	static public $instances = array();

	protected $_db;
	protected $_id;
	protected $_key;
	protected $_self;
	protected $_data;
	protected $_hook;
	protected $_checksum;

	static public function getById($id)
	{

		$self = get_called_class();
		$key  = $self.$id;

		if (array_key_exists($key, self::$instances) === false) {

			try {

				self::$instances[$key] = new $self($id);

			} catch (Exception $e) {

				return false;

			}

		}

		return self::$instances[$key];

	}

	protected function __construct($id)
	{

		$this->_db   = Database::getInstance();
		$this->_id   = $id;
		$this->_self = get_called_class();
		$this->_key  = $this->_self.$id;

		if ($this->_db->recordExistsInDB(
			$this->_structure['table'],
			array('id' => $this->_id)
		) === false) {

			throw new Exception('Not a valid object');

		}

		if ($this->_loadObject() === false) {

			$this->_retrieveFromDb();

		}

		$this->_runHookQueue();

		$this->_checksum = md5(json_encode($this->_data));

	}

	private function _loadObject()
	{

		if ($this->_db->cacheExists($this->_key) === true) {

			$this->_data = $this->_db->load($this->_key);
			return true;

		} else {

			return false;

		}

	}

	private function _saveObject($updateDb=true)
	{

		if ($updateDb === true) {

			$tbl     = $this->_structure['table'];
			$sql     = 'SHOW COLUMNS FROM `'.$tbl.'`';
			$columns = $this->_db->query($sql, null, true, false);
			$data    = array();

			foreach ($columns as $key => $value) {

				if (array_key_exists($value['Field'], $this->_data) === true) {

					$data[$value['Field']] = $this->_data[$value['Field']];

				}

			}

			$this->_db->update($tbl, $data, array('id' => $this->_id));

			if ($this->_structure['meta'] === true
				&& empty($this->_data['meta']) === false
			) {

				$sql = 'REPLACE INTO `'.$tbl.'_meta`
						(`parent_id`, `key`, `value`)
						VALUES ';

				foreach ($this->_data['meta'] as $key => $val) {

					$args[]   = $this->_id;
					$args[]   = $key;
					$args[]   = $val;
					$values[] = '(?, ?, ?)';

				}

				$sql .= implode(',', $values);
				$this->_db->query($sql, $args);

			}

		}//end if

		$this->_db->save($this->_key, $this->_data);

	}

	private function _retrieveFromDb()
	{

		$tbl    = $this->_structure['table'];
		$sql    = 'SELECT ';
		$fields = array();

		foreach ($this->_structure['fields'] as $field) {

			$fields[] = '`'.$tbl.'`.`'.$field.'` AS `'.$field.'`';

		}

		$sql .= implode(', ', $fields).L;
		$sql .= 'FROM `'.$tbl.'`'.L;
		$sql .= 'WHERE `'.$tbl.'`.`id` = ?'.L;
		$sql .= 'LIMIT 1';

		$this->_data = $this->_db->query($sql, array($this->_id), true, false);

		// If the model has a *_meta table.
		if ($this->_structure['meta'] === true) {

			$sql = 'SELECT `key`, `value`
					FROM `'.$tbl.'_meta`
					WHERE parent_id = ?';

			$meta = $this->_db->query($sql, array($this->_id), true, false);

			foreach ($meta as $val) {

				$this->_data['meta'][$val['key']] = $val['value'];

			}

		}

		$this->_saveObject(false);

	}

	private function _runHookQueue()
	{

		if (is_callable(array($this, 'hookQueue')) === true) {

			$data        = $this->hookQueue();
			$this->_data = array_merge($data, $this->_data);

		}

	}

	public function __destruct()
	{

		$checksum = md5(json_encode($this->_data));

		if ($checksum !== $this->_checksum) {

			$this->_saveObject();

		}

	}

	public function getObfuscatedId()
	{

		return generateId($this->_id, false, false, $this->_structure['table']);

	}

	public function get($key)
	{

		if (array_key_exists($key, $this->_data) === true) {

			return $this->_data[$key];

		} else {

			return false;

		}

	}

	public function set($key, $value)
	{

		if ($key === 'id') {

			trigger_error('You can\'t change the ID!', E_USER_WARNING);

		} else {

			$this->_data[$key] = $value;

		}

	}

	public function getMeta($key)
	{

		if (array_key_exists($key, $this->_data['meta']) === true) {

			return $this->_data['meta'][$key];

		} else {

			return false;

		}

	}

	public function setMeta($key, $value)
	{

		$this->_data['meta'][$key] = $value;

	}

	public function removeMeta($key)
	{

		$sql = 'DELETE FROM `'.$this->_structure['table'].'_meta`
				WHERE `parent_id` = ?
				AND `key` = ?
				LIMIT 1';

		$this->_db->query($sql, array($this->_id, $key));
		unset($this->_data['meta'][$key]);

	}

	public function data()
	{

		return $this->_data;

	}

	public function debug()
	{

		if (Request::$controller !== 'debug' || DEBUG === false) {

			trigger_error('This function is strictly forbidden', E_USER_ERROR);
			exit(1);

		}

		$results = array();
		$self    = get_called_class();
		$test    = $self.'_tests';

		foreach ($this->debug as $method => $info) {

			$ret  = call_user_func_array(array($this, $method), $info['args']);
			$real = call_user_func(array($test, $method));

			if ($ret === $info['success']) {

				$res = true;

			} else {

				$res = false;

			}

			$results[$method]['success']  = $res;
			$results[$method]['expected'] = $info['success'];
			$results[$method]['recieved'] = $ret;

		}

		return $results;

	}

}