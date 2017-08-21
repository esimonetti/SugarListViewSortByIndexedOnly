<?php

// Enrico Simonetti
// enricosimonetti.com
//
// 2017-08-18 on Sugar 7.9.1.0
// filename: custom/include/indexFinder.php
//
// Tool that helps retrieve and cache indexes on a per-module basis

class indexFinder
{
    public static function getIndexes($module = '') 
    {
        if(!empty($module)) {
            if(SugarCache::instance()->useBackend()) {
                $cached_indexes = SugarCache::instance()->get(self::getCacheKey($module));
                if(!empty($cached_indexes)) {
                    // we have cached values
                    return json_decode($cached_indexes, true);
                } else {
                    // fresh values
                    $indexes = self::getIndexesFromDB($module);
                    SugarCache::instance()->set(self::getCacheKey($module), json_encode($indexes));
                    return $indexes;
                }
            } else {
                return self::getIndexesFromVardefs($module);
            }
        } else {
            return array();
        }
    }

    protected static function getCacheKey($module = '')
    {
        return !empty($module) ? strtolower($module).'_modules_indexes' : '';
    }

    protected static function getIndexesFromDB($module)
    {
        $bean = BeanFactory::getBean($module);
        if(!empty($bean) && !empty($bean->table_name)) {    
            $indexes = $GLOBALS['db']->get_indices($bean->table_name);
            return self::extractIndexedFields($indexes);
        }
        return array();
    }

    protected static function getIndexesFromVardefs($module)
    {
        $bean = BeanFactory::getBean($module);
        if(!empty($bean) && !empty($bean->object_name)) {
            $indexes = $GLOBALS['dictionary'][$bean->object_name]['indices'];
            return self::extractIndexedFields($indexes);
        }
        return array();
    }

    protected static function extractIndexedFields($indexes)
    {
        $indexed_fields = array();
        if(!empty($indexes)) {
            foreach($indexes as $index) {
                if(!empty($index['fields'])) {
                    foreach($index['fields'] as $field) {
                        if(empty($indexed_fields[$field])) {
                            $indexed_fields[$field] = $field;
                        }
                    }
                }
            }
        }
        return $indexed_fields;
    }
}
