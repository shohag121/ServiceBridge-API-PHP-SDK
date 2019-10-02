<?php
/**
 * Service Bridge API
 */

 namespace shohag\ServiceBridgeSDK;

class ServiceBridge{
    /**
     * session key
     */
    private $sessionKey = null;


    /**
     * Client API User ID
     */
    private $userID;

    /**
     * Client Password
     */
    private $userSecret;

    /**
     * auth header
     * @var string
     */
    private $auth;


    /**
     * ServiceBridge constructor.
     * @param $userID
     * @param $passWord
     */
    public function __construct($userID, $passWord) {

        $this->userID = $userID;
        $this->userSecret = $passWord;

        $this->auth = base64_encode("$this->userID:$this->userSecret");

    }


    /**
     * destruction method called and invoke logout method.
     */
    public function __destruct()
    {
        if ($this->sessionKey){
            $this->logout();
        }
    }

    /**
     * invoke the logout method
     */
    private function logout()
    {
        $url = "https://cloud.servicebridge.com/api/v1/Logout";

        $this->doCurl($url, array("SessionKey" => $this->sessionKey));

    }


    /**
     *
     * login function is needed when only Basic Auth is not enabled.
     */
    public function login(){

        $url = "https://cloud.servicebridge.com/api/v1/Login";
        $loginCredential = array(
            "UserId"=> $this->userID,
            "Password" => $this->userSecret,
        );

        $jsonResult = $this->doCurl($url, $loginCredential);

        $result = json_decode($jsonResult);

        if ($result->Success){
            $this->sessionKey = $result->Data;
        } else {
            dd($jsonResult);
        }

    }



    /**
     * Get all contact or the single contact
     *
     * @param null $contactID
     * @return bool|string
     */
    public function getContact($contactID = null){

        if (!$contactID){
            $url = 'https://cloud.servicebridge.com/api/v1/Contacts?page=1&pageSize=500&';
        } else {
            $url = 'https://cloud.servicebridge.com/api/v1/Contacts/'.$contactID."?";
        }

        $contact = $this->doCurl($url);

        return $contact;

    }

    public function getUpdatedContacts($minutes = null){

        date_default_timezone_set('UTC');
        if ($minutes == null){
            $previousTime  = date("c", strtotime(date("c")." -30 minutes"));
        } else {
            $previousTime  = date("c", strtotime(date("c")." -{$minutes} minutes"));
        }
        // Slice the timezone
        $previousTime = str_replace('+00:00', '', $previousTime);


        // $url = "https://cloud.servicebridge.com/api/v1/Contacts?page=1&pageSize=5&changeTime=$previousTime&";
        $url = "https://cloud.servicebridge.com/api/v1/Contacts?changeTime=$previousTime&";
// dd($url);

        $contact = $this->doCurl($url);
        return $contact;
    }



    
    public function getUpdatedCustomers($minutes = null){

        date_default_timezone_set('UTC');
        if ($minutes == null){
            $previousTime  = date("c", strtotime(date("c")." -30 minutes"));
        } else {
            $previousTime  = date("c", strtotime(date("c")." -{$minutes} minutes"));
        }
        // Slice the timezone
        $previousTime = str_replace('+00:00', '', $previousTime);


        $url = "https://cloud.servicebridge.com/api/v1/Customers?page=1&pageSize=500&changeTime=$previousTime&";
        

        $customers = $this->doCurl($url);
        return $customers;
    }



    /**
     * get All customer or single customer with customer id
     *
     * @param null $customerID
     * @return bool|string
     */
    public function getCustomer($customerID = null){

        if (!$customerID){
            $url = 'https://cloud.servicebridge.com/api/v1/Customers?page=1&pageSize=500&';
        } else {
            $url = 'https://cloud.servicebridge.com/api/v1/Customers/'.$customerID."/?";
        }

        $customer = $this->doCurl($url);

        return $customer;

    }

    /**
     * Create New customer with primary location and contact
     * @param array $dataArray
     * @param null $customerID
     * @return bool|string
     */
    public function setCustomer(Array $dataArray, $customerID = null){
        if (!$customerID){
            $url = 'https://cloud.servicebridge.com/api/v1/Customers?';
        } else {
            $url = 'https://cloud.servicebridge.com/api/v1/Customers/'.$customerID."/?";
        }

        $customer = $this->doCurl($url, $dataArray);

        return $customer;
    }


    /**
     *
     * this method is to do curl request from public function
     * to get or post data.
     *
     * we need to send endpoint $url for get method also need $dataArray
     * for post method.
     *
     * @param string $url
     * @param array $dataArray
     * @return bool|string
     */
    private function doCurl( $url , $dataArray = array()){

        // if login method invoked. sessionkey added to url
        if ($this->sessionKey){
            $url = $url."sessionKey=$this->sessionKey";
        }
        // Check method is post or get
        if (!empty($dataArray)){
            $postMethod = 1;
        } else {
            $postMethod = 0;
        }

        error_reporting(E_ALL); // check all type of errors
        ini_set('display_errors',1); // display those errors
        // Get cURL resource

        $curl = curl_init();

        $sendpostdata = json_encode($dataArray);

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'W3S Cloud SDK',
            CURLOPT_POST => $postMethod,
        ));

        if ($postMethod){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $sendpostdata);
        }

        //Assign header for curl
        $headerArray = array('Accept: application/json','Content-Type: application/json');

        // If not login method invoked then add auth header.
        if (!$this->sessionKey){
            array_push($headerArray,"Authorization: Basic $this->auth");
        }
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headerArray);


        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);

        return $resp;

    }


}