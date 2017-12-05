<?php
namespace Ipol\DPD\DB;

use \Bitrix\Main\SystemException;
use \Ipol\DPD\Utils;

/**
 * Класс модели таблицы
 * Каждый экземпляр класса - одна строка из таблицы
 *
 * К значениям полей можно обратиться двумя способами
 *
 * - как к св-ву объекта, в этом случае перед чтением/записи св-ва
 *   будет произведен поиск метода setPopertyName/getPropertyName 
 *   и если они есть они будут вызваны и возвращен результат этого вызова
 *
 * - как к массиву, в этом случае данные будут записаны/возвращены как есть
 */
class Model implements \ArrayAccess
{
	/**
	 * Поля записи
	 * @var array
	 */
	protected $fields = false;

	/**
	 * @var \Ipol\DPD\DB\TableInterface
	 */
	protected $table;

	/**
	 * @return \Ipol\DPD\DB\TableInterface
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Конструктор класса
	 * 
	 * @param mixed $id ID или массив полей сущности
	 */
	public function __construct(TableInterface $table, $id = false)
	{
		$this->table  = $table;
		$this->fields = $table->getFields();

		$this->load($id);
	}

	/**
	 * Получает поля сущности из БД
	 * 
	 * @param  mixed $id ID или массив полей сущности
	 * 
	 * @return bool
	 */
	public function load($id)
	{
		if (!$id) {
			return false;
		}

		$data = is_array($id)
			? $id
			: $this->getTable()->findFirst($id)
		;

		if (!$data) {
			return false;
		}

		$this->fields = $data;
		$this->afterLoad();

		return true;
	}

	/**
	 * Вызывается после получения полей сущности из БД
	 * 
	 * @return void
	 */
	public function afterLoad()
	{}

	/**
	 * Добавляет запись в таблицу
	 * 
	 * @return bool
	 */
	public function insert()
	{
		if ($this->id) {
			throw new \Exception('Record is exists');
		}

		$ret = $this->getTable()->add($this->fields);
		
		if ($ret) {
			$this->id = $ret;
		}
		
		return $ret;
	}

	/**
	 * Обновляет запись в таблице
	 * 
	 * @return bool
	 */
	public function update()
	{
		if (!$this->id) {
			throw new \Exception('Record is not exists');
		}

		$ret = $this->getTable()->update($this->id, $this->fields);

		return $ret;
	}

	/**
	 * Сохраняет запись вне зависимости от ее состояния
	 * 
	 * @return bool
	 */
	public function save()
	{
		if ($this->id) {
			return $this->update();
		}

		return $this->insert();
	}

	/**
	 * Удаляет запись из таблицы
	 * 
	 * @return bool
	 */
	public function delete()
	{
		if (!$this->id) {
			throw new \Exception('Record is not exists');
		}

		$ret = $this->getTable()->delete($this->id);

		if ($ret) {
			$this->id = null;
		}

		return $ret;
	}

	/**
	 * Возвращает представление записи в виде массива
	 * 
	 * @return array
	 */
	public function getArrayCopy()
	{
		return $this->fields;
	}

	/**
	 * Проверяет существование св-ва
	 * 
	 * @param  string  $prop
	 * @return boolean
	 */
	public function __isset($prop)
	{
		$prop = Utils::camelCaseToUnderScore($prop);

		return array_key_exists($prop, $this->fields);
	}

	/**
	 * Удаляет св-во сущности
	 * 
	 * @param string $prop
	 * 
	 * @return void
	 */
	public function __unset($prop)
	{
		throw new \Exception("Can\'t be removed property {$prop}");
	}

	/**
	 * Получает значение св-ва сущности
	 * 
	 * @param  string $prop
	 * 
	 * @return mixed
	 */
	public function __get($prop)
	{
		$method = 'get'. Utils::UnderScoreToCamelCase($prop, true);
		if (method_exists($this, $method)) {
			return $this->$method();
		}

		$prop = Utils::camelCaseToUnderScore($prop);
		if (!$this->__isset($prop)) {
			throw new \Exception("Missing property {$prop}");
		}

		return $this->fields[$prop];
	}

	/**
	 * Задает значение св-ва сущности
	 * 
	 * @param string $prop
	 * @param mixed $value
	 * 
	 * @return void
	 */
	public function __set($prop, $value)
	{
		$method = 'set'. Utils::UnderScoreToCamelCase($prop, true);
		if (method_exists($this, $method)) {
			return $this->$method($value);
		}

		$prop = Utils::camelCaseToUnderScore($prop);
		if (!$this->__isset($prop)) {
			throw new \Exception("Missing property {$prop}");
		}

		$this->fields[$prop] = $value;
	}

	/**
	 * @param string $prop
	 * 
	 * @return bool
	 */
	public function offsetExists($prop)
	{
		return $this->__isset($prop);
	}

	/**
	 * @param string $prop
	 * 
	 * @return void
	 */
	public function offsetUnset($prop)
	{
		throw new \Exception("Can\'t be removed property {$prop}");
	}

	/**
	 * @param string $prop
	 * 
	 * @return mixed
	 */
	public function offsetGet($prop)
	{
		if (!$this->offsetExists($prop)) {
			throw new \Exception("Missing property {$prop}");
		}

		return $this->fields[$prop];
	}

	/**
	 * @param string $prop
	 * 
	 * @return void
	 */
	public function offsetSet($prop, $value)
	{
		if (!$this->offsetExists($prop)) {
			throw new \Exception("Missing property {$prop}");
		}

		$this->fields[$prop] = $value;
	}
}