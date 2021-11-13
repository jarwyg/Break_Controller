<?php




function print_debug_array($itemlist){
	
	$display_frame = "";
	for($tmp_i = 0 ; $tmp_i < 40 ; $tmp_i++){
		$display_frame .= "-";
	}
	echo $display_frame."\n";
	
	foreach( $itemlist as $key => $value)
	{
		while(strlen($key) < 20){
			$key .= " ";
		}
		if($value === true){
			$value = "true";
		}else if($value === false){
			$value = "false";
		}
		
		echo $key." = ".$value."\n";
		echo $display_frame."\n";

	}
}


function mplayer_is_playing(){
	
	$command_output = `ps -aux | grep mplayer | grep -v 'grep mplayer'`;
		
	if( strstr($command_output, "mplayer") !== false ){
		return true;
	}else{
		return false;
	}
	
}


function stop_mplayer(){
	`killall mplayer`;
}

function set_volume_level($level){
	$volume_level_int = intval($level, 10);
	if($volume_level_int >100) $volume_level_int = 100;
	if($volume_level_int < 0) $volume_level_int = 0;
	
	exec('amixer sset Master '.$volume_level_int.'%');
	echo 'Setting VOLUME: '.$volume_level_int."\n";
}



//take config file and convert into array
function config_file_to_array($file_name){

	$file_handle = fopen($file_name,'r');
	
	$data_from_file_array = array();
	
	while(!feof($file_handle)){
		$file_line = trim(fgets($file_handle));
		
		if( !empty($file_line) ){
			$file_line_array = explode('=',$file_line);
			$data_from_file_array[$file_line_array[0]] = $file_line_array[1];
		}
	}
	fclose($file_handle);
	
	return $data_from_file_array;
}


function radio_file_to_array($file_name){
	
	$file_handle = fopen($file_name,'r');
	
	$data_from_file_array = array();
	
	while(!feof($file_handle)){
		$file_line = trim(fgets($file_handle));
		
		
		if( !empty($file_line) ){
			$file_line_array = explode('=',$file_line);
			$radio_obj = new stdClass();
			$radio_obj->radio_name = $file_line_array[0];
			$radio_obj->radio_url = $file_line_array[1];
			
			array_push($data_from_file_array, $radio_obj);
			
		}
	}
	fclose($file_handle);
	
	return $data_from_file_array;
	
}

function music_file_to_array($file_name){
	
	$file_handle = fopen($file_name,'r');
	
	$data_from_file_array = array();
	
	while(!feof($file_handle)){
		$file_line = trim(fgets($file_handle));
		
		
		if( !empty($file_line) ){
			$file_line_array = explode('=',$file_line);
			$music_obj = new stdClass();
			
			$music_godz = explode(":", $file_line_array[0]);
				
			$music_obj->music_start = $music_godz[0].":".$music_godz[1];
			$music_obj->music_stop = $music_godz[2].":".$music_godz[3];
		
			$music_obj->music_name = $file_line_array[1];
			
			array_push($data_from_file_array, $music_obj);
			
		}
	}
	fclose($file_handle);
	
	return $data_from_file_array;
	
}



function play_bell($day_number, $volume_level){
	
	set_volume_level($volume_level);
	
	if( mplayer_is_playing() == true){
		stop_mplayer();
	}
	
	$mplayer_patch = "/var/www/html/bells/0".intval($day_number).".wav";
	$mplayer_patch_escaped = escapeshellarg($mplayer_patch);

	$log_file_path = "/var/www/html/log/".date("Y-m-d").".log";
	$log_file_path_escaped = escapeshellarg($log_file_path);

	
	exec('mplayer '.$mplayer_patch_escaped.' -msglevel all=4 </dev/null | tee -a '.$log_file_path_escaped.' > /dev/null 2>&1 &');
}
function play_music($filename, $volume_level){
	
	set_volume_level($volume_level);
	
	if( mplayer_is_playing() == true){
		stop_mplayer();
	}
	
	$mplayer_patch = "/var/www/html/music/".$filename;
	$mplayer_patch_escaped = escapeshellarg($mplayer_patch);

	$log_file_path = "/var/www/html/log/".date("Y-m-d").".log";
	$log_file_path_escaped = escapeshellarg($log_file_path);

	
	exec('mplayer '.$mplayer_patch_escaped.' -msglevel all=4 </dev/null | tee -a '.$log_file_path_escaped.' > /dev/null 2>&1 &');
}


function play_radio($radio_number, $volume_level){
	$radio_array = radio_file_to_array("/home/user/Break_Controller/config/radio.txt");
	
	if(isset($radio_array[$radio_number-1])){
	
		$mplayer_patch = $radio_array[$radio_number-1]->radio_url;
		$mplayer_patch_escaped = escapeshellarg($mplayer_patch);

		$log_file_path = "/var/www/html/log/".date("Y-m-d").".log";
		$log_file_path_escaped = escapeshellarg($log_file_path);

		set_volume_level($volume_level);
		
		exec('mplayer '.$mplayer_patch_escaped.' -msglevel all=4 </dev/null | tee -a '.$log_file_path_escaped.' > /dev/null 2>&1 &');
	}
	
}


//var_dump( music_file_to_array("/var/www/html/music.conf") );
//die();


$bell_ringing = false;
$last_bell_ringing_time = "";
$radio_playing = false;
$last_radio_playing_number = 0; 
$last_volume = 0;

