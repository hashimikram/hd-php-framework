<?php
namespace HDFramework\src;

use DateTime;
use Exception;

/**
 * Class used for posts to Identity mind
 *
 * @author cornel
 * @package framework
 */
class HDIdentityMind
{

    private static $instance = null;

    private static $identityMindUserName = '';

    private static $identityMindPassword = '';

    private static $identityMindKycConsumerEvaluateLink = '';

    private static $identityMindFeedbackAcceptLink = '';

    private static $identityMindFeedbackRejectedLink = '';

    private static $identityMindBankAccountSalt = '';

    private static $identityMind = '';

    private static $identityMindHost = '';

    private static $m3KycConsumerEvaluateIdentityMindResponseLink = '';

    private static $m3FeedbackIdentityMindResponseLink = '';

    private static $customHeaders = array();

    /**
     * COnstructs a HDIdentityMind object
     *
     * @throws Exception - if mandatory inctance fields are missing
     */
    private function __construct($params)
    {
        self::$identityMindUserName = $params['identityMindUserName'];
        self::$identityMindPassword = $params['identityMindPassword'];
        self::$identityMindKycConsumerEvaluateLink = $params['identityMindKycConsumerEvaluateLink'];
        self::$identityMindFeedbackAcceptLink = $params['identityMindFeedbackAcceptLink'];
        self::$identityMindFeedbackRejectedLink = $params['identityMindFeedbackRejectedLink'];
        self::$identityMindHost = $params['identityMindHost'];
        self::$m3KycConsumerEvaluateIdentityMindResponseLink = $params['m3KycConsumerEvaluateIdentityMindResponseLink'];
        self::$m3FeedbackIdentityMindResponseLink = $params['m3FeedbackIdentityMindResponseLink'];
        self::$identityMindBankAccountSalt = $params['identityMindBankAccountSalt'];

        if (empty(self::$identityMindUserName))
            throw new Exception('Identity mind username empty');
        if (empty(self::$identityMindPassword))
            throw new Exception('Identity mind password empty');
        if (empty(self::$identityMindKycConsumerEvaluateLink))
            throw new Exception('Identity mind kyc consumer evaluate link empty');
        if (empty(self::$identityMindHost))
            throw new Exception('Identity mind host empty');
        if (empty(self::$identityMindBankAccountSalt))
            throw new Exception('Identity mind bank account salt empty');
        if (empty(self::$m3KycConsumerEvaluateIdentityMindResponseLink))
            throw new Exception('M3Kyc Consumer Evaluate Identity Mind Response Link empty');
        if (empty(self::$m3FeedbackIdentityMindResponseLink))
            throw new Exception('M3Feedback Identity Mind Response Link empty');

        self::$customHeaders[] = 'host: ' . self::$identityMindHost;
        self::$customHeaders[] = 'authorization: Basic ' . base64_encode(self::$identityMindUserName . ':' . self::$identityMindPassword);
        self::$customHeaders[] = 'cache-control: no-cache';
        self::$customHeaders[] = 'content-type: application/json';
        self::$customHeaders[] = 'expect:';
    }

    /**
     * Creates singleton object of the class
     *
     * @return HDIdentityMind
     */
    public static function getInstance($params)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($params);
        }
        return self::$instance;
    }

    /**
     * Sends consumer data to Identity minds
     *
     * @param array $parameters
     *            - M3 parameters
     * @return string - empty string
     */
    public function postConsumerData($parameters)
    {
        $requestData = $this->createConsumerPostRequestData($parameters);

        if (count($requestData) == 0)
            return '';

        $response = HDUtils::http(self::$identityMindKycConsumerEvaluateLink, 'POST', $requestData, self::$customHeaders);
        HDLog::AppLogMessage('HDIdentityMind', 'postConsumerData', '$response', $response, 3, 'L');
        if (HDUtils::isJson($response['FILE'])) {
            $data_array = array();
            $data_array['identityMindResponse'] = $response['FILE'];
            $data_array['m3_id'] = $parameters['mobilePhoneNumber'];
            $data_array['memo'] = $parameters['memo'];
            HDLog::AppLogMessage('HDIdentityMind', 'postConsumerData', 'm3KycConsumerEvaluateIdentityMindResponseLink', self::$m3KycConsumerEvaluateIdentityMindResponseLink, 3, 'L');
            HDUtils::http(self::$m3KycConsumerEvaluateIdentityMindResponseLink, 'POST', $data_array);
        }
        return '';
    }

    /**
     * Send feedback data to Identity minds
     *
     * @param array $parameters
     *            - M3 parameters
     * @return string - empty string
     */
    public function postFeedbackData($parameters)
    {
        $requestData = $this->createFeedbackPostRequestData($parameters);

        $url = '';

        // check to see if we have tid so that we can use the IM feedback post and construct the url to use for the post
        if (empty($parameters['tid']) === false) {
            // construct the url to use
            switch ($parameters['type']) {
                case 'ACCP':
                    $url = sprintf(self::$identityMindFeedbackAcceptLink, $parameters['tid']);
                    break;
                case 'RJCT':
                    $url = sprintf(self::$identityMindFeedbackRejectedLink, $parameters['tid']);
                    break;
                default:
                    HDLog::AppLogMessage('HDIdentityMind', 'postFeedbackData', 'bad type received', $parameters['type'], 4, 'L');
            }
        }

        if ($url != '') {
            $response = HDUtils::http($url, 'POST', $requestData, self::$customHeaders);
            if (HDUtils::isJson($response['FILE'])) {
                $data_array = array();
                $data_array['requestedBy'] = $parameters['requestedBy'];
                $data_array['type'] = $parameters['type'];
                $data_array['reason'] = $parameters['reason'];
                $data_array['description'] = $parameters['description'];
                $data_array['validate'] = ($parameters['validate']) ? 'true' : 'false';
                $data_array['mobilePhoneNumber'] = $parameters['mobilePhoneNumber'];
                $data_array['identityMindResponse'] = $response['FILE'];

                HDUtils::http(self::$m3FeedbackIdentityMindResponseLink, 'POST', $data_array);
            }
        }
        return '';
    }

    /**
     * Creates Identity mind feedback object from M3 object
     *
     * @param array $parameters
     *            - M3 object
     * @return array - Identity mind feedback object
     */
    private function createFeedbackPostRequestData($parameters)
    {
        $return = array();
        if (empty($parameters['reason']) === false) {
            $return['reason'] = $parameters['reason'];
        }

        if (empty($parameters['description']) === false) {
            $return['description'] = $parameters['description'];
        }

        if (is_bool($parameters['validate']) === true) {
            $return['validate'] = $parameters['validate'];
        }
        return $return;
    }

    /**
     * Creates Identity mind consumer object from M3 object
     *
     * @param array $parameters
     *            - M3 object
     * @return array - Identity mind consumer object
     */
    private function createConsumerPostRequestData($parameters)
    {
        $return = array();

        if ((empty($parameters['firstName']) === true) || (empty($parameters['lastName']) === true)) {
            return '';
        } else {
            $return['man'] = hash('md5', $parameters['mobilePhoneNumber']);
            $return['bfn'] = trim($parameters['firstName']);
            $return['bln'] = trim($parameters['lastName']);
        }

        if (empty($parameters['birthday']) === false) {
            $return['dob'] = date('Y-m-d', intval($parameters['birthday']));
        }

        if (empty($parameters['tid']) === false) {
            $return['tid'] = $parameters['tid'];
        }

        if (empty($parameters['email']) === false) {
            $return['tea'] = $parameters['email'];
        }

        if (empty($parameters['lastUsedIp']) === false) {
            $return['ip'] = $parameters['lastUsedIp'];
        }

        if (empty($parameters['time']) === false) {
            $return['tti'] = date(DateTime::ISO8601, intval($parameters['time']));
        }

        if (empty($parameters['address']) === false) {
            if (empty($parameters['address']['addressLine1']) === false) {
                $return['bsn'] = trim($parameters['address']['addressLine1']);
            }

            if (empty($parameters['address']['city']) === false) {
                $return['bc'] = trim($parameters['address']['city']);
            }

            if (empty($parameters['address']['region']) === false) {
                $return['bs'] = trim($parameters['address']['region']);
            }

            if (empty($parameters['address']['postalCode']) === false) {
                $return['bz'] = trim($parameters['address']['postalCode']);
            }

            if (empty($parameters['address']['country']) === false) {
                $return['bco'] = trim($parameters['address']['country']);
            }
        }

        if (empty($parameters['dateRegistered']) === false) {
            $return['accountCreationTime'] = intval($parameters['dateRegistered']);
        }

        if (empty($parameters['language']) === false) {
            $return['blg'] = $parameters['language'];
        }

        if (empty($parameters['mobilePhoneNumber']) === false) {
            $return['pm'] = strval($parameters['mobilePhoneNumber']);
        }

        if (empty($parameters['memo']) === false) {
            $return['memo'] = $parameters['memo'];

            switch ($return['memo']) {
                case 'MY-ACCOUNT-DOC':
                    $parameters['stage'] = "2";
                    break;
                default:
                    $parameters['stage'] = "1";
            }
        }

        if (empty($parameters['doc_tag']) === false) {
            if (empty($parameters['doc_page_one']) === false) {
                $doc_page_one_size = HDUtils::getRemoteFilesize(str_replace("https", "http", $parameters['doc_page_one']));

                // check filesize of file, don't allow pictures greater than 4Mb
                if (($doc_page_one_size != - 1) && ($doc_page_one_size < 4194304)) {
                    $doc_page_one_base64 = base64_encode(file_get_contents($parameters['doc_page_one']));
                    $return['faceImageData'] = $doc_page_one_base64;
                    $return['scanData'] = $doc_page_one_base64;
                }
            }

            if (empty($parameters['doc_page_two']) === false) {
                $doc_page_two_size = HDUtils::getRemoteFilesize(str_replace("https", "http", $parameters['doc_page_two']));

                // check filesize of file, don't allow pictures greater than 4Mb
                if (($doc_page_two_size != - 1) && ($doc_page_two_size < 4194304)) {
                    $doc_page_two_base64 = base64_encode(file_get_contents($parameters['doc_page_two']));
                    $return['backsideImageData'] = $doc_page_two_base64;
                }
            }

            switch ($parameters['doc_tag']) {
                case 'Id Card':
                case 'Proof of Address':
                    $return['docType'] = 'ID';
                    break;
                case 'Passport':
                    $return['docType'] = 'PP';
                    break;
                case 'Driving License':
                    $return['docType'] = 'DL';
                    break;
                default:
                    $return['docType'] = 'ID';
                    HDLog::AppLogMessage('HDIdentityMind', 'createConsumerPostRequestData', 'bad doc_tag received', $parameters['doc_tag'], 4, 'L');
            }

            if (empty($parameters['nationality']) === false) {
                $return['docCountry'] = $parameters['nationality'];
            }
        }

        if (empty($parameters['bank_account_type']) === false) {
            switch ($parameters['bank_account_type']) {
                case 'US':

                    // remove white spaces
                    $bank_account_number = preg_replace('/\s/', '', $parameters['bank_account_number']);
                    $bank_account_aba = preg_replace('/\s/', '', $parameters['bank_account_aba']);

                    // hash salt + bank account + routing number
                    $return['pach'] = sha1(self::$identityMindBankAccountSalt . $bank_account_number . $bank_account_aba);
                    break;
                case 'IBAN':

                    // remove white spaces
                    $bank_account_iban = preg_replace('/\s/', '', $parameters['bank_account_iban']);

                    // hash salt + bank account iban
                    $return['pach'] = sha1(self::$identityMindBankAccountSalt . $bank_account_iban);
                    break;
                case 'CA':
                    // remove white spaces
                    $bank_account_number = preg_replace('/\s/', '', $parameters['bank_account_number']);
                    $bank_account_branch_code = preg_replace('/\s/', '', $parameters['bank_account_branch_code']);
                    $bank_account_institution_number = preg_replace('/\s/', '', $parameters['bank_account_institution_number']);

                    // hash salt + institution number + branch code + bank account number
                    $return['pach'] = sha1(self::$identityMindBankAccountSalt . $bank_account_institution_number . $bank_account_branch_code . $bank_account_number);
                    break;
                case 'GB':

                    // remove white spaces
                    $bank_account_number = preg_replace('/\s/', '', $parameters['bank_account_number']);
                    $bank_account_sort_code = preg_replace('/\s/', '', $parameters['bank_account_sort_code']);

                    // hash salt + sort code + bank account number
                    $return['pach'] = sha1(self::$identityMindBankAccountSalt . $bank_account_number . $bank_account_sort_code);
                    break;
                case 'OTHER':

                    // remove white spaces
                    $bank_account_bic = preg_replace('/\s/', '', $parameters['bank_account_bic']);
                    $bank_account_number = preg_replace('/\s/', '', $parameters['bank_account_number']);

                    // hash nic + account number
                    $return['pach'] = sha1(self::$identityMindBankAccountSalt . $bank_account_bic . $bank_account_number);
                    break;
                default:
                    HDLog::AppLogMessage('HDIdentityMind', 'createConsumerPostRequestData', 'unknown bank account type', $parameters['bank_account_type'], 4, 'L');
            }
        }
        return $return;
    }
}
