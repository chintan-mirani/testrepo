<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Mailer\Email;
use Cake\Core\Configure;
use org\jsonrpcphp\JsonRPCClient;
use Cake\Http\Client;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;
use FrankFoerster\Bitly\Exception\BitlyException;
use FrankFoerster\Bitly\UrlShortener;

class LimeSurveyComponent extends Component
{
    public $components = array('Auth');

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
    }

    public function loadoModel($model)
    {
        $this->controller->loadModel($model);
        return $this->controller->$model;
    }
     /**
     * Function for getRPCClient
     *
     * @param string $userType
     *
     * @return array
     */
    function getRPCClient($userType = 'user')
    {
        $user =  $this->Auth->user('survey_username');
        $pass =  $this->Auth->user('survey_password');
        if($userType  == 'admin'){
            $user =  Configure::read('Application.LimeSurvey.admin_username');
            $pass =  Configure::read('Application.LimeSurvey.admin_password');
        }
        $url = ( isset($data) && isset($data['url']))?$data['url']:Configure::read('Application.LimeSurvey.baseUrl');
        $myJSONRPCClient = new JsonRPCClient( $url.'/admin/remotecontrol', false );
        $sessionKey = $myJSONRPCClient->get_session_key( $user , $pass );
        return [$myJSONRPCClient, $sessionKey];
    }
    /**
     * Function for generate Plot From LimeSurveyResponse
     *
     * @param Object $request
     * @param array $questions
     * @param array $allResponses
     * @param integer $surveyId
     * @param integer $questionId
     * @param array $loggedInUser
     * @param string $token
     *
     * @return array
     */
    public function generatePlotFromLimeSurveyResponse($request, $questions, $allResponses, $surveyId, $questionId, $loggedInUser, $token = '')
    {
        $participants = $this->loadoModel('Participants');
        $participantList = $participants->listParticipants($surveyId);
        $limeResponses = $allResponses[$questionId];

        $responseId = max(array_keys($limeResponses['responses']));
        $mode = $request->getData('mode');

        $postData = $request->getData();

        $type = $postData['type'] = isset($postData['type']) ? $postData['type'] : 'simple';
        $analyzeType = strtolower($type);

        $plotCloudType = $postData['plotCloudType'] = isset($postData['plotCloudType']) ? $postData['plotCloudType'] : 'words';
        $plotCluster = $postData['plotCluster'] = (isset($postData['plotCluster']) && $postData['plotCluster']) ? 1 : 0;
        $plotWordcloud = $postData['plotWordcloud'] = (isset($postData['plotWordcloud']) && $postData['plotWordcloud']) ? 1 : 0;
        $plotTestType = $postData['plotTestType'] = isset($postData['plotTestType']) ? $postData['plotTestType'] : [];
        $plotWordcloudType = $postData['plotWordcloudType'] = isset($postData['plotWordcloudType']) ? $postData['plotWordcloudType'] : '';
        $valence = $postData['valence'] = isset($postData['valence']) ? $postData['valence'] : '';
        $prediction = $postData['prediction'] = isset($postData['prediction']) ? $postData['prediction'] : '';
        $diagnosUserId = $postData['diagnosUserId'] = isset($postData['diagnosUserId']) ? $postData['diagnosUserId'] : '';
        $xAxisSelection = $postData['x-axis-selection'] = isset($postData['x-axis-selection']) ? $postData['x-axis-selection'] : '';
        $yAxisSelection = $postData['y-axis-selection'] = isset($postData['y-axis-selection']) ? $postData['y-axis-selection'] : '';
        $zAxisSelection = $postData['z-axis-selection'] = isset($postData['z-axis-selection']) ? $postData['z-axis-selection'] : '';
        $network = $postData['network'] = isset($postData['network']) ? $postData['network'] : 0;
        $plotNetworkModel = $postData['prediction'] = isset($postData['prediction']) ? $postData['prediction'][$questionId] : '';
        $question = $questions[$questionId];

        //find all the question id by logged user
        $loggedUserAllAnswerIds = [];
        $loggedInUserId = '';
        if ($loggedInUser){
            $userId = $loggedInUser['id'];
        }

        $type = 'simple';
        $allowEmptyData = false;
        $needToSave = false;

        if (isset($limeResponses['single_url']) && !empty($limeResponses['single_url'])) {
            $needApiCall = false;
            $img_url = $limeResponses['single_url'];
        } else {
            $needApiCall = true;
            $img_url = '';
        }

        if(strtolower($mode) == 'save' || empty($img_url)) {
            $needToSave = true;
            $mode = 'save';
        }

        if (strtolower($mode) == 'save' || empty($img_url) || strtolower($mode) == 'updateplot') {
            $needApiCall = true;
        }

        //$responses = array();
        $needApiCall = true;
        if ($needApiCall == true) {

            $userIde = array();
            $userIdeNames = array();
            $data = [];
            $xaxel = [];
            $ideArr = [];
            foreach ($limeResponses['responses'] as $userId => $res) {
                // Only integer or rating scale questions
                if ($question['type'] == 'integer' || ($question['type'] == 'category' && isset($question['subQuestion']))) {
                    $allowEmptyData = true;
                    if (is_array($res)) {
                        foreach ($res as $catval) {
                            $xaxel[] = $catval+1;
                        }
                    } else {
                        $xaxel[] = ($res);
                    }
                } else if ($question['type'] == 'category') {
                    if (is_array($res)) {
                        foreach ($res as $catval) {
                            $xaxel[] = $catval+1;
                        }
                    } else {
                        $xaxel[] = ($res);
                    }
                } else {
                    $data[] = !empty($res) ? (string) $res : "";
                }

                if (is_array($res)) {
                    for ($i=0;$i<count($res);$i++) {
                    $ideArr[] = '_userreference'.$userId;
                    $userIde[] = 'user'.$userId;
                    $userIdeNames[] = (isset($participantList[$userId]) ? $participantList[$userId] : '');
                    }
                } else {
                    $ideArr[] = '_userreference'.$userId;
                    $userIde[] = 'user'.$userId;
                    $userIdeNames[] = (isset($participantList[$userId]) ? $participantList[$userId] : '');
                }
            }

            $responses =  [
                'data' => $data,
                'identifier' => $ideArr,
                'language' => 'en',//$question->language,
                'refkey' => 'refdev'.$questionId,
                'type' => $type,
                'userIdentifier' => $userIde,
                'userIdeNames' => $userIdeNames,
                'questionId' => $questionId
            ];
            $responses['yaxel'] = [];
            $responses['zaxel'] = [];
            $responses['xaxel'] = $xaxel;

            $responses['advanceParam'] = [];


            if ($mode) {
                $responses['mode'] = $mode;
            }

            if ($plotCloudType == 'diagnos') {
                $responses['plotCloudType'] =  "diagnos";
                $responses['plotProperty'] =  $prediction;
                $responses['advanceParam']['diagnosUserId'] = $diagnosUserId;
            }

            if ($loggedInUserId) {
                $responses['userCallId'] = [$loggedInUserId];
            }

            $responses['type'] =  $type;
            $responses['plotCloudType'] =  $plotCloudType;
            $responses['plotCluster'] =  $plotCluster;
            $responses['plotWordcloud'] =  $plotWordcloud;
            $responses['plotTestType'] =  $plotTestType;
            $responses['documentSpace'] = Configure::read('Application.documentSpace').'en_'.$surveyId;

            if ($network && count($plotNetworkModel)) {
                $responses['parameters'] = [

                    'plotOnCircle' => 1,
                    'plotNetWorkAnalysis' => 1,
                    'plotNetworkModel' => $plotNetworkModel[0],
                    'plotColorCodesFor' => 'value',
                    'getPropertyShow' => 'pred2percentage',
                    'plotTestType' => 'property',
                    'plotProperty'=> $plotNetworkModel[0],
                    'plotSignificantColors' => 9
                ];
                $responses['plotWordcloud'] = 1;
            }

            if (count($responses['plotTestType']) == 0) {
                unset($responses['plotTestType']);
            }
            if ($analyzeType == 'x-axis') {
                $responses['valence'] = ['','',''];

                if ($xAxisSelection) {
                    $responses['plotProperty'] = ['', '', ''];
                    if ($xAxisSelection == '_predvalence') {
                        $responses['valence'][0] =  '_predvalence';
                    } else {
                        $xquestion = $questions[$xAxisSelection];
                        // If category question selected in x- axis

                        if ($xquestion['type'] == 'category') {

                            $nominalLabels = [];
                            foreach ($xquestion['plotNominalLabels'] as $values) {
                                $nominalLabels[] = $values['label'];
                            }
                            $responses['plotWordcloudType'] = 'nominal';
                            $responses['plotNominalLabels'] =  $nominalLabels;
                        } else {
                            $responses['advanceParam']['units'][] = 'N/A';
                        }
                        $responses['xaxel'] = array_values($allResponses[$xAxisSelection]['responses']);
                    }
                }

                if ($yAxisSelection) {
                    if ($yAxisSelection == '_predvalence') {
                        $responses['valence'][1] =  '_predvalence';
                    } else {
                        $yquestion = $questions[$yAxisSelection];
                        $responses['advanceParam']['units'][] = 'N/A';
                        $responses['yaxel'] = array_values($allResponses[$yAxisSelection]['responses']);
                    }
                }

                if ($zAxisSelection) {
                    if ($zAxisSelection == '_predvalence') {
                        $responses['valence'][2] =  '_predvalence';
                    } else {
                        $zquestion = $questions[$zAxisSelection];
                        $responses['advanceParam']['units'][] = 'N/A';
                        $responses['zaxel'] = array_values($allResponses[$zAxisSelection]['responses']);
                    }
                }
            }
            // If only category question not scale rating question
            if ($question['type'] == 'category') {

                $allowEmptyData = true;
                $responses['plotWordcloudType'] = 'nominal';

                foreach ($question['plotNominalLabels'] as $key => $value) {
                    $responses['plotNominalLabels'][] = $value['label'];
                }

                $responses['data'] = [];
            }
        }

        // Remove plotProperty if no any plotProperty selected in x,y, and z axis
        if (isset($responses['plotProperty']) && is_array($responses['plotProperty'])) {
            $responses['plotProperty'] = array_filter($responses['plotProperty']);

            if (count($responses['plotProperty']) == 0) {
                unset($responses['plotProperty']);
            }
        }

        // Remove valance if no any sentiment selected in x,y, and z axis
        if (isset($responses['valence']) && count(array_filter($responses['valence'])) == 0) {
            unset($responses['valence']);
        }

        $responses['allowEmptyData'] = $allowEmptyData;
        $img_url = ($img_url == '')?$request->getAttribute('webroot').'img/ajax-loader.gif':$img_url;
        $img_url = $this->viewPlotImage($img_url);

        return array(
            $img_url,
            $needApiCall,
            $responses,
            $responseId,
            $needToSave
        );
    }
     /**
     * Function for view plot image
     *
     * @param string $plotUrl
     * @param array $$config
     *
     * @return html
     */
    public function viewPlotImage($plotUrl, $config = array())
    {
        $config += [
            'url' => 'javascript:void(0)',
            'currentFigureType' => null,
            'showPvalue' => true
        ];

        $realImage = explode('~',$plotUrl);

        $real_image_url = explode('~', $plotUrl);

        $imgUrls = explode('|', $real_image_url[0]);
        $count = count($imgUrls);

        if($count == 2){
            $class = 'width50';
        }elseif($count == 3){
            $class = 'width33';
        }else{
            $class = '';
        }
        $return = '';
        $return .= '<a href="'.$config["url"].'">';
        $i = 0;
        foreach($imgUrls as $img_url){
            $return .= '<div class="center '.$class .'">';
            if($count == 2 ){
            //$return .= ($i == 0 )?'<span>'.$question->left_que.'</span>':'<span>'.$question->right_que.'</span>';
            }
            $return .= '<img class="" src="'.$img_url.'">';
            $return .= '</div>';
            $i++;
        }
        $return .= '</a>';

        if ($config['showPvalue']) {
            if (!empty($real_image_url[1])){
                $return .=  '<span class="pvalue">'.$real_image_url[1].'</span>';
            }
        }
        return $return;
    }
     /**
     * Function for get Plots
     *
     * @param array $params
     * @param array $loggedInUser
     *
     * @return array
     */
    public function getPlots($params, $loggedInUser)
    {
        ini_set('max_execution_time', 600);

        $responses = $params['data'];
        $responses = $this->repairData($responses);
        if ($responses) {
            $this->autoRender = false;
            $http = new Client();

            $mode = isset($responses['mode']) ? $responses['mode'] : '';
            unset($responses['mode']);
            //$responses['documentSpace'] = Configure::read('Application.documentSpace');
            // set new login user in param
            if($loggedInUser) {
                $name = $loggedInUser['first_name'];
                $name .= ($loggedInUser['last_name'] != '') ? ' '.$loggedInUser['last_name'] : '';
                $responses['userCallId']  = $name;
            }

            $redis  = new \Redis();
    $redis->connect('redis', 6379);
    $redis->rpush('wdQueue', json_encode($responses, true));

    $t=null;

    $tmpObj = "";
    while($t === null) {
        $t = $redis->lpop("wdQueueanswer");
        echo "($t)"; 
        if($t != null) {
            echo "[]";
            $tmpObj = json_decode($t);
            echo json_encode(array("status" => "ok","data" => "No result"));
            return;
        }
        var_dump($t);
        sleep(1);
    }


    echo "<pre>"; var_dump($tmpObj); exit;
            // Simple get with querystring & additional headers
            $response = $http->post(Configure::read('Application.SemanticExcel.Api.3woords'),
                    (string)http_build_query($responses),
                [
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'hash' => Configure::read('Application.SemanticExcel.hash'),
                        'public' => Configure::read('Application.SemanticExcel.public')
                    ]
                ]
            );
            $res = $response->body();
            //echo "<pre>"; print_r($res); exit;
            $result = json_decode($res);

            if(!empty($result) && $result->status == 'ok' && $result->data->results != 'Error during calculating'){
                $real_image_url = explode('~', $result->data->results);
                $imgUrls = explode('|', $real_image_url[0]);

                // Get images from semantic excel
                // TODO: Remove images from semanticexcel
                $result->data->needToSave = false;
                if ($mode && strtolower($mode) == 'save') {
                    $result->data->needToSave = true;
                    foreach($imgUrls as $img_url){
                        $data = file_get_contents($img_url);
                        $name = basename($img_url);
                        file_put_contents(WWW_ROOT.'3words_plots/'.$name, $data);
                    }
                    $result->data->results = str_replace('http://www.semanticexcel.com/',Router::url('/', true),$result->data->results);
                } else {
                    $result->data->results = $result->data->results;
                }

            }
            return $result;
        }
    }
    /**
     * Function for get Property
     *
     * @param array $params
     * @param array $loggedInUser
     *
     * @return array
     */
    public function getProperty($params, $loggedInUser)
    {
        ini_set('max_execution_time', 600);

        $responses = $params['data'];
        if ($responses) {
            $this->autoRender = false;
            $http = new Client();

            $mode = isset($responses['mode']) ? $responses['mode'] : '';
            unset($responses['mode']);
            //$responses['documentSpace'] = Configure::read('Application.documentSpace');
            // set new login user in param
            if($loggedInUser) {
                $name = $loggedInUser['first_name'];
                $name .= ($loggedInUser['last_name'] != '') ? ' '.$loggedInUser['last_name'] : '';
                $responses['userCallId']  = $name;
            }
            // Simple get with querystring & additional headers

            $response = $http->post(Configure::read('SemanticExcel.Api.3woords'),
                    (string)http_build_query($responses),
                [
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'hash' => Configure::read('SemanticExcel.hash'),
                        'public' => Configure::read('SemanticExcel.public')
                    ]
                ]
            );
            $res = $response->body();
            $result = json_decode($res);

            if(!empty($result) && $result->status == 'ok' && $result->data->results != 'Error during calculating'){
                $real_image_url = explode('~', $result->data->results);
                $imgUrls = explode('|', $real_image_url[0]);

                // Get images from semantic excel
                // TODO: Remove images from semanticexcel
                $result->data->needToSave = false;
                if ($mode && strtolower($mode) == 'save') {
                    $result->data->needToSave = true;
                    foreach($imgUrls as $img_url){
                        $data = file_get_contents($img_url);
                        $name = basename($img_url);
                        file_put_contents(WWW_ROOT.'3words_plots/'.$name, $data);
                    }
                    $result->data->results = str_replace('http://www.semanticexcel.com/',Router::url('/', true),$result->data->results);
                } else {
                    $result->data->results = $result->data->results;
                }

            }
            return $result;
        }
    }
     /**
     * Function for preapre survey question
     *
     * @param array $surveyXmlArray
     *
     * @return array
     */
    public function prepareSurveyQuestion($surveyXmlArray)
    {
        $questionList = [];

        if (isset($surveyXmlArray['questionnaire']['section'])) {

            // Hook for single group in survey
            if (isset($surveyXmlArray['questionnaire']['section']['question'])) {
                $surveyXmlArray['questionnaire']['section'][] = ['question' => $surveyXmlArray['questionnaire']['section']['question']];
                unset($surveyXmlArray['questionnaire']['section']['question']);
            }

            foreach ($surveyXmlArray['questionnaire']['section'] as $question) {

                // Hook for single question in a group
                if (isset($question['question']['response'])) {
                    $question['question'][] = $question['question'];
                    unset($question['question']['response']);
                }

            foreach ($question['question'] as $response) {
                if (isset($response['response'])) {

                    $option = array(
                        'title' => (isset($response['text']) ? $response['text'] : '')
                    );

                    // Save group name
                    if (isset($question['sectionInfo']['text'])) {
                        $option['groupName'] = $question['sectionInfo']['text'];
                    }
                    if (isset($response['response']['fixed']['category'])) {
                        $option['type'] = 'category';
                        foreach ($response['response']['fixed']['category'] as $key => $category) {

                            $option['plotNominalLabels'][] = array (
                                'id' => $key,
                                'label' => $category['label'],
                                'value' => $category['value']
                            );
                        }

                        if (isset($response['subQuestion'])) {
                            foreach ($response['subQuestion'] as $key => $subQuestion) {
                                $subQueCode = $subQuestion['@attributes']['varName'];
                                $option['subQuestion'][] = $subQueCode;
                                $option['subQuestionText'][$subQueCode] = $subQuestion['text'];
                            }
                        }
                    } elseif ($response['response']['free']['format'] == 'integer') {
                        $option['type'] = 'integer';
                    } else if ($response['response']['free']['format'] == 'longtext') {
                        $option['type'] = 'longtext';
                    } else {
                        $option['type'] = 'text';
                        foreach ($response['subQuestion'] as $key => $subQuestion) {
                            $option['subQuestion'][] = $subQuestion['@attributes']['varName'];
                        }
                    }
                    $questionList[$response['response']['@attributes']['varName']] = $option;
                }
            }
        }
        }
        return $questionList;
    }
    /**
     * Function for preapre survey answers
     *
     * @param array $questions
     * @param array $responses
     * @param array $answer
     *
     * @return array
     */
    public function prepareSurveyAnswer($questions, $responses, $answer)
    {
        foreach ($questions as $var => $question) {
            $answer [$var]['title'] = $question['title'];
            $answer [$var]['type'] = $question['type'];
            $answer [$var]['single_url'] = '';
            $answer [$var]['tokens'][] = $responses['token'];

            $words = '';
            if ($question['type'] == 'category') {
                foreach ($question['plotNominalLabels'] as $labels) {
                    if ($responses[$var] == $labels['value']) {
                        $words = $labels['id'];
                    }
                }
            } else if ($question['type'] == 'integer') {
                $words = $responses[$var];
            } else if ($question['type'] == 'text' && isset($question['subQuestion'])) {
                $responseWords = [];
                foreach ($question['subQuestion'] as $subquestion) {
                    if (array_key_exists($subquestion, $responses)) {
                        $responseWords[] = $responses[$subquestion];
                    } else {
                        $subQ = explode('_', $subquestion);
                        $responseWords[] = $responses[$subQ[0].'['.$subQ[1].']'];
                    }
                }
                $words = implode(',', $responseWords);
                unset($responseWords);
            } else if ($question['type'] == 'longtext') {
                $words = $responses[$var];
            }

            $answer[$var]['responses'][$responses['token']] = trim(strip_tags($words));
        }
        return $answer;
    }
    /**
     * Function for preapre survey answers new
     *
     * @param array $questions
     * @param array $responses
     *
     * @return array
     */
    public function prepareSurveyAnswerNew($questions, $responses)
    {
        $answer = [];
        foreach ($questions as $queCode => $question) {

            if ($question['type'] == 'category') {
                    $responseWords = [];
                    if (isset($question['subQuestion'])) {
                        foreach ($question['subQuestion'] as $subquestion) {
                            if (array_key_exists($subquestion, $responses)) {
                                $responseWords[$subquestion] = $responses[$subquestion];
                            } else {
                                $subQ = explode('_', $subquestion);
                                $responseWords[$subquestion] = $responses[$subQ[0].'['.$subQ[1].']'];
                            }
                        }
                        $words = $responseWords;
                    } else {
                        $words = $responses[$queCode];
                    }
            } else if ($question['type'] == 'integer') {
                $words = $responses[$queCode];
            } else if (($question['type'] == 'text' || $question['type'] == 'longtext') && isset($question['subQuestion'])) {
                $responseWords = [];
                foreach ($question['subQuestion'] as $subquestion) {
                    if (array_key_exists($subquestion, $responses)) {
                        $responseWords[$subquestion] = $responses[$subquestion];
                    } else {
                        $subQ = explode('_', $subquestion);
                        $responseWords[$subquestion] = $responses[$subQ[0].'['.$subQ[1].']'];
                    }
                }
                $words = implode(', ',$responseWords);

            } else {
                $words = $responses[$queCode];
            }
            $answer[$queCode] = $words;
        }
        return $answer;
    }
    /**
     * Function for defalut settings of the plot
     *
     * @return array
     */
    public function getPlotDefaultSetting()
    {
        return  [
            'plotCluster' => 0,
            'plotCloudType' => 'words',
            'plotWordcloud' => 1
        ];
    }
    /**
     * Function for prediction from 3woords
     *
     * @return array
     */
    public function get3Woordsprediction()
    {
        $http = new Client();
        $response = $http->post('http://semanticexcel.com/index.php/apiv1/getAllPublicPrediction',
                    '',
                [
                    'headers' => [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'hash' => 'e249c439ed7697df2a4b045d97d4b9b7e1854c3ff8dd668c779013653913572e',
                        'public' => '3441df0babc2a2dda551d7cd39fb235bc4e09cd1e4556bf261bb49188f548348'
                    ]
                ]
            );
            $res = $response->body();
        return $result = json_decode($res);
    }
    /**
     * Function for sync Questions from limesurvey
     *
     * @param integer $surveyId
     *
     * @return boolean
     */
    public function syncQuestions($surveyId)
    {
        list($myJSONRPCClient, $sessionKey) = $this->getRPCClient();
        $xmlArray = $myJSONRPCClient->export_survey_question($sessionKey, $surveyId);

        if (strtolower($xmlArray['status']) == 'ok') {

            $questionList = $this->prepareSurveyQuestion(['questionnaire' => $xmlArray['survey']]);
            $limeSurveyQuestions = TableRegistry::get('LimeSurveyQuestions');
            $limeSurvey = $limeSurveyQuestions
                ->find('all')
                ->where(['survey_id' => $surveyId])
                ->first();


            $title = $xmlArray['survey']['title'];
            $data = [
                'survey_id' => $surveyId,
                'name'=> $title,
                'question'  => json_encode($questionList),
                'survey_xml_data' => json_encode($xmlArray),
            ];

            if ($limeSurvey) {
                $limeSurvey = $limeSurveyQuestions->patchEntity($limeSurvey, $data);
            } else {
                $limeSurvey = $limeSurveyQuestions->newEntity($data);
            }

            return ($limeSurveyQuestions->save($limeSurvey));
        }
    }
    /**
     * Function for sync Answers from limesurvey
     *
     * @param integer $surveyId
     *
     * @return boolean
     */
    public function syncAnswers($surveyId)
    {
        $limeSurveyQuestions = $this->loadoModel('LimeSurveyQuestions');
        $particiapnts = $this->loadoModel('Participants');

        $limeSurveyQuestions = TableRegistry::get('LimeSurveyQuestions');
        $limeSurveyResponse = TableRegistry::get('LimeSurveyResponse');

        list($myJSONRPCClient, $sessionKey) = $this->getRPCClient();
        $responses = $myJSONRPCClient->export_responses($sessionKey, $surveyId, 'json', '', 'complete');
        if (!is_array($responses)) {
            $responses = json_decode(base64_decode($responses), true);
        } else {
            $responses = [];
        }
        $myJSONRPCClient->release_session_key( $sessionKey );

        $answer = [];
        $questionList = $limeSurveyQuestions->getSurveyQuestionsList($surveyId);


        if (count($responses)) {
            $allAnswers = [];
            foreach ($responses['responses'] as $row) {
                foreach ($row as $response) {
                   $answer[$response['token']]['responses'] = $this->prepareSurveyAnswerNew($questionList, $response);
                   $allAnswers[$response['token']]['responses'] = $answer[$response['token']]['responses'];
                   $allAnswers[$response['token']]['submitted_date'] = $response['submitdate'];
                }
            }
            if (count($allAnswers)) {
                $participantList = $particiapnts->getNotSynced($surveyId);
                foreach ($participantList as $participant) {
                    if (array_key_exists($participant->token, $allAnswers)) {
                        $participant->response = json_encode($allAnswers[$participant->token]['responses']);
                        $participant->response_time = date(
                            'Y-m-d H:i:s',
                            strtotime($allAnswers[$participant->token]['submitted_date'])
                        );
                        $particiapnts->save($participant);
                    }
                }
            }
        }
        $surveyResponse = $limeSurveyResponse->getSurveyResponse($surveyId);

      return true;
    }
    /**
     * Function for get Participants listing
     *
     * @param integer $surveyId
     * @param boolean $dropdown
     *
     * @return array
     */
    public function listParticipants($surveyId, $dropdown = false)
    {
        list($myJSONRPCClient, $sessionKey) = $this->getRPCClient();
        $participants = $myJSONRPCClient->list_participants($sessionKey, $surveyId);
        $myJSONRPCClient->release_session_key( $sessionKey );

        $participantList = [];
        if ($dropdown) {
            foreach ($participants as $record) {
                $participantList[$record['token']] = $record['participant_info']['firstname'].' '.$record['participant_info']['lastname'];
            }
        } else {
            $participantList = $participants;
        }
        return $participantList;
    }
    /**
     * Function for get token wise response
     *
     * @param integer $surveyId
     * @param string $token
     *
     * @return array
     */
    public function tokenWiseResponse($surveyId, $token)
    {
        list($myJSONRPCClient, $sessionKey) = $this->getRPCClient();
        $responses = $myJSONRPCClient->export_responses_by_token($sessionKey, $surveyId, 'json', $token, '', 'complete');
        $responses = json_decode(base64_decode($responses), true);
        $myJSONRPCClient->release_session_key( $sessionKey );
        $answers = [];
        if (is_array($responses) && !empty($responses)) {
            $limeSurveyQuestions = TableRegistry::get('LimeSurveyQuestions');
            $surveyDetails = $limeSurveyQuestions->getSuveryDetails($surveyId);
            $questions = json_decode($surveyDetails['question'],true);

            foreach($responses['responses'] as $response) {
                foreach ($response as $key => $data) {
                    $answers = $this->prepareSurveyAnswer($questions, $data, $answers);
                }
            }
        }

        return $answers;
    }
     /**
     * Function for test filter
     *
     * @param string $string
     *
     * @return string
     */
    public function filterText($string)
    {
        $string = strip_tags($string);
        $string = trim(preg_replace('/\s+/', ' ', $string));
        $string =strlen($string) > 50 ? substr($string,0,50)."... " : $string;
        return $string;
    }

     /**
     * Function for apply settings on survey result
     *
     * @param integer $surveyId
     * @param array $allResponses
     * @param array $settings
     * @param array $questions
     * @param integer $surveyId
     *
     * @return array
     */
    public function applySetting($surveyId, $queResponse, $allResponses, $settings, $questions, $userId = false, $getPredValues = true)
        {
        $editSetting = $settings['edit_setting'];

        foreach ($queResponse as $id => $responses) {
            $queSetting = $editSetting[$id];
            $question = $questions[$id];
            $selectedUserId = $userId;
            $isSingleUser = ($selectedUserId) ? true : false;
            if (isset($queSetting['participant_id']) &&
                !empty($queSetting['participant_id']) && !$userId) {
                $isSingleUser = true;
                $selectedUserId = $queSetting['participant_id'];
                $responseData[$queSetting['participant_id']] = $responses['responses'][$queSetting['participant_id']];
                $responses['responses'] = $responseData;
                if ($question['type'] == 'integer') {
                    $responses['_all_response_'] = $responses['responses'][$queSetting['participant_id']];
                } else if ($question['type'] == 'longtext' || $question['type'] == 'text') {
                    $responses['_all_response_'] = $responses['responses'][$queSetting['participant_id']];
                    foreach ($responses['responses'] as $key => $record) {
                        $responses['responses'][$key] = str_replace(',', ' ', $record);
                    }
                } else if ($question['type'] == 'category') {

                    $categories = $responses['responses'][$queSetting['participant_id']];

                    if (isset($question['subQuestion'])) {
                        $scaleSum = array_sum($categories);
                        $responses['subSingleAnswers'] = $responses['responses'][$queSetting['participant_id']];
                        $responses['ratingScale'] = 'SINGLE_USER';
                        $responses['responses'][$queSetting['participant_id']] = $scaleSum;
                        $responses['_all_response_'][] = $scaleSum;
                    } else {
                        foreach ($questions[$id]['plotNominalLabels'] as $labels) {
                            if ($labels['value'] == $categories) {
                                    $responses['_all_response_'][] = $labels['label'];
                            }
                        }
                    }
                    $responses['_all_response_'] = implode(', ', $responses['_all_response_']);
                }
            } else {
                if ($question['type'] == 'integer') {
                    $responses['_all_response_'] = array_sum($responses['responses']);
                } else if ($question['type'] == 'longtext' || $question['type'] == 'text') {
                    $responses['_all_response_'] = implode(', ', $responses['responses']);
                    foreach ($responses['responses'] as $key => $record) {
                        $responses['responses'][$key] = str_replace(',', ' ', $record);
                    }
                } else if ($question['type'] == 'category') {
                    $responses['_all_response_'] = '';
                    $categoryString = [];
                    if ($isSingleUser && $selectedUserId) {
                        $categories = $responses['responses'][$selectedUserId];
                        if (isset($question['subQuestion'])) {
                            $scaleSum = array_sum($categories);
                            $responses['subSingleAnswers'] = $responses['responses'][$selectedUserId];
                            $responses['responses'][$selectedUserId] = $scaleSum;
                            $responses['ratingScale'] = 'SINGLE_USER';
                            $categoryString[] = $scaleSum;
                        } else {
                            $responses['_all_response_'] = [];
                            foreach ($questions[$id]['plotNominalLabels'] as $labels) {
                                if ($labels['value'] == $categories) {
                                    $categoryString[] = $labels['label'];
                                }
                            }
                        }
                        $responses['_all_response_'] = implode(', ', $categoryString);
                    } else {
                        $allCategoryResponses = [];
                        $scaleSum = 0;

                        if (isset($question['subQuestion'])) {
                            $responses['ratingScale'] = 'MULTI_USER';
                            foreach ($responses['responses'] as $key => $singleResponse) {

                                foreach ($singleResponse as $queCode => $value) {
                                    $responses['subMultiAnswers'][$queCode][] = $value;
                                }
                                $scaleSum += array_sum($singleResponse);
                                $responses['responseForPlot'][$key] = $scaleSum;
                                $responses['responses'][$key] = array_sum($singleResponse);
                            }

                            foreach ($responses['subMultiAnswers'] as $subQueCode => $subAnswers) {
                                $responses['subMultiAnswers'][$subQueCode] = array_count_values($subAnswers);
                            }
                            $meanValue = round(($scaleSum/count($responses['responses'])), 2);
                            $responses['_total_'] = $scaleSum;
                            $responses['_mean_'] = $meanValue;
                            $categoryString[] = ' Total = '.$scaleSum;
                            $categoryString[] = ' Mean = '.$meanValue;

                        } else {
                            foreach ($responses['responses'] as $singleResponse) {
                                $allCategoryResponses = array_merge($allCategoryResponses, explode(',', $singleResponse));
                            }
                            $cetegoryCounts = array_count_values($allCategoryResponses);
                            foreach ($questions[$id]['plotNominalLabels'] as $labels) {
                                if (isset($cetegoryCounts[$labels['value']])) {
                                    $categoryString[] = $labels['label'] .'('.$cetegoryCounts[$labels['value']].') ';
                                } else {
                                    //$categoryString[] = $labels['label'] . '(0) ';
                                }
                            }
                        }
                    }
                    $responses['_all_response_'] = implode(', ', $categoryString);
                }
            }

            if (isset($queSetting['selectedQue']) &&
                !empty($queSetting['selectedQue'])) {

                $addWord = $substractWord = [];
                $addNumber = $substractNumber = 0;
                $isAddSubStract = false;

                foreach ($queSetting['selectedQue'] as $qId => $mode) {

                    $selectedQueResponse = [];
                    // Get response of participant if participant is selected
                    if (isset($queSetting['participant_id']) &&
                    !empty($queSetting['participant_id'])) {
                        $selectedQueResponse[$queSetting['participant_id']] = $allResponses[$qId]['responses'][$queSetting['participant_id']];
                    // else all response
                    } else {
                        $selectedQueResponse = $allResponses[$qId]['responses'];
                    }
                    if ($question['type'] == 'integer') {

                        if (is_string($mode) && strtoupper($mode) == 'ADD')
                            $responses['_all_response_'] += array_sum($selectedQueResponse);
                        else if (is_string($mode) && strtoupper($mode == 'SUBSTRACT')) {
                            $responses['_all_response_'] -= array_sum($selectedQueResponse);
                        }

                    } else if ($question['type'] == 'longtext' || $question['type'] == 'text') {
                        $selectedQueResponseStr = implode(',',$selectedQueResponse);
                        $selectedQueResponseArr = explode(',', $selectedQueResponseStr);
                        if (is_string($mode) &&  strtoupper($mode) == 'ADD'){
                            $allResponse = explode(',', $responses['_all_response_']);
                            $allResponse = array_merge($allResponse, $selectedQueResponseArr);
                            $allResponse = array_filter($allResponse);
                            $allResponse = array_unique($allResponse);
                            $responses['_all_response_'] = implode(',', $allResponse);
                        }
                        else if (is_string($mode) && strtoupper($mode == 'SUBSTRACT')) {
                            $responses['_all_response_'] = str_replace($selectedQueResponseArr, '', $responses['_all_response_']);
                            $responses['_all_response_'] = implode(',',array_filter(explode(',', $responses['_all_response_'])));
                        }
                    } else if ($question['type'] == 'category') {

                       if (isset($question['subQuestion'])) {
                            $sum = 0;
                            $singleResponseSum = [];
                            foreach ($allResponses as $key => $allResponse) {
                                if($key ==  $qId){
                                    $singleResponseSum[$key] = $allResponse['responses'];
                                    if (is_array($singleResponseSum[$key])){
                                        foreach ($allResponse['responses'] as $key => $responseSum){
                                            if (is_string($mode)) {
                                                if (strtoupper($mode) == 'ADD') {
                                                    $responses['_total_'] += (is_array($responseSum)) ? array_sum($responseSum) : $responseSum;
                                                } else if (strtoupper($mode == 'SUBSTRACT')) {

                                                    $responses['_total_'] -= (is_array($responseSum)) ? array_sum($responseSum) : $responseSum;
                                                }
                                            } else if (is_array($mode)) {
                                                foreach ($mode as $subqId => $subMode) {
                                                    if ($subMode == 'ADD') {
                                                        $responses['_total_'] += $responseSum[$subqId];
                                                    } else if ($subMode == 'SUBSTRACT') {
                                                        $responses['_total_'] -= $responseSum[$subqId];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
                // Calculate sum and mean If category question only
                if ($question['type'] == 'category' && isset($responses['_total_'])) {
                    $responses['_all_response_'] = ' Total = '.$responses['_total_'];
                    // No need to calculate
                    /*if (!$isSingleUser) {
                        $meanValue = round(($responses['_total_']/count($responses['responses'])), 2);
                        $responses['_all_response_'] .= ', Mean = '.$meanValue;
                    }*/
                }
            }

            if (isset($queSetting['prediction']) &&
                !empty($queSetting['prediction']) && $getPredValues &&
                (array_key_exists('values', $queSetting['showField']) ||
                 array_key_exists('z_values', $queSetting['showField']) ||
                 array_key_exists('p_values', $queSetting['showField']) ||
                 array_key_exists('label', $queSetting['showField']))) {
                $identifierOrText = $responses['_all_response_'];
                $property = $queSetting['prediction'];
                $language = 'en';
                $documentSpace = 'managerWDLocal_chintan_'.$id;
                $http = new Client();

                if (!$userId) {
                    $allResponseStr = '';
                    foreach ($queSetting['prediction'] as $key => $prediction) {
                        $mapPredictions2Labels = [];
                        $getPropertyShow = [];
                        $answerTexts = [];
                        $refKey = [];
                        $apiData = [];
                        if (isset($queSetting['showField']['values']) && $queSetting['showField']['values']) {
                            $answerTexts[] = str_replace(',', ' ', $responses['_all_response_']);
                            $mapPredictions2Labels [] = 0;
                            $getPropertyShow[] = '';
                            $refKey[] = 'refkey'.$surveyId.''.$id;
                        }

                        if (isset($queSetting['showField']['label']) && $queSetting['showField']['label']) {
                            $answerTexts[] = str_replace(',', ' ', $responses['_all_response_']);
                            $mapPredictions2Labels [] = 1;
                            $getPropertyShow[] = 'pred2percentage';
                            $refKey[] = 'refkey'.$surveyId.''.$id;
                        }

                        if (isset($queSetting['showField']['z_values']) && $queSetting['showField']['z_values']) {
                            $answerTexts[] = str_replace(',', ' ', $responses['_all_response_']);
                            $mapPredictions2Labels [] = 0;
                            $getPropertyShow[] = 'pred2z';
                            $refKey[] = 'refkey'.$surveyId.''.$id;
                        }

                        if (isset($queSetting['showField']['p_values']) && $queSetting['showField']['p_values']) {
                            $answerTexts[] = str_replace(',', ' ', $responses['_all_response_']);
                            $mapPredictions2Labels [] = 0;
                            $getPropertyShow[] = 'pred2percentage';
                            $refKey[] = 'refkey'.$surveyId.''.$id;
                        }
                        foreach ($responses['responses'] as $clientId => $answer) {
                            if (isset($queSetting['showField']['values']) && $queSetting['showField']['values']) {
                                $answerTexts[] = $answer;
                                $mapPredictions2Labels [] = 0;
                                $getPropertyShow[] = '';
                                $refKey[] = 'refkey'.$surveyId.''.$clientId;
                            }

                            if (isset($queSetting['showField']['label']) && $queSetting['showField']['label']) {
                                $answerTexts[] = $answer;
                                $mapPredictions2Labels [] = 1;
                                $getPropertyShow[] = 'pred2percentage';
                                $refKey[] = 'refkey'.$surveyId.''.$clientId;
                            }

                            if (isset($queSetting['showField']['z_values']) && $queSetting['showField']['z_values']) {
                                $answerTexts[] = $answer;
                                $mapPredictions2Labels [] = 0;
                                $getPropertyShow[] = 'pred2z';
                                $refKey[] = 'refkey'.$surveyId.''.$clientId;
                            }
                            if (isset($queSetting['showField']['p_values']) && $queSetting['showField']['p_values']) {
                                $answerTexts[] = $answer;
                                $mapPredictions2Labels [] = 0;
                                $getPropertyShow[] = 'pred2percentage';
                                $refKey[] = 'refkey'.$surveyId.''.$clientId;
                            }
                        }

                        if (count($getPropertyShow)) {
                        $apiData = [
                            'singlemultiple' => 'multipletext',
                            'wordset1' => $answerTexts,
                            'refwordset1' => $refKey,
                            'word5' => $prediction,
                            'refkey' => md5($surveyId.$id),
                            'documentlanguage' => 'sv',
                            'language' => 'sv',
                            'documentid' => $surveyId.$id,
                            'parameters' => [
                                'mapPredictions2Labels' => $mapPredictions2Labels,
                                'getPropertyShow' => $getPropertyShow,
                                'callFrom' => 'WD'
                            ]
                        ];

                        $response = $http->post(Configure::read('Application.SemanticExcel.Api.GetProperty'),
                            (string)http_build_query($apiData),
                            [
                                'headers' => [
                                    'X-Requested-With' => 'XMLHttpRequest',
                                    'hash' => Configure::read('Application.SemanticExcel.hash'),
                                    'public' => Configure::read('Application.SemanticExcel.public')
                                ]
                            ]
                        );
                        $res = $response->body();
                        $result = json_decode($res, true);
                        $calculatedValues = explode(';', $result['answer']);
                        $count = 0;

                        $allResponseArr = [];
                        if (isset($queSetting['showField']['values']) && $queSetting['showField']['values']) {
                            $allResponseArr[] =  ($calculatedValues[$count]) ? $this->applyNumberFormat("values", $calculatedValues[$count], 1): '-';
                            $count++;
                        }
                        if (isset($queSetting['showField']['label']) && $queSetting['showField']['label']) {
                            $allResponseArr[] =  ($calculatedValues[$count]) ? $this->applyColor($calculatedValues[$count]): '-';
                            $count++;
                        }

                        if (isset($queSetting['showField']['z_values']) && $queSetting['showField']['z_values']) {
                            $allResponseArr[] =  ($calculatedValues[$count]) ? $this->applyNumberFormat("z_values", $calculatedValues[$count], 2): '-';
                            $count++;
                        }

                        if (isset($queSetting['showField']['p_values']) && $queSetting['showField']['p_values']) {
                            $allResponseArr[] =  ($calculatedValues[$count]) ? $this->applyNumberFormat("p_values", $this->caculatePValue($calculatedValues[$count]), 0): '-';
                            $count++;
                        }
                        $allResponseStr .= $prediction.': '.implode(', ', $allResponseArr).'<br>';

                        foreach ($responses['responses'] as $clientId => $answer) {
                            if (isset($queSetting['showField']['values']) && $queSetting['showField']['values']) {
                                $responses['prediction_values'][$prediction]['values'][$clientId] = ($calculatedValues[$count]) ? $calculatedValues[$count]: '-';
                                $count++;
                            }

                            if (isset($queSetting['showField']['label']) && $queSetting['showField']['label']) {
                                $responses['prediction_values'][$prediction]['label'][$clientId] = ($calculatedValues[$count]) ? $this->applyColor($calculatedValues[$count]): '-';
                                $count++;
                            }

                            if (isset($queSetting['showField']['z_values']) && $queSetting['showField']['z_values']) {
                                $responses['prediction_values'][$prediction]['z_values'][$clientId] = ($calculatedValues[$count]) ? $calculatedValues[$count]: '-';
                                $count++;
                            }
                            if (isset($queSetting['showField']['p_values']) && $queSetting['showField']['p_values']) {
                                $responses['prediction_values'][$prediction]['p_values'][$clientId] = ($calculatedValues[$count]) ? $this->caculatePValue($calculatedValues[$count]): '-';
                                $count++;
                            }
                        }
                        }
                    }
                    $responses['_calculation_'] = $allResponseStr;
                }
            }
            if ($question['type'] == 'integer' && !$isSingleUser) {
                $responses['_all_response_'] = round(($responses['_all_response_'] / (count($responses['responses']))), 2);
            }
            $allResponses[$id] = $responses;
        }
        return $allResponses;
    }
    /**
     * Function for get number and category questions list
     *
     * @param array $questions
     *
     * @return array
     */
    public function getNumberAndCategoryQueList($questions)
    {
        $numberQuestions = [];
        $categoryQuestion = [];
        foreach ($questions as $id => $singleQue) {
            $title = (is_array($singleQue['title']) ? implode(',', $singleQue['title']) : $singleQue['title']);
            $title = $this->filterText($title);
            $singleQue['title'] = $title;
            $questions[$id] = $singleQue;
            if ($singleQue['type'] == 'integer') {
                $numberQuestions[$id] = $title;
            } else if ($singleQue['type'] == 'category') {
                $categoryQuestion[$id] = $title;
            }
        }

        return array(
            $numberQuestions,
            $categoryQuestion
        );
    }
    /**
     * Function for generate randomCode
     *
     * @return string
     */
    public function randomCode() {
        $alphabet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
    /**
     * Function for generateToken
     *
     * @param $token for survey
     *
     * @return string
     */
    public function generateToken($token)
    {
        $newCode = md5('WDSOLIVE'.$token);
        return substr($newCode, 0, 7);
    }

     /**
     * Function for matchCode
     *
     * @param $code for survey
     * @param $token for survey
     *
     * @return boolean
     */

    public function matchCode($code, $token)
    {
        $matchCode = $this->generateToken($token);
        return ($code == $matchCode);
    }

    /**
     * Function for generate link
     *
     * @param integer $surveyId
     * @param $token for survey
     *
     * @return string
     */
    public function generateLink($surveyId,$token)
    {
        $newLink = Configure::read('Application.LimeSurvey.url').'/'.$surveyId.'?token='.$token.'&lang=sv';
        return $newLink ;
    }
    /**
     * Function for download csv file
     * @param array $results
     * @param string $name of the file
     *
     */
    function download_csv_results($results, $name)
    {
        header("Content-type: txt");
        header("Content-Disposition: attachment; filename=".$name);
        header("Pragma: no-cache");
        header("Expires: 0");
        foreach ($results as $key => $val) {
             echo  $key.': '.$val."\n";
        }
        exit();
    }

    /**
     * Returns short URL of survey invitation url
     *
     * @param string $longurl
     * @return string
     */
    public function _generateShortUrl($longurl)
    {
       // Currently works in prod mode only
        if (!$this->isProduction()) {
            return $longurl;
        }

        $urlShortener = new UrlShortener();

        try {
            $shortUrl = $urlShortener->shorten($longurl);
            return $shortUrl->url;
        } catch (BitlyException $e) {
            // fallback to default
            return $surveyLink;
       }
    }
    /**
     * Production Mode validation
     *
     * this function is check for producion mode
     *
     * e.g. `$this->isProduction();`
     *
     * @return boolean
     */
    public function isProduction()
    {
        if (Configure::read('Application.production')) {
            return true;
        }
        return false;
    }

    public function generateLinkInviteParticipants($surveyId)
    {
        $newLink = Router::url(
            ['controller' => 'surveys', 'action' => 'inviteParticipants', $surveyId],
            true
        );
        return $newLink ;
    }

    public function caculatePValue($value)
    {
        return round(($value*100),2);
    }

    public function applyColor($label)
    {
        return '<span class="'.strtolower($label).'">'.$label.'</span>';
    }

    /**
     * Function for get survey list from Limesurvey
     * @param $isActive boolean true or false
     *
     * @return Array
     */

    public function getMySurveys($isActive = true){
        list($myJSONRPCClient, $sessionKey) = $this->getRPCClient();
        $surveys = $myJSONRPCClient->list_surveys($sessionKey);
        $myJSONRPCClient->release_session_key( $sessionKey );
        $activeSurveys = [];
        foreach ($surveys as $key => $survey) {
            if ($this->Auth->user('survey_user_id') == $survey['owner_id']) {
                if ($isActive) {
                    if ($survey['active'] == 'Y') {
                        $activeSurveys[$key] = $survey;
                    }
                } else {
                    $activeSurveys[$key] = $survey;
                }
            }
        }
        return $activeSurveys;
    }

    /**
     * function to modify answer in number format i.e WDdep2018: 12.1, Mild, z=1.18, 88%
     */
    public function applyNumberFormat($label,$value,$precision){
        if($label =='z_values'){
            $modifiedValue = "z=".number_format($value, $precision);
        }elseif($label =='p_values'){
            $modifiedValue = number_format($value, $precision)."%";
        }elseif($label =='values'){
            $modifiedValue = ($value >= 2) ? number_format($value, $precision) : number_format($value, 2);
        }
        return $modifiedValue;
    }

    public function repairData($responses)
    {

        $data = $responses['data'];
        $allowEmptyData = $responses['allowEmptyData'];
        $compareData = $responses['compare_data'];
        $compareIde = $responses['compare_identifier'];

        if (!isset($responses['compare_identifier'])) {
            $responses['compare_data'] = [];
            $responses['compare_identifier'] = [];
        }

        if (!isset($responses['language'])) { 
            $responses['language'] = "en";
        }

        if (!isset($responses['type'])) { 
            $responses['type'] = "single";
        }

        if (!isset($responses['plotCloudType'])) { 
            $responses['plotCloudType'] = "words";
        }

        if (!isset($responses['plotCluster'])) { 
            $responses['plotCluster'] = "0";
        }

        if (!isset($responses['plotWordcloud']) || is_null($responses['plotWordcloud'])) { 
            $responses['plotWordcloud'] = "1";
        }

        if (!isset($responses['plotTestType'])) { 
            $responses['plotWordcloud'] = "1";
        }

        if (!isset($responses['plotTestType'])) { 
            $responses['plotWordcloud'] = [];
        }

        if (!isset($responses['userIdeNames']) || empty($responses['userIdeNames'])) { 
            $responses['userIdeNames'] = [];
        }


        if (!isset($responses['userIdentifier']) || empty($responses['userIdentifier'])) { 
            $responses['userIdentifier'] = [];
        }

        if (!isset($responses['numbersData']) || empty($responses['numbersData'])) { 
            $responses['numbersData'] = [];
        }

        if (!isset($responses['numbersData']) || empty($responses['numbersData'])) { 
            $responses['numbersData'] = [];
        }

        if (!isset($responses['plotWordcloudType']) || empty($responses['plotWordcloudType'])) { 
            $responses['plotWordcloudType'] = '';
        }


        if (!isset($responses['plotNominalLabels']) || !is_array($responses['plotNominalLabels'])) { 
            $responses['plotNominalLabels'] = [];
        }


        if (!isset($responses['plotNominalLabels']) || !is_array($responses['plotNominalLabels'])) { 
            $responses['plotNominalLabels'] = [];
        }

        if (!isset($responses['advanceParam'])) {
            $advanceParam = [];
        }


        if (isset($responses['valence']) && !empty($responses['valence'])) {
            $advanceParam['plotProperty3'] = $valence;
        }

        if (isset($responses['plotProperty']) && !empty($responses['plotProperty'])) {
            $advanceParam['plotProperty'] = $plotProperty;
        }

        if(!isset($responses['documentSpace']) || $responses['documentSpace']=="") { 
            $documentSpace = "3words".$responses['language'];
        }else{
            $documentSpace = $responses['documentSpace'].$responses['language'];
        }

        if (!empty($responses["userCallId"])) {
            $advanceParam['userCallId'] = $responses["userCallId"];
        }

        $responses['documentSpace'] = $documentSpace;
        $responses['advanceParam'] = $advanceParam;

        $responses["xaxel"] = isset($responses["xaxel"]) ? $responses["xaxel"] : [];
        $responses["yaxel"] = isset($responses["yaxel"]) ? $responses["yaxel"] : [];
        $responses["zaxel"] = isset($responses["zaxel"]) ? $responses["zaxel"] : [];
        return $responses;
    }
    
}

