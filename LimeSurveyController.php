<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Xml;
use org\jsonrpcphp\JsonRPCClient;
use Cake\Routing\Router;
use Cake\View\View;
/**
 * LimeSurvey Controller
 *
 * @property \App\Model\Table\LimeSurveyTable
 */
class LimeSurveyController extends AppController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['limeSurvey']);
    }

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('RequestHandler');
    }

    public function limeSurvey()
    {
        $uploadData = '';
        if ($this->request->is('post')) {
            $file = $this->request->data['file']['tmp_name'];
            $xmlArray = Xml::toArray(Xml::build($file));
            $id = $xmlArray['questionnaire']['@id'];
            $title = $xmlArray['questionnaire']['title'];


            $this->loadModel('LimeSurveyQuestions');
            $limeSurvey = $this->LimeSurveyQuestions->find('all')->where(['survey_id' => $id])->first();

            $data = [
                'survey_id' => $id,
                'name'=> $title,
                'question'  => json_encode($questionList),
                'survey_xml_data' => json_encode($xmlArray),
            ];
            if ($limeSurvey) {
                $limeSurvey = $this->LimeSurveyQuestions->patchEntity($limeSurvey, $data);
            } else {
                $limeSurvey = $this->LimeSurveyQuestions->newEntity($data);
            }

            if ($this->LimeSurveyQuestions->save($limeSurvey)) {

                $this->Flash->success(__('Data save successfully.'));

                return $this->redirect(['controller' => 'Surveys','action' => 'index']);
            }
            $this->Flash->error(__('Survey question imported not be saved. Please, try again.'));
        }
        $this->set('uploadData', $uploadData);
    }


    public function limeSurveyResult($surveyId)
    {


if (isset($_GET['test']) && $_GET['test'] == 'test') {
    $redis  = new \Redis();
    $redis->connect('redis', 6379);
    $redis->rpush('wdQueue', json_encode(['name' => 'chintan']));

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
}

        $questions = [];
        $plotSetting = [];
        $questionList = [];
        $plotInfo = [];
        $this->loadModel('LimeSurveyResponse');
        $this->loadModel('LimeSurveyQuestions');
        $this->loadModel('LimeSurveySetting');
        $this->loadModel('Participants');
        $loggedInUser = $this->Auth->User();

        $participanId = $this->request->getQuery('participant_id', '');
        $code = $this->request->getdata('code', '');
        $askCode = false;
        if ($participanId && $this->Participants->isCodeRequired($participanId)) {
            $token = $this->Participants->getTokenById($participanId);
            if (!$this->LimeSurvey->matchCode($code, $token)) {
                $askCode = true;
            }
        }

        if ($this->request->is('ajax')) {
            $this->response->type('json');
            if ($askCode) {
                $this->response->body(json_encode(['status' => false, 'message' => 'Code mismatch']));
            } else {
                $this->response->body(json_encode(['status' => true]));
            }
            return $this->response;
        }


        $defaultSetting = $this->LimeSurvey->getPlotDefaultSetting();
        $predictionList = $this->LimeSurvey->get3Woordsprediction();
        $settings = $this->LimeSurveySetting->getSurveySetting($surveyId);
        $participant = $this->Participants->listParticipants($surveyId);
        $showQuestion = json_decode($settings['show_questions']);
        // Get survey and question list
        $surveyDetails = $this->LimeSurveyQuestions->getSuveryDetails($surveyId);
        if (!empty($surveyDetails)) {
            $questions = json_decode($surveyDetails['question'],true);
            $plotSetting = json_decode($surveyDetails['plot_setting'],true);
            $plotInfo =  json_decode($surveyDetails['plot_info'],true);
        } else {
            $url = Router::url(['controller' => 'Surveys','action' => 'syncQuestion', $surveyId]);
            $this->Flash->error(__('Survey is not sync. <a href="'.$url.'">Click here</a> to sync your survey.'));
            return $this->redirect(['controller' => 'Surveys','action' => 'index']);
        }
        list($numberQuestions, $categoryQuestion) = $this->LimeSurvey->getNumberAndCategoryQueList($questions);


        $allResponses = $this->Participants->getSurveyResponse($surveyId, $participanId);

        //$allResponses = $this->LimeSurvey->applySetting($surveyId, $allResponses, $settings, $questions, $participanId);

        if(isset($settings['question_report'])){
            $allPredValues = json_decode($settings['question_report'], true);
            if (count($allPredValues)) {
                foreach ($allPredValues as $queId => $predValues) {
                    $allResponses[$queId]['prediction_values'] = $predValues;

                }
            }
        }


        /*fetch min max date*/
        $minMaxDate = $this->Participants->getMinMaxDate($surveyId, $participanId);

        $questionList = [];

        foreach ($questions as $key => $question) {
            $questionList[$question['type']][$key] = $question;
        }
        //prepare array for default checkbox are checked
        $defaultReportSetting = [
            'words' => 'true',
            'date' => 'true',
            'label' => 'true',
            'p_values' => 'true',
            'z_values' => 'true',
        ];
        $this->set('questionList', $questionList);
        $this->set('surveyDetails', $surveyDetails);
        $this->set('allResponses', $allResponses);
        $this->set('questions', $questions);
        $this->set('defaultSetting', $defaultSetting);
        $this->set('plotSetting', $plotSetting);
        $this->set('predictionList', $predictionList);
        $this->set('numberQuestions', $numberQuestions);
        $this->set('categoryQuestion', $categoryQuestion);
        $this->set('participant', $participant);
        $this->set('selectedUser', $this->request->getQuery('participant_id'));
        $this->set('settings', $settings);
        $this->set('plotInfo', $plotInfo);
        $this->set('showQuestion', $showQuestion);
        $this->set('minMaxDate', $minMaxDate);
        $this->set('askCode', $askCode);
        $this->set('defaultReportSetting', $defaultReportSetting);
    }

    public function ajaxShowmore()
    {
        $questionId = $this->request->getData('qid');
        $surveyId = $this->request->getData('surveyId');
        $this->loadModel('LimeSurveyResponse');
        $this->loadModel('LimeSurveyQuestions');
        $response = $this->LimeSurveyResponse->getSurveyResponse($surveyId);
        $allResponses = json_decode($response->response_data, true);
        $viewMoreResponses = $allResponses[$questionId]['responses'];
        $this->response->type('json');
        $this->response->body(json_encode(implode(',', $viewMoreResponses)));

        return $this->response;
    }
    public function checkPlot($questionId = null)
    {
        $surveyId = $this->request->getData('survey_id');
        $qId = $this->request->getData('question_id');
        $mode = $this->request->getData('mode');
        $participants = $this->request->getData('participant_id');
        $clientId = $participants[$qId];
        $this->loadModel('LimeSurveyResponse');
        $this->loadModel('LimeSurveyQuestions');
        $this->loadModel('LimeSurveySetting');
        $this->loadModel('Participants');
        $loggedInUser = $this->Auth->User();
        // Get survey and questions list
        $questions = $this->LimeSurveyQuestions->getSurveyQuestionsList($surveyId);
        $allResponses = $this->Participants->getSurveyResponse($surveyId);
        $settings = $this->LimeSurveySetting->getSurveySetting($surveyId);
        $filteredResponses = $this->LimeSurvey->applySetting($surveyId, $allResponses, $settings, $questions, $clientId, $getPredValues = false);
        // Fetch particular question response
        $responses = $filteredResponses[$questionId];


        $allResponses = [];
        if ($clientId) {
            $allResponses[$questionId]['responses'][$clientId] = $responses['responses'][$clientId];
        } else {
            $allResponses = $filteredResponses;
        }
        // Prepare data to generate plot

        list($img_url, $needApiCall, $apiResponse, $responseId, $needToSave) =
        $this->LimeSurvey->generatePlotFromLimeSurveyResponse($this->request, $questions, $allResponses, $surveyId, $questionId, $loggedInUser);
        $resultJ = [
            'type' => 'single_url',
            'data' => $apiResponse,
            'image' => $img_url,
            'responseId' => $responseId,
            'needToSave' => $needToSave,
            'needApiCall' => $needApiCall,
        ];

        // Call api and generate plot
        $result = $this->LimeSurvey->getPlots($resultJ, $loggedInUser);

        $figureNote = '';
        // Plot api response
        if ($result && $result->status == 'ok') {
            $data = $result->data;
            $imageName = $data->results;
            $imageName = explode('~', $imageName);
            $image = $imageName[0];
            $figureNote = $data->figureNote;

            if (strtolower($mode) == 'save' && $image) {

                $limeSurveyPlot = $this->LimeSurveyQuestions->find('all')->where(['survey_id' => $surveyId])->first();
                $plotSetting = json_decode($limeSurveyPlot->plot_setting, true);

                $plotSetting[$postData['question_id']] = [
                    'plotCloudType' => (isset($postData['plotCloudType'])) ? $postData['plotCloudType'] : '0',
                    'plotWordcloud' => (isset($postData['plotWordcloud'])) ? $postData['plotWordcloud'] : '0',
                    'prediction' => (isset($postData['prediction'])) ? $postData['prediction'] : '0',
                    'plotCluster' => (isset($postData['plotCluster'])) ? $postData['plotCluster'] : '0',
                    'x-axis-selection' => (isset($postData['x-axis-selection'])) ? $postData['x-axis-selection'] : '',
                    'y-axis-selection' => (isset($postData['y-axis-selection'])) ? $postData['y-axis-selection'] : '',
                    'z-axis-selection' => (isset($postData['z-axis-selection'])) ? $postData['z-axis-selection'] : '',
                ];

                $limeSurveyPlot->plot_setting = json_encode($plotSetting);

                $plotInfo = [];
                if ($limeSurveyPlot->plot_info) {
                    $plotInfo = json_decode($limeSurveyPlot->plot_info, true);
                }
                $plotInfo[$questionId]['figureNote'] = $figureNote;
                $plotInfo[$questionId]['single_url'] = $image;

                $limeSurveyPlot->plot_info = json_encode($plotInfo);
                $this->LimeSurveyQuestions->save($limeSurveyPlot);
            }
            $response = ['status' => true, 'message' => 'Success','image' => $image, 'figureNote' => $figureNote];
        } else {
            $response = ['status' => false, 'message' => $result->message, 'image' => '', 'figureNote' => $figureNote];
        }

        $this->response->type('json');
        $this->response->body(json_encode($response));
        return $this->response;
    }
    private function _setResponse($response, $responseCode = 400)
    {
        $this->response->statusCode($responseCode);
        $this->response->type('json');
        $this->response->body(json_encode($response));
        return $this->response;
    }

    public function removeQuestion()
    {
        $surveyId = $this->request->getData('survey_id');
        $questionId = $this->request->getData('question_id');

        $this->loadModel('LimeSurveySetting');

        $saved = $this->LimeSurveySetting
            ->removeQuestion($surveyId, $questionId);
        $status = false;
        if ($saved) {
            $status = true;
        }
        $this->response->type('json');
        $this->response->body(json_encode(['status' => $status]));
        return $this->response;
    }

    public function addQuestion()
    {
        $surveyId = $this->request->getData('survey_id');
        $questionId = $this->request->getData('question_id');

        $this->loadModel('LimeSurveySetting');

        $saved = $this->LimeSurveySetting
            ->addQuestion($surveyId, $questionId);

        $status = false;
        if ($saved) {
            $status = true;
        }
        $this->response->type('json');
        $this->response->body(json_encode(['status' => $status]));
        return $this->response;
    }

    public function setOrder()
    {
        $sortQuestions = $this->request->getData('sort');
        $surveyId = $this->request->query('survey_id');

        $this->loadModel('LimeSurveySetting');

        $saved = $this->LimeSurveySetting
            ->setQuestionOrder($surveyId, $sortQuestions);

        $status = false;
        if ($saved) {
            $status = true;
            $message = '<section class="content-header"><div class="alert alert-success">'.__('Question has beed reordered successfully').'</div></section>';
        } else {
            $message = '<section class="content-header"><div class="alert alert-error">'.__('Something went wrong. Please try again!').'</div></section>';
        }
        $this->response->type('json');
        $this->response->body(json_encode(['status' => $status, 'message' => $message]));
        return $this->response;
    }

    public function applyFilter()
    {
        $qId = $this->request->getData('question_id');
        $surveyId = $this->request->getData('survey_id');
        $participant = $this->request->getData('participant_id');
        $prediction = $this->request->getData('prediction');
        $selectedQuestion = $this->request->getData('selected-numeric-que');
        $showFields = $this->request->getData('show', []);

        $this->loadModel('LimeSurveyResponse');
        $this->loadModel('LimeSurveyQuestions');
        $this->loadModel('LimeSurveySetting');
        $this->loadModel('Participants');
        $this->loadModel('Clients');
        $loggedInUser = $this->Auth->User();

        $defaultSetting = $this->LimeSurvey->getPlotDefaultSetting();
        $predictionList = $this->LimeSurvey->get3Woordsprediction();

        $questions = $this->LimeSurveyQuestions->getSurveyQuestionsList($surveyId);
        list($numberQuestions, $categoryQuestion) = $this->LimeSurvey->getNumberAndCategoryQueList($questions);
        $participantList = $this->Participants->listParticipants($surveyId);
        $surveyDetails = $this->LimeSurveyQuestions->getSuveryDetails($surveyId);

        $allResponses = $this->Participants->getSurveyResponse($surveyId);
        $allResponsesDate = $this->Participants->getSurveyDate($surveyId);

        $queResponse = [ $qId => $allResponses[$qId]];

        $settings = ['edit_setting' => [
            $qId => [
                'participant_id' => (isset($participant[$qId]) ? $participant[$qId] : ''),
                'prediction' => (isset($prediction[$qId]) ? $prediction[$qId] : ''),
                'selectedQue' => (isset($selectedQuestion[$qId]) ? $selectedQuestion[$qId]: []),
                'showField' => $showFields[$qId]
            ]
        ]];

        // Show short or full question from show option
        $questionText = '';

            $view = new View($this->request);
            $showField = $showFields[$qId];
            $question = $questions[$qId];
            $queSetting = $settings['edit_setting'][$qId];
            $queSetting['show'] = $queSetting['showField'];
            $view->set(compact('qId', 'question', 'queSetting'));
            $view->layout = false;
            $questionText = $view->render('Element'.DIRECTORY_SEPARATOR.'question-name');

        $allResponses = $this->LimeSurvey->applySetting($surveyId, $queResponse, $allResponses, $settings, $questions, $id = '');

        $loggedInUser = $this->Auth->User();
        /*list($img_url, $needApiCall, $apiResponse, $responseId, $needToSave) =
        $this->LimeSurvey->generatePlotFromLimeSurveyResponse($this->request, $questions, $allResponses, $surveyId, $qId, $loggedInUser);

        $resultJ = [
            'type' => 'single_url',
            'data' => $apiResponse,
            'image' => $img_url,
            'responseId' => $responseId,
            'needToSave' => $needToSave,
            'needApiCall' => $needApiCall,
        ];*/
        $image = '';
        $figureNote = '';
        /*$result = $this->LimeSurvey->getPlots($resultJ, $loggedInUser);

        if ($result && $result->status == 'ok') {
            $data = $result->data;
            $imageName = $data->results;
            $imageName = explode('~', $imageName);
            $image = $imageName[0];
            $figureNote = $data->figureNote;
        }*/

        $subQuestionText = '';
        $question = $questions[$qId];
        $response = $allResponses[$qId];
        $view = new View($this->request);

        $session = $this->request->getSession();
        $predictionValues = $response['prediction_values'];
        $allPredValues = $session->read('all_question_pred_values', []);
        $allPredValues[$qId] = $predictionValues;
        $session->write('all_question_pred_values', $allPredValues);
        $showField = $showFields[$qId];
        $view->layout = false;
        $answer = $allResponses[$qId]['_all_response_'];
        $calculation = $allResponses[$qId]['_calculation_'];

        if($participant[$qId]){
            $clients = $this->Participants->find('all')
                     ->contain(['Clients'])
                     ->where(['Participants.id' => $participant[$qId]])->first();
            $patientName = $clients->client->first_name.' '.$clients->client->last_name.' '.$clients->client->ssn_number;
            $resposeDate = $allResponses['response_time'][$participant[$qId]];
            $view->set(compact('answer','resposeDate','patientName','showField','calculation'));
            $patientReport = $view->render('Element'.DIRECTORY_SEPARATOR.'single-patient-report');
        }else{
            $view->set(compact('question', 'response', 'participantList', 'showField','allResponsesDate'));
            if (isset($allResponses[$qId]['ratingScale'])) {
                $subQuestionText = $view->render('Element'.DIRECTORY_SEPARATOR.'sub-question');
            } else if ($question['type'] == 'text') {
                $subQuestionText = $view->render('Element'.DIRECTORY_SEPARATOR.'text-answer-detail');
            }
        }
        if (isset($allResponses[$qId]['ratingScale'])){
            $viewObj = new View($this->request);
            $viewObj->layout = false;
            $viewObj->set(compact('question', 'response', 'participantList', 'showField','allResponsesDate'));
            $subQuestionText = $viewObj->render('Element'.DIRECTORY_SEPARATOR.'sub-question');
        }
        $status = true;
        $this->response->type('json');
        $this->response->body(json_encode([
            'status' => $status,
            'image' => $image,
            'figureNote' => $figureNote,
            'subQuestionText' => $subQuestionText,
            'questionText' => $questionText,
            'patientReport' => $patientReport,
            'answer' => $answer
            ]));
        return $this->response;

        $this->RequestHandler->renderAs($this, 'json');
        $this->set(compact('status', 'answer', 'image', 'figureNote', 'subQuestionText'));
        $this->set(['_serialize' => ['status', 'answer', 'image', 'figureNote', 'subQuestionText']]);
        exit;
    }

    public function saveSetting()
    {
        $editSetting = [];
        $status = false;

        $participant = $this->request->getData('participant_id');
        $prediction  = $this->request->getData('prediction');
        $selectedQue = $this->request->getData('selected-numeric-que');
        $questions = $this->request->getData('question_id');
        $surveyId = $this->request->getData('survey_id');
        $figure = $this->request->getData('figure');
        $show = $this->request->getData('show');
        $edit = $this->request->getData('edit');
        $showQuestion = $this->request->getData('showQuestion');
        if (count($questions)) {
            foreach ($questions as $question) {
                if (count($participant) && isset($participant[$question]))
                    $editSetting[$question]['participant_id'] =  $participant[$question];

                if (count($prediction) && isset($prediction[$question]))
                    $editSetting[$question]['prediction'] =  $prediction[$question];

                if (count($selectedQue) && isset($selectedQue[$question]))
                    $editSetting[$question]['selectedQue'] =  $selectedQue[$question];

                if (count($figure) && isset($figure[$question]))
                    $editSetting[$question]['figure'] =  $figure[$question];

                if (count($show) && isset($show[$question]))
                    $editSetting[$question]['show'] =  $show[$question];

                if (count($edit) && isset($edit[$question]))
                    $editSetting[$question]['edit'] =  $edit[$question];
            }
        }

        $this->loadModel('LimeSurveySetting');
        $saved = $this->LimeSurveySetting->updateSetting($surveyId, 'edit_setting', $editSetting);

        $session = $this->request->getSession();
        $allPredValues = $session->read('all_question_pred_values', []);

        $allQuestionReport = $this->LimeSurveySetting->find('all')->where(['survey_id' => $surveyId])->first();

        $allQueReports = (isset($allQuestionReport['question_report'])) ? json_decode($allQuestionReport['question_report'], true): [];

        foreach ($allPredValues as $key => $allQueReport){
                $allQueReports[$key] = $allQueReport;
         }
        $saved = $this->LimeSurveySetting->updateQuestionReport($surveyId, 'question_report', $allQueReports);

        $saved = $this->LimeSurveySetting->updateQuestionReport($surveyId, 'show_questions', array_values($showQuestion));

        if ($saved) {
            $session->delete('all_question_pred_values');
            $status = true;
            $this->Flash->success(__('Setting saved successfully.'));
        }

        $this->response->type('json');
        $this->response->body(json_encode(['status' => $status]));
        return $this->response;
    }

    public function displayQuestionList(){
        $qId = $this->request->getData('qId');
        $surveyId = $this->request->getData('survey_id');
        $questions = [];
        $plotSetting = [];
        $questionList = [];
        $plotInfo = [];
        $this->loadModel('LimeSurveyResponse');
        $this->loadModel('LimeSurveyQuestions');
        $this->loadModel('LimeSurveySetting');
        $this->loadModel('Participants');
        $loggedInUser = $this->Auth->User();

        $surveyDetails = $this->LimeSurveyQuestions->getSuveryDetails($surveyId);
        if (!empty($surveyDetails)) {
            $questions = json_decode($surveyDetails['question'],true);
        }
        // Main Question
        $question = $questions[$qId];

        $sameGroupQue = [];
        $otherQue = [];
        // Separate group wise question
        foreach ($questions as $key => $singleQue) {
            if ($question['type'] == $singleQue['type']) {
                if ($question['groupName'] == $singleQue['groupName']) {
                    $sameGroupQue[$singleQue['type']][$key] = $singleQue;
                } else {
                    $otherQue[$singleQue['type']][$key] = $singleQue;
                }
            }
        }
        // Merge group question first and rest at last
        /*foreach ($otherQue as $k => $record) {
            foreach ($record as $j => $subRecord) {
                $sameGroupQue[$k][] = $subRecord;
            }
        }*/
        //$questionList = $sameGroupQue;
        $settings = $this->LimeSurveySetting->getSurveySetting($surveyId);
        $view = new View($this->request);
        if ($this->request->is('ajax')) {

            $queSetting = $settings['edit_setting'][$qId];

            $view->set(compact('sameGroupQue','otherQue', 'question', 'qId', 'queSetting'));
            $view->layout = false;
            $subQuestionList = $view->render('Element'.DIRECTORY_SEPARATOR.'question-list');

            $this->response->type('json');
            $this->response->body(json_encode([
            'subQuestionList' => $subQuestionList,
            ]));
            return $this->response;
        }
    }
}

