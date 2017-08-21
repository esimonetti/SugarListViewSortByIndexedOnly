#!/usr/bin/env php
<?php

// config variables

$id = 'orderby_list_indexed';
$name = 'Order by indexed fields on listviews';
$built_in_version = '7.9.1.0';
$author = 'Enrico Simonetti, SugarCRM Inc.';
$regex_matches = '^7.9.[\d]+.[\d]+$';

$template_destination_folder = 'custom/Extension/modules/{MODULENAME}/Ext/clients/base/';

// end config variables


if (empty($argv[1])) {
    die("Use $argv[0] [version]\n");
}

$version = $argv[1];
$id .= '_'. $version;
$zipFile = "releases/module_{$id}.zip";

if (file_exists($zipFile)) {
    die("Release $zipFile already exists!\n");
}

// generating files
$modules = array();
require('yourmodules.php');

// clear current src
$src_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(realpath('src'), RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

if(!empty($src_files)) {
    foreach($src_files as $src_file) {
        unlink($src_file->getPathname());
    }
}

// copy into src all common files
$common_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(realpath('common'), RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$common_files_list = getFiles($common_files, realpath('common'));
if(!empty($common_files_list)) {
    foreach($common_files_list as $fileRel => $fileReal) {
        $destination_folder = 'src' . DIRECTORY_SEPARATOR . dirname($fileRel) . DIRECTORY_SEPARATOR;
        
        if(!is_dir($destination_folder)) {
            mkdir($destination_folder, 0777, true);
        }
        copy($fileReal, $destination_folder . basename($fileReal));
    }
}

// generate runtime files based on the template
$template_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(realpath('template'), RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$template_files_list = getFiles($template_files, realpath('template'));
if(!empty($template_files_list)) {
    $template_destination_folder = str_replace('/', DIRECTORY_SEPARATOR, $template_destination_folder);
    
    foreach($modules as $module) {
        echo 'Generating template files for module: '.$module.PHP_EOL;
        // replace modulename from path
        $current_module_destination = str_replace('{MODULENAME}', $module, $template_destination_folder);
        foreach($template_files_list as $fileRel => $fileReal) {
            // build destination
            $destination_folder = 'src' . DIRECTORY_SEPARATOR . dirname($current_module_destination) . DIRECTORY_SEPARATOR . dirname($fileRel) . DIRECTORY_SEPARATOR;
            
            if(!is_dir($destination_folder)) {
                mkdir($destination_folder, 0777, true);
            }
            copy($fileReal, $destination_folder . basename($fileRel));

            // modify content
            $content = file_get_contents($destination_folder . basename($fileRel));
            $content = str_replace('{MODULENAME}', $module, $content);
            file_put_contents($destination_folder . basename($fileRel), $content);
        }
    }
}

$manifest = array(
    'id' => $id,
    'built_in_version' => $built_in_version,
    'name' => $name,
    'description' => $name,
    'version' => $version,
    'author' => $author,
    'is_uninstallable' => true,
    'published_date' => date("Y-m-d H:i:s"),
    'type' => 'module',
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(
        ),
        'regex_matches' => array(
            $regex_matches,
        ),
    ),
);

$installdefs = array('copy' => array());
echo "Creating {$zipFile} ... \n";

$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE);
$basePath = realpath('src');

$module_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$module_files_list = getFiles($module_files, $basePath);

if(!empty($module_files_list)) {
    foreach($module_files_list as $fileRel => $fileReal) {
        $zip->addFile($fileReal, $fileRel);
        if(substr($fileRel, -6) != 'LICENSE') {
            $installdefs['copy'][] = array(
                'from' => '<basepath>/' . $fileRel,
                'to' => $fileRel,
            );
        }
    }
}

$manifestContent = sprintf(
    "<?php\n\$manifest = %s;\n\$installdefs = %s;\n",
    var_export($manifest, true),
    var_export($installdefs, true)
);

$zip->addFromString('manifest.php', $manifestContent);
$zip->close();

echo 'done' . PHP_EOL;

function getFiles(RecursiveIteratorIterator $files, $basePath) {
    $result = array();
    if(!empty($files) && !empty($basePath)) {
        foreach ($files as $name => $file) {
            if ($file->isFile()) {
                $fileReal = $file->getRealPath();
                if(!in_array($file->getFilename(), array('.DS_Store', '.gitkeep'))) {
                    $fileRelative = '' . str_replace($basePath . '/', '', $fileReal);
                    $result[$fileRelative] = $fileReal;
                }
            }
        }
    }
    return $result;
}

exit(0);
