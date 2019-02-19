<?php
/* OrphanImages snippet */
 
/* Modify the next line as necessary; the search is recursive, so it will cover the dir.
   specified, and all directories under it */
 
$imageDirectory = MODX_ASSETS_PATH . 'images';
 
require_once MODX_CORE_PATH . 'components/dirwalker/model/dirwalker/dirwalker.class.php';
 
/* Get all the files */
$dw = new DirWalker();
$dw->dirWalk($imageDirectory, true);
$files = $dw->getFiles();
 
$fileDirectories = [
    'css' => ['path' => MODX_ASSETS_PATH . 'css','format' => '.css'],
    'js' => ['path' => MODX_ASSETS_PATH . 'js','format' => '.js'],
];



/* Create Query */
$objects = array(
    $modx->getTableName('modResource') => 'content',
    $modx->getTableName('modChunk') => 'snippet',
    $modx->getTableName('modSnippet') => 'snippet',
    $modx->getTableName('modPlugin') => 'plugincode',
    $modx->getTableName('modTemplate') => 'content',
    $modx->getTableName('modTemplateVarResource') => 'value',
    $modx->getTableName('modTemplateVar') => 'default_text',
);
 
$string = '';
$s = array();
foreach($objects as $table => $fieldName) {
    $s[] =  "(SELECT $fieldName FROM $table WHERE $fieldName LIKE :s)";
}
 
$string = implode(' UNION ', $s);
 
/* Prepare Query and bind $s param */
$sql = $modx->prepare($string);
$sql->bindParam("s", $searchTerm);
 
 
/* Execute query once for each file */
$output = '';
$limit = 100;
$counter = 0;
foreach($files as $path => $searchTerm) {
    $temp_search_term = $searchTerm;
    $searchTerm = "%" . $searchTerm . "%";
    $sql->execute();
    $count = $sql->rowCount();
    
    if(!$count){
        $count = findImageFromFile($temp_search_term,$fileDirectories);
    }
    echo 'count' . $count ;exit();
    //Break loop when has limit and limit reach
    if($limit & $counter >= $limit){
        echo 'test';
        break;
    }
    $counter++;
    //Skip moving when file exist
    if($count){
        echo 'skip';
       continue;
    }
    $move_file = moveFile($path,$temp_search_term,$imageDirectory,null);
    $move_file_status = $move_file ? 'Move success!!! ' : 'Failed to move!!! ';
    $msg = '<span style="color:red">ORPHAN </span> ' . $move_file_status ;
    $output .= "\n<br>" . $msg . $path . " Count: " . $count;
 
}

//Check the image from the files if they exist
function findImageFromFile($keyword,$fileDirectories,$filters = ['css','js']){
    if($keyword){
        foreach($filters as $filter){
            if(isset($fileDirectories[$filter])){
                $dw_file = new DirWalker();
                $dw_file->setIncludes($fileDirectories[$filter]['format']);
                $dw_file->dirWalk($fileDirectories[$filter]['path'],true);
                $dw_files = $dw_file->getFiles();
                if(count($dw_files)){
                    foreach($dw_files as $file => $value){
                        $contents =  file_get_contents($file);
                        $key_quote = preg_quote($keyword);
                        
                        if(strpos($contents,$keyword) !== false || preg_match($key_quote,$contents)){
                            return 1;
                        }
                        
                    }
                }
            }
        }
        
    }
    
    return 0;


}

function moveFile($path,$filename,$old_dir,$new_dir = null){
    
    $dir =  dirname($path);
    $old_dir_quote = preg_quote($old_dir,'/');
    $new_dir = $new_dir ? $new_dir : MODX_ASSETS_PATH . 'orphan';
    $new_dir_replace = preg_replace("/{$old_dir_quote}/i",$new_dir,$dir);
    if (!is_dir($new_dir_replace)) {
        mkdir($new_dir_replace, 0777, true);
    }
    return rename($path,$new_dir_replace .'/' . $filename);
    
}
return $output;