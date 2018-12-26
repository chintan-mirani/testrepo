<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Apiv1 extends CI_Controller { 
     var $host = "127.0.0.1:6379";
   //var $host = "109.74.204.34:6379";
    
    public function index()
    {
    }
    
    private function parseWord($word) {
    log_message("error", " parsing word $word");
        $test = strtoupper($word);
        if($word == 'frequency' || $test == 'FREQUENCY') {
            log_message("error", "parsing word $word, and got frequency");
            return '_frequency';
        }
        else if($word == 'associates' || $test == 'ASSOCIATES') {
            log_message("error", "parsing word $word, and got frequency");
            return '_associates';
        }
        else if($word == 'wordlength' || $test == 'WORDLENGTH') {
            log_message("error", "parsing word $word, and got frequency");
            return '_wordlength';
        }
        else if($word == 'nwordsfound' || $test == 'NWORDSFOUND') {
            log_message("error", "parsing word $word, and got frequency");
            return '_nwordsfound';
        }
        else if($word == 'predict' || $test == 'PREDICT') {
            log_message("error", "parsing word $word, and got frequency");
            return '_predmyvars';
        }
        else if($word == 'context' || $test == 'CONTEXT') {
            log_message("error", "parsing word $word, and got frequency");
            return '_context';
        }
        else if($word == 'furthestassociates' || $test == 'FURTHESTASSOCIATES') {
            log_message("error", "parsing word $word, and got frequency");
            return '_furthestassociates';
        }
        else if($word == 'word' || $test == 'WORD') {
            log_message("error", "parsing word $word, and got frequency");
            return '_word';
        }
        else if($word == 'comment' || $test == 'COMMENT') {
            log_message("error", "parsing word $word, and got frequency");
            return '_comment';
        }
        else if($word == 'sentencelength' || $test == 'SENTENCELENGTH') {
            log_message("error", "parsing word $word, and got frequency");
            return '_sentencelength';
        }
        else if($word == 'language' || $test == 'LANGUAGE') {
            log_message("error", "parsing word $word, and got frequency");
            return '_language';
        }
        else if($word == 'listpropertydata' || $test == 'LISTPROPERTYDATA') {
            log_message("error", "parsing word $word, and got frequency");
            return '_listpropertydata';
        }
        else if($word == 'listproperty' || $test == 'LISTPROPERTY') {
            log_message("error", "parsing word $word, and got frequency");
            return '_listproperty';
        }
        else if($word == 'wordclass' || $test == 'WORDCLASS') {
            log_message("error", "parsing word $word, and got frequency");
            return '_wordclass';
        }
        else if($word == 'space' || $test == 'SPACE') {
            log_message("error", "parsing word $word, and got frequency");
            return '_space';
        }
        else if($word == 'nwords' || $test == 'NWORDS') {
            log_message("error", "parsing word $word, and got frequency");
            return '_nwords';
        }
        else if($word == 'nwordsfound' || $test == 'NWORDSFOUND') {
            log_message("error", "parsing word $word, and got frequency");
            return '_nwordsfound';
        }
        else if($word == 'logfrequency' || $test == 'LOGFREQUENCY') {
            log_message("error", "parsing word $word, and got frequency");
            return '_logfrequency';
        }
        else if($word == 'wordlength' || $test == 'WORDLENGTH') {
            log_message("error", "parsing word $word, and got frequency");
            return '_wordlength';
        }
        else if($word == 'bigram' || $test == 'BIGRAM') {
            log_message("error", "parsing word $word, and got frequency");
            return '_bigram';
        }
        else if($word == 'typetokenratio' || $test == 'TYPETOKENRATIO') {
            log_message("error", "parsing word $word, and got frequency");
            return '_typetokenratio';
        }
        else if($word == 'coherence' || $test == 'COHERENCE') {
            log_message("error", "parsing word $word, and got frequency");
            return '_coherence';
        }
        else if($word == 'varcoherence' || $test == 'VARCOHERENCE') {
            log_message("error", "parsing word $word, and got frequency");
            return '_varcoherence';
        }
        else if($word == 'variability' || $test == 'VARIABILITY') {
            log_message("error", "parsing word $word, and got frequency");
            return '_variability';
        }
        else if($word == 'NWORDS' || $test == 'nwords') {
            log_message("error", "parsing word $word, and got nwords");
            return '_nwords';
        }
        else if($word == 'PRINTIDENTIFIERS' || $test == 'printidentifiers') {
            log_message("error", "parsing word $word, and got printidentifiers");
            return '_printidentifiers';
        }
        else if($word == 'predict' || $test == 'PREDICT') {
            log_message("error", "parsing word $word, and got frequency");
            return '_predmyvars';
        }
        else {
            return '_'.$word;
        }
    }
    
    public function authenticateAPIRequest($public,$hash)
    {	
        $mongo = new Mongo();	
        $db = $mongo->semantic;
        $table = $db->api_client;
        $apiClientObj = $table->findOne(array("publicHash" => $public));
        if($apiClientObj["privateHash"]) {
          $responseHash = hash_hmac('sha256', $public, $apiClientObj["privateHash"]);
 if($apiClientObj["privateHash"]==$hash) {
//          if($responseHash==$hash) {	  
              return true;
          } else {
              return false;
          }
        } else {
            return false;
        }
    }
        
    
    public function getProperty() {
        
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        
        if($authentication) {
            $redis = new Predis\Client($this->host);
	    
    	    $mongo = new Mongo();	
    	    $db = $mongo->semantic;
    	    $table = $db->api_client;
    	    $apiClientObj = $table->findOne(array("publicHash" => $_SERVER["HTTP_PUBLIC"]));
    	    $ar=array();
    		if($this->input->post("identifierOrText")) {
    			$identifierOrText = $this->input->post("identifierOrText");
    			$property = $this->input->post("property");
    			$ref1 = $apiClientObj["privateHash"];
    			$refkey = md5($word1.$word2.$ref1.$apiClientObj["privateHash"]);
    			$property = $this->parseWord($property);
                $language=$this->input->post("language");
                $document = $this->input->post("documentSpace");
                $parameterType = $this->input->post("parameterType")?$this->input->post("parameterType"):array();
                $parameterValue =$this->input->post("parameterValue")?$this->input->post("parameterValue"):array();
                                        
                if(!empty($parameterType)){
                    if(count($parameterType)!=count($parameterValue)) {
                       echo json_encode(array("status" => "error","message" => "parameters and their values are mismatched!"));
                       return;
                    }
                }
    			
    			if($property=="") { 
    			    echo json_encode(array("status" => "error","message" => "property is required"));
    			    return;
    			}
                if($ref1=="") { 
    			    echo json_encode(array("status" => "error","message" => "reference of input text is required"));
    			    return;
    			}
                if($refkey=="") { 
    			    echo json_encode(array("status" => "error","message" => "reference key is required"));
    			    return;
    			}
                if($language=="") { 
    			    echo json_encode(array("status" => "error","message" => "language is required"));
    			    return;
    			}
                if($document=="") {
                    $document = "API";
    			}

    			$ref1 = "_ref".$ref1;
    			$ar["identifierOrText"]=$identifierOrText;
                $ar["ref1"]=$ref1;
                $ar["property"]=$property;
                $ar["refkey"]=$refkey;
                $ar["documentlanguage"]=$language;
                $ar["documentid"]=$document;
                $ar["parameterType"]=$parameterType;
                $ar["parameterValue"]=$parameterValue;
            }  else {
                echo json_encode(array("status" => "error","message" => "invalid type of text. specify singletext/multipletext"));
                return;
            }
            $result =$this->input->post();
            
            if($result["weightTargetWord"]!="") {
                $ar["weightTargetWord"]=$result["weightTargetWord"];
            }
            if($result["weightWordClass"]!="") {
                $ar["weightWordClass"]=$result["weightWordClass"];
            }
            if($result["semanticDistance"]!="") {
                $ar["semanticDistance"]=$result["semanticDistance"];
            }
            if($result["parameters"]!="") {
                $ar["parameters"]=$result["parameters"];
            }
//echo "<pre>"; print_r($ar); exit;
            $redis->lpush("getpropertyapi", json_encode($ar));
           
            $t=null;
            
            $tmpObj = "";
            while($t === null) {                
                $t = $redis->lpop("getpropertyapianswer_".$refkey);
                if($t != null) {
                    $tmpObj = json_decode($t);
                    if(isset($tmpObj->refkey))
                        echo json_encode(array("status" => "ok","data" => $tmpObj));
                    else {
                        echo json_encode(array("status" => "ok","data" => "No result"));
                    }
                    return;
                }
                sleep(1);
            }     

            echo json_encode(array("error" => "true") );
            return;
        } else {
            echo "error";
        }
    }
    
    public function setProperty() {
            
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        
        if($authentication) {
            $redis = new Predis\Client($this->host);
    	    $mongo = new Mongo();	
    	    $db = $mongo->semantic;
    	    $table = $db->api_client;
    	    $apiClientObj = $table->findOne(array("publicHash" => $_SERVER["HTTP_PUBLIC"]));
    	    $ar=array();
            if($this->input->post("data")) {
                $identifier = array();
    			$identifier1 = $this->input->post("identifier");
    			$datalabel = ($this->input->post("datalabel"))?$this->input->post("datalabel"):array();
                $data = $this->input->post("data");
    			$refkey = md5($ref1.$apiClientObj["privateHash"]);
                $language=$this->input->post("language");
                $documentSpace=$this->input->post('documentSpace');                        
                if(!empty($datalabel)){
                    if(count($data)!=count($datalabel)) {
                       echo json_encode(array("status" => "error","message" => "size of label and data shoule have same size"));
                       return;
                    }
                }
                if(!empty($identifier1)){
                    if(count($data)!=count($identifier1)) {
                       echo json_encode(array("status" => "error","message" => "size of identifier and data shoule have same size"));
                       return;
                    }else{
                        foreach($identifier1 as $id){
                            $identifier[]=  $this->parseWord($id);
                       }
                    }
                }                        
                if($refkey=="") { 
    			    echo json_encode(array("status" => "error","message" => "reference key is required"));
    			    return;
                }
                if($language=="") { 
    			    echo json_encode(array("status" => "error","message" => "language is required"));
    			    return;
    			}
                if($documentSpace=="") { 
    			    echo json_encode(array("status" => "error","message" => "documentSpace is required"));
    			    return;
    			}

    			$ar["identifier"] = $identifier; 
    			$ar["datalabel"] = $datalabel;
                $ar["data"] = $data;
                $ar["refkey"] = $refkey;
                $ar["documentlanguage"] = $language;
                $ar["documentSpace"] = $documentSpace;
            } else {
                echo json_encode(array("status" => "error","message" => "specify data"));
                return;
            }  
            $redis->lpush("setpropertyqueue", json_encode($ar));

            $t=null;
            $tmpObj = "";
            while($t === null) {
                $t = $redis->lpop("setpropertyanswers_".$refkey);
                if($t != null) {                       
                    $tmpObj = json_decode($t);
                    if(isset($tmpObj->refkey))
                        echo json_encode(array("status" => "ok","data" => $t));
                    else {
                        echo json_encode(array("error" => "true") );
                    }
                    return;
                }
                sleep(1);
            }     
            echo json_encode(array("error" => "true") );
            return;
        } else {
            echo "error";
        }
    }



    // this function is used for third party API. 
    public function get3wordsApi() {
    	$authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        
        if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
            if($this->input->post("data")) {
				$identifier = $this->input->post("identifier");
            	$data = $this->input->post("data");
                $userIdeNames = $this->input->post("userIdeNames");
                $userIdentifier = $this->input->post("userIdentifier");

				if(!is_array($userIdeNames)){
                	$userIdeNames = [];
                }
				
                if(!is_array($userIdentifier)){
                    $userIdentifier = [];
                }

                $language=$this->input->post("language");
               	$refkey = '3wordsapiref'.rand(111111,999999);

                // New Parameter
                $plotCloudType=$this->input->post('plotCloudType');
                $plotCluster=$this->input->post('plotCluster');
                $plotWordcloud=$this->input->post('plotWordcloud');
                $plotTestType=$this->input->post('plotTestType');

               	$numbersData=$this->input->post('numbersData');

                $documentSpace=$this->input->post('documentSpace');
                $type=$this->input->post('type');
                $compareData = $this->input->post('compare_data');
                $compareIde = $this->input->post('compare_identifier');

                $advanceParam = $this->input->post('advanceParam');
                if(empty($advanceParam)){
                    $advanceParam = json_encode('[]');
                }

                $xaxel = !empty($this->input->post('xaxel')) ? $this->input->post('xaxel') : [];
                $yaxel = !empty($this->input->post('yaxel')) ? $this->input->post('yaxel') : [];
                $zaxel = !empty($this->input->post('zaxel')) ? $this->input->post('zaxel') : [];

                 if(!empty($compareIde)){
                    if(count($compareData)!=count($compareIde)) {
                       echo json_encode(array("status" => "error","message" => "size of compare identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    $compareIde = $compareData = array();
                }

                if(!empty($identifier)){
                    if(count($data)!=count($identifier)) {
                       echo json_encode(array("status" => "error","message" => "size of identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    echo json_encode(array("status" => "error","message" => "specify referece words"));
                    return;
                }     

                if($refkey=="") { 
                    echo json_encode(array("status" => "error","message" => "reference key is required"));
                    return;
                }
                if(empty($language)) { 
                    $language = "en";
                }

                if(empty($type)) { 
                    $type = "single";
                }
                 
                if(empty($plotCloudType)) { 
                    $plotCloudType = "words";
                }
                
                if(empty($plotCluster)) { 
                    $plotCluster = "0";
                }

                if(empty($plotWordcloud)) { 
                    $plotWordcloud = "1";
                }

                if(empty($plotTestType)) { 
                    $plotTestType = [];
                }

                if(empty($userIdeNames)) { 
                    $userIdeNames = [];
                }

                if(empty($userIdentifier)) { 
                    $userIdentifier = [];
                }

                if(empty($numbersData)) { 
                    $numbersData = [];
                }

             	if(!in_array($plotCluster,[0,1]) ){
                	echo json_encode(array("status" => "error","message" => "Invalid parameter plotCluster. valid value: 0, 1"));
                    return;
                }
                if(!in_array($plotCloudType,['words','users','category']) ){
                	echo json_encode(array("status" => "error","message" => "Invalid parameter plotCloudType. valid values: words, users or category"));
                    return;
                }
                if(!in_array($plotWordcloud,[0,1]) ){
                	echo json_encode(array("status" => "error","message" => "Invalid parameter plotWordcloud. valid values: 0, 1"));
                    return;
                }
			
                $refkey = md5($refkey);
                $ar["type"] = $type;
                $ar["identifier"] = $identifier;
                $ar["data"] = $data;
                $ar["compareIde"] = $compareIde;
                $ar["compareData"] = $compareData;
                $ar["refkey"] = $refkey;
                $ar["documentlanguage"] = $language;
                $ar["documentSpace"] = $documentSpace;

                // New Parameter
                $ar["plotCloudType"] = $plotCloudType;
                $ar["plotNominalLabels"] = array();
                $ar["plotCluster"] = $plotCluster;
                $ar["plotWordcloud"] = $plotWordcloud;
                $ar["plotTestType"] = $plotTestType;
                $ar["userIdeNames"] = $userIdeNames;
                $ar["userIdentifier"] = $userIdentifier;
                $ar["numbersData"] = $numbersData;
                $ar["xaxel"] = $xaxel;
                $ar["yaxel"] = $yaxel;
                $ar["zaxel"] = $zaxel;

                // Advance Param for color and font
                $ar["advanceParam"] = $advanceParam;
            } else {
                echo json_encode(array("status" => "error","message" => "specify data"));
                return;
            }  
            
            $redis->lpush("3wordsqueue", json_encode($ar));
            $t=null;
            $tmpObj = "";
            while($t === null) {
                $t = $redis->lpop("3wordsanswers_".$refkey);
                if($t != null) {
                    $tmpObj = json_decode($t);
                   	if($tmpObj->results != 'Error during calculating'){
                			$real_image_url = explode('~', $tmpObj->results);
            				$real_image_url = explode('|', $real_image_url[0]);
            				$tmpObj->results = $real_image_url[0];
			                echo json_encode(array("success" => true,"refkey" => $tmpObj->refkey,'results'=>['plot-url'=>$tmpObj->results]));

            		}else{
 						echo json_encode(array("success" => false,'message'=>$tmpObj->results));
            		}
                    return;
                }
                sleep(1);
            }     
            echo json_encode(array("error" => "true") );
            return;
        } else {
            echo "error";
        }
    }

    public function get3words() {
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        $data = (array) $this->input->post(NULL,TRUE);
ini_set('max_execution_time', 600);        
if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
	    $allowEmptyData = $this->input->post("allowEmptyData");

            if($this->input->post("data") || $allowEmptyData) {
		
                $identifier = $this->input->post("identifier");
                $data = $this->input->post("data");
                $refkey = $this->input->post("refkey");
                $language=$this->input->post("language");
                
		
                $advanceParam = (!empty($this->input->post("advanceParam"))) ? $this->input->post("advanceParam") : [];

                if(!empty($this->input->post("userCallId"))){
                    $advanceParam['userCallId'] = $this->input->post("userCallId");
                }

                // New Parameter
                $plotCloudType=$this->input->post('plotCloudType');
                $plotCluster=$this->input->post('plotCluster');
                $plotWordcloud=$this->input->post('plotWordcloud');
                $plotTestType=$this->input->post('plotTestType');

                $plotWordcloudType = $this->input->post('plotWordcloudType');
                $plotNominalLabels = $this->input->post('plotNominalLabels');

                $userIdeNames=$this->input->post('userIdeNames');
                $userIdentifier=$this->input->post('userIdentifier');
                $numbersData=$this->input->post('numbersData');

                $documentSpace=$this->input->post('documentSpace');
                $type=$this->input->post('type');
                $compareData = $this->input->post('compare_data');
                $compareIde = $this->input->post('compare_identifier');

                // Valence Paramter
                $valence =  $this->input->post('valence');
                $plotProperty =  $this->input->post('plotProperty');

		// justTakenSurvey
		$justTakenSurvey =  $this->input->post('justTakenSurvey');
		if (!$justTakenSurvey || $justTakenSurvey == "" || $justTakenSurvey == null) {
		    $justTakenSurvey = "0";
		}

		// Parameters
		$parameters = $this->input->post('parameters');
                
		if(!empty($compareIde)){
                    if(count($compareData)!=count($compareIde)) {
                       echo json_encode(array("status" => "error","message" => "size of compare identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    $compareIde = $compareData = array();
                }

                if(!empty($identifier)){
                    if(!$allowEmptyData && count($data)!=count($identifier)) {
                       echo json_encode(array("status" => "error","message" => "size of identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    echo json_encode(array("status" => "error","message" => "specify referece words"));
                    return;
                }     

                if($refkey=="") { 
                    echo json_encode(array("status" => "error","message" => "reference key is required"));
                    return;
                }
                if(empty($language)) { 
                    $language = "en";
                }

                if(empty($type)) { 
                    $type = "single";
                }
                 
                if(empty($plotCloudType)) { 
                    $plotCloudType = "words";
                }
                
                if(empty($plotCluster)) { 
                    $plotCluster = "0";
                }

                if(is_null($plotWordcloud)) { 
                    $plotWordcloud = "1";
                }

                if(empty($plotTestType)) { 
                    $plotTestType = [];
                }

                if(empty($userIdeNames)) { 
                    $userIdeNames = [];
                }

                if(empty($userIdentifier)) { 
                    $userIdentifier = [];
                }

                if(empty($numbersData)) { 
                    $numbersData = [];
                }

                if(empty($plotWordcloudType)) {
                    $plotWordcloudType = '';
                }

                if(empty($plotNominalLabels) || !is_array($plotNominalLabels)) {
                    $plotNominalLabels = array();
                }

                if (!empty($valence)) {
                    $advanceParam['plotProperty3'] = $valence;
                }

                if (!empty($plotProperty)) {
                    $advanceParam['plotProperty'] = $plotProperty;
                }

                if($documentSpace=="") { 
                    $documentSpace = "3words".$language;
                }else{
                    $documentSpace = $documentSpace.$language;
                }
                $refkey = md5($refkey);
                $ar["type"] = $type;
                $ar["identifier"] = $identifier;
                $ar["data"] = $data;
                $ar["compareIde"] = $compareIde;
                $ar["compareData"] = $compareData;
                $ar["refkey"] = $refkey;
                $ar["documentlanguage"] = $language;
                $ar["documentSpace"] = $documentSpace;

                if ($parameters && is_array($parameters)) {
			$ar['parameters'] = json_encode($parameters);
                }

		//echo "<pre>"; print_r($ar); exit;
                // Advance Param for color and font
	
		//if (isset($advanceParam['plotNetworkModel'])) {
	//		$advanceParam['plotNetworkModel'] = json_encode($advanceParam['plotNetworkModel']);
//		}
                $ar["advanceParam"] = json_encode($advanceParam, true);
		$ar["justTakenSurvey"] = $justTakenSurvey;

                // New Parameter
                $ar["valence"] = $valence;
                $ar["plotWordcloudType"] = $plotWordcloudType;
                $ar["plotNominalLabels"] = $plotNominalLabels;
                $ar["plotCloudType"] = $plotCloudType;
                $ar["plotCluster"] = $plotCluster;
                $ar["plotWordcloud"] = $plotWordcloud;
                $ar["plotTestType"] = $plotTestType;
                $ar["userIdeNames"] = $userIdeNames;
                $ar["userIdentifier"] = $userIdentifier;
                $ar["numbersData"] = $numbersData;
                $ar["xaxel"] = !empty($this->input->post('xaxel')) ? $this->input->post('xaxel') : [];
                $ar["yaxel"] = !empty($this->input->post('yaxel')) ? $this->input->post('yaxel') : [];
                $ar["zaxel"] = !empty($this->input->post('zaxel')) ? $this->input->post('zaxel') : [];

		if (!$ar["data"]) {
			$ar["data"] = [];
		}

            } else {
                echo json_encode(array("status" => "error","message" => "specify data"));
                return;
            }  
//            echo json_encode($ar);exit;
            $redis->lpush("3wordsqueue", json_encode($ar));
//             echo json_encode($ar);exit;
            $t=null;
            $tmpObj = "";
            while($t === null) {

                $t = $redis->lpop("3wordsanswers_".$refkey);
                if($t != null) {
//echo "<pre>"; print_r($t); exit;
                    $tmpObj = json_decode($t);
                    echo json_encode(array("status" => "ok","data" => $tmpObj));
                    return;
                }
                sleep(1);
            }     
            echo json_encode(array("error" => "true") );
            return;
        } else {
            echo "error";
        }
    }

    public function verifyWords() {
            
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        
        if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
            if($this->input->post("data")) {
                $identifier = $this->input->post("identifier");
                $data = $this->input->post("data");
                $refkey = $this->input->post("refkey");
                $language=$this->input->post("language");
                $documentSpace=$this->input->post('documentSpace');
                $type=$this->input->post('type');
                $compareData = $this->input->post('compare_data');
                $compareIde = $this->input->post('compare_identifier');

                 if(!empty($compareIde)){
                    if(count($compareData)!=count($compareIde)) {
                       echo json_encode(array("status" => "error","message" => "size of compare identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    $compareIde = $compareData = array();
                }

                if(!empty($identifier)){
                    if(count($data)!=count($identifier)) {
                       echo json_encode(array("status" => "error","message" => "size of identifier and data shoule have same size"));
                       return;
                    }
                }else{
                    echo json_encode(array("status" => "error","message" => "specify referece words"));
                    return;
                }     

                if($refkey=="") { 
                    echo json_encode(array("status" => "error","message" => "reference key is required"));
                    return;
                }
                if(empty($language)) { 
                    $language = "en";
                }

                if(empty($type)) { 
                    $type = "single";
                }

                if($documentSpace=="") { 
                    $documentSpace = "3words".$language;
                }
                $refkey = md5($refkey);
                $ar["type"] = $type;
                $ar["identifier"] = $identifier;
                $ar["data"] = $data;
                $ar["compareIde"] = $compareIde;
                $ar["compareData"] = $compareData;
                $ar["refkey"] = $refkey;
                $ar["documentlanguage"] = $language;
                $ar["documentSpace"] = $documentSpace;
            } else {
                echo json_encode(array("status" => "error","message" => "specify data"));
                return;
            }  
            //var_dump(json_encode($ar));exit;
            $redis->lpush("3wordsqueue", json_encode($ar));
            $t=null;
            $tmpObj = "";
            while($t === null) {
                $t = $redis->lpop("3wordsanswers_".$refkey);
                if($t != null) {
                    $tmpObj = json_decode($t);
                    echo json_encode(array("status" => "ok","data" => $tmpObj));
                    return;
                }
                sleep(1);
            }     
            echo json_encode(array("error" => "true") );
            return;
        } else {
            echo "error";
        }
    }

    public function getAllPublicAndOwnNorms() {
        $mongo = new Mongo();
        $db = $mongo->semantic;
        $table = $db->listNorms;
        $listNames = $table->find();
        $nameList = array();
        foreach($listNames as $l) {
	    if(!empty($l['public_access']) && ($l['public_access']=="true" || $l['public_access'] == '1'))
            array_push($nameList, substr($l['name'],1));
        }
        echo json_encode($nameList);
    }

    public function getAllPublicPrediction() {
	$mongo = new Mongo();
        $db = $mongo->semantic;
        $listtable = $db->listNames;
        $allpredictions=array();
        $listDetails = $listtable->find(array("public_access" => "true"));

        foreach ($listDetails as $listdet) {
            //$allpredictions[$i]=$listdet;
            array_push($allpredictions, substr($listdet['name'],1));
        }

        echo json_encode($allpredictions);
    }

public function get3wordsPropertyNew() {
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        $data = (array) $this->input->post(NULL,TRUE);
        ini_set('max_execution_time', 3000);
        if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
            $params = $this->input->post("data");
            if(count($params)) {

                foreach ($params as $key => $bunch) {
                    foreach ($bunch as $jKey => $data) {
                        if ($data["singlemultiple"] == "singletext") {
                            $word1 = $data["word1"];
                            $word2 = $data["word2"];
                            $refword = $data["ref1"];
                            $refkey = $data["refkey"];
                            $word2 = $this->parseWord($word2);
                            $refword = "_ref".$refword;
                            $grid_id = $data["documentid"];
                            $documentlanguage = $data["documentlanguage"];
                            $t = NULL;

                            $ar = array(
                                "singlemultiple" => "singletext",
                                "word1" => $word1,
                                "word2" => $word2,
                                "refword"=>$refword ,
                                "refkey" => $refkey,
                                "documentid" => $grid_id,
                                "documentlanguage" => $documentlanguage);

                        }
	
			if (isset($data['parameters'])) {
				$ar['parameters'] = json_encode($data['parameters']);
			}
			/*if (isset($data['getPropertyShow'])) {
				$ar['getPropertyShow'] = json_encode($data['getPropertyShow']);
			}
			
			if (isset($data['mapPredictions2Labels'])) {
                                $ar['mapPredictions2Labels'] = $data['mapPredictions2Labels'];
                        }*/
//echo "<pre>"; print_r(json_encode($ar)); exit;
                        $redis->lpush("matlabqueue", json_encode($ar));
                    }
                }
                $tmpObj = [];
                 foreach ($params as $key => $bunch) {
                    foreach ($bunch as $jKey => $data) {
			${$key.$jKey} = null;

                        while(${$key.$jKey} === null) {
                            ${$key.$jKey} = $redis->lpop("answerqueue_".$data["refkey"]);
                            //log_message('error', "got " . print_r($t, true));
                            if(${$key.$jKey} != null) {
                                $tmpResult = json_decode(${$key.$jKey});
                                if(isset($tmpResult->refkey)) {
                                    $tmpObj[$key][$jKey] = $tmpResult->answer;
                                } else {
                                    $tmpObj[$key][$jKey] = '';
                                }
                            }
                            sleep(1);   
                        }

                    }
                }
                echo json_encode($tmpObj);
            return;
            } else {
                echo "error";
            }
        }
    }
    public function get3wordsProperty() {
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        $data = (array) $this->input->post(NULL,TRUE);
        ini_set('max_execution_time', 600);        
        if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
//echo json_encode(['test']); exit;
//echo "<pre>"; print_r(($this->input->post("singlemultiple")); exit;            
            if($this->input->post("singlemultiple")) {
        
                if($this->input->post("singlemultiple")=="singletext") {
                    $word1 = $this->input->post("word1");
                    $word2 = $this->input->post("word2");
                    $refword = $this->input->post("ref1");
                    $refkey = $this->input->post("refkey");
                    $word2 = $this->parseWord($word2);
                    $refword = "_ref".$refword;
		    $grid_id = $this->input->post("documentid");
                    $documentlanguage = $this->input->post("documentlanguage");
                    $t = NULL;

                    $ar = array(
                        "singlemultiple" => "singletext", 
                        "word1" => $word1, 
                        "word2" => $word2, 
                        "refword"=>$refword , 
                        "refkey" => $refkey, 
                        "documentid" => $grid_id, 
                        "documentlanguage" => $documentlanguage);
                    
                } else if($this->input->post("singlemultiple")=="multipletext") {
                    
                    $wordset1 = $this->input->post("wordset1");
                    $refwordset1 = $this->input->post("refwordset1");
                    $refkey = $this->input->post("refkey");
                    $word5 = $this->input->post("word5");
                    $word2 = $this->parseWord($word5);
 		    $grid_id = $this->input->post("documentid");
                    $documentlanguage = $this->input->post("documentlanguage");
                    $t = NULL;


                    $ar = array("singlemultiple" => "multipletext", "wordset1" => $wordset1, "refwordset1" => $refwordset1, "word2" => $word2, "refkey" => $refkey, "documentid" => $grid_id, "documentlanguage" => $documentlanguage);

                    if (isset($data['parameters'])) {
                          $ar['parameters'] = json_encode($data['parameters']);
                    }

                }
//echo "<pre>"; print_r(json_encode($ar)); exit;
                $redis->lpush("matlabqueue", json_encode($ar));
                
		$t=null;
$tmpObj = "";
            while($t === null) {
                $t = $redis->lpop("answerqueue_".$refkey);
                //log_message('error', "got " . print_r($t, true));
                if($t != null) {
                    $tmpObj = json_decode($t);
                    if(isset($tmpObj->refkey)) {
                        echo $t;
                    } else {
                        echo json_encode(array("error" => "true") );
                    }
                    return;
                }
                sleep(1);
            }
            echo json_encode(array("error" => "true") );
            return;
            } else {
                echo "error";
            }
        }
    }

    public function get3wordsPropertyWD() {
        $authentication=$this->authenticateAPIRequest($_SERVER["HTTP_PUBLIC"],$_SERVER["HTTP_HASH"]);
        $data = (array) $this->input->post(NULL,TRUE);
        ini_set('max_execution_time', 600);        
        if($authentication) {
            $redis = new Predis\Client($this->host);
            $ar=array();
            
            if($this->input->post("singlemultiple")) {
        
                if($this->input->post("singlemultiple")=="singletext") {
                    $word1 = $this->input->post("word1");
                    $word2 = $this->input->post("word2");
                    $refword = $this->input->post("ref1");
                    $refkey = $this->input->post("refkey");
                    $word2 = $this->parseWord($word2);
                    $refword = "_ref".$refword;
                    $grid_id = $this->input->post("documentid");
                    $documentlanguage = $this->input->post("documentlanguage");
                    $t = NULL;

                    $ar = array(
                        "singlemultiple" => "singletext", 
                        "word1" => $word1, 
                        "word2" => $word2, 
                        "refword"=>$refword , 
                        "refkey" => $refkey, 
                        "documentid" => $grid_id, 
                        "documentlanguage" => $documentlanguage);
                    
                } else if($this->input->post("singlemultiple")=="multipletext") {
                    
                    $wordset1 = $this->input->post("wordset1");
                    $refwordset1 = $this->input->post("refwordset1");
                    $refkey = $this->input->post("refkey");
                    $word5 = $this->input->post("word5");
                    $word2 = $this->parseWord($word5);
                    $grid_id = $this->input->post("documentid");
                    $documentlanguage = $this->input->post("documentlanguage");
                    $t = NULL;

                    $ar = array("singlemultiple" => "multipletext", "wordset1" => $wordset1, "refwordset1" => $refwordset1, "word2" => $word2, "refkey" => $refkey, "documentid" => $grid_id, "documentlanguage" => $documentlanguage);
      
                    if (isset($data['parameters'])) {
			   if (isset($data['parameters']['mapPredictions2Labels'])) {
                                foreach ($data['parameters']['mapPredictions2Labels'] as $key => $value)
                                        $data['parameters']['mapPredictions2Labels'][$key] = (int) $value;
                          }
                          $ar['parameters'] = json_encode($data['parameters']);

                    }

                }
//echo "<pre>"; print_r(json_encode($ar)); exit;

                $redis->lpush("matlabqueue", json_encode($ar));
                
                $t=null;
                $tmpObj = "";
                while($t === null) {
                    $t = $redis->lpop("answerqueue_".$refkey);
                    //log_message('error', "got " . print_r($t, true));
                    if($t != null) {
//echo "<pre>"; print_t($t); exit;
                        $tmpObj = json_decode($t);
                        if(isset($tmpObj->refkey)) {
                            echo $t;
                        } else {
                            echo json_encode(array("error" => "true") );
                        }
                        return;
                    }
                    sleep(1);
                }
                echo json_encode(array("error" => "true") );
                return;
            } else {
                echo "error";
            }
        }
    }

    public function getAllWdPublicPrediction() {
        $mongo = new Mongo();
        $db = $mongo->semantic;
        $listtable = $db->listNames;
        $allpredictions=array();
        $listDetails = $listtable->find(array("wd_public_access" => "true"));

        foreach ($listDetails as $listdet) {
            //$allpredictions[$i]=$listdet;
            array_push($allpredictions, substr($listdet['name'],1));
        }
        echo json_encode($allpredictions);
    }
}

