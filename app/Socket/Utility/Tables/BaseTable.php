<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/3
 * Time: 21:08
 */

namespace App\Socket\Utility\Tables;


use App\Service\Singleton;

class BaseTable
{
    use Singleton;

    protected $table;

    public function __construct($table)
    {
        TableManager::getInstance()->add(static::class, $table);
        $this->table = TableManager::getInstance()->get(static::class);
    }

    public function __call($name, $arguments)
    {
        return  $this->table->$name(...$arguments);
    }
}