<?php

/*
	---------------------------------------------------------------

		PHP Scripted FFmpeg Video Converter 2.2.0
		By Kenny Svalgaard
		http://sye.dk/

		See readme.txt for EULA, settings and usage information

	---------------------------------------------------------------
*/

// Heure actuelle
echo date('h:i:s') . "\n";
$nextWeek = time() + (7 * 24 * 60 * 60);
// 7 jours; 24 heures; 60 minutes; 60 secondes
echo 'Aujourd\'hui :       '. date('Y-m-d') ."\n";
echo 'Semaine prochaine : '. date('Y-m-d', $nextWeek) ."\n";
// ou en utilisant strtotime():
echo 'Semaine prochaine : '. date('Y-m-d', strtotime('+1 week')) ."\n";
// Stoppe pour 10 secondes
sleep(5);


// retour !
echo date('h:i:s') . "\n";
foreach (glob("\gro\*.txt") as $filename) {
    echo "$filename occupe " . filesize($filename) . "\n";
}
print_r(glob("*.txt"));

    /* to use:
    pattern = glob pattern to match
    flags = glob flags
    path = path to search
    depth = how deep to travel, -1 for unlimited, 0 for only current directory
    */
     
    $folders = folder_tree('*.*', 0, 'system', -1);
     
    print_r($folders);
    $output = print_r($folders, true);
    $fp = fopen("textfile.txt", "w");
    fwrite($fp, $output);
    fclose($fp);
    function folder_tree($pattern = '*', $flags = 0, $path = false, $depth = 0, $level = 0) {
    	$tree = array();
     
    	$files = glob($path.$pattern, $flags);
    	$paths = glob($path.'*', GLOB_ONLYDIR|GLOB_NOSORT);
     
    	if (!empty($paths) && ($level < $depth || $depth == -1)) {
    		$level++;
    		foreach ($paths as $sub_path) {
    			$tree[$sub_path] = folder_tree($pattern, $flags, $sub_path.DIRECTORY_SEPARATOR, $depth, $level);
    		}	
    	}
     
    	$tree = array_merge($tree, $files);
     
    	return $tree;
    }
$path = "system/";
$files = scandir($path);
foreach ($files as &$value) {
    //echo "<a href='http://localhost/".$value."' target='_black' >".$value."</a><br/>";
	echo $value ;
	echo " ";
}
sleep(5);
?>