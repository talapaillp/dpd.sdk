<?php
namespace Ipol\DPD\DB;

/**
 * Интерфейс работы с таблицой БД
 */
interface TableInterface
{
    /**
     * Возвращает соединение ассоциированное с таблицей
     * 
     * @return \Ipol\DPD\DB\ConnectionInterface
     */
    public function getConnection();

    /**
     * Возвращает инстанс PDO
     * 
     * @return \PDO
     */
    public function getPDO();

    /**
     * Возвращает имя таблицы
     * 
     * @return string
     */
    public function getTableName();

    /**
     * Возвращает имя класса модели
     * 
     * @return string
     */
    public function getModelClass();

    /**
     * Возвращает набор полей таблицы
     * [
     *  fieldName => defaultValue
     * ]
     * 
     * @return array 
     */
    public function getFields();

    /**
     * Добавление записи
     * 
     * @param array $values
     * 
     * @return bool
     */
    public function add($values);

    /**
     * Обновление записи
     * 
     * @param int   $id
     * @param array $values
     * 
     * @return bool
     */
    public function update($id, $values);

    /**
     * Удаление записи
     * 
     * @param int $id
     * 
     * @return bool
     */
    public function delete($id);

    /**
     * Выборка записей
     * 
     * $parms = "id = 1" or
     * $parms = [
     *  'select' => '*',
     *  'where'  => 'id = :id',
     *  'order'  => 'id asc',
     *  'limit'  => '0,1',
     *  'bind'   => [':id' => 1]
     * ]
     * 
     * @param string|array $parms
     * 
     * @return \PDOStatement
     */
    public function find($parms = []);

    /**
     * Выборка одной записи, псевдномим над find([limit => 0,1])
     * 
     * @param int|string|array $parms
     * 
     * @return array
     */
    public function findFirst($parms = []);

    /**
     * Проверка и создание таблицы по необходимости
     * 
     * @return void
     */
    public function checkTableSchema();
}