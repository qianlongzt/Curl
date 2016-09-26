<?php
	class Curl{
		private $cookie = array();
		public $respBody = '';
		public $respHeader;
		private $host;
		public function showCookie() {
			var_dump($this->cookie);
		}
		public function get( $url) {
			return $this-> request('get', $url);
		}

		public function post( $url, $data) {
			return $this-> request('post', $url, $data);
		}

		private function request($method, $url, $data = array()) {
			$this -> host = $this -> getDomain($url);
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_COOKIE, $this -> getCookie());
			if(strtolower($method) === 'post') {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			}
			$resp = curl_exec($curl);
			curl_close($curl);
			$data = explode("\r\n\r\n", $resp, 2);
			$respHeader = explode("\n", $data[0]);
			$this -> parseHeader($respHeader);
			if(isset($data[1])) {
				$this -> respBody = $data[1];
			}
			return $this -> respBody;
		}
		
		private function parseHeader($respHeader) {
			$this -> respHeader = array();
			foreach($respHeader as $header) {
				if(preg_match('#HTTP/\S* *(\d+) *(\S+)#', $header, $match)) {
					$this -> respHeader['status'] = trim($match[1]);
					$this -> respHeader['msg'] = trim($match[2]);
				}

				if(preg_match("#([^:]+)[\s]*:[\s]*(.+)$#U", $header, $match)) {
					$this -> respHeader[strtolower(trim($match[1]))] = trim($match[2]);
				}

				if(preg_match_all("#^ *Set-Cookie *: *(.+)#", $header, $match)) {
					foreach($match[1] as $cookie){
						$data = explode(';', $cookie);
						$cookie = explode('=', $data[0]);
						$this->cookie[$this->host][trim($cookie[0])] = trim($cookie[1]);
					}
				}
			}
		}
		
		private function getDomain($url) {
			preg_match('#https?://([^/?]*)#', $url, $match);
			return $match[1];
		} 
		private function getCookie() {
			$cookieArr = array();
			foreach($this -> cookie as $host=> $cookies) {
				if(strpos(strtolower($this->host), $host) !== false) {
					foreach($cookies as $key => $value) {
						$cookieArr[] = $key.'='.$value;
					}
				}
			}
			
			$str =  implode(';', $cookieArr);
			return $str;
		}
	}
