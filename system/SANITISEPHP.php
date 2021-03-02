 <?php
$myfile = fopen("newfile.txt", "w") or die("Unable to open file!");
$txt = "John Doe\n";
fwrite($myfile, $txt);
$txt = "Jane Doe\n";

$smaller = "not a .té#.txt";
//echo strip_tags($smaller);    // -> not a tag < 5
//$txt = filter_var ( $smaller, FILTER_SANITIZE_STRING); // -> not a tag
$txt = preg_replace('/([^.a-z0-9]+)/i', '_',$smaller);

fwrite($myfile, $txt);
fclose($myfile);

$dir = getcwd();
//$dir    = '/';
$files1 = scandir($dir);
$files2 = scandir($dir, 1);

//print_r($files1);
//print_r($files2);
foreach(glob('*.txt', GLOB_NOSORT) as $image)   
    {  
        echo "Filename: " . $image .preg_replace('/([^.a-z0-9]+)/i', '_',$image). "\n";     
		rename($image,preg_replace('/([^.a-z0-9]+)/i', '_',$image));	
    }  
	
foreach(glob('*.epub', GLOB_NOSORT) as $image)   
    {  
        echo "Filename: " . $image .preg_replace('/([^.a-z0-9]+)/i', '_',$image). "\n";     
		rename($image,preg_replace('/([^.a-z0-9]+)/i', '_',$image));	
    } 
foreach(glob('*.jpg', GLOB_NOSORT) as $image)   
    {  
        echo "Filename: " . $image .preg_replace('/([^.a-z0-9]+)/i', '_',$image). "\n";     
		rename($image,preg_replace('/([^.a-z0-9]+)/i', '_',$image));	
    }
foreach(glob('*.pdf', GLOB_NOSORT) as $image)   
    {  
        echo "Filename: " . $image .preg_replace('/([^.a-z0-9]+)/i', '_',$image). "\n";     
		rename($image,preg_replace('/([^.a-z0-9]+)/i', '_',$image));	
    }
?> 