<?

class Shipping{

	var $carrier; 			/*	Options: fedex,ups,usps				*/
	var $service; 			/*	Options: ship, rate, track, address	*/
	var $ci;
	var $debug = 'print';	/*	Options: off,print,error_log		*/
	
	
	function Shipping(){
		
		$this->ci =& get_instance();
	}
	
	function init($carrier, $userID){
				
		$this->carrier = strtolower($carrier);		
		$this->ci->load->library('curl');
		$this->ci->load->library('xmlparser');
		$this->ci->load->library('Shipping/'.$carrier, array($userID));
	}
	
	
	/*			UTILITY METHODS				*/
	
	function send($server, $request){
	
		$ci =& get_instance();
		$response = $ci->curl->post($server, $request);
		
		return $ci->xmlparser->GetXMLTree($response);
	}
	
	function addPackage($data){
		
		$carrier = $this->carrier;
		$this->ci->$carrier->addPackage($data);
	}
	
	
	/*			SERVICE METHODS				*/
	
	function getRate(){
		
		$carrier = $this->carrier;
		return $this->ci->$carrier->getRate();
	}
	
	
	
	/*			ERROR HANDLING METHODS			*/
	
	function error($msg){
		
		if($this->debug == 'print') print $msg.'<br>';
		else if($this->debug == 'error_log') error_log(strip_tags($msg));
	}

}


?>