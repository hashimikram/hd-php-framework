<?php
namespace HDFramework\src;

use MangoPay\Address;
use MangoPay\KycDocument;
use MangoPay\KycPage;
use MangoPay\UserLegal;
use MangoPay\UserNatural;
use MangoPay\Wallet;
use Exception;

/**
 * Wrapper class to implement finance provider API
 *
 * Configurations dependencies:<br />
 * <br />Release date: 08/10/2017
 *
 * @version 7.0
 * @package framework
 */
class HDMangoPay implements HDPayable
{

    private static $mangoPayApiObject = null;

    private static $accountId = null;

    private static $password = null;

    private static $baseUrl = null;

    private static $temporaryFolder = null;

    private static $templateUrl = null;

    function __construct($accountId, $password, $baseUrl, $temporaryFolder, $templateUrl)
    {
        self::$accountId = $accountId;
        self::$password = $password;
        self::$baseUrl = $baseUrl;
        self::$temporaryFolder = $temporaryFolder;
        self::$templateUrl = $templateUrl;
    }

    /**
     * Init API object
     */
    public static function initApiObject()
    {
        if (self::$mangoPayApiObject == null) {

            self::$mangoPayApiObject = new \MangoPay\MangoPayApi();
            self::$mangoPayApiObject->Config->ClientId = self::$accountId;
            self::$mangoPayApiObject->Config->ClientPassword = self::$password;
            self::$mangoPayApiObject->Config->TemporaryFolder = self::$temporaryFolder;
            self::$mangoPayApiObject->Config->BaseUrl = self::$baseUrl;
            self::$mangoPayApiObject->Config->DebugMode = false;
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'initApiObject', 'out', 'no params', 3, "OUT");
        return self::$mangoPayApiObject;
    }

