<?

class USPS extends Shipping{

	var $server		= 'http://production.shippingapis.com/ShippingAPI.dll';	
	var $docURL		= 'http://www.usps.com/webtools/htm';
	var $userID;
	var $packages;
	var $error;
	
	function USPS($params){
		
		$this->userID = $params[0];
	}
	
	
	/*			UTILITY METHODS				*/
	
	function addPackage($data){
		
		$data = $this->_verifyRequiredPackageData($data);
		if($data) $this->packages[] = $data;
	}
	
	
	
	/*			SERVICE METHODS				*/
	
	function getRate(){
	
		if(!$this->packages){
			$this->error("No packages added");
			return;
		}
				
		$map	= $this->_getRateMap();
		$xml	= '<RateV3Request USERID="'.$this->userID.'" PASSWORD="">';		
				
		foreach($this->packages as $package => $data){
		
			$xml .= '<Package ID="'.$package.'">';
			
			foreach($map as $node => $field){
				
				if(array_key_exists($field, $data)){
					$value = $data[$field];
					$xml .= '<'.$node.'>'.$value.'</'.$node.'>';
				}
			}
			
			$xml .= '</Package>';
		}
		
		$xml	.= '</RateV3Request>';
		
		
		$request = 'API=RateV3&XML='.$xml;
		$response = parent::send($this->server, $request);
		
		// Check For Connection Error
		
		if(isset($response['ERROR'])){
			
			$this->_responseError($response);
			return;
		}
		
		$response = $response['RATEV3RESPONSE'][0]['PACKAGE'];	
		$retval = array();
		
		$x = 0;
		foreach($response as $package){			
			
			// Check For Error
			if(isset($package['ERROR'])){
				
				$this->_responseError($package);
				continue;
			}
			
			foreach($package['POSTAGE'] as $rate){
				
				$retval[$x][$rate['MAILSERVICE'][0]['VALUE']]['rate'] = $rate['RATE'][0]['VALUE'];
				$retval[$x][$rate['MAILSERVICE'][0]['VALUE']]['commitmentDate'] = isset($rate['COMMITMENTDATE']) ? $rate['COMMITMENTDATE'][0]['VALUE'] : '';
			}
			
			$x++;
		}
		
		if(count($retval) == 1) $retval = $retval[0];
		
		return $retval;
	}
	
		
	
	/*			VERIFICATION METHODS		*/
	
	function _verifyRequiredPackageData($data){
	
		$retval = array();
		
		$required = array('service'		=> 'All',
						  'fromZip'		=> '',
						  'destZip'		=> '',
						  'pounds'		=> 0,
						  'ounces'		=> 0,
						  'size'		=> 'Regular',
						  'machinable' 	=> 'true',
						  'type'	   	=> 'parcel',
						  'shipDate'	=> date('d-M-Y', mktime(0,0,0,date('m'), date('d')+1,date('Y')))	/*	Defaults to tomorrow 	*/
						 );
						 
		foreach($required as $key => $default){
					
			$value = array_key_exists($key, $data) ? $data[$key] : $required[$key];
			$retval[$key] = $value;
		}
		
		if(strtoupper($retval['service']) != 'FIRST CLASS') unset($retval['type']);
		
		return $retval;
	}
	
	
	/*			FIELD MAPPING				*/
	
	function _getRateMap(){
					 
		$data = array('Service'				=>	'service'	,
					  'FirstClassMailType'	=>	'type'		,
					  'ZipOrigination'		=>	'fromZip'	,
					  'ZipDestination'		=>	'destZip'	,
					  'Pounds'				=>	'pounds'	,
					  'Ounces'				=>	'ounces'	,
					  'Size'				=>	'size'		,
					  'Width'				=>	'width'		,
					  'Height'				=>	'height'	,
					  'Length'				=>	'length'	,
					  'Girth'				=>	'girth'		,
					  'Machinable'			=>	'machinable',
					  'ShipDate'			=>	'shipDate'	,
					  'Container'			=> 	'container'
					 );
		
		return $data;
	}
	
	
	/*			ERROR HANDLING				*/
	
	function _responseError($response){
				
		if(isset($response['ERROR'])) { 
			
			$msg = '';
			$error 	= array();
			
            $error['Number'] 		= $response['ERROR'][0]['NUMBER'][0]['VALUE'];
            $error['Source'] 		= $response['ERROR'][0]['SOURCE'][0]['VALUE'];
            $error['Description'] 	= $response['ERROR'][0]['DESCRIPTION'][0]['VALUE'];
            
            foreach($error as $key => $value){
            	$msg .= '<strong>'.$key.'</strong> - '.$value.'<br>';
            }
            
            $this->error($msg);
            
            return true;
        } 
        else return false;
	}

}

?>