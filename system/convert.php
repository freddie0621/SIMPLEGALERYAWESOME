<?php

/*
	---------------------------------------------------------------

		PHP Scripted FFmpeg Video Converter 2.2.0
		By Kenny Svalgaard
		http://sye.dk/

		See readme.txt for EULA, settings and usage information

	---------------------------------------------------------------
*/


	function getSetting(&$line)
	{
		$lb = mb_strpos($line, '[');
		$rb = mb_strpos($line, ']');
		if (($lb !== false) and ($rb !== false) and ($lb < $rb))
		{
			if (mb_strtolower(trim(mb_substr($line, $lb+1, $rb-$lb-1)))==='x')
			{
				$co = mb_strrpos($line, ':');
				if (!$co)
				{
					$co = mb_strlen($line);
				}
				$val = trim(mb_substr($line, $rb+1, $co-$rb-1));
				return array('value', $val);
			}
		}
		else
		{
			$lt = mb_strpos($line, '<');
			$gt = mb_strpos($line, '>');
			if (($lt !== false) and ($gt !== false) and ($lt < $gt))
			{
				return array('identifier', trim($line, " <>\t"));
			}
		}
		return false;
	}


	function SetConstant($name, $val, $verify=false)
	{
		$constants = array
		(
			'DIR_IN' => true,
			'DIR_OUT' => true,
			'DIR_DONE' => true,
			'DIR_FAILED' => true,
			'DIR_EXISTED' => true,
			'CONVERT_SUCCESS_ACTION' => array('move', 'delete'),
			'CONVERT_FAILED_ACTION' => array('move', 'delete'),
			'DIR_OUT_ACTION' => array('move', 'overwrite', 'rename', 'delete'),
			'DIR_DONE_ACTION' => array('overwrite', 'rename', 'delete'),
			'DIR_EXISTED_ACTION' => array('overwrite', 'rename', 'delete'),
			'DIR_FAILED_ACTION' => array('overwrite', 'rename', 'delete'),
			'UNIQUE_PREFIX' => true,
			'MAX_DIMENSIONS' => true,
			'BIT_RATE_FORMULA' => true,
			'FRAME_RATE_DEFAULT' => true,
			'VIDEO_BIT_RATE' => array('source', 'formula', 'scale', 'lowest'),
			'VIDEO_DIMENSIONS_EVEN' => array('true', 'false'),
			'AUDIO_FIXED_BIT_RATE' => true,
			'AUDIO_BIT_RATE' => array('source', 'fixed', 'lowest'),
			'VIDEO_EXTENSIONS' => true,
			'EXCLUDE_EXTENSIONS' => true,
			'USE_EXCLUDE_EXTENSIONS' => array('true', 'false'),
			'WRITE_LOG' => array('true', 'false'),
			'CLEAR_LOG_FILE' => array('true', 'false'),
			'KEEP_RUNNING' => array('true', 'false'),
			'FFMPEG_PARAMETERS' => true,
			'GENERATE_SCREENSHOTS' => array('true', 'false'),
			'FFMPEG_SCREENSHOT_PARAMETERS' => true,
			'DESTINATION_EXTENSION' => true
		);
		if ($verify)
		{
			foreach ($constants as $key => $value)
			{
				if (!defined($key))
				{
					abort('Setting for < '.$key.' > is missing.');
				}
			}
			echoTime('Settings loaded successfully.');
		}
		else
		{
			$set = false;
			if (array_key_exists($name, $constants))
			{
				if (defined($name))
				{
					abort('< '.$name.' > is already set.');
				}
				else
				{
					if (is_array($constants[$name]))
					{
						if(in_array($val, $constants[$name]))
						{
							$set = true;
						}
						else
						{
							echoTime('Setting for < '.$name.' > is invalid.');
							echoTime('< '.$name.' > must be set to one of:');
							foreach($constants[$name] as $n)
							{
								echoTime('[x] '.$n);
							}
							abort();
						}
					}
					else
					{
						if ($val !== '')
						{
							$set = true;
						}
						else
						{
							abort('Setting for < '.$name.' > is missing.');
						}
					}
				}
			}
			else
			{
				abort('Unknown setting < '.$name.' > found in setting file.');
			}
			if ($set)
			{
				echoTime('Setting '.$name.' to: '.$val);
				if ($val==='true')
				{
					$val = true;
				}
				elseif ($val==='false')
				{
					$val = false;
				}
				define($name, $val);
			}
		}
	}


	function abort($str=false)
	{
		if ($str!=='')
		{
			echoTime($str);
		}
		echoTime('Terminating script.');
		echo 'Press Enter to terminate script.';
		fgets(STDIN);
		exit();
	}


	function echoTime($str, $onlyLog=false)
	{
		$str = date('Y-m-d H:i:s', time()+UTC_OFFSET_SEC).' '.$str."\r\n";
		if (!$onlyLog)
		{
			echo $str;
		}
		if (WRITE_LOG)
		{
			if ($fp = fopen(LOG_FILE, 'a'))
			{
				fwrite($fp, $str);
				fclose($fp);
			}
		}
	}


	function uniqueName($path, $file)
	{
		$i = 0;
		$newName = $file;
		if (mb_strrpos($file, '.') !== false)
		{
			$pos = mb_strrpos($file, '.');
			$length = mb_strlen($file);
			while(file_exists($path.$newName))
			{
				$i++;
				$newName = mb_substr($file, 0, $pos).UNIQUE_PREFIX.str_pad($i, 3, '0', STR_PAD_LEFT).mb_substr($file, $pos, $length);
			}
		}
		else
		{
			while(file_exists($path.$newName))
			{
				$i++;
				$newName = $file.UNIQUE_PREFIX.str_pad($i, 3, '0', STR_PAD_LEFT);
			}
		}
		echoTime('Making new unique name: '.$newName);
		return $newName;
	}


	function moveFile($file, $from, $to, $duplicateAction, $altDest='', $altDuplicateAction='')
	{
		makeDir($to);
		echoTime('Moving "'.$file.'" from "'.$from.'" to "'.$to.'"');
		$move = true;
		$fileTo = $file;
		if (file_exists($to.$file))
		{
			echoTime('File with same name already exists: "'.$to.$file.'"');
			if ($duplicateAction == 'overwrite')
			{
				echoTime('Overwriting existing file.');
			}
			elseif ($duplicateAction == 'rename')
			{
				$fileTo = uniqueName($to, $file);
				echoTime('Moving "'.$from.$file.'" to "'.$to.$fileTo.'"');
			}
			elseif ($duplicateAction == 'delete')
			{
				deleteFile($from.$file);
				$move = false;
			}
			elseif ($duplicateAction == 'move')
			{
				moveFile($file, $from, $altDest, $altDuplicateAction);
				$move = false;
			}
		}
		if ($move)
		{
			if (!rename($from.$file, $to.$fileTo))
			{
				abort('Unable to move file: '.$from.$file);
			}
		}
	}


	function deleteFile($file)
	{
		echoTime('Deleting "'.$file.'"');
		if (!unlink($file))
		{
			abort('Unable to delete file: '.$file);
		}
	}


	function moveEmptyDir($dir)
	{
		echoTime('Moving empty directory "'.$dir.'" from "'.DIR_IN.'" to "'.DIR_OUT.'"');
		if (!is_dir(DIR_OUT.$dir))
		{
			if (mkdir(DIR_OUT.$dir, 0777, true))
			{
				echoTime('Creating directory: '.DIR_OUT.$dir);
			}
			else
			{
				abort('Unable to create directory: '.DIR_OUT.$dir);
			}
		}
		else
		{
			echoTime('Directory already exists: '.DIR_OUT.$dir);
		}
		if (rmdir(DIR_IN.$dir))
		{
			echoTime('Deleting directory: '.DIR_IN.$dir);
		}
		else
		{
			abort('Unable to delete directory: '.DIR_IN.$dir);
		}
	}


	function makeDir($dir)
	{
		if (!is_dir($dir))
		{
			echoTime('Creating directory: "'.$dir.'"');
			if(!mkdir($dir, 0777, true))
			{
				abort('Unable to create directory: '.$dir);
			}
		}
	}


	function niceTime($seconds)
	{
		if (is_int($seconds))
		{
			$hours = floor($seconds / 3600);
			$mins = floor(($seconds - $hours*3600) / 60);
			$secs = $seconds - ($hours*3600 + $mins*60);
			return str_pad($hours, 2, '0', STR_PAD_LEFT).':'.str_pad($mins, 2, '0', STR_PAD_LEFT).':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
		}
		else
		{
			return '??:??:??';
		}
	}


	function niceSize($size)
	{
		$sizename = array("Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
		return ($size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2)." ".$sizename[$i] : "0 Bytes");
	}


	function realFileSize($file)
	{
		exec('for %I in ("'.$file.'") do @echo %~zI', $returnVar);
		return (float)$returnVar[0];
	}


	function probeVideo($from)
	{
		echoTime('Probing file: "'.$from.'"');
		exec('.\system\ffprobe.exe -i "'.$from.'" -v quiet -print_format json -show_streams', $probeJson, $returnVar);
		if ($returnVar!==0)
		{
			echoTime('File does not contain known or valid video format.');
			return false;
		}
		$probeArray = json_decode(implode($probeJson), true);
		if (!isset($probeArray['streams']))
		{
			echoTime('No streams found in file.');
			return false;
		}
		$videoStream = false;
		$audioBitRate = false;
		foreach ($probeArray['streams'] as $pro)
		{
			if (($pro['codec_type'] === 'video') and !$videoStream)
			{
				$videoStream = true;
				$width = (isset($pro['width'])?(int)$pro['width']:false);
				$height = (isset($pro['height'])?(int)$pro['height']:false);
				$videoBitRate = (isset($pro['bit_rate'])?(int)$pro['bit_rate']:false);
				$avgFrameRate = (isset($pro['avg_frame_rate'])?round(@(float)eval('return ('.$pro['avg_frame_rate'].');'),4):false);
				$duration = (isset($pro['duration'])?(int)$pro['duration']:false);
			}
			elseif ($pro['codec_type'] === 'audio')
			{
				$audioBitRate = (isset($pro['bit_rate'])?$pro['bit_rate']:false);
			}
		}
		if ($videoStream)
		{
			if ($width and $height)
			{
				echoTime('Data found in video. Width:'.$width.' Height:'.$height.' VideoBitRate:'.($videoBitRate?$videoBitRate:'na').' FrameRate:'.($avgFrameRate?$avgFrameRate:'na').' Duration:'.($duration?niceTime($duration):'na').' AudioBitRate:'.($audioBitRate?$audioBitRate:'na'));
				return array($width, $height, $videoBitRate, $duration, $avgFrameRate, $audioBitRate);
			}
			else
			{
				echoTime('Height and width not found in video stream.');
				return false;
			}
		}
		else
		{
			echoTime('No video stream found in file.');
			return false;
		}
	}


	function convertVideo($path, $file, $fromWidth, $fromHeight, $fromBitRate, $videoDuration, $frameRate, $fromAudioBitRate)
	{
		$from = DIR_IN.$path.$file;
		$fromNoExt = mb_substr($from, 0, mb_strrpos($from, '.'));
		$toPath = DIR_OUT.$path;
		$toFileName = mb_substr($file, 0, mb_strrpos($file, '.')).'.'.DESTINATION_EXTENSION;
		$to = $toPath.$toFileName;
		$convert = true;
		if (file_exists($to))
		{
			echoTime('Destination file "'.$to.'" already exists.');
			if (DIR_OUT_ACTION == 'move')
			{
				$convert = false;
				moveFile($file, DIR_IN.$path, DIR_EXISTED.$path, DIR_EXISTED_ACTION);
			}
			elseif (DIR_OUT_ACTION == 'overwrite')
			{
				deleteFile($to);
			}
			elseif (DIR_OUT_ACTION == 'rename')
			{
				$toFileName = uniqueName($toPath, $toFileName);
				$to = $toPath.$toFileName;
			}
			elseif (DIR_OUT_ACTION == 'delete')
			{
				$convert = false;
				deleteFile($from);
			}
		}
		if ($convert)
		{
			makeDir(DIR_OUT.$path);
			echoTime('Preparing to convert "'.$from.'" to "'.$to.'"');
			if (($fromWidth > MAX_WIDTH) or ($fromHeight > MAX_HEIGHT))
			{
				$aspectX = $fromWidth / MAX_WIDTH;
				$aspectY = $fromHeight / MAX_HEIGHT;
				if ($aspectX > $aspectY)
				{
					$toWidth = MAX_WIDTH;
					$toHeight = round($fromHeight / $aspectX);
				}
				else
				{
					$toWidth = round($fromWidth / $aspectY);
					$toHeight = MAX_HEIGHT;
				}
				echoTime('Dimension set to: '.$toWidth.'x'.$toHeight);
				if (VIDEO_DIMENSIONS_EVEN and (($toWidth%2!=0) or ($toHeight%2!=0)))
				{
					$toWidth += ($toWidth%2!=0?1:0);
					$toHeight += ($toHeight%2!=0?1:0);
					echoTime('Dimension not even, changed to: '.$toWidth.'x'.$toHeight);
				}
			}
			else
			{
				$toWidth = $fromWidth;
				$toHeight = $fromHeight;
				echoTime('Dimension unchanged: '.$toWidth.'x'.$toHeight);
			}
			if (!$frameRate)
			{
				$frameRate = FRAME_RATE_DEFAULT;
				echoTime('No frame rate found in source video, using default frame rate for calculation: '.$frameRate);
			}
			$form = str_replace('{fps}', $frameRate, BIT_RATE_FORMULA);
			$form = str_replace('{width}', $toWidth, $form);
			$form = str_replace('{height}', $toHeight, $form);
			$bitRateFormula = round(@(float)eval('return ('.$form.');'));
			if ($fromBitRate)
			{
				$bitRateScale = round(($fromBitRate/($fromWidth*$fromHeight))*($toWidth*$toHeight));
				if (VIDEO_BIT_RATE == 'source')
				{
					$bitRate = $fromBitRate;
					echoTime('Using source video bit rate for destination video: '.$bitRate);
				}
				elseif(VIDEO_BIT_RATE == 'formula')
				{
					$bitRate = $bitRateFormula;
					echoTime('Formula calculated bit rate: '.$form.' = '.$bitRate);
				}
				elseif(VIDEO_BIT_RATE == 'scale')
				{
					$bitRate = $bitRateScale;
					echoTime('Video bit rate scaled to: '.$bitRate);
				}
				elseif(VIDEO_BIT_RATE == 'lowest')
				{
					if ($bitRateScale < $bitRateFormula)
					{
						$bitRate = $bitRateScale;
						echoTime('Video bit rate set to scaled bit rate (lowest): '.$bitRate);
					}
					else
					{
						$bitRate = $bitRateFormula;
						echoTime('Video bit rate set to formula calculated bit rate (lowest): '.$bitRate);
					}
				}
			}
			else
			{
				if ((VIDEO_BIT_RATE == 'source') or (VIDEO_BIT_RATE == 'scale') or (VIDEO_BIT_RATE == 'lowest'))
				{
					echoTime('No bit rate found in source video, using formula calculated bit rate for destination video.');
				}
				$bitRate = $bitRateFormula;
				echoTime('Formula calculated bit rate: '.$form.' = '.$bitRate);
			}
			if ($fromAudioBitRate)
			{
				if (AUDIO_BIT_RATE == 'source')
				{
					$audioBitRate = $fromAudioBitRate;
					echoTime('Audio bit rate unchanged from source: '.$audioBitRate);
				}
				elseif (AUDIO_BIT_RATE == 'fixed')
				{
					$audioBitRate = AUDIO_FIXED_BIT_RATE;
					echoTime('Audio bit rate set by script to: '.$audioBitRate);
				}
				elseif (AUDIO_BIT_RATE == 'lowest')
				{
					if ($fromAudioBitRate < AUDIO_FIXED_BIT_RATE)
					{
						$audioBitRate = $fromAudioBitRate;
					}
					else
					{
						$audioBitRate = AUDIO_FIXED_BIT_RATE;
					}
					echoTime('Audio bit rate set by script to (lowest): '.$audioBitRate);
				}
			}
			else
			{
				$audioBitRate = AUDIO_FIXED_BIT_RATE;
				echoTime('Audio bit rate set by script to: '.$audioBitRate);
			}
			echoTime('Converting...');
			$param = str_replace('{from}', $from, FFMPEG_PARAMETERS);
			$param = str_replace('{from_no_ext}', $fromNoExt, $param);
			$param = str_replace('{to}', $to, $param);
			$param = str_replace('{width}', $toWidth, $param);
			$param = str_replace('{height}', $toHeight, $param);
			$param = str_replace('{videobit}', $bitRate, $param);
			$param = str_replace('{audiobit}', $audioBitRate, $param);
			$cmd = '.\system\ffmpeg.exe '.$param;
			echoTime ($cmd, true);
			$startTime = time();
			$handle = popen($cmd.' 2>&1', 'r');
			if ($handle !== false)
			{
				echo "\n";
				$line = '';
				$percentFactor = (($videoDuration and ($videoDuration>1)) ? 100/$videoDuration : false);
				if ($percentFactor)
				{
					echo 'Total       Elapsed     Remaining   Progress'."\n";
				}
				else
				{
					echo 'Unable to show remaining time.'."\n";
				}
				$progressBarLength = 50;
				$progressBarStep = 100/$progressBarLength;
				$timeTotal = false;
				$timeRemaining = false;
				while (($char = fgetc($handle)) !== false)
				{
					if ($char == "\r")
					{
						$charFrom = strpos($line, 'time=');
						$charTo = strpos($line, ' bitrate=');
						if (($charFrom !== false) and ($charTo !== false))
						{
							$charFrom += 5;
							$strTime = substr($line, $charFrom, $charTo-$charFrom);
							$timeArray = explode(':', $strTime);
							$secProgress = ((int)$timeArray[0] * 3600)+((int)$timeArray[1] * 60)+((int)$timeArray[2]);
							$timeElapsed = time()-$startTime;
							if ($percentFactor)
							{
								$percentColplete = ($percentFactor*$secProgress);
								$percentColplete = ($percentColplete>100?100:$percentColplete);
								if (($timeElapsed>1)and($percentColplete>0.5))
								{
									$timeTotal = (int)(($timeElapsed/$percentColplete)*100);
									$timeRemaining = (($timeTotal-$timeElapsed)<0?0:($timeTotal-$timeElapsed));
								}
								$progressBar = '';
								for ($i=0; $i<$progressBarLength; $i++)
								{
									$progressBar .= ((($i*$progressBarStep)+($progressBarStep/2)<$percentColplete)?chr(254):' ');
								}	
								echo niceTime($timeTotal).'    '.niceTime($timeElapsed).'    '.niceTime($timeRemaining).'    '.str_pad((int)$percentColplete, 2, '0', STR_PAD_LEFT).'%'.' ['.$progressBar.'] '."\r";
							}
							else
							{
								echo 'Time elapsed: '.niceTime($timeElapsed).' Time in video: '.niceTime($secProgress)."  \r";
							}
						}
						$line = '';
					}
					else
					{
						$line .= $char;
					}
				}
				$returnVar = pclose($handle);
				echo "\n\n";
			}
			else
			{
				abort('Unable to start FFMpeg.');
			}
			if ($returnVar === 0)
			{
				$fileSizeFrom = realFileSize($from);
				$fileSizeTo = realFileSize($to);
				echoTime('Conversion completed successfully in '.niceTime(time()-$startTime).'. File size from '.niceSize($fileSizeFrom).' to '.niceSize($fileSizeTo).', it is '.round(100/$fileSizeFrom*$fileSizeTo).'% of source file size.');
				if (CONVERT_SUCCESS_ACTION == 'delete')
				{
					deleteFile($from);
				}
				elseif (CONVERT_SUCCESS_ACTION == 'move')
				{
					moveFile($file, DIR_IN.$path, DIR_DONE.$path, DIR_DONE_ACTION);
				}
				if (GENERATE_SCREENSHOTS)
				{
					generateScreenshot($path, $toFileName, $videoDuration);
				}
			}
			else
			{
				echoTime('Conversion failed. (FFMpeg return code: '.$returnVar.')');
				if (file_exists($to))
				{
					deleteFile($to);
				}
				else
				{
					echoTime('No output file created.');
				}
				if (CONVERT_FAILED_ACTION == 'delete')
				{
					deleteFile($from);
				}
				elseif (CONVERT_FAILED_ACTION == 'move')
				{
					moveFile($file, DIR_IN.$path, DIR_FAILED.$path, DIR_FAILED_ACTION);
				}
			}
		}
	}


	function generateScreenshot($path, $videoFile, $videoDuration)
	{
		$sourceFilePath = $path.$videoFile;
		if (file_exists(DIR_IN.$sourceFilePath.'.jpg'))
		{
			echoTime('Screenshot already exists in In-directory. Skipping generation.');
		}
		elseif (file_exists(DIR_OUT.$sourceFilePath.'.jpg'))
		{
			echoTime('Screenshot already exists in Out-directory. Skipping generation.');
		}
		else
		{
			echoTime('Creating screenshot from "'.$sourceFilePath.'"');
			if($videoDuration)
			{
				$time=round(($videoDuration/10),1);
			}
			else
			{
				$time=10;
			}
			$param = str_replace('{from}', DIR_OUT.$sourceFilePath, FFMPEG_SCREENSHOT_PARAMETERS);
			$param = str_replace('{to}', DIR_OUT.$sourceFilePath.'.jpg', $param);
			$param = str_replace('{time}', $time, $param);
			$cmd='.\system\ffmpeg.exe '.$param;
			echoTime($cmd, true);
			exec($cmd);
		}
	}


	function getNextElement($dir='')
	{
		global $excludeExtensions;
		$dirs = array();
		$files = array();
		$excludeFiles = array();
		if (!file_exists(DIR_IN.$dir.'.'))
		{
			abort('Unable to access: '.DIR_IN.$dir);
		}
		$directory_handle = opendir(DIR_IN.$dir);
		if ($directory_handle !== false)
		{
			while($var = readdir($directory_handle))
			{
				if (is_dir(DIR_IN.$dir.$var))
				{
					if (($var != '.') and ($var != '..'))
					{
						$dirs[] = $var;
					}
				}
				else
				{
					if (USE_EXCLUDE_EXTENSIONS and (array_search(mb_strtolower(mb_substr($var, mb_strrpos($var, '.')+1)), $excludeExtensions) !== false))
					{
						$excludeFiles[] = $var;
					}
					else
					{
						$files[] = $var;
					}
				}
			}
			closedir($directory_handle);
			if (count($dirs)>0)
			{
				sort($dirs);
				return getNextElement($dir.$dirs[0].'\\');
			}
			elseif (count($files)>0)
			{
				sort($files);
				return array('file', $dir, $files[0]);
			}
			elseif (count($excludeFiles)>0)
			{
				sort($excludeFiles);
				return array('exclude', $dir, $excludeFiles[0]);
			}
			elseif ($dir!=='')
			{
				return array('dir', $dir);
			}
			else
			{
				return array('empty');
			}
		}
		else
		{
			abort('Unable to access: '.DIR_IN.$dir);
		}
	}


	mb_internal_encoding("UTF-8");
	date_default_timezone_set('UTC');
	exec('mode con: cols=100 lines=40');
	exec('wmic os get LocalDateTime', $localDateTime);
	define('UTC_OFFSET_SEC',(int)substr($localDateTime[1],strlen($localDateTime[1])-4)*60);
	define('LOG_FILE', './system/logfile.txt');
	if (CLEAR_LOG_FILE and file_exists(LOG_FILE))
	{
		unlink(LOG_FILE);
	}
	echoTime('PHP Scripted FFmpeg Video Converter started.');
	$settingsFile = 'settings.txt';
	if (isset($argv[1]))
	{
		echoTime('Parameter set: '.$argv[1]);
		if (file_exists('./'.$argv[1]))
		{
			echoTime('File exists, using as settings file: '.$argv[1]);
			$settingsFile = $argv[1];
		}
		else
		{
			abort('Settings file do not exist: '.$argv[1]);
		}
	}
	else
	{
		echoTime('No parameter set, using default settings file: '.$settingsFile);
	}
	echoTime('Loading settings.');
	$settings = @file($settingsFile, FILE_IGNORE_NEW_LINES);
	if ($settings)
	{
		reset($settings);
		$name='';
		while (list(, $line) = each($settings))
		{
			$setting = getSetting($line);
			if ($setting[0]=='identifier')
			{
				$getValue = true;
				$name = $setting[1];
				while ($getValue and (list(, $line) = each($settings)))
				{
					$value = getSetting($line);
					if ($value)
					{
						if ($value[0]=='identifier')
						{
							abort('Setting error. Missing setting for: < '.$name.' >');
						}
						elseif ($value[0]=='value')
						{
							$val = $value[1];
							SetConstant($name, $val);
							$getValue = false;
						}
					}
				}
			}
			elseif ($setting[0]=='value')
			{
				if ($name)
				{
					abort('Setting error. Double setting for: < '.$name.' >');
				}
				else
				{
					abort('Setting error. Setting found before identifier.');
				}
			}
		}
	}
	else
	{
		abort('Unable to load settings file.');
	}
	SetConstant('', '', true);
	$maxDimArray = explode('x', MAX_DIMENSIONS);
	define('MAX_WIDTH', trim($maxDimArray[0]));
	define('MAX_HEIGHT', trim($maxDimArray[1]));
	$showEmptyMessage = true;
	$videoExtensions = explode(',', mb_strtolower(str_replace(' ', '', VIDEO_EXTENSIONS)));
	$excludeExtensions = explode(',', mb_strtolower(str_replace(' ', '', EXCLUDE_EXTENSIONS)));
	while (true)
	{
		$ele = getNextElement();
		if($ele[0]=='file')
		{
			$showEmptyMessage = true;
			echo "---------------------------------------\n";
			if (array_search(mb_strtolower(mb_substr($ele[2], mb_strrpos($ele[2], '.')+1)), $videoExtensions) !== false)
			{
				echoTime('Next item (video): '.DIR_IN.$ele[1].$ele[2]);
				$probe = probeVideo(DIR_IN.$ele[1].$ele[2]);
				if ($probe)
				{
					convertVideo($ele[1], $ele[2], $probe[0], $probe[1], $probe[2], $probe[3], $probe[4], $probe[5]);
				}
				else
				{
					moveFile($ele[2], DIR_IN.$ele[1], DIR_FAILED.$ele[1], DIR_FAILED_ACTION);
				}
			}
			else
			{
				echoTime('Next item (file): '.DIR_IN.$ele[1].$ele[2]);
				moveFile($ele[2], DIR_IN.$ele[1], DIR_OUT.$ele[1], DIR_OUT_ACTION, DIR_EXISTED.$ele[1], DIR_EXISTED_ACTION);
			}
		}
		if($ele[0]=='exclude')
		{
			$showEmptyMessage = true;
			echo "---------------------------------------\n";
			echoTime('Next item (exclude file): '.DIR_IN.$ele[1].$ele[2]);
			moveFile($ele[2], DIR_IN.$ele[1], DIR_DONE.$ele[1], DIR_DONE_ACTION);
		}
		elseif ($ele[0]=='dir')
		{
			$showEmptyMessage = true;
			echo "---------------------------------------\n";
			echoTime('Next item (empty directory): '.DIR_IN.$ele[1].$ele[2]);
			moveEmptyDir($ele[1]);
		}
		elseif ($ele[0]=='empty')
		{
			if (KEEP_RUNNING)
			{
				if ($showEmptyMessage)
				{
					echo "---------------------------------------\n";
					echoTime('In-folder is empty. Waiting for videos to convert.');
					$showEmptyMessage = false;
					$startTime = time();
				}
				for ($sec=60; $sec>0; $sec--)
				{
					echo 'Checking In-folder again in '.$sec.'.sec. Waited: '.niceTime(time()-$startTime)."  \r";
					usleep(500000);
					echo 'Checking In-folder again in '.$sec." sec.\r";
					usleep(500000);
				}
				echo "                                                                 \r";
			}
			else
			{
				echoTime('In-folder is empty. Script terminating.');
				exit();
			}
		}
	}
?>