<?php
namespace app\common\lib;
/**
 * 时间
 * Class Time
 * @package app\common
 */
class Time {

    /**
     * 生成13位时间戳
     * @return int
     */
    public static function get13TimeStamp(){
		list($t1,$t2) = explode(' ',microtime());
    	return $t2.ceil($t1*1000);
    }

}