<?php

// Enrico Simonetti
// enricosimonetti.com
//
// 2017-08-18 on Sugar 7.9.1.0
//
// Subpanel list sorting removal of all non-indexed fields

$module = '{MODULENAME}';
$indexed_fields = indexFinder::getIndexes($module);

foreach($viewdefs[$module]['base']['view']['subpanel-list']['panels']['0']['fields'] as $key => $field) {
    if(!in_array($field['name'], $indexed_fields)) {
        $viewdefs[$module]['base']['view']['subpanel-list']['panels']['0']['fields'][$key]['sortable'] = 0;
    } else {
        $viewdefs[$module]['base']['view']['subpanel-list']['panels']['0']['fields'][$key]['sortable'] = 1;
    }
}