$music_playing = false;
$last_music_stop_time = "";

$last_realy_state = 0;

while(1){

	$config_file_array = config_file_to_array("/home/user/Break_Controller/config/controler.conf");


	//relay
	$comport = $config_file_array["com_port"];
	$realy = explode(";", $config_file_array["relay"]);
	$realy_mode = $realy[0];
	$realy_enable_time = $realy[1];
	$realy_disable_time = $realy[2];
	if( ($realy_enable_time == date("H:i")) && ($last_realy_state == 0) ){
		
		$last_realy_state = 1;
		
	}else if( ($realy_disable_time == date("H:i")) && ($last_realy_state == 1) ){
		
		$last_realy_state = 0;
	}


	if($config_file_array["state"] == "on"){

		if( $config_file_array["przerwy"] == "krotkie" ){
			$break_time = $config_file_array["krotkie"];
		}else if( $config_file_array["przerwy"] == "dlugie" ){
			$break_time = $config_file_array["dlugie"];
		}

		$week_days = $config_file_array["weekdays"];
		$week_days_array = explode(";", $week_days);

		$break_time_array = explode(";", $break_time);
		unset($break_time_array[0]);//delete first element

		$day_number = date("N");//1 (for Monday) through 7 (for Sunday)
		
		if($week_days_array[$day_number-1] == "1"){//bells active in particular day

			foreach($break_time_array as $break_time_array_foreach){
				
				if( (date("H:i") == $break_time_array_foreach) && ($bell_ringing == false) ){
					play_bell($day_number, $config_file_array["volume"]);
					
					//prevent starting over and over again
					$last_bell_ringing_time = date("H:i");
					$bell_ringing = true;//bell_ringing lock enabled
					
					$radio_playing = false;// radio_playing lock disabled to allow auto start radio again
				}
			}
			
			
		}

	}
	
	
	if($config_file_array["music"] == "1"){
		
		$music_file_array = music_file_to_array("/home/user/Break_Controller/config/music.conf");
		
		foreach($music_file_array as $music_file_array_val){
			
			
			if( ($music_file_array_val->music_start == date("H:i")) && ($music_playing == false) && ($bell_ringing == false) ){
				
				//echo "music_Start\n";
				//print_r(intval($music_file_array_val->music_name));
				if( intval($music_file_array_val->music_name) != 0 ){
					//music radio
					stop_mplayer();
					play_radio( intval($music_file_array_val->music_name,10), $config_file_array["volume_music"]);
					$music_playing = true;
					$radio_playing = false;// radio_playing lock disabled to allow auto start radio again
					$last_music_stop_time = "";
					
				}else{
					//music file
					play_music( $music_file_array_val->music_name, $config_file_array["volume_music"] );
					$music_playing = true;
					$radio_playing = false;// radio_playing lock disabled to allow auto start radio again
					$last_music_stop_time = "";
					
				}
				
			}else if( ($music_file_array_val->music_stop == date("H:i")) && ($music_playing == true) && ($bell_ringing == false) && ($last_music_stop_time != date("H:i")) ){
				stop_mplayer();
				$music_playing = false;
				$last_music_stop_time = $music_file_array_val->music_stop;
				
			}
			

			
			
		}
		
		
	}
	
	
	
	
	//mplayer not playing 
	//bell_ringing lock is on 
	//and time is different from time when bell started ringing
	//then bell_ringing lock is reseted
	if( ( mplayer_is_playing() == false) && ($bell_ringing == true) && ($last_bell_ringing_time != date("H:i"))){
		$bell_ringing = false;
		echo "stop_bell\n";	
	}
	
	
	
	//radio can play only when bell is not ringing 
	//radio number must be different from 0 (0-radio off)
	//radio_playing lock must be false to prevent starting over and over again ;)) 
	if( ($config_file_array["radio"] != 0) && ($bell_ringing == false) && ($music_playing == false) && ($radio_playing == false) ){
		$last_radio_playing_number = intval($config_file_array["radio"],10);
		play_radio($config_file_array["radio"], $config_file_array["volume_music"]);
		$radio_playing = true;	
	}
	
	//when radio number inside config file is changed radio_playing lock is turned off and mplayer stopped
	if( ( $last_radio_playing_number != intval($config_file_array["radio"],10) ) && ($radio_playing == true) ){
		$radio_playing = false;
		stop_mplayer();
	}
	
	
	//when radio is playing and volume_music is changed in config file new volume level is setted in system using alsa
	if( ($radio_playing == true) || ($music_playing == true) ){
		if($last_volume != intval($config_file_array["volume_music"],10) ) {
			set_volume_level( intval($config_file_array["volume_music"],10) );
			$last_volume = intval($config_file_array["volume_music"],10);
		}
		
	}
	
	
	
	system("clear");	
	$debug_array = array(
		"time" => date("H:i:s"),
		"bell_ringing" => $bell_ringing,
		"last_bell_ringing_time" => $last_bell_ringing_time,
		"radio_playing" => $radio_playing,
		"last_radio_playing_number" => $last_radio_playing_number,
		"last_volume" => $last_volume,
		"music_playing" => $music_playing,
		"last_music_stop_time" => $last_music_stop_time,
		"realy_mode" => $realy_mode,
		"realy_enable_time" => $realy_enable_time,
		"realy_disable_time" => $realy_disable_time,
		
	);
	print_debug_array($debug_array);

	
	//sleep(1);
	usleep(500*1000);
}

?>
