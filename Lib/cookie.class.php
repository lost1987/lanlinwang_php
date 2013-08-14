<?php
Class Cookie {
	
	private $secret;
	
	private $timeout;
	
	private $path;
	
	public function __construct(){
		$this -> secret = COOKIE_SECRECT;
		$this -> timeout = COOKIE_TIMEOUT;
		$this -> path = COOKIE_DOMAIN;
	}
	
	public function set_userdata($key,$val=''){
		
		if(empty($key))return;
		
		if(is_string($key)){
			if(!empty($val)) $val = authcode($val,'ENCODE',$this->secret);
			setcookie($key,$val,time()+$this -> timeout,$this ->path);
		}
		else if(is_array($key)){
			foreach($key as $k => $v){
				if(!empty($v)) $v = authcode($v,'ENCODE',$this->secret);
				setcookie($k,$v,time()+$this -> timeout,$this ->path);
			}
		}
		else{
			return;
		}
	}
	
	
	public function userdata($key){
		if(empty($_COOKIE[$key]))return '';
		return authcode($_COOKIE[$key],'DECODE',$this->secret);
	}
	
	public function unset_userdata($key){
		if(empty($key))return;
		
		if(is_string($key)){
			setcookie($key,'',time()-$this -> timeout,$this ->path);
		}
		else if(is_array($key)){
			foreach($key as $k => $v){
				setcookie($k,'',time()-$this -> timeout,$this ->path);
			}
		}
		else{
			return;
		}
	}
	
	public function sess_destroy(){
		if(count($_COOKIE) > 0){
			foreach($_COOKIE as $k => $v){
				setcookie($k,'',time()-$this -> timeout,$this ->path);
			}
		}
	}

}
?>