    public static function createBankAccount($bankAccountParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createBankAccount', 'bankAccountParameters', $bankAccountParameters, 3, "IN");

        // init mango api
        $mangoPayApiObject = self::initApiObject();

        $userId = $bankAccountParameters['UserId'];
        $bankAccount = new \MangoPay\BankAccount();
        $bankAccount->OwnerName = $bankAccountParameters['OwnerName'];

        $bankAccount->OwnerAddress = new \MangoPay\Address();
        $bankAccount->OwnerAddress->AddressLine1 = $bankAccountParameters['AddressLine1'];
        $bankAccount->OwnerAddress->City = $bankAccountParameters['City'];
        $bankAccount->OwnerAddress->Country = $bankAccountParameters['Country'];
        if (array_key_exists('PostalCode', $bankAccountParameters))
            $bankAccount->OwnerAddress->PostalCode = $bankAccountParameters['PostalCode'];
        if (array_key_exists('Region', $bankAccountParameters))
            $bankAccount->OwnerAddress->Region = $bankAccountParameters['Region'];
        $bankAccount->Type = $bankAccountParameters['Type'];

        switch ($bankAccountParameters['Type']) {
            case 'IBAN':
                $bankAccount->Details = new \MangoPay\BankAccountDetailsIBAN();
                $bankAccount->Details->IBAN = $bankAccountParameters['IBAN'];
                if (array_key_exists('BIC', $bankAccountParameters))
                    $bankAccount->Details->BIC = $bankAccountParameters['BIC'];
                break;
            case 'GB':
                $bankAccount->Details = new \MangoPay\BankAccountDetailsGB();
                $bankAccount->Details->AccountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->Details->SortCode = $bankAccountParameters['SortCode'];
                break;
            case 'US':
                $bankAccount->Details = new \MangoPay\BankAccountDetailsUS();
                $bankAccount->Details->AccountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->Details->ABA = $bankAccountParameters['ABA'];
                $bankAccount->Details->DepositAccountType = $bankAccountParameters['DepositAccountType'];
                break;
            case 'CA':
                $bankAccount->Details = new \MangoPay\BankAccountDetailsCA();
                $bankAccount->Details->AccountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->Details->BankName = $bankAccountParameters['BankName'];
                $bankAccount->Details->InstitutionNumber = $bankAccountParameters['InstitutionNumber'];
                $bankAccount->Details->BranchCode = $bankAccountParameters['BranchCode'];
                break;
            case 'OTHER':
                $bankAccount->Details = new \MangoPay\BankAccountDetailsOTHER();
                $bankAccount->Details->AccountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->Details->Country = $bankAccountParameters['Country'];
                $bankAccount->Details->BIC = $bankAccountParameters['BIC'];
                // $bankAccount->Details->Type = $bankAccountParameters['Type'];
                break;
        }

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createBankAccount', 'bankAccount', get_object_vars($bankAccount), 3, "IN");
        $result = array();
        try {
            $result['bankAccount'] = $mangoPayApiObject->Users->CreateBankAccount($userId, $bankAccount);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createBankAccount', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function createBankWire($bankWireParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createBankWire', 'bankWireParameters', $bankWireParameters, 3, "IN");
        $mangoPayApiObject = self::initApiObject();

        $payOut = new \MangoPay\PayOut();
        $payOut->Tag = $bankWireParameters['Tag'];
        $payOut->AuthorId = $bankWireParameters['AuthorId'];
        $payOut->DebitedWalletId = $bankWireParameters['DebitedWalletId'];
        $payOut->DebitedFunds = new \MangoPay\Money();
        $payOut->DebitedFunds->Amount = $bankWireParameters['DebitedFunds']['Amount'];
        $payOut->DebitedFunds->Currency = $bankWireParameters['DebitedFunds']['Currency'];
        $payOut->Fees = new \MangoPay\Money();
        $payOut->Fees->Amount = $bankWireParameters['Fees']['Amount'];
        $payOut->Fees->Currency = $bankWireParameters['Fees']['Currency'];
        $payOut->MeanOfPaymentDetails = new \MangoPay\PayOutPaymentDetailsBankWire();
        $payOut->MeanOfPaymentDetails->BankAccountId = $bankWireParameters['BankAccountId'];

        $result = array();
        try {
            $result['bankWire'] = $mangoPayApiObject->PayOuts->Create($payOut);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createBankWire', 'result', $result, 3, "IN");
        return $result;
    }

    public static function createLegalUser($legalUserParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'createLegalUser', 'userParameters', $legalUserParameters, 3, "IN");
        $result = array();

        // check mandatory
        $mandatoryKeys = array(
            'LegalPersonType',
            'Name',
            'LegalRepresentativeBirthday',
            'LegalRepresentativeCountryOfResidence',
            'LegalRepresentativeNationality',
            'LegalRepresentativeFirstName',
            'LegalRepresentativeLastName',
            'Email'
        );
        foreach ($mandatoryKeys as $mandatoryKey) {
            if (! array_key_exists($mandatoryKey, $legalUserParameters)) {
                $result['error'][] = 'Missing mandatory fields';
                return $result;
            }
        }
        unset($mandatoryKeys, $mandatoryKey);

        $mangoPayApiObject = self::initApiObject();

        $User = new UserLegal();

        $User->LegalPersonType = $legalUserParameters['LegalPersonType'];
        $User->Name = $legalUserParameters['Name'];
        $User->LegalRepresentativeBirthday = $legalUserParameters['LegalRepresentativeBirthday'];
        $User->LegalRepresentativeCountryOfResidence = $legalUserParameters['LegalRepresentativeCountryOfResidence'];
        $User->LegalRepresentativeNationality = $legalUserParameters['LegalRepresentativeNationality'];
        $User->LegalRepresentativeFirstName = $legalUserParameters['LegalRepresentativeFirstName'];
        $User->LegalRepresentativeLastName = $legalUserParameters['LegalRepresentativeLastName'];
        $User->Email = $legalUserParameters['Email'];

        isset($legalUserParameters['Tag']) ? $User->Tag = $legalUserParameters['Tag'] : '';
        isset($legalUserParameters['LegalRepresentativeEmail']) ? $User->LegalRepresentativeEmail = $legalUserParameters['LegalRepresentativeEmail'] : '';

        $addressMandatoryKeys = array(
            'AddressLine1',
            'City',
            'Region',
            'PostalCode',
            'Country'
        );

        if (array_key_exists('HeadquartersAddress', $legalUserParameters)) {
            foreach ($addressMandatoryKeys as $addressMandatoryKey) {
                if (! array_key_exists($addressMandatoryKey, $legalUserParameters['HeadquartersAddress']) || ! isset($legalUserParameters['HeadquartersAddress'][$addressMandatoryKey])) {
                    $result['error'][] = 'Headquarters Address missing mandatory fields';
                    return $result;
                }
            }
            $User->HeadquartersAddress = new Address();
            $User->HeadquartersAddress->AddressLine1 = $legalUserParameters['HeadquartersAddress']['AddressLine1'];
            $User->HeadquartersAddress->City = $legalUserParameters['HeadquartersAddress']['City'];
            $User->HeadquartersAddress->Region = $legalUserParameters['HeadquartersAddress']['Region'];
            $User->HeadquartersAddress->PostalCode = $legalUserParameters['HeadquartersAddress']['PostalCode'];
            $User->HeadquartersAddress->Country = $legalUserParameters['HeadquartersAddress']['Country'];
            isset($legalUserParameters['HeadquartersAddress']['AddressLine2']) ? $User->HeadquartersAddress->AddressLine2 = $legalUserParameters['HeadquartersAddress']['AddressLine2'] : '';
        }

        if (array_key_exists('LegalRepresentativeAddress', $legalUserParameters)) {
            foreach ($addressMandatoryKeys as $addressMandatoryKey) {
                if (! array_key_exists($addressMandatoryKey, $legalUserParameters['LegalRepresentativeAddress']) || ! isset($legalUserParameters['LegalRepresentativeAddress'][$addressMandatoryKey])) {
                    $result['error'][] = 'Legal Representative Address missing mandatory fields';
                    return $result;
                }
            }

            $User->LegalRepresentativeAddress = new Address();
            $User->LegalRepresentativeAddress->AddressLine1 = $legalUserParameters['LegalRepresentativeAddress']['AddressLine1'];
            $User->LegalRepresentativeAddress->City = $legalUserParameters['LegalRepresentativeAddress']['City'];
            $User->LegalRepresentativeAddress->Region = $legalUserParameters['LegalRepresentativeAddress']['Region'];
            $User->LegalRepresentativeAddress->PostalCode = $legalUserParameters['LegalRepresentativeAddress']['PostalCode'];
            $User->LegalRepresentativeAddress->Country = $legalUserParameters['LegalRepresentativeAddress']['Country'];
            isset($legalUserParameters['LegalRepresentativeAddress']['AddressLine2']) ? $User->LegalRepresentativeAddress->AddressLine2 = $legalUserParameters['LegalRepresentativeAddress']['AddressLine2'] : '';
        }

        try {
            $result['user'] = $mangoPayApiObject->Users->Create($User);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'createLegalUser', 'result', $result, 3, "OUT");

        return $result;
    }

    public static function createPayIn($payInParameters, $defaultCurrency = 'EUR')
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createPayIn', 'payInParameters', $payInParameters, 3, "IN");
        $mangoPayApiObject = self::initApiObject();

        $PayIn = new \MangoPay\PayIn();

        $PayIn->AuthorId = $payInParameters['AuthorId'];
        $PayIn->Tag = $payInParameters['Tag'];

        $PayIn->DebitedFunds = new \MangoPay\Money();
        $PayIn->DebitedFunds->Amount = $payInParameters['DebitedFunds'];
        $PayIn->DebitedFunds->Currency = $defaultCurrency;

        $PayIn->Fees = new \MangoPay\Money();
        $PayIn->Fees->Amount = $payInParameters['Fees'];
        $PayIn->Fees->Currency = $defaultCurrency;
        $PayIn->CreditedWalletId = $payInParameters['CreditedWalletId'];

        $PayIn->ExecutionDetails = new \MangoPay\PayInExecutionDetailsWeb();
        $PayIn->ExecutionDetails->ReturnURL = $payInParameters['ReturnURL'];
        $PayIn->ExecutionDetails->Culture = $payInParameters['Culture'];
        $PayIn->ExecutionDetails->SecureMode = $payInParameters['SecureMode'];

        $PayIn->ExecutionDetails->TemplateURLOptions = new \MangoPay\PayInTemplateURLOptions();
        $PayIn->ExecutionDetails->TemplateURLOptions->PAYLINE = self::$templateUrl;

        $PayIn->PaymentDetails = new \MangoPay\PayInPaymentDetailsCard();
        $PayIn->PaymentDetails->CardType = $payInParameters['CardType'];

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createPayIn', 'PayIn', get_object_vars($PayIn), 3, "OUT");
        $result = array();
        try {
            $result['payIn'] = $mangoPayApiObject->PayIns->Create($PayIn);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createPayIn', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function createTransfer($transferParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createTransfer', 'transferParameters', $transferParameters, 3, "IN");
        $mangoPayApiObject = self::initApiObject();

        $Transfer = new \MangoPay\Transfer();
        $Transfer->DebitedWalletId = $transferParameters['DebitedWalletId'];
        $Transfer->CreditedWalletId = $transferParameters['CreditedWalletId'];
        $Transfer->AuthorId = $transferParameters['AuthorId'];
        $Transfer->DebitedFunds = new \MangoPay\Money();
        $Transfer->DebitedFunds->Amount = $transferParameters['DebitedFunds']['Amount'];
        $Transfer->DebitedFunds->Currency = $transferParameters['DebitedFunds']['Currency'];
        $Transfer->Fees = new \MangoPay\Money();
        $Transfer->Fees->Amount = $transferParameters['Fees']['Amount'];
        $Transfer->Fees->Currency = $transferParameters['Fees']['Currency'];
        $Transfer->Tag = $transferParameters['Tag'];

        $result = array();
        try {
            $result['Transfer'] = $mangoPayApiObject->Transfers->Create($Transfer);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    /**
     * This method creates the reverse of a previously created transfer
     *
     * @param array $transferRefundParameters
     *            - ("TransferId", "AuthorId", "Tag")
     * @param
     *            string "TransferId" - ID of the previously created transfer
     * @param
     *            string "AuthorId" - ID of the debited wallet
     * @param
     *            string "Tag" - additional information related to the current transfer
     */
    public static function createTransferRefund($transferRefundParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'createTransferRefund', 'transferRefundParameters', $transferRefundParameters, 3, "IN");

        $mangoPayApiObject = self::initApiObject();

        $Refund = new \MangoPay\Refund();
        $Refund->AuthorId = $transferRefundParameters["AuthorId"];
        $Refund->Tag = $transferRefundParameters["Tag"];

        $result = array();
        try {
            $result['TransferRefund'] = $mangoPayApiObject->Transfers->CreateRefund($transferRefundParameters["TransferId"], $Refund);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            self::processException($e, $result);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        return $result;
    }

    /**
     * Function to create a user
     *
     * @param array $userParameters
     *            <br />mandatory keys: 'Email','FirstName','LastName','Birthday','Nationality','CountryOfResidence'
     */
    public static function createUser($userParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createUser', 'userParameters', $userParameters, 3, "IN");
        $result = array();

        // check mandatory
        $mandatoryKeys = array(
            'Email',
            'FirstName',
            'LastName',
            'Birthday',
            'Nationality',
            'CountryOfResidence'
        );
        foreach ($mandatoryKeys as $mandatoryKey) {
            if (! array_key_exists($mandatoryKey, $userParameters)) {
                $result['error'][] = 'Bad response';
                return $result;
            }
        }
        unset($mandatoryKeys, $mandatoryKey);
        $mangoPayApiObject = self::initApiObject();

        $User = new UserNatural();
        $User->Email = $userParameters['Email'];
        $User->FirstName = $userParameters['FirstName'];
        $User->LastName = $userParameters['LastName'];
        $User->Birthday = intval($userParameters['Birthday']);
        $User->Nationality = $userParameters['Nationality'];
        $User->CountryOfResidence = $userParameters['CountryOfResidence'];
        $User->Tag = $userParameters['Tag'];

        try {
            $result['user'] = $mangoPayApiObject->Users->Create($User);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.confirm', 'result', $result, 3, "OUT");
        return $result;
    }

    /**
     * Function to create a wallet
     *
     * @param array $walletParameters
     */
    public static function createWallet($walletParameters)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createWallet', '$walletParameters', $walletParameters, 3, "IN");
        $result = array();

        // check mandatory
        $mandatoryKeys = array(
            'Owners',
            'Description',
            'Currency'
        );
        foreach ($mandatoryKeys as $mandatoryKey) {
            if (! array_key_exists($mandatoryKey, $walletParameters)) {
                $result['wallet'] = '';
                $result['error'][] = 'Bad response';
                return $result;
            }
        }
        unset($mandatoryKeys, $mandatoryKey);
        $mangoPayApiObject = self::initApiObject();

        $Wallet = new Wallet();
        $Wallet->Owners = $walletParameters['Owners'];
        $Wallet->Description = $walletParameters['Description'];
        $Wallet->Currency = $walletParameters['Currency'];
        $Wallet->Tag = $walletParameters['Tag'];

        try {
            $result['wallet'] = $mangoPayApiObject->Wallets->Create($Wallet);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.createWallet', '$result', $result, 3, "OUT");
        return $result;
    }

    public static function documentsList($userId)
    {
        $result = array();
        $mangoPayApiObject = self::initApiObject();

        try {
            $sorting = new \MangoPay\Sorting();
            $sorting->AddField('CreationDate', \MangoPay\SortDirection::DESC);
            $result['documentsList'] = $mangoPayApiObject->Users->GetKycDocuments($userId, new \MangoPay\Pagination(), $sorting, null);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    /**
     * Function to edit user parameters
     *
     * @param array $userParameters
     *            <br />mandatory key: 'Id'
     *            <br />if you do provide value for one address field then all address fields become mandatory
     *            <br />https://docs.mangopay.com/api-v2-01-overview/
     */
    public static function editUser($userParameters)
    {
        $result = array();

        $mangoPayApiObject = self::initApiObject();

        $User = new UserNatural();
        $User->Id = $userParameters['Id'];
        array_key_exists('Email', $userParameters) ? $User->Email = $userParameters['Email'] : "";
        array_key_exists('FirstName', $userParameters) ? $User->FirstName = $userParameters['FirstName'] : "";
        array_key_exists('LastName', $userParameters) ? $User->LastName = $userParameters['LastName'] : "";
        array_key_exists('Birthday', $userParameters) ? $User->Birthday = intval($userParameters['Birthday']) : "";
        array_key_exists('Nationality', $userParameters) ? $User->Nationality = $userParameters['Nationality'] : "";
        array_key_exists('CountryOfResidence', $userParameters) ? $User->CountryOfResidence = $userParameters['CountryOfResidence'] : "";
        array_key_exists('Tag', $userParameters) ? $User->Tag = $userParameters['Tag'] : "";
        array_key_exists('Occupation', $userParameters) ? $User->Occupation = $userParameters['Occupation'] : "";
        array_key_exists('IncomeRange', $userParameters) ? $User->IncomeRange = $userParameters['IncomeRange'] : "";

        if (array_key_exists('Address', $userParameters)) {
            $User->Address = new Address();
            $User->Address->AddressLine1 = $userParameters['Address']['AddressLine1'];
            $User->Address->City = $userParameters['Address']['City'];
            $User->Address->Country = $userParameters['Address']['Country'];
            $User->Address->PostalCode = $userParameters['Address']['PostalCode'];
            $User->Address->Region = $userParameters['Address']['Region'];
        }

        try {
            $result['user'] = $mangoPayApiObject->Users->Update($User);
        } catch (\MangoPay\Libraries\Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.editUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function editLegalUser($userParameters)
    {
        $result = array();
        $mangoPayApiObject = self::initApiObject();
        $User = new \MangoPay\UserLegal();

        array_key_exists('Id', $userParameters) ? $User->Id = $userParameters['Id'] : '';
        array_key_exists('Tag', $userParameters) ? $User->Tag = $userParameters['Tag'] : '';
        array_key_exists('Name', $userParameters) ? $User->Name = $userParameters['Name'] : '';
        array_key_exists('Email', $userParameters) ? $User->Email = $userParameters['Email'] : '';
        array_key_exists('LegalRepresentativeBirthday', $userParameters) ? $User->LegalRepresentativeBirthday = $userParameters['LegalRepresentativeBirthday'] : '';
        array_key_exists('LegalRepresentativeCountryOfResidence', $userParameters) ? $User->LegalRepresentativeCountryOfResidence = $userParameters['LegalRepresentativeCountryOfResidence'] : '';
        array_key_exists('LegalRepresentativeNationality', $userParameters) ? $User->LegalRepresentativeNationality = $userParameters['LegalRepresentativeNationality'] : '';
        array_key_exists('LegalRepresentativeEmail', $userParameters) ? $User->LegalRepresentativeEmail = $userParameters['LegalRepresentativeEmail'] : '';
        array_key_exists('LegalRepresentativeFirstName', $userParameters) ? $User->LegalRepresentativeFirstName = $userParameters['LegalRepresentativeFirstName'] : '';
        array_key_exists('LegalRepresentativeLastName', $userParameters) ? $User->LegalRepresentativeLastName = $userParameters['LegalRepresentativeLastName'] : '';
        array_key_exists('LegalPersonType', $userParameters) ? $User->LegalPersonType = $userParameters['LegalPersonType'] : '';

        if (array_key_exists('LegalRepresentativeAddress', $userParameters)) {
            $User->LegalRepresentativeAddress = new \MangoPay\Address();
            $User->LegalRepresentativeAddress->AddressLine1 = $userParameters['LegalRepresentativeAddress']['AddressLine1'];
            $User->LegalRepresentativeAddress->City = $userParameters['LegalRepresentativeAddress']['City'];
            $User->LegalRepresentativeAddress->PostalCode = $userParameters['LegalRepresentativeAddress']['PostalCode'];
            $User->LegalRepresentativeAddress->Country = $userParameters['LegalRepresentativeAddress']['Country'];
            $User->LegalRepresentativeAddress->Region = $userParameters['LegalRepresentativeAddress']['Region'];
            array_key_exists('AddressLine2', $userParameters['LegalRepresentativeAddress']) ? $User->LegalRepresentativeAddress->AddressLine2 = $userParameters['LegalRepresentativeAddress']['AddressLine2'] : '';
            if (! in_array($userParameters['LegalRepresentativeAddress']['Country'], array(
                'US',
                'CA',
                'MX'
            ))) {
                array_key_exists('Region', $userParameters['LegalRepresentativeAddress']) ? $User->LegalRepresentativeAddress->Region = $userParameters['LegalRepresentativeAddress']['Region'] : '';
            } else {
                $User->LegalRepresentativeAddress->Region = $userParameters['LegalRepresentativeAddress']['Region'];
            }
        }

        if (array_key_exists('HeadquartersAddress', $userParameters)) {
            $User->HeadquartersAddress = new \MangoPay\Address();
            $User->HeadquartersAddress->AddressLine1 = $userParameters['HeadquartersAddress']['AddressLine1'];
            $User->HeadquartersAddress->City = $userParameters['HeadquartersAddress']['City'];
            $User->HeadquartersAddress->PostalCode = $userParameters['HeadquartersAddress']['PostalCode'];
            $User->HeadquartersAddress->Country = $userParameters['HeadquartersAddress']['Country'];
            $User->HeadquartersAddress->Region = $userParameters['HeadquartersAddress']['Region'];
            array_key_exists('AddressLine2', $userParameters['HeadquartersAddress']) ? $User->HeadquartersAddress->AddressLine2 = $userParameters['HeadquartersAddress']['AddressLine2'] : '';
            if (! in_array($userParameters['HeadquartersAddress']['Country'], array(
                'US',
                'CA',
                'MX'
            ))) {
                array_key_exists('Region', $userParameters['HeadquartersAddress']) ? $User->HeadquartersAddress->Region = $userParameters['HeadquartersAddress']['Region'] : '';
            } else {
                $User->HeadquartersAddress->Region = $userParameters['HeadquartersAddress']['Region'];
            }
        }

        try {
            $result['user'] = $mangoPayApiObject->Users->Update($User);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.editLegalUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getPayIn($payInId)
    {
        $payInId = (string) $payInId;
        $mangoPayApiObject = self::initApiObject();

        try {
            $result = $mangoPayApiObject->PayIns->Get($payInId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getPayOut($payOutId)
    {
        $payOutId = (string) $payOutId;
        $mangoPayApiObject = self::initApiObject();

        try {
            $result = $mangoPayApiObject->PayOuts->Get($payOutId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getSettlement($transferId)
    {
        $mangoPayApiObject = self::initApiObject();

        try {
            $result = $mangoPayApiObject->Disputes->GetSettlementTransfer($transferId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getTransfer($transferId)
    {
        $mangoPayApiObject = self::initApiObject();

        try {
            $result = $mangoPayApiObject->Transfers->Get($transferId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getRefund($refundId)
    {
        $mangoPayApiObject = self::initApiObject();

        try {
            $result = $mangoPayApiObject->Refunds->Get($refundId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getUser($userId)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.getUser', 'userId', $userId, 3, "IN");
        $result = array();
        $mangoPayApiObject = self::initApiObject();

        try {
            $result['user'] = $mangoPayApiObject->Users->Get($userId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.getUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getWallet($walletId)
    {
        $mangoPayApiObject = self::initApiObject();
        $result = array();
        try {
            $result = $mangoPayApiObject->Wallets->Get($walletId);
            $result->ProviderId = $result->Id;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getWalletTransactions($walletId, $pagination = null, $filter = null, $sorting = null)
    {
        $mangoPayApiObject = self::initApiObject();
        $result = array();
        try {
            $filter = null;
            $sorting = new \MangoPay\Sorting();
            $sorting->AddField('CreationDate', \MangoPay\SortDirection::DESC);
            if ($pagination != null) {
                $pagination = new \MangoPay\Pagination($pagination['page'], $pagination['itemsPerPage']);
            } else {
                $pagination = new \MangoPay\Pagination();
            }
            $result['Transactions'] = $mangoPayApiObject->Wallets->GetTransactions($walletId, $pagination, $filter, $sorting);
            $result['Pagination'] = $pagination;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getWebWalletTransactions($walletId, $pagination, $filter, $sorting)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.getWebWalletTransactions', 'walletId', $walletId, 3, "IN");
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.getWebWalletTransactions', 'filter', $filter, 3, "IN");

        $mangoPayApiObject = self::initApiObject();
        $result = array();

        try {
            if ($pagination != null) {
                $pagination = new \MangoPay\Pagination($pagination['page'], $pagination['itemsPerPage']);
            } else {
                $pagination = new \MangoPay\Pagination();
            }

            if ($filter != null) {
                $filtering = new \MangoPay\FilterTransactions();
                $filtering->AfterDate = array_key_exists("afterDate", $filter) ? $filter["afterDate"] : "";
                $filtering->BeforeDate = array_key_exists("beforeDate", $filter) ? $filter["beforeDate"] : "";
                $filtering->Nature = array_key_exists("nature", $filter) ? $filter["nature"] : "";
                $filtering->Status = array_key_exists("status", $filter) ? $filter["status"] : "";
                $filtering->Type = array_key_exists("type", $filter) ? $filter["type"] : "";
            } else {
                $filtering = $filter;
            }

            if ($sorting != null) {
                $sort = new \MangoPay\Sorting();
                foreach (array_keys($sorting) as $key) {
                    $sort->AddField($key, \MangoPay\SortDirection::$value);
                }
            } else {
                $sort = $sorting;
            }
            $resultObject = $mangoPayApiObject->Wallets->GetTransactions($walletId, $pagination, $filtering, $sort);
            foreach ($resultObject as &$transaction) {
                $transaction->ProviderId = $transaction->Id;
            }
            $result['Transactions'] = $resultObject;
            $result['Pagination'] = $pagination;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        return $result;
    }

    public static function getTransaction($transactionId)
    {
        // not implemented
        return null;
    }

    public static function saveDocument($userId, $uploadType, $uploadTag, $uploadDocPath1, $uploadDocPath2)
    {
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'userId', $userId, 3, "IN");
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'uploadType', $uploadType, 3, "IN");
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'uploadDocPath1', $uploadDocPath1, 3, "IN");
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'uploadDocPath2', $uploadDocPath2, 3, "IN");

        // init mango api
        $mangoPayApiObject = self::initApiObject();

        // create document
        try {
            // throw exception
            /*
             * $requestUrl = "https://api.sandbox.mangopay.com/v2.01/m3application5test/users/$userId/KYC/documents";
             * $code = 400;
             * $error = new \MangoPay\Libraries\Error();
             * $error->Message = 'plm eroare';
             * throw new \MangoPay\Libraries\ResponseException($requestUrl, $code, $error);
             */
            $kycDocument = new KycDocument();
            $kycDocument->Type = $uploadType;
            $kycDocument->Tag = $uploadTag;

            $document = $mangoPayApiObject->Users->CreateKycDocument($userId, $kycDocument);
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'document', get_object_vars($document), 3, "L");
        } catch (Exception $e) {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error creating Mango document: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error creating Mango document: " . $e->getTraceAsString(), 3, "L");
            return array(
                'error' => "Error creating document. Please retry!"
            );
        }

        if ($uploadDocPath1 != "") {
            $base64_picture_01 = base64_encode(file_get_contents($uploadDocPath1));

            try {
                $page_01 = new KycPage();
                $page_01->File = $base64_picture_01;
                $mangoPayApiObject->Users->CreateKycPage($userId, $document->Id, $page_01);
            } catch (Exception $e) {
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', $e->GetErrorDetails()->Errors, 3, "L");
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error uploading document page 1: " . $e->getMessage(), 3, "L");
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error uploading document page 1: " . $e->getTraceAsString(), 3, "L");
                return array(
                    'error' => "Error uploading document page 1"
                );
            }
        } else {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Document 1 missing " . $uploadDocPath1, 3, "L");
            return array(
                'error' => "Your document was not properly uploaded"
            );
        }

        if ($uploadDocPath2 != "") {
            $base64_picture_02 = base64_encode(file_get_contents($uploadDocPath2));
            try {
                $page_02 = new KycPage();
                $page_02->File = $base64_picture_02;
                $mangoPayApiObject->Users->CreateKycPage($userId, $document->Id, $page_02);
            } catch (Exception $e) {
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', $e->GetErrorDetails()->Errors, 3, "L");
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error uploading document page 2: " . $e->getMessage(), 3, "L");
                HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error uploading document page 2: " . $e->getTraceAsString(), 3, "L");
                return array(
                    'error' => "Error uploading document page 2"
                );
            }
        } else {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Document 2 missing " . $uploadDocPath2, 3, "L");
        }

        try {
            $document->Status = 'VALIDATION_ASKED';
            $updateDocument = $mangoPayApiObject->Users->UpdateKycDocument($userId, $document);
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'updateDocument', get_object_vars($updateDocument), 3, "OUT");
            return array(
                'document' => $updateDocument
            );
        } catch (Exception $e) {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', $e->GetErrorDetails()->Errors, 3, "L");
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error submitting document for approval: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.saveDocument', 'result', "Error submitting document for approval: " . $e->getTraceAsString(), 3, "L");
            return array(
                'error' => "Error submitting document for approval"
            );
        }
    }

    public static function processException($e, &$result)
    {
        if (get_class($e) == 'MangoPay\Libraries\ResponseException') {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'GetErrorDetails object', $e, 4, "IN");
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'GetErrorDetails message', $e->getMessage(), 3, "IN");
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'GetErrorDetails code', $e->getCode(), 3, "IN");

            if (is_object($e->GetErrorDetails()->Message)) {
                $errors = get_object_vars($e->GetErrorDetails()->Message);
            } else {
                $errors = array(
                    $e->GetErrorDetails()->Message
                );
            }

            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'GetErrorDetails Errors', $errors, 3, "OUT");

            if (count($errors) != 0) {
                foreach ($errors as $error) {
                    $result['error'][] = str_replace(' An incorrect resource ID also raises this kind of error.', '', $error);
                }
            } else {
                $result['error'][] = $e->getMessage();
            }
            unset($error, $errors);
        } else {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'Exception object', $e, 3, "OUT");
            $result['error'][] = $e->getMessage();
        }
        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.processException', 'result', $result, 3, "OUT");
    }

    /**
     * Retreive the ballance of a MangoPay wallet
     *
     * @param string $walletId
     *            - Mango Id
     * @return string wallet ballance in cents, in case of error array['error'] = 'error message';
     */
    public static function getBalanceByWalletId($walletId)
    {
        $mangoPayApiObject = self::initApiObject();
        try {
            $result = $mangoPayApiObject->Wallets->Get($walletId);
            $result = strval($result->Balance->Amount);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getStaticErrors()
    {
        return null;
    }

    public static function createCardRegistrationObject($params)
    {
        return null;
    }

    public static function updateCardRegistrationObject($params)
    {
        return null;
    }

    public static function getDepositCardByProviderId($cardProviderId)
    {
        return null;
    }

    public static function createDirectPayIn($payInParameters, $defaultCurrency = 'EUR')
    {
        return null;
    }

    public static function deactivateDepositCard($cardId)
    {
        return null;
    }

    public static function createPayInRefund($payInRefundParameters, $payInProviderId)
    {
        return null;
    }

    public static function getProcessedCallback($resourceId)
    {
        return null;
    }

    public static function getPayInStatus($providerId)
    {
        return null;
    }

    public static function getKycDocument($documentId)
    {
        return null;
    }
}