<?php
namespace HDFramework\src;

use LoloPay\Address;
use LoloPay\Amount;
use LoloPay\KycDocument;
use LoloPay\KycDocumentSubmit;
use LoloPay\KycPage;
use LoloPay\LoloPayApi;
use LoloPay\PersonType;
use LoloPay\RefundReasonDetails;
use LoloPay\RefundReasonType;
use LoloPay\Transaction;
use LoloPay\Ubo;
use LoloPay\UboDeclaration;
use LoloPay\UserNatural;
use LoloPay\Wallet;
use Exception;
use stdClass;
use LoloPay\Birthplace;

/**
 * Wrapper class to implement finance provider API
 *
 * Configurations dependencies:<br />
 * - index.php: base_libs<br />
 * <br />Release date: 29/09/2017
 *
 * @version 7.0
 * @package framework
 */
class HDLoloPay implements HDPayable, HDCardSupplier
{

    private static $loloPayApiObject = null;

    private static $loloPayKycLevels = array(
        'STANDARD' => 'LIGHT',
        'VERIFIED' => 'REGULAR',
        'HIGHRISK' => 'LIGHT'
    );

    private static $accountId = null;

    private static $applicationId = null;

    private static $password = null;

    private static $baseUrl = null;

    private static $temporaryFolder = null;

    private static $templateUrl = null;

    function __construct($accountId, $applicationId, $password, $baseUrl, $temporaryFolder, $templateUrl)
    {
        self::$accountId = $accountId;
        self::$applicationId = $applicationId;
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
        if (self::$loloPayApiObject == null) {
            // TODO remove this rule if someone else will develop on localhost this file

            // if (HDApplication::getEnvType() == 'local')
            // {
            // require_once '/home/cornel/git/lolopay-php-sdk/vendor/autoload.php';
            // }
            // else
            // {
            // }
            self::$loloPayApiObject = new LoloPayApi();
            self::$loloPayApiObject->config->accountId = self::$accountId;
            self::$loloPayApiObject->config->applicationId = self::$applicationId;
            self::$loloPayApiObject->config->password = self::$password;
            self::$loloPayApiObject->config->baseUrl = self::$baseUrl;
            self::$loloPayApiObject->config->temporaryFolder = self::$temporaryFolder;
            self::$loloPayApiObject->setDebugMode(false);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'initApiObject', 'out', 'no params', 3, "OUT");
        return self::$loloPayApiObject;
    }

    public static function createBankAccount($bankAccountParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'bankAccountParameters', $bankAccountParameters, 3, "IN");

        // init lolo api
        $loloPayApiObject = self::initApiObject();
        $result = array();

        switch ($bankAccountParameters['Type']) {
            case 'IBAN':
                $bankAccount = new \LoloPay\BankAccountDetailsIBAN();
                $bankAccount->iban = $bankAccountParameters['IBAN'];
                if (array_key_exists('BIC', $bankAccountParameters))
                    $bankAccount->bic = $bankAccountParameters['BIC'];
                break;
            case 'GB':
                $bankAccount = new \LoloPay\BankAccountDetailsGB();
                $bankAccount->accountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->sortCode = $bankAccountParameters['SortCode'];
                break;
            case 'US':
                $bankAccount = new \LoloPay\BankAccountDetailsUS();
                $bankAccount->accountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->aba = $bankAccountParameters['ABA'];
                $bankAccount->depositAccountType = $bankAccountParameters['DepositAccountType'];
                break;
            case 'CA':
                $bankAccount = new \LoloPay\BankAccountDetailsCA();
                $bankAccount->accountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->bankName = $bankAccountParameters['BankName'];
                $bankAccount->institutionNumber = $bankAccountParameters['InstitutionNumber'];
                $bankAccount->branchCode = $bankAccountParameters['BranchCode'];
                break;
            case 'OTHER':
                $bankAccount = new \LoloPay\BankAccountDetailsOTHER();
                $bankAccount->accountNumber = $bankAccountParameters['AccountNumber'];
                $bankAccount->country = $bankAccountParameters['Country'];
                $bankAccount->bic = $bankAccountParameters['BIC'];
                break;
        }

        $bankAccount->ownerName = $bankAccountParameters['OwnerName'];
        $bankAccount->type = $bankAccountParameters['Type'];
        $bankAccount->userId = $bankAccountParameters['UserId'];
        $bankAccount->ownerAddress = new \LoloPay\Address();
        $bankAccount->ownerAddress->addressLine1 = $bankAccountParameters['AddressLine1'];
        $bankAccount->ownerAddress->city = $bankAccountParameters['City'];
        $bankAccount->ownerAddress->country = $bankAccountParameters['Country'];
        if (array_key_exists('PostalCode', $bankAccountParameters))
            $bankAccount->ownerAddress->postalCode = $bankAccountParameters['PostalCode'];
        if (array_key_exists('Region', $bankAccountParameters))
            $bankAccount->ownerAddress->county = $bankAccountParameters['Region'];
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createBankAccount', 'bankAccount', get_object_vars($bankAccount), 3, "IN");

        try {
            $resultObject = $loloPayApiObject->bankAccounts->Create($bankAccount);
            $newObject = new stdClass();
            $newObject->Id = $resultObject->id;
            $newObject->CreationDate = $resultObject->createdAt;
            $newObject->Tag = $resultObject->customTag;
            $newObject->Type = $resultObject->type;
            $newObject->OwnerName = $resultObject->ownerName;
            $newObject->UserId = $resultObject->userId;
            $newObject->Active = $resultObject->active;
            $newObject->OwnerAddress = new stdClass();
            $newObject->OwnerAddress->AddressLine1 = $resultObject->ownerAddress->addressLine1;
            $newObject->OwnerAddress->AddressLine2 = $resultObject->ownerAddress->addressLine2;
            $newObject->OwnerAddress->City = $resultObject->ownerAddress->city;
            $newObject->OwnerAddress->Region = $resultObject->ownerAddress->county;
            $newObject->OwnerAddress->PostalCode = $resultObject->ownerAddress->postalCode;
            $newObject->OwnerAddress->Country = $resultObject->ownerAddress->country;
            $newObject->Details = new stdClass();
            switch ($resultObject->type) {
                case 'IBAN':
                    $newObject->Details->IBAN = $resultObject->iban;
                    isset($resultObject->bic) ? $newObject->Details->BIC = $resultObject->bic : "";
                    break;
                case 'GB':
                    $newObject->Details->SortCode = $resultObject->sortCode;
                    $newObject->Details->AccountNumber = $resultObject->accountNumber;
                    break;
                case 'US':
                    $newObject->Details->AccountNumber = $resultObject->accountNumber;
                    $newObject->Details->ABA = $resultObject->aba;
                    $newObject->Details->DepositAccountType = $resultObject->depositAccountType;
                    break;
                case 'CA':
                    $newObject->Details->BranchCode = $resultObject->branchCode;
                    $newObject->Details->InstitutionNumber = $resultObject->institutionNumber;
                    $newObject->Details->AccountNumber = $resultObject->accountNumber;
                    $newObject->Details->BankName = $resultObject->bankName;
                    break;
                case 'OTHER':
                    $newObject->Details->Country = $resultObject->country;
                    $newObject->Details->BIC = $resultObject->bic;
                    $newObject->Details->AccountNumber = $resultObject->accountNumber;
                    break;
            }
            $result['bankAccount'] = $newObject;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createBankAccount', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function createBankWire($bankWireParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'bankWireParameters', $bankWireParameters, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        $payOut = new \LoloPay\PayOut();
        $payOut->customTag = $bankWireParameters['Tag'];
        $payOut->debitedWalletId = $bankWireParameters['DebitedWalletId'];

        $payOut->amount = new \LoloPay\Amount();
        $payOut->amount->value = $bankWireParameters['DebitedFunds']['Amount'];
        $payOut->amount->currency = $bankWireParameters['DebitedFunds']['Currency'];

        $payOut->fees = new \LoloPay\Amount();
        $payOut->fees->value = $bankWireParameters['Fees']['Amount'];
        $payOut->fees->currency = $bankWireParameters['Fees']['Currency'];

        $payOut->feeModel = \LoloPay\FeeModel::Included;

        $payOut->bankAccountId = $bankWireParameters['BankAccountId'];

        $payOut->AuthorId = $bankWireParameters['AuthorId'];
        $result = array();
        try {
            $resultObject = $loloPayApiObject->payOuts->Create($payOut);
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createBankWire', 'resultObject', $resultObject, 3, "IN");
            $newObject = new stdClass();

            foreach ($resultObject as $payOut) {
                if ($payOut->type == \LoloPay\TransactionType::payout) {
                    $newObject->DebitedWalletId = $payOut->debitedWalletId;
                    $newObject->PaymentType = $payOut->paymentType;

                    $newObject->MeanOfPaymentDetails = new stdClass();
                    $newObject->MeanOfPaymentDetails->BankAccountId = $payOut->bankAccountId;
                    $newObject->MeanOfPaymentDetails->BankWireRef = $payOut->bankWireRef;
                    $newObject->AuthorId = $payOut->debitedUserId;

                    $newObject->DebitedFunds = new stdClass();
                    $newObject->DebitedFunds->Currency = $payOut->amount->currency;
                    $newObject->DebitedFunds->Amount = $payOut->amount->value;

                    $newObject->CreditedFunds = new stdClass();
                    $newObject->CreditedFunds->Currency = $payOut->amount->currency;
                    $newObject->CreditedFunds->Amount = $payOut->amount->value;

                    $newObject->Status = $payOut->status;
                    $newObject->ResultCode = $payOut->resultCode; // returns the provider code transaction
                    $newObject->ResultMessage = $payOut->resultMessage; // returns the provider transaction message
                    $newObject->ExecutionDate = $payOut->executionDate;
                    $newObject->Type = $payOut->type;
                    $newObject->Id = $payOut->id;

                    $newObject->CreationDate = $payOut->createdAt;
                    $newObject->Tag = $payOut->customTag;
                    $newObject->Nature = $payOut->nature;

                    $newObject->CreditedWalletId = $payOut->creditedWalletId;
                    $newObject->CreditedUserId = $payOut->creditedUserId;

                    $newObject->DebitedWalletId = $payOut->debitedWalletId;

                    $newObject->Fees = self::getTransactionFees($payOut);
                }
            }

            $result['bankWire'] = $newObject;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createBankWire', 'result', $result, 3, "IN");
        return $result;
    }

    public static function createLegalUser($legalUserParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'legalUserParameters', $legalUserParameters, 3, "IN");

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

        $loloPayApiObject = self::initApiObject();

        $User = new \LoloPay\UserLegal();

        $User->mobilePhone = $legalUserParameters['mobilePhone'];
        $User->PersonType = \LoloPay\PersonType::Legal;
        $User->companyType = $legalUserParameters['LegalPersonType'];
        $User->companyName = $legalUserParameters['Name'];
        $User->birthDate = $legalUserParameters['LegalRepresentativeBirthday'];
        $User->countryOfResidence = $legalUserParameters['LegalRepresentativeCountryOfResidence'];
        $User->nationality = $legalUserParameters['LegalRepresentativeNationality'];
        $User->firstName = $legalUserParameters['LegalRepresentativeFirstName'];
        $User->lastName = $legalUserParameters['LegalRepresentativeLastName'];
        $User->companyEmail = $legalUserParameters['Email'];
        $User->companyRegistrationNumber = $legalUserParameters['CompanyNumber'];

        isset($legalUserParameters['Tag']) ? $User->customTag = $legalUserParameters['Tag'] : '';
        isset($legalUserParameters['LegalRepresentativeEmail']) ? $User->email = $legalUserParameters['LegalRepresentativeEmail'] : '';

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
            $User->companyAddress = new \LoloPay\Address();
            $User->companyAddress->addressLine1 = $legalUserParameters['HeadquartersAddress']['AddressLine1'];
            $User->companyAddress->city = $legalUserParameters['HeadquartersAddress']['City'];
            $User->companyAddress->county = $legalUserParameters['HeadquartersAddress']['Region'];
            $User->companyAddress->postalCode = $legalUserParameters['HeadquartersAddress']['PostalCode'];
            $User->companyAddress->country = $legalUserParameters['HeadquartersAddress']['Country'];
            isset($legalUserParameters['HeadquartersAddress']['AddressLine2']) ? $User->companyAddress->addressLine2 = $legalUserParameters['HeadquartersAddress']['AddressLine2'] : '';
        }

        if (array_key_exists('LegalRepresentativeAddress', $legalUserParameters)) {
            foreach ($addressMandatoryKeys as $addressMandatoryKey) {
                if (! array_key_exists($addressMandatoryKey, $legalUserParameters['LegalRepresentativeAddress']) || ! isset($legalUserParameters['LegalRepresentativeAddress'][$addressMandatoryKey])) {
                    $result['error'][] = 'Legal Representative Address missing mandatory fields';
                    return $result;
                }
            }

            $User->address = new \LoloPay\Address();
            $User->address->addressLine1 = $legalUserParameters['LegalRepresentativeAddress']['AddressLine1'];
            $User->address->city = $legalUserParameters['LegalRepresentativeAddress']['City'];
            $User->address->county = $legalUserParameters['LegalRepresentativeAddress']['Region'];
            $User->address->postalCode = $legalUserParameters['LegalRepresentativeAddress']['PostalCode'];
            $User->address->country = $legalUserParameters['LegalRepresentativeAddress']['Country'];
            isset($legalUserParameters['LegalRepresentativeAddress']['AddressLine2']) ? $User->address->addressLine2 = $legalUserParameters['LegalRepresentativeAddress']['AddressLine2'] : '';
        }

        try {
            HDLog::AppLogMessage('HDLoloPay.php', 'createLegalUser', 'User', $User, 3, "L");
            $loloLegalUser = $loloPayApiObject->users->Create($User);
            HDLog::AppLogMessage('HDLoloPay.php', 'createLegalUser', 'loloLegalUser', $loloLegalUser, 3, "L");

            $m3LegalUser = new stdClass();
            $m3LegalUser->Name = $loloLegalUser->companyName;
            $m3LegalUser->LegalPersonType = $loloLegalUser->companyType;

            $m3LegalUser->HeadquartersAddress = new stdClass();
            $m3LegalUser->HeadquartersAddress->AddressLine1 = $loloLegalUser->companyAddress->addressLine1;
            $m3LegalUser->HeadquartersAddress->AddressLine2 = $loloLegalUser->companyAddress->addressLine2;
            $m3LegalUser->HeadquartersAddress->City = $loloLegalUser->companyAddress->city;
            $m3LegalUser->HeadquartersAddress->Region = $loloLegalUser->companyAddress->county;
            $m3LegalUser->HeadquartersAddress->PostalCode = $loloLegalUser->companyAddress->postalCode;
            $m3LegalUser->HeadquartersAddress->Country = $loloLegalUser->companyAddress->country;

            $m3LegalUser->LegalRepresentativeFirstName = $loloLegalUser->firstName;
            $m3LegalUser->LegalRepresentativeLastName = $loloLegalUser->lastName;

            $m3LegalUser->LegalRepresentativeAddress = new stdClass();
            $m3LegalUser->LegalRepresentativeAddress->AddressLine1 = $loloLegalUser->address->addressLine1;
            $m3LegalUser->LegalRepresentativeAddress->AddressLine2 = $loloLegalUser->address->addressLine2;
            $m3LegalUser->LegalRepresentativeAddress->City = $loloLegalUser->address->city;
            $m3LegalUser->LegalRepresentativeAddress->Region = $loloLegalUser->address->county;
            $m3LegalUser->LegalRepresentativeAddress->PostalCode = $loloLegalUser->address->postalCode;
            $m3LegalUser->LegalRepresentativeAddress->Country = $loloLegalUser->address->country;

            $m3LegalUser->LegalRepresentativeEmail = $loloLegalUser->email;
            $m3LegalUser->LegalRepresentativeBirthday = $loloLegalUser->birthDate;
            $m3LegalUser->LegalRepresentativeNationality = $loloLegalUser->nationality;
            $m3LegalUser->LegalRepresentativeCountryOfResidence = $loloLegalUser->countryOfResidence;
            $m3LegalUser->ProofOfIdentity = null;
            $m3LegalUser->Statute = null;
            $m3LegalUser->ProofOfRegistration = null;
            $m3LegalUser->ShareholderDeclaration = null;

            $m3LegalUser->PersonType = $loloLegalUser->type;
            $m3LegalUser->Email = $loloLegalUser->companyEmail;
            $m3LegalUser->KYCLevel = $loloLegalUser->kycLevel;
            $m3LegalUser->Id = $loloLegalUser->id;
            $m3LegalUser->Tag = $loloLegalUser->customTag;
            $m3LegalUser->CreationDate = $loloLegalUser->createdAt;
            $m3LegalUser->CompanyNumber = $loloLegalUser->companyRegistrationNumber;

            $result['user'] = $m3LegalUser;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'createLegalUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function createPayIn($payInParameters, $defaultCurrency = 'EUR')
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payInParameters', $payInParameters, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        $PayIn = new \LoloPay\PayIn();

        $PayIn->customTag = $payInParameters['Tag'];

        $PayIn->amount = new \LoloPay\Amount();
        $PayIn->amount->currency = $defaultCurrency;
        $PayIn->amount->value = $payInParameters['DebitedFunds'];

        $PayIn->feeModel = \LoloPay\FeeModel::Included;

        $PayIn->fees = new \LoloPay\Amount();
        $PayIn->fees->currency = $defaultCurrency;
        $PayIn->fees->value = $payInParameters['Fees'];

        $PayIn->creditedWalletId = $payInParameters['CreditedWalletId'];

        $PayIn->returnURL = $payInParameters['ReturnURL'];
        $PayIn->culture = $payInParameters['Culture'];
        $PayIn->secureMode = $payInParameters['SecureMode'];

        $PayIn->templateURL = self::$templateUrl;
        $PayIn->cardType = $payInParameters['CardType'];

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createPayIn', 'PayIn', get_object_vars($PayIn), 3, "OUT");
        $result = array();
        try {
            $loloPayInsResponse = $loloPayApiObject->payIns->Create($PayIn);
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createPayIn', '$loloPayInsResponse', $loloPayInsResponse, 3, "OUT");
            $m3PayIn = new stdClass();

            foreach ($loloPayInsResponse as $loloPayInResponse) {
                if ($loloPayInResponse->type == \LoloPay\TransactionType::payIn) {
                    $m3PayIn->CreditedWalletId = $loloPayInResponse->creditedWalletId;
                    $m3PayIn->PaymentType = $loloPayInResponse->paymentType;
                    $m3PayIn->ExecutionType = $loloPayInResponse->executionType;

                    $m3PayIn->CreditedUserId = $loloPayInResponse->creditedUserId;
                    $m3PayIn->Status = $loloPayInResponse->status;
                    $m3PayIn->ResultCode = $loloPayInResponse->resultCode;
                    $m3PayIn->ResultMessage = $loloPayInResponse->resultMessage;
                    $m3PayIn->ExecutionDate = $loloPayInResponse->executionDate;
                    $m3PayIn->Type = $loloPayInResponse->type;
                    $m3PayIn->Nature = $loloPayInResponse->nature;
                    $m3PayIn->DebitedWalletId = $loloPayInResponse->debitedWalletId;
                    $m3PayIn->Id = $loloPayInResponse->id;
                    $m3PayIn->Tag = $loloPayInResponse->customTag;
                    $m3PayIn->CreationDate = $loloPayInResponse->createdAt;
                    $m3PayIn->AuthorId = $loloPayInResponse->creditedUserId;

                    $m3PayIn->PaymentDetails = new stdClass();
                    $m3PayIn->PaymentDetails->CardType = $loloPayInResponse->cardType;
                    $m3PayIn->PaymentDetails->CardId = '';

                    $m3PayIn->ExecutionDetails = new stdClass();
                    $m3PayIn->ExecutionDetails->RedirectURL = $loloPayInResponse->redirectURL;
                    $m3PayIn->ExecutionDetails->ReturnURL = $loloPayInResponse->returnURL;
                    $m3PayIn->ExecutionDetails->TemplateURL = $loloPayInResponse->templateURL;
                    $m3PayIn->ExecutionDetails->TemplateURLOptions = '';
                    $m3PayIn->ExecutionDetails->Culture = $loloPayInResponse->culture;
                    $m3PayIn->ExecutionDetails->SecureMode = $loloPayInResponse->secureMode;

                    $m3PayIn->DebitedFunds = new stdClass();
                    $m3PayIn->DebitedFunds->Currency = $loloPayInResponse->amount->currency;
                    $m3PayIn->DebitedFunds->Amount = $loloPayInResponse->amount->value;

                    $m3PayIn->CreditedFunds = new stdClass();
                    $m3PayIn->CreditedFunds->Currency = $loloPayInResponse->amount->currency;
                    $m3PayIn->CreditedFunds->Amount = $loloPayInResponse->amount->value;
                    $m3PayIn->Fees = self::getTransactionFees($loloPayInResponse);
                }
            }

            $result['payIn'] = $m3PayIn;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createPayIn', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function createTransfer($transferParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'transferParameters', $transferParameters, 3, "IN");

        $loloPayApiObject = self::initApiObject();
        $result = array();

        $Transfer = new \LoloPay\Transfer();
        $Transfer->debitedWalletId = $transferParameters['DebitedWalletId'];
        $Transfer->creditedWalletId = $transferParameters['CreditedWalletId'];
        $Transfer->authorId = $transferParameters['AuthorId'];
        $Transfer->amount = new \LoloPay\Amount();
        $Transfer->amount->value = $transferParameters['DebitedFunds']['Amount'];
        $Transfer->amount->currency = $transferParameters['DebitedFunds']['Currency'];
        $Transfer->fees = new \LoloPay\Amount();
        $Transfer->fees->value = $transferParameters['Fees']['Amount'];
        $Transfer->fees->currency = $transferParameters['Fees']['Currency'];
        $Transfer->customTag = $transferParameters['Tag'];
        $Transfer->feeModel = \LoloPay\FeeModel::Included;

        try {
            $resultsArray = $loloPayApiObject->transfers->Create($Transfer);
            $loloPayTransfer = new stdClass();
            $loloPayTransferFee = new stdClass();

            foreach ($resultsArray as $resultTransfers) {
                if ($resultTransfers->type == "TRANSFER") {
                    $loloPayTransfer = $resultTransfers;
                }

                if ($resultTransfers->type == "TRANSFER_FEE") {
                    $loloPayTransferFee = $resultTransfers;
                }
            }

            $newObject = new stdClass();
            $newObject->DebitedWalletId = $loloPayTransfer->debitedWalletId;
            $newObject->CreditedWalletId = $loloPayTransfer->creditedWalletId;
            $newObject->AuthorId = $loloPayTransfer->debitedUserId;
            $newObject->CreditedUserId = $loloPayTransfer->creditedUserId;
            $newObject->DebitedFunds = new stdClass();
            $newObject->DebitedFunds->Currency = $loloPayTransfer->amount->currency;
            $newObject->DebitedFunds->Amount = $loloPayTransfer->amount->value;
            $newObject->CreditedFunds = new stdClass();
            $newObject->CreditedFunds->Currency = $loloPayTransfer->amount->currency;
            $newObject->CreditedFunds->Amount = $loloPayTransfer->amount->value;
            $newObject->Status = $loloPayTransfer->status;
            $newObject->ResultCode = ''; // lolopay never sends this for transfers
            $newObject->ResultMessage = ''; // lolopay never sends this for transfers
            $newObject->ExecutionDate = isset($loloPayTransfer->executionDate) ? $loloPayTransfer->executionDate : '';
            $newObject->Nature = $loloPayTransfer->nature;
            $newObject->Id = $loloPayTransfer->id;
            $newObject->Tag = $loloPayTransfer->customTag;
            $newObject->CreationDate = $loloPayTransfer->createdAt;
            $newObject->Type = $loloPayTransfer->type;
            $newObject->Fees = new stdClass();

            $newObject->Fees->Currency = "";
            if (isset($loloPayTransferFee->amount->currency)) {
                $newObject->Fees->Currency = $loloPayTransferFee->amount->currency;
            }

            $newObject->Fees->Amount = 0;
            if (isset($loloPayTransferFee->amount->value)) {
                $newObject->Fees->Amount = $loloPayTransferFee->amount->value;
            }

            $result['Transfer'] = $newObject;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createTransfer', 'result', $result, 3, "OUT");
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
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'transferRefundParameters', $transferRefundParameters, 3, "IN");

        $result = array();
        $loloPayApiObject = self::initApiObject();

        $Refund = new \LoloPay\Refund();
        $Refund->transactionId = $transferRefundParameters["TransferId"];
        $Refund->customTag = $transferRefundParameters['Tag'];

        try {
            $loloRefunds = $loloPayApiObject->transfers->CreateRefund($Refund);

            HDLog::AppLogMessage('HDLoloPay.php', 'createTransferRefund', 'loloRefunds', $loloRefunds, 3, "L");

            $loloPayRefund = new stdClass();
            $loloPayRefundFee = new stdClass();

            foreach ($loloRefunds as $loloRefund) {
                if ($loloRefund->type == "TRANSFER") {
                    $loloPayRefund = $loloRefund;
                }

                if ($loloRefund->type == "TRANSFER_FEE") {
                    $loloPayRefundFee = $loloRefund;
                }
            }

            $m3Refund = new stdClass();
            $m3Refund->Id = $loloPayRefund->id;
            $m3Refund->CreationDate = $loloPayRefund->createdAt;
            $m3Refund->Tag = $loloPayRefund->customTag;
            $m3Refund->DebitedFunds = new stdClass();
            $m3Refund->DebitedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->DebitedFunds->Amount = $loloPayRefund->amount->currency;
            $m3Refund->CreditedFunds = new stdClass();
            $m3Refund->CreditedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->CreditedFunds->Amount = $loloPayRefund->amount->currency;
            $m3Refund->DebitedWalletId = $loloPayRefund->debitedWalletId;
            $m3Refund->CreditedWalletId = $loloPayRefund->creditedWalletId;
            $m3Refund->CreditedUserId = $loloPayRefund->creditedUserId;
            $m3Refund->Nature = $loloPayRefund->nature;
            $m3Refund->Status = $loloPayRefund->status;
            $m3Refund->ExecutionDate = $loloPayRefund->executionDate;
            $m3Refund->Type = $loloPayRefund->type;

            $m3Refund->Fees = new stdClass();
            $m3Refund->Fees->Currency = "";
            if (isset($loloPayRefundFee->amount->currency)) {
                $m3Refund->Fees->Currency = $loloPayRefundFee->amount->currency;
            }

            $m3Refund->Fees->Amount = 0;
            if (isset($loloPayRefundFee->amount->value)) {
                $m3Refund->Fees->Amount = $loloPayRefundFee->amount->value;
            }

            $m3Refund->InitialTransactionId = $loloPayRefund->initialTransactionId;
            $m3Refund->InitialTransactionType = $loloPayRefund->initialTransactionType;
            $m3Refund->AuthorId = $loloPayRefund->debitedUserId;

            $m3Refund->RefundReason = new stdClass();

            $m3Refund->RefundReason->RefusedReasonType = "";
            if (! empty($loloPayRefund->refundReasonDetails->refundReasonType)) {
                $m3Refund->RefundReason->RefusedReasonType = $loloPayRefund->refundReasonDetails->refundReasonType;
            }

            $m3Refund->RefundReason->RefusedReasonMessage = "";
            if (! empty($loloPayRefund->refundReasonDetails->refusedReasonMessage)) {
                $m3Refund->RefundReason->RefusedReasonMessage = $loloPayRefund->refundReasonDetails->refusedReasonMessage;
            }

            $m3Refund->InitialTransactionId = $loloPayRefund->initialTransactionId;
            $m3Refund->InitialTransactionType = $loloPayRefund->initialTransactionType;
            $m3Refund->ResultCode = $loloPayRefund->resultCode;
            $m3Refund->ResultMessage = $loloPayRefund->resultMessage;

            $result['TransferRefund'] = $m3Refund;
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
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userParameters', $userParameters, 3, "IN");

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
        $loloPayApiObject = self::initApiObject();

        $user = new UserNatural();
        $user->email = $userParameters['Email'];
        $user->firstName = $userParameters['FirstName'];
        $user->lastName = $userParameters['LastName'];
        $user->birthDate = intval($userParameters['Birthday']);
        $user->nationality = $userParameters['Nationality'];
        $user->countryOfResidence = $userParameters['CountryOfResidence'];
        $user->customTag = $userParameters['Tag'];
        $user->mobilePhone = $userParameters['Tag'];

        if (array_key_exists('Address', $userParameters)) {
            $user->address = new Address();
            $user->address->addressLine1 = $userParameters['Address']['AddressLine1'];
            $user->address->city = $userParameters['Address']['City'];
            $user->address->country = $userParameters['Address']['Country'];
            $user->address->postalCode = $userParameters['Address']['PostalCode'];
            $user->address->county = $userParameters['Address']['Region'];
        }

        try {
            $resultObject = $loloPayApiObject->users->Create($user);
            $newObject = new stdClass();
            $newObject->FirstName = $resultObject->firstName;
            $newObject->LastName = $resultObject->lastName;
            $newObject->Address = $resultObject->address;
            $newObject->Birthday = $resultObject->birthDate;
            $newObject->Nationality = $resultObject->nationality;
            $newObject->CountryOfResidence = $resultObject->countryOfResidence;
            $newObject->Occupation = $resultObject->occupation;
            $newObject->IncomeRange = $resultObject->incomeRange;
            $newObject->ProofOfIdentity = null; // lolopay does not have this field
            $newObject->ProofOfAddress = null; // lolopay does not have this field
            $newObject->PersonType = $resultObject->type;
            $newObject->Email = $resultObject->email;

            if (isset($resultObject->kycLevel) && array_key_exists($resultObject->kycLevel, self::$loloPayKycLevels)) {
                $newObject->KYCLevel = self::$loloPayKycLevels[$resultObject->kycLevel];
            } else {
                $newObject->KYCLevel = self::$loloPayKycLevels['STANDARD'];
            }

            $newObject->Id = $resultObject->id;
            $newObject->Tag = $resultObject->customTag;
            $newObject->CreationDate = $resultObject->createdAt;
            $result['user'] = $newObject;
            unset($newObject, $resultObject);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.confirm', 'result', $result, 3, "OUT");
        return $result;
    }

    /**
     * Function to create a wallet
     *
     * @param array $walletParameters
     */
    public static function createWallet($walletParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'walletParameters', $walletParameters, 3, "IN");

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
        $loloPayApiObject = self::initApiObject();

        $Wallet = new Wallet();
        $Wallet->userId = $walletParameters['Owners'][0];
        $Wallet->description = $walletParameters['Description'];
        $Wallet->currency = $walletParameters['Currency'];
        $Wallet->customTag = $walletParameters['Tag'];

        try {
            $resultObject = $loloPayApiObject->wallets->Create($Wallet);
            $newObject = new stdClass();
            $newObject->Owners = array(
                $resultObject->userId
            );
            $newObject->Description = $resultObject->description;
            $newObject->Balance = new stdClass();
            $newObject->Balance->Currency = $resultObject->balance->currency;
            $newObject->Balance->Amount = $resultObject->balance->value;
            $newObject->Currency = $resultObject->currency;
            $newObject->Id = $resultObject->id;
            $newObject->Tag = $resultObject->customTag;
            $newObject->CreationDate = $resultObject->createdAt;
            $result['wallet'] = $newObject;
            unset($resultObject, $newObject);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createWallet', '$result', $result, 3, "OUT");
        return $result;
    }

    public static function documentsList($userId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userId', $userId, 3, "IN");

        $result = array();
        $loloPayApiObject = self::initApiObject();

        try {

            $currentPage = 1;
            $totalPages = 1;
            $itemsPerPage = 10;

            $currentDocIndex = 0;
            $newObject = array();

            while ($currentPage <= $totalPages) {
                $pagination = new \LoloPay\Pagination($currentPage, $itemsPerPage);
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, '$pagination before', $pagination, 3, "OUT");

                $resultObject = $loloPayApiObject->users->GetKycDocuments($userId, $pagination);
                HDLog::AppLogMessage(__CLASS__, __FUNCTION__, '$resultObject', $resultObject, 3, "OUT");

                foreach ($resultObject as $document) {
                    $newObject[$currentDocIndex] = new stdClass();
                    $newObject[$currentDocIndex]->Type = $document->type;
                    $newObject[$currentDocIndex]->Status = $document->status;
                    $newObject[$currentDocIndex]->RefusedReasonType = $document->rejectionReasonType;
                    $newObject[$currentDocIndex]->Tag = $document->customTag;
                    $newObject[$currentDocIndex]->UserId = $document->userId;
                    $newObject[$currentDocIndex]->Id = $document->id;
                    $newObject[$currentDocIndex]->CreationDate = $document->createdAt;
                    $currentDocIndex ++;
                }

                $totalPages = $pagination->totalPages;
                $currentPage ++;
            }

            $result['documentsList'] = $newObject;
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', $result, 3, "OUT");
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function createUboDeclaration($providerUserId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'providerUserId', $providerUserId, 3, "IN");
        $result = array();
        $loloPayApiObject = self::initApiObject();
        try {
            $uboDeclaration = new UboDeclaration();
            $uboDeclaration->userId = $providerUserId;
            $document = $loloPayApiObject->ubo->CreateDeclaration($uboDeclaration);
            $result['ubo_declaration'] = $document;
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'document', get_object_vars($document), 3, "L");
        } catch (Exception $e) {
            self::processException($e, $result);
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', "Error creating Ubo declaration: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', "Error creating Ubo declaration: " . $e->getTraceAsString(), 3, "L");
        }
        return $result;
    }

    public static function createUboEntry($providerUserId, $uboDeclarationId, $params, $uboId = null)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'params', $params, 3, "IN");
        $resultObject = array();
        $loloPayApiObject = self::initApiObject();
        try {
            $uboEntry = new Ubo();
            $uboEntry->firstName = $params['firstName'];
            $uboEntry->lastName = $params['lastName'];
            $uboEntry->nationality = $params['nationality'];
            $uboEntry->birthday = strtotime(date('Y-m-d', strtotime($params['birthDate'])));

            $address = new Address();
            $address->addressLine1 = $params['address']['addressLine1'];
            $address->addressLine2 = $params['address']['addressLine2'];
            $address->city = $params['address']['city'];
            $address->county = $params['address']['region'];
            $address->postalCode = $params['address']['postalCode'];
            $address->country = $params['address']['country'];
            $uboEntry->address = $address;

            $birthplace = new Birthplace();
            $birthplace->city = $params['birthplace']['city'];
            $birthplace->country = $params['birthplace']['country'];
            $uboEntry->birthplace = $birthplace;

            HDLog::AppLogMessage('HDLoloPay.php', 'createUboEntry', 'uboEntry', $uboEntry, 3, "OUT");
            if ($uboId == null or $uboId == "") {
                $resultObject['ubo_entry'] = $loloPayApiObject->ubo->CreateUbo($providerUserId, $uboDeclarationId, $uboEntry);
            } else {
                $resultObject['ubo_entry'] = $loloPayApiObject->ubo->UpdateUbo($providerUserId, $uboDeclarationId, $uboId, $uboEntry);
            }
        } catch (Exception $e) {
            self::processException($e, $resultObject);
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', "Error creating Ubo declaration: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', "Error creating Ubo declaration: " . $e->getTraceAsString(), 3, "L");
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'createUboEntry', 'resultObject', $resultObject, 3, "OUT");
        return $resultObject;
    }

    public static function submitUboDeclaration($providerUserId, $uboDeclarationId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'providerUserId', $providerUserId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uboDeclarationId', $uboDeclarationId, 3, "IN");

        $resultObject = array();
        $loloPayApiObject = self::initApiObject();

        $uboDeclaration = new UboDeclaration();
        $uboDeclaration->userId = $providerUserId;
        $uboDeclaration->providerId = $uboDeclarationId;
        try {
            $resultObject = $loloPayApiObject->ubo->SubmitDeclaration($uboDeclaration);
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'resultObject', $resultObject, 3, "OUT");
            return $resultObject;
        } catch (Exception $e) {
            self::processException($e, $resultObject);
        }
        return $resultObject;
    }

    public function getUboDeclaration($uboDeclarationId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uboDeclarationId', $uboDeclarationId, 3, "IN");

        $resultObject = array();
        $loloPayApiObject = self::initApiObject();

        $uboDeclaration = new UboDeclaration();
        $uboDeclaration->providerId = $uboDeclarationId;
        try {
            $resultObject = $loloPayApiObject->ubo->getUboDeclaration($uboDeclaration);
            HDLog::AppLogMessage('HDLoloPay.php', 'getUboDeclaration', 'resultObject', $resultObject, 3, "OUT");
            return $resultObject;
        } catch (Exception $e) {
            self::processException($e, $resultObject);
        }
        return $resultObject;
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
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userParameters', $userParameters, 3, "IN");

        $result = array();

        $loloPayApiObject = self::initApiObject();

        $User = new UserNatural();
        $User->id = $userParameters['Id'];
        array_key_exists('Email', $userParameters) ? $User->email = $userParameters['Email'] : "";
        array_key_exists('FirstName', $userParameters) ? $User->firstName = $userParameters['FirstName'] : "";
        array_key_exists('LastName', $userParameters) ? $User->lastName = $userParameters['LastName'] : "";
        array_key_exists('Birthday', $userParameters) ? $User->birthDate = intval($userParameters['Birthday']) : "";
        array_key_exists('Nationality', $userParameters) ? $User->nationality = $userParameters['Nationality'] : "";
        array_key_exists('CountryOfResidence', $userParameters) ? $User->countryOfResidence = $userParameters['CountryOfResidence'] : "";
        array_key_exists('Tag', $userParameters) ? $User->customTag = $userParameters['Tag'] : "";
        array_key_exists('Occupation', $userParameters) ? $User->occupation = $userParameters['Occupation'] : "";

        if (array_key_exists('IncomeRange', $userParameters)) {
            switch ($userParameters['IncomeRange']) {
                case 1:
                    $User->incomeRange = 'BELOW_18K';
                    break;
                case 2:
                    $User->incomeRange = 'BELOW_30K';
                    break;
                case 3:
                    $User->incomeRange = 'BELOW_50K';
                    break;
                case 4:
                    $User->incomeRange = 'BELOW_80K';
                    break;
                case 5:
                    $User->incomeRange = 'BELOW_120K';
                    break;
                case 6:
                    $User->incomeRange = 'ABOVE_120K';
                    break;
                default:
                    $User->incomeRange = 'BELOW_18K';
            }
        }

        if (array_key_exists('Address', $userParameters)) {
            $User->address = new Address();
            $User->address->addressLine1 = $userParameters['Address']['AddressLine1'];
            $User->address->city = $userParameters['Address']['City'];
            $User->address->country = $userParameters['Address']['Country'];
            $User->address->postalCode = $userParameters['Address']['PostalCode'];
            $User->address->county = $userParameters['Address']['Region'];
        }

        try {
            $result['user'] = $loloPayApiObject->users->Save($User);
            // don't convert object for now because the result received from server is never used
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.editUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function editLegalUser($userParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userParameters', $userParameters, 3, "IN");

        $result = array();
        $loloPayApiObject = self::initApiObject();
        $User = new \LoloPay\UserLegal();

        array_key_exists('Id', $userParameters) ? $User->id = $userParameters['Id'] : '';
        array_key_exists('Tag', $userParameters) ? $User->customTag = $userParameters['Tag'] : '';
        array_key_exists('Name', $userParameters) ? $User->companyName = $userParameters['Name'] : '';
        array_key_exists('Email', $userParameters) ? $User->companyEmail = $userParameters['Email'] : '';
        array_key_exists('LegalRepresentativeBirthday', $userParameters) ? $User->birthDate = $userParameters['LegalRepresentativeBirthday'] : '';
        array_key_exists('LegalRepresentativeCountryOfResidence', $userParameters) ? $User->countryOfResidence = $userParameters['LegalRepresentativeCountryOfResidence'] : '';
        array_key_exists('LegalRepresentativeNationality', $userParameters) ? $User->nationality = $userParameters['LegalRepresentativeNationality'] : '';
        array_key_exists('LegalRepresentativeEmail', $userParameters) ? $User->email = $userParameters['LegalRepresentativeEmail'] : '';
        array_key_exists('LegalRepresentativeFirstName', $userParameters) ? $User->firstName = $userParameters['LegalRepresentativeFirstName'] : '';
        array_key_exists('LegalRepresentativeLastName', $userParameters) ? $User->lastName = $userParameters['LegalRepresentativeLastName'] : '';
        array_key_exists('LegalPersonType', $userParameters) ? $User->companyType = $userParameters['LegalPersonType'] : '';
        array_key_exists('CompanyNumber', $userParameters) ? $User->companyRegistrationNumber = $userParameters['CompanyNumber'] : '';

        if (array_key_exists('LegalRepresentativeAddress', $userParameters)) {
            $User->address = new \LoloPay\Address();
            $User->address->addressLine1 = $userParameters['LegalRepresentativeAddress']['AddressLine1'];
            $User->address->city = $userParameters['LegalRepresentativeAddress']['City'];
            $User->address->postalCode = $userParameters['LegalRepresentativeAddress']['PostalCode'];
            $User->address->country = $userParameters['LegalRepresentativeAddress']['Country'];
            $User->address->region = $userParameters['LegalRepresentativeAddress']['Region'];
            array_key_exists('AddressLine2', $userParameters['LegalRepresentativeAddress']) ? $User->address->addressLine2 = $userParameters['LegalRepresentativeAddress']['AddressLine2'] : '';
            if (! in_array($userParameters['LegalRepresentativeAddress']['Country'], array(
                'US',
                'CA',
                'MX'
            ))) {
                array_key_exists('Region', $userParameters['LegalRepresentativeAddress']) ? $User->address->county = $userParameters['LegalRepresentativeAddress']['Region'] : '';
            } else {
                $User->address->county = $userParameters['LegalRepresentativeAddress']['Region'];
            }
        }

        if (array_key_exists('HeadquartersAddress', $userParameters)) {
            $User->companyAddress = new \LoloPay\Address();
            $User->companyAddress->addressLine1 = $userParameters['HeadquartersAddress']['AddressLine1'];
            $User->companyAddress->city = $userParameters['HeadquartersAddress']['City'];
            $User->companyAddress->postalCode = $userParameters['HeadquartersAddress']['PostalCode'];
            $User->companyAddress->country = $userParameters['HeadquartersAddress']['Country'];
            $User->companyAddress->region = $userParameters['HeadquartersAddress']['Region'];
            array_key_exists('AddressLine2', $userParameters['HeadquartersAddress']) ? $User->companyAddress->addressLine2 = $userParameters['HeadquartersAddress']['AddressLine2'] : '';
            if (! in_array($userParameters['HeadquartersAddress']['Country'], array(
                'US',
                'CA',
                'MX'
            ))) {
                array_key_exists('Region', $userParameters['HeadquartersAddress']) ? $User->companyAddress->county = $userParameters['HeadquartersAddress']['Region'] : '';
            } else {
                $User->companyAddress->county = $userParameters['HeadquartersAddress']['Region'];
            }
        }

        try {
            HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.editLegalUser', 'User', $User, 3, "L");
            $loloLegalUser = $loloPayApiObject->users->Save($User);

            HDLog::AppLogMessage('HDLoloPay.php', 'editLegalUser', 'loloLegalUser', $loloLegalUser, 3, "L");

            $m3LegalUser = new stdClass();
            $m3LegalUser->Name = $loloLegalUser->companyName;
            $m3LegalUser->LegalPersonType = $loloLegalUser->companyType;

            $m3LegalUser->HeadquartersAddress = new stdClass();
            $m3LegalUser->HeadquartersAddress->AddressLine1 = $loloLegalUser->companyAddress->addressLine1;
            $m3LegalUser->HeadquartersAddress->AddressLine2 = $loloLegalUser->companyAddress->addressLine2;
            $m3LegalUser->HeadquartersAddress->City = $loloLegalUser->companyAddress->city;
            $m3LegalUser->HeadquartersAddress->Region = $loloLegalUser->companyAddress->county;
            $m3LegalUser->HeadquartersAddress->PostalCode = $loloLegalUser->companyAddress->postalCode;
            $m3LegalUser->HeadquartersAddress->Country = $loloLegalUser->companyAddress->country;

            $m3LegalUser->LegalRepresentativeFirstName = $loloLegalUser->firstName;
            $m3LegalUser->LegalRepresentativeLastName = $loloLegalUser->lastName;

            $m3LegalUser->LegalRepresentativeAddress = new stdClass();
            $m3LegalUser->LegalRepresentativeAddress->AddressLine1 = $loloLegalUser->address->addressLine1;
            $m3LegalUser->LegalRepresentativeAddress->AddressLine2 = $loloLegalUser->address->addressLine2;
            $m3LegalUser->LegalRepresentativeAddress->City = $loloLegalUser->address->city;
            $m3LegalUser->LegalRepresentativeAddress->Region = $loloLegalUser->address->county;
            $m3LegalUser->LegalRepresentativeAddress->PostalCode = $loloLegalUser->address->postalCode;
            $m3LegalUser->LegalRepresentativeAddress->Country = $loloLegalUser->address->country;

            $m3LegalUser->LegalRepresentativeEmail = $loloLegalUser->email;
            $m3LegalUser->LegalRepresentativeBirthday = $loloLegalUser->birthDate;
            $m3LegalUser->LegalRepresentativeNationality = $loloLegalUser->nationality;
            $m3LegalUser->LegalRepresentativeCountryOfResidence = $loloLegalUser->countryOfResidence;
            $m3LegalUser->ProofOfIdentity = null;
            $m3LegalUser->Statute = null;
            $m3LegalUser->ProofOfRegistration = null;
            $m3LegalUser->ShareholderDeclaration = null;

            $m3LegalUser->PersonType = $loloLegalUser->type;
            $m3LegalUser->Email = $loloLegalUser->companyEmail;
            $m3LegalUser->KYCLevel = $loloLegalUser->kycLevel;
            $m3LegalUser->Id = $loloLegalUser->id;
            $m3LegalUser->Tag = $loloLegalUser->customTag;
            $m3LegalUser->CreationDate = $loloLegalUser->createdAt;
            $m3LegalUser->CompanyNumber = $loloLegalUser->companyRegistrationNumber;

            $result['user'] = $m3LegalUser;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDMangoPay.php', 'HDMangoPay.editLegalUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getPayIn($payInId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payInId', $payInId, 3, "IN");

        $payInId = (string) $payInId;
        $loloPayApiObject = self::initApiObject();

        $m3PayIn = new stdClass();

        try {
            $loloPayIn = $loloPayApiObject->payIns->Get($payInId);

            $m3PayIn->CreditedWalletId = $loloPayIn->creditedWalletId;
            $m3PayIn->PaymentType = $loloPayIn->paymentType;
            $m3PayIn->PaymentDetails = new stdClass();
            $m3PayIn->PaymentDetails->CardType = $loloPayIn->cardType;
            $m3PayIn->PaymentDetails->CardId = '';

            $m3PayIn->ExecutionType = $loloPayIn->executionType;

            $m3PayIn->ExecutionDetails = new stdClass();
            $m3PayIn->ExecutionDetails->RedirectURL = $loloPayIn->redirectURL;
            $m3PayIn->ExecutionDetails->ReturnURL = $loloPayIn->returnURL;
            $m3PayIn->ExecutionDetails->TemplateURL = $loloPayIn->templateURL;
            $m3PayIn->ExecutionDetails->TemplateURLOptions = '';
            $m3PayIn->ExecutionDetails->Culture = $loloPayIn->culture;
            $m3PayIn->ExecutionDetails->SecureMode = $loloPayIn->secureMode;

            $m3PayIn->AuthorId = $loloPayIn->creditedUserId;
            $m3PayIn->CreditedUserId = $loloPayIn->creditedUserId;

            $m3PayIn->DebitedFunds = new stdClass();
            $m3PayIn->DebitedFunds->Currency = $loloPayIn->amount->currency;
            $m3PayIn->DebitedFunds->Amount = $loloPayIn->amount->value;

            $m3PayIn->CreditedFunds = new stdClass();
            $m3PayIn->CreditedFunds->Currency = $loloPayIn->amount->currency;
            $m3PayIn->CreditedFunds->Amount = $loloPayIn->amount->value;

            $m3PayIn->Status = $loloPayIn->status;
            $m3PayIn->ResultCode = $loloPayIn->resultCode;
            $m3PayIn->ResultMessage = $loloPayIn->resultMessage;
            $m3PayIn->ExecutionDate = $loloPayIn->executionDate;
            $m3PayIn->Type = $loloPayIn->type;
            $m3PayIn->Nature = $loloPayIn->nature;
            $m3PayIn->DebitedWalletId = $loloPayIn->debitedWalletId;
            $m3PayIn->Id = $loloPayIn->id;
            $m3PayIn->Tag = $loloPayIn->customTag;
            $m3PayIn->CreationDate = $loloPayIn->createdAt;
            $m3PayIn->AuthorId = $loloPayIn->creditedUserId;
            $m3PayIn->ProviderId = $loloPayIn->providerId;
            $m3PayIn->CardProviderId = $loloPayIn->cardProviderId;
            $m3PayIn->StatementDescriptor = $loloPayIn->statementDescriptor;
            $m3PayIn->SecurityInfo = $loloPayIn->securityInfo;

            $m3PayIn->Fees = self::getTransactionFees($loloPayIn);
        } catch (Exception $e) {
            self::processException($e, $m3PayIn);
        }
        return $m3PayIn;
    }

    public static function getPayOut($payOutId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payOutId', $payOutId, 3, "IN");

        $payOutId = (string) $payOutId;
        $loloPayApiObject = self::initApiObject();
        $result = new stdClass();

        try {
            $loloPayOut = $loloPayApiObject->payOuts->Get($payOutId);

            $m3PayOut = new stdClass();
            $m3PayOut->DebitedWalletId = $loloPayOut->debitedWalletId;
            $m3PayOut->AuthorId = $loloPayOut->debitedUserId;
            $m3PayOut->DebitedFunds = new stdClass();
            $m3PayOut->DebitedFunds->Currency = $loloPayOut->amount->currency;
            $m3PayOut->DebitedFunds->Amount = $loloPayOut->amount->value;
            $m3PayOut->CreditedFunds = new stdClass();
            $m3PayOut->CreditedFunds->Currency = $loloPayOut->amount->currency;
            $m3PayOut->CreditedFunds->Amount = $loloPayOut->amount->value;
            $m3PayOut->Status = $loloPayOut->status;
            $m3PayOut->ResultCode = $loloPayOut->resultCode;
            $m3PayOut->ResultMessage = $loloPayOut->resultMessage;
            $m3PayOut->ExecutionDate = $loloPayOut->executionDate;
            $m3PayOut->Type = $loloPayOut->type;
            $m3PayOut->Nature = $loloPayOut->nature;
            $m3PayOut->Id = $loloPayOut->id;
            $m3PayOut->Tag = $loloPayOut->customTag;
            $m3PayOut->CreationDate = $loloPayOut->createdAt;
            $m3PayOut->CreditedWalletId = $loloPayOut->creditedWalletId;
            $m3PayOut->CreditedUserId = $loloPayOut->creditedUserId;
            $m3PayOut->MeanOfPaymentDetails = new stdClass();
            $m3PayOut->MeanOfPaymentDetails->BankAccountId = $loloPayOut->bankAccountId;
            $m3PayOut->MeanOfPaymentDetails->BankWireRef = $loloPayOut->bankWireRef;
            $m3PayOut->PaymentType = $loloPayOut->paymentType;
            $m3PayOut->ProviderId = $loloPayOut->providerId;
            $m3PayOut->Fees = self::getTransactionFees($loloPayOut);

            $result = $m3PayOut;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getSettlement($transferId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'transferId', $transferId, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        try {
            $loloResult = $loloPayApiObject->transactions->Get($transferId);
            $result = new stdClass();
            $result->DebitedWalletId = $loloResult->debitedWalletId;
            $result->CreditedWalletId = $loloResult->creditedWalletId;
            $result->CreditedUserId = $loloResult->creditedUserId;
            $result->Status = $loloResult->status;
            $result->ResultCode = $loloResult->resultCode;
            $result->ResultMessage = $loloResult->resultMessage;
            $result->ExecutionDate = $loloResult->executionDate;
            $result->Type = $loloResult->type;
            $result->Nature = $loloResult->nature;
            $result->Id = $loloResult->id;
            $result->Tag = $loloResult->customTag;
            $result->CreationDate = $loloResult->createdAt;
            $result->AuthorId = $loloResult->debitedUserId;
            $result->DebitedFunds = new stdClass();
            $result->DebitedFunds->Amount = $loloResult->amount->value;
            $result->DebitedFunds->Currency = $loloResult->amount->currency;
            $result->CreditedFunds = new stdClass();
            $result->CreditedFunds->Amount = $loloResult->amount->value;
            $result->CreditedFunds->Currency = $loloResult->amount->currency;
            $result->Fees = self::getTransactionFees($loloResult);
            $result->ProviderId = $loloResult->providerId;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getTransfer($transferId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'transferId', $transferId, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        try {
            $loloResult = $loloPayApiObject->transfers->Get($transferId);
            HDLog::AppLogMessage('HDLoloPay.php', 'getTransfer', 'result $loloResult', $loloResult, 3, "OUT");

            $result = new stdClass();
            $result->Id = $loloResult->id;
            $result->Status = $loloResult->status;
            $result->CreationDate = $loloResult->createdAt;
            $result->AuthorId = $loloResult->debitedUserId;
            $result->DebitedWalletId = $loloResult->debitedWalletId;
            $result->DebitedFunds = new stdClass();
            $result->DebitedFunds->Amount = $loloResult->amount->value;
            $result->DebitedFunds->Currency = $loloResult->amount->currency;
            $result->CreditedFunds = new stdClass();
            $result->CreditedFunds->Amount = $loloResult->amount->value;
            $result->CreditedFunds->Currency = $loloResult->amount->currency;

            $result->CreditedUserId = $loloResult->creditedUserId;
            $result->CreditedWalletId = $loloResult->creditedWalletId;
            $result->Type = $loloResult->type;
            $result->Nature = $loloResult->nature;
            $result->ExecutionDate = isset($loloResult->executionDate) ? $loloResult->executionDate : '';

            $result->Tag = isset($loloResult->customTag) ? $loloResult->customTag : '';
            $result->ResultMessage = ''; // lolopay never sends this for transfers
            $result->ResultCode = ''; // lolopay never sends this for transfers
            $result->Fees = self::getTransactionFees($loloResult);
            $result->ProviderId = $loloResult->providerId;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getRefund($refundId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'refundId', $refundId, 3, "IN");

        $loloPayApiObject = self::initApiObject();
        $m3Refund = new stdClass();

        try {
            $loloPayRefund = $loloPayApiObject->refunds->Get($refundId);
            HDLog::AppLogMessage('HDLoloPay', 'getRefund', 'loloPayRefund', $loloPayRefund, 3, 'L');

            $m3Refund->Id = $loloPayRefund->id;
            $m3Refund->CreationDate = $loloPayRefund->createdAt;
            $m3Refund->Tag = $loloPayRefund->customTag;
            $m3Refund->DebitedFunds = new stdClass();
            $m3Refund->DebitedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->DebitedFunds->Amount = $loloPayRefund->amount->value;
            $m3Refund->CreditedFunds = new stdClass();
            $m3Refund->CreditedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->CreditedFunds->Amount = $loloPayRefund->amount->value;
            $m3Refund->DebitedWalletId = $loloPayRefund->debitedWalletId;
            $m3Refund->CreditedWalletId = $loloPayRefund->creditedWalletId;
            $m3Refund->CreditedUserId = $loloPayRefund->creditedUserId;
            $m3Refund->Nature = $loloPayRefund->nature;
            $m3Refund->Status = $loloPayRefund->status;
            $m3Refund->ExecutionDate = $loloPayRefund->executionDate;
            $m3Refund->Type = $loloPayRefund->type;
            $m3Refund->Fees = self::getTransactionFees($loloPayRefund);
            $m3Refund->AuthorId = $loloPayRefund->debitedUserId;
            $m3Refund->RefundReason = new stdClass();
            $m3Refund->RefundReason->RefusedReasonType = empty($loloPayRefund->refundReasonDetails->refundReasonType) ? "" : $loloPayRefund->refundReasonDetails->refundReasonType;
            $m3Refund->RefundReason->RefusedReasonMessage = empty($loloPayRefund->refundReasonDetails->refusedReasonMessage) ? "" : $loloPayRefund->refundReasonDetails->refusedReasonMessage;
            $m3Refund->InitialTransactionId = $loloPayRefund->initialTransactionId;
            $m3Refund->InitialTransactionType = $loloPayRefund->initialTransactionType;
            $m3Refund->ResultCode = $loloPayRefund->resultCode;
            $m3Refund->ResultMessage = $loloPayRefund->resultMessage;
            $m3Refund->ProviderId = $loloPayRefund->providerId;
            unset($loloPayRefund);
        } catch (Exception $e) {
            self::processException($e, $m3Refund);
        }
        return $m3Refund;
    }

    public static function getUser($userId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userId', $userId, 3, "IN");

        $result = array();
        $loloPayApiObject = self::initApiObject();

        try {
            $resultObject = $loloPayApiObject->users->Get($userId);
            $newObject = new stdClass();

            $newObject->Id = $resultObject->id;
            $newObject->CreationDate = $resultObject->createdAt;
            $newObject->PersonType = $resultObject->type;
            $newObject->ProviderId = $resultObject->providerId;
            $newObject->Tag = isset($resultObject->customTag) ? $resultObject->customTag : '';

            if (isset($resultObject->kycLevel)) {
                $newObject->KYCLevel = self::$loloPayKycLevels[$resultObject->kycLevel];
            } else {
                $newObject->KYCLevel = self::$loloPayKycLevels['STANDARD'];
            }

            if ($resultObject->type == PersonType::Natural) {
                $newObject->FirstName = $resultObject->firstName;
                $newObject->LastName = $resultObject->lastName;
                $newObject->Email = $resultObject->email;
                $newObject->Birthday = $resultObject->birthDate;
                $newObject->Nationality = $resultObject->nationality;
                $newObject->CountryOfResidence = $resultObject->countryOfResidence;
                $newObject->Occupation = isset($resultObject->occupation) ? $resultObject->occupation : '';
                $newObject->IncomeRange = 0;
                if (isset($resultObject->incomeRange)) {
                    switch ($resultObject->incomeRange) {
                        case 'BELOW_18K':
                            $newObject->IncomeRange = 1;
                            break;
                        case 'BELOW_30K':
                            $newObject->IncomeRange = 2;
                            break;
                        case 'BELOW_50K':
                            $newObject->IncomeRange = 3;
                            break;
                        case 'BELOW_80K':
                            $newObject->IncomeRange = 4;
                            break;
                        case 'BELOW_120K':
                            $newObject->IncomeRange = 5;
                            break;
                        case 'ABOVE_120K':
                            $newObject->IncomeRange = 6;
                            break;
                        default:
                            $newObject->IncomeRange = 0;
                    }
                }
                $newObject->ProofOfAddress = isset($resultObject->proofOfAddress) ? $resultObject->proofOfAddress : '';
                $newObject->ProofOfIdentity = isset($resultObject->proofOfIdentity) ? $resultObject->proofOfIdentity : '';

                if (property_exists($resultObject, 'address')) {
                    $address = $resultObject->address;
                    $newObject->Address = new stdClass();
                    $newObject->Address->AddressLine1 = isset($address->addressLine1) ? $address->addressLine1 : '';
                    $newObject->Address->AddressLine2 = isset($address->addressLine2) ? $address->addressLine2 : '';
                    $newObject->Address->City = isset($address->city) ? $address->city : '';
                    $newObject->Address->Region = isset($address->county) ? $address->county : '';
                    $newObject->Address->PostalCode = isset($address->postalCode) ? $address->postalCode : '';
                    $newObject->Address->Country = isset($address->country) ? $address->country : '';
                }
            } elseif ($resultObject->type == PersonType::Legal) {
                $newObject->LegalPersonType = $resultObject->companyType;
                $newObject->Name = $resultObject->companyName;
                $newObject->LegalRepresentativeBirthday = $resultObject->birthDate;
                $newObject->LegalRepresentativeCountryOfResidence = $resultObject->countryOfResidence;
                $newObject->LegalRepresentativeNationality = $resultObject->nationality;
                $newObject->LegalRepresentativeFirstName = $resultObject->firstName;
                $newObject->LegalRepresentativeLastName = $resultObject->lastName;
                $newObject->Email = $resultObject->companyEmail;
                $newObject->LegalRepresentativeEmail = isset($resultObject->email) ? $resultObject->email : '';
                $newObject->LegalRepresentativeProofOfIdentity = isset($resultObject->proofOfIdentity) ? $resultObject->proofOfIdentity : '';
                $newObject->Statute = isset($resultObject->companyStatute) ? $resultObject->companyStatute : '';
                $newObject->ShareholderDeclaration = isset($resultObject->companyShareHolderDeclaration) ? $resultObject->companyShareHolderDeclaration : '';
                $newObject->ProofOfRegistration = isset($resultObject->companyProofOfRegistration) ? $resultObject->companyProofOfRegistration : '';

                if (property_exists($resultObject, 'address')) {
                    $address = $resultObject->address;
                    $newObject->LegalRepresentativeAddress = new stdClass();
                    $newObject->LegalRepresentativeAddress->AddressLine1 = isset($address->addressLine1) ? $address->addressLine1 : '';
                    $newObject->LegalRepresentativeAddress->AddressLine2 = isset($address->addressLine2) ? $address->addressLine2 : '';
                    $newObject->LegalRepresentativeAddress->City = isset($address->city) ? $address->city : '';
                    $newObject->LegalRepresentativeAddress->Region = isset($address->county) ? $address->county : '';
                    $newObject->LegalRepresentativeAddress->PostalCode = isset($address->postalCode) ? $address->postalCode : '';
                    $newObject->LegalRepresentativeAddress->Country = isset($address->country) ? $address->country : '';
                }

                if (property_exists($resultObject, 'companyAddress')) {
                    $address = $resultObject->companyAddress;
                    $newObject->HeadquartersAddress = new stdClass();
                    $newObject->HeadquartersAddress->AddressLine1 = isset($address->addressLine1) ? $address->addressLine1 : '';
                    $newObject->HeadquartersAddress->AddressLine2 = isset($address->addressLine2) ? $address->addressLine2 : '';
                    $newObject->HeadquartersAddress->City = isset($address->city) ? $address->city : '';
                    $newObject->HeadquartersAddress->Region = isset($address->county) ? $address->county : '';
                    $newObject->HeadquartersAddress->PostalCode = isset($address->postalCode) ? $address->postalCode : '';
                    $newObject->HeadquartersAddress->Country = isset($address->country) ? $address->country : '';
                }
            }

            $result['user'] = $newObject;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getUser', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getWallet($walletId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'walletId', $walletId, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        try {
            $loloResult = $loloPayApiObject->wallets->Get($walletId);

            $result = new stdClass();
            $result->Description = $loloResult->description;
            $result->Owners = array(
                $loloResult->userId
            );
            $result->Balance = new stdClass();
            $result->Balance->Currency = $loloResult->balance->currency;
            $result->Balance->Amount = $loloResult->balance->value;
            $result->Currency = $loloResult->currency;
            $result->Id = $loloResult->id;
            $result->Tag = $loloResult->customTag;
            $result->CreationDate = $loloResult->createdAt;
            $result->ProviderId = $loloResult->providerId;

            HDLog::AppLogMessage('HDLoloPay.php', 'getWallet', 'result $result', $result, 3, "OUT");
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getWalletTransactions($walletId, $pagination = null, $filter = null, $sorting = null)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'walletId', $walletId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'pagination', $pagination, 3, "IN");

        $loloPayApiObject = self::initApiObject();
        $result = array();
        try {
            if ($pagination != null) {
                $pagination = new \LoloPay\Pagination($pagination['page'], $pagination['itemsPerPage']);
            } else {
                $pagination = new \LoloPay\Pagination();
            }
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getWalletTransactions', '$pagination before', $pagination, 3, "OUT");
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getWalletTransactions', '$walletId', $walletId, 3, "OUT");

            if ($filter != null) {
                $filtering = new \LoloPay\FilterTransactions();
                $filtering->providerId = array_key_exists("providerId", $filter) ? $filter["providerId"] : "";
            } else {
                $filtering = $filter;
            }

            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, '$filtering', $filtering, 3, "L");

            if ($sorting != null) {
                $sort = new \LoloPay\Sorting();
                foreach ($sorting as $key => $value) {
                    $sort->AddField($key, constant('\LoloPay\SortDirection::' . $value));
                }
            } else {
                $sort = $sorting;
            }
            HDLog::AppLogMessage(__CLASS__, __FUNCTION__, '$sort', $sort, 3, "L");

            $resultObject = $loloPayApiObject->wallets->GetTransactions($walletId, $pagination, $filtering, $sort);
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getWalletTransactions', '$resultObject', $resultObject, 3, "L");
            $newObject = array();
            foreach ($resultObject as $i => $transaction) {
                $newObject[$i] = self::getNewObjectForWalletTransaction($transaction);
            }
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getWalletTransactions', 'newObject', $newObject, 3, "OUT");
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getWalletTransactions', '$pagination after', $pagination, 3, "OUT");
            $result['Transactions'] = $newObject;
            $result['Pagination'] = new stdClass();
            $result['Pagination']->TotalPages = $pagination->totalPages;
            $result['Pagination']->Page = $pagination->page;
            $result['Pagination']->PageSize = $pagination->pageSize;
            $result['Pagination']->TotalRecords = $pagination->totalRecords;
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    private static function getNewObjectForWalletTransaction($transaction)
    {
        $newObject = new stdClass();
        $newObject->Id = $transaction->id;
        $newObject->ProviderId = $transaction->providerId;
        $newObject->Status = $transaction->status;
        $newObject->CreationDate = $transaction->createdAt;
        if ($transaction->type != \LoloPay\TransactionType::payIn) {
            $newObject->AuthorId = $transaction->debitedUserId;
        } else {
            $newObject->AuthorId = $transaction->creditedUserId;
        }
        $newObject->DebitedUserId = $transaction->debitedUserId;
        $newObject->DebitedWalletId = $transaction->debitedWalletId;
        $newObject->DebitedFunds = new stdClass();
        $newObject->DebitedFunds->Amount = $transaction->amount->value;
        $newObject->DebitedFunds->Currency = $transaction->amount->currency;
        $newObject->CreditedFunds = new stdClass();
        $newObject->CreditedFunds->Amount = $transaction->amount->value;
        $newObject->CreditedFunds->Currency = $transaction->amount->currency;

        $newObject->CreditedUserId = $transaction->creditedUserId;
        $newObject->CreditedWalletId = $transaction->creditedWalletId;
        $newObject->Type = $transaction->type;
        $newObject->Nature = $transaction->nature;
        $newObject->ExecutionDate = isset($transaction->executionDate) ? $transaction->executionDate : '';

        $newObject->Tag = isset($transaction->customTag) ? $transaction->customTag : '';
        $newObject->ResultMessage = $transaction->resultMessage;
        $newObject->ResultCode = $transaction->resultCode;
        $newObject->Fees = self::getTransactionFees($transaction);
        $newObject->ProviderId = $transaction->providerId;
        return $newObject;
    }

    public static function getTransaction($transactionId)
    {
        $loloPayApiObject = self::initApiObject();
        $transactionFromProvider = $loloPayApiObject->transactions->Get($transactionId);
        return self::getNewObjectForWalletTransaction($transactionFromProvider);
    }

    public static function getWebWalletTransactions($walletId, $pagination, $filter, $sorting)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'walletId', $walletId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'pagination', $pagination, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'filter', $filter, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'sorting', $sorting, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        $result = array();
        try {
            if ($pagination != null) {
                $pagination = new \LoloPay\Pagination($pagination['page'], $pagination['itemsPerPage']);
            } else {
                $pagination = new \LoloPay\Pagination();
            }

            if ($filter != null) {
                $filtering = new \LoloPay\FilterTransactions();
                $filtering->afterDate = array_key_exists("afterDate", $filter) ? $filter["afterDate"] : "";
                $filtering->beforeDate = array_key_exists("beforeDate", $filter) ? $filter["beforeDate"] : "";
                $filtering->nature = array_key_exists("nature", $filter) ? $filter["nature"] : "";
                $filtering->status = array_key_exists("status", $filter) ? $filter["status"] : "";
                $filtering->type = array_key_exists("type", $filter) ? $filter["type"] : "";
            } else {
                $filtering = $filter;
            }

            if ($sorting != null) {
                $sort = new \LoloPay\Sorting();
                foreach (array_keys($sorting) as $key) {
                    $sort->AddField($key, \LoloPay\SortDirection::$value);
                }
            } else {
                $sort = $sorting;
            }

            $resultObject = $loloPayApiObject->wallets->GetTransactions($walletId, $pagination, $filtering, $sort);
            $newObject = array();

            foreach ($resultObject as $i => $transaction) {
                $newObject[$i] = new stdClass();
                $newObject[$i]->Id = $transaction->id;
                $newObject[$i]->Status = $transaction->status;
                $newObject[$i]->CreationDate = $transaction->createdAt;
                if ($transaction->type != \LoloPay\TransactionType::payIn) {
                    $newObject[$i]->AuthorId = $transaction->debitedUserId;
                } else {
                    $newObject[$i]->AuthorId = $transaction->creditedUserId;
                }
                $newObject[$i]->DebitedWalletId = $transaction->debitedWalletId;
                $newObject[$i]->DebitedFunds = new stdClass();
                $newObject[$i]->DebitedFunds->Amount = $transaction->amount->value;
                $newObject[$i]->DebitedFunds->Currency = $transaction->amount->currency;
                $newObject[$i]->CreditedFunds = new stdClass();
                $newObject[$i]->CreditedFunds->Amount = $transaction->amount->value;
                $newObject[$i]->CreditedFunds->Currency = $transaction->amount->currency;

                $newObject[$i]->CreditedUserId = $transaction->creditedUserId;
                $newObject[$i]->CreditedWalletId = $transaction->creditedWalletId;
                $newObject[$i]->Type = $transaction->type;
                $newObject[$i]->Nature = $transaction->nature;
                $newObject[$i]->ExecutionDate = isset($transaction->executionDate) ? $transaction->executionDate : '';

                $newObject[$i]->Tag = isset($transaction->customTag) ? $transaction->customTag : '';
                $newObject[$i]->ResultMessage = ''; // lolopay never sends this for transfers
                $newObject[$i]->ResultCode = ''; // lolopay never sends this for transfers
                $newObject[$i]->Fees = self::getTransactionFees($transaction);
                $newObject[$i]->ProviderId = $transaction->providerId;
            }
            $result['Transactions'] = $newObject;
            $result['Pagination'] = new stdClass();
            $result['Pagination']->TotalPages = $pagination->totalPages;
            $result['Pagination']->Page = $pagination->page;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        return $result;
    }

    public static function saveDocument($userId, $uploadType, $uploadTag, $uploadDocPath1, $uploadDocPath2 = "")
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'userId', $userId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uploadType', $uploadType, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uploadTag', $uploadTag, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uploadDocPath1', $uploadDocPath1, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'uploadDocPath2', $uploadDocPath2, 3, "IN");

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        // create document
        try {
            // throw exception
            $kycDocument = new KycDocument();
            $kycDocument->type = $uploadType;
            $kycDocument->customTag = $uploadTag;
            $kycDocument->userId = $userId;

            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'kycDocument', get_object_vars($kycDocument), 3, "L");
            $document = $loloPayApiObject->users->CreateKycDocument($kycDocument);
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'document', get_object_vars($document), 3, "L");
        } catch (Exception $e) {
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error creating Mango document: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error creating Mango document: " . $e->getTraceAsString(), 3, "L");
            return array(
                'error' => "Error creating document. Please retry!"
            );
        }

        if ($uploadDocPath1 != "") {
            $base64_picture_01 = base64_encode(file_get_contents($uploadDocPath1));

            try {
                $page_01 = new KycPage();
                $page_01->file = $base64_picture_01;
                $page_01->userId = $userId;
                $page_01->documentId = $document->id;

                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'page_01', get_object_vars($page_01), 3, "L");
                $response = $loloPayApiObject->users->CreateKycPage($page_01);
                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'page_01 $response', get_object_vars($response), 3, "L");
            } catch (Exception $e) {
                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error uploading document page 1: " . $e->getMessage(), 3, "L");
                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error uploading document page 1: " . $e->getTraceAsString(), 3, "L");
                return array(
                    'error' => "Error uploading document page 1"
                );
            }
        } else {
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Document 1 missing " . $uploadDocPath1, 3, "L");
            return array(
                'error' => "Your document was not properly uploaded"
            );
        }

        if ($uploadDocPath2 != "") {
            $base64_picture_02 = base64_encode(file_get_contents($uploadDocPath2));
            try {
                $page_02 = new KycPage();
                $page_02->file = $base64_picture_02;
                $page_02->userId = $userId;
                $page_02->documentId = $document->id;
                $loloPayApiObject->users->CreateKycPage($page_02);
            } catch (Exception $e) {
                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error uploading document page 2: " . $e->getMessage(), 3, "L");
                HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error uploading document page 2: " . $e->getTraceAsString(), 3, "L");
                return array(
                    'error' => "Error uploading document page 2"
                );
            }
        } else {
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Document 2 missing " . $uploadDocPath2, 3, "L");
        }

        try {
            $kycDocumentSubmit = new KycDocumentSubmit();
            $kycDocumentSubmit->documentId = $document->id;
            $updateDocumentObject = $loloPayApiObject->users->UpdateKycDocument($kycDocumentSubmit);
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'updateDocument', get_object_vars($updateDocumentObject), 3, "OUT");

            $updateDocument = new stdClass();
            $updateDocument->Type = $updateDocumentObject->type;
            $updateDocument->Id = $updateDocumentObject->id;
            $updateDocument->Status = $updateDocumentObject->status;
            $updateDocument->Tag = $updateDocumentObject->customTag;
            $updateDocument->RefusedReasonType = $updateDocumentObject->rejectionReasonType;
            $updateDocument->CreationDate = $updateDocumentObject->createdAt;

            return array(
                'document' => $updateDocument
            );
        } catch (Exception $e) {
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error submitting document for approval: " . $e->getMessage(), 3, "L");
            HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.saveDocument', 'result', "Error submitting document for approval: " . $e->getTraceAsString(), 3, "L");
            return array(
                'error' => "Error submitting document for approval"
            );
        }
    }

    public static function processException($e, &$result)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'e', $e, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'result', $result, 3, "IN");

        $result = array();
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.processException', 'Exception message', $e->getMessage(), 3, "OUT");
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.processException', 'Exception code', $e->getCode(), 3, "OUT");
        if (HDUtils::isJson($e->getMessage())) {
            $errorMessage = json_decode($e->getMessage(), true);
            $errorCode = $errorMessage['errors'][0]['errorCode'];
            $errorMessage = $errorMessage['errors'][0]['errorDescription'];
            $errorMessage = str_replace("_", " ", $errorMessage);
            $errorMessage = strtolower($errorMessage);
            $errorMessage = ucfirst($errorMessage);
            $errorMessage = $errorCode . " " . $errorMessage;
        } else {
            $errorMessage = "Internal server Error";
        }
        $result['error'][] = $errorMessage;
        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.processException', 'result', $result, 3, "OUT");
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
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'walletId', $walletId, 3, "IN");

        $loloPayApiObject = self::initApiObject();
        try {
            $result = $loloPayApiObject->wallets->Get($walletId);
            $result = strval($result->balance->value);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    private static function getTransactionFees(Transaction $tansaction)
    {
        $returnObject = new stdClass();
        if (empty($tansaction->relatedTransactionId)) {
            $returnObject->Currency = "";
            $returnObject->Amount = 0;
        } else {
            $feeTransaction = self::$loloPayApiObject->transactions->Get($tansaction->relatedTransactionId);
            $returnObject->Currency = $feeTransaction->amount->currency;
            $returnObject->Amount = $feeTransaction->amount->value;
        }
        return $returnObject;
    }

    /**
     * Creates a bank card object with only id field.
     *
     * @param array $cardParameters
     * @return \LoloPay\BankCard
     */
    public static function createCard($cardParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardParameters', $cardParameters, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $bankCard = new \LoloPay\BankCard();
        $bankCard->userId = $cardParameters['userId'];
        $bankCard->type = $cardParameters['type'];
        $bankCard->embossName = $cardParameters['embossName'];
        $bankCard->firstName = $cardParameters['firstName'];
        $bankCard->lastName = $cardParameters['lastName'];
        $bankCard->address1 = $cardParameters['address1'];
        $bankCard->address2 = $cardParameters['address2'];
        $bankCard->city = $cardParameters['city'];
        $bankCard->countryCode = $cardParameters['countryCode'];
        $bankCard->countyName = $cardParameters['countyName'];
        $bankCard->zipCode = $cardParameters['zipCode'];
        $bankCard->cardUserInfo = $cardParameters['cardUserInfo'];

        try {
            $result = $loloPayApiObject->bankCards->Create($bankCard);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createCard', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function addCurrencyToCard($cardId, $currency)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'currency', $currency, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $entity = new stdClass();
        $entity->cardId = strval($cardId);
        $entity->currency = $currency;

        try {
            $result = $loloPayApiObject->bankCards->AddCurrencyToCard($entity);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.addCurrencyToCard', 'result', $result, 3, "OUT");
        return $result;
    }

    /**
     * Gets a bank card object from payment provider.
     *
     * @param string $cardId
     * @return \LoloPay\BankCard $bankCard
     */
    public static function getCard($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->Get($cardId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getCard', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getCardWallet($cardId, $currency)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'currency', $currency, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCardWallets->Get($cardId, $currency);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getCardWallet', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getCardWalletTransactions($cardId, $currency, $startDate, $endDate)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'currency', $currency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'startDate', $startDate, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'endDate', $endDate, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCardWallets->GetCardWalletTransactions($cardId, $currency, $startDate, $endDate);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.getCardWalletTransactions', 'result', $result, 3, "OUT");
        return $result;
    }

    /**
     *
     * @param string $cardId
     *            id of the card
     * @param array $cardParameters
     *            paramters for card upgrade (delivery address)
     * @return array
     */
    public static function upgradeCard($cardId, $cardParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $cardObject = new stdClass();
        $cardObject->cardId = strval($cardId);
        $cardObject->address1 = $cardParameters['address1'];
        $cardObject->address2 = $cardParameters['address2'];
        $cardObject->city = $cardParameters['city'];
        $cardObject->countryCode = $cardParameters['countryCode'];
        $cardObject->countyName = $cardParameters['countyName'];
        $cardObject->zipCode = $cardParameters['zipCode'];

        try {
            $result = $loloPayApiObject->bankCards->Upgrade($cardObject);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.upgradeCard', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function changeCardStatus($cardId, $oldStatus, $newStatus)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'oldStatus', $oldStatus, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'newStatus', $newStatus, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $cardObject = new stdClass();
        $cardObject->cardId = strval($cardId);
        $cardObject->oldStatus = strval($oldStatus);
        $cardObject->newStatus = strval($newStatus);

        try {
            $result = $loloPayApiObject->bankCards->ChangeStatus($cardObject);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.changeCardStatus', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getLocalCardNumber($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->GetLocalCardNumber($cardId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getCardNumber($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->GetCardNumber($cardId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getExpiryDate($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->GetExpiryDate($cardId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getCvv($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->GetCvv($cardId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function transferToCard($params)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'params', $params, 3, "IN");
        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $transfer = new stdClass();
        $transfer->cardId = $params['cardId'];
        $transfer->currency = $params['currency'];
        $transfer->amount = $params['amount'];

        try {
            $result = $loloPayApiObject->bankCards->TransferToCard($transfer);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function sendCardPinToUser($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $entity = new stdClass();
        $entity->cardId = $cardId;

        try {
            $result = $loloPayApiObject->bankCards->SendCardPinToUser($entity);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function lockUnlockCard($cardId, $oldStatus, $newStatus)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'oldStatus', $oldStatus, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'newStatus', $newStatus, 3, "IN");
        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $entity = new stdClass();
        $entity->cardId = $cardId;
        $entity->oldStatus = $oldStatus;
        $entity->newStatus = $newStatus;

        try {
            $result = $loloPayApiObject->bankCards->LockUnlockCard($entity);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function transferFromCard($cardId, $currency, $amount)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'currency', $currency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'amount', $amount, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $entity = new stdClass();
        $entity->cardId = $cardId;
        $entity->currency = $currency;
        $entity->amount = $amount;

        try {
            $result = $loloPayApiObject->bankCards->TransferFromCard($entity);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    /**
     * Updates card information at provider
     *
     * @param string $cardId
     * @param array $cardParameters
     *            array containing card user info, 'firstName', 'lastName' (names on card truncated to proper lengths)
     * @return array the result of the update
     */
    public static function updateCard($cardId, $cardParameters)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $cardObject = new stdClass();
        $cardObject->cardId = strval($cardId);
        $cardObject->firstName = $cardParameters['firstName'];
        $cardObject->lastName = $cardParameters['lastName'];

        if (! empty($cardParameters['cardUserInfo'])) {
            $cardObject->cardUserInfo = $cardParameters['cardUserInfo'];
        }

        try {
            $result = $loloPayApiObject->bankCards->Update($cardObject);
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.updateCard', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function getAccountCardWallets()
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'start', 'No params', 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->bankCards->GetAccountCardWallets();
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function replaceCard($cardId, $reason)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'reason', $reason, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();
        $replaceCardRequest = new stdClass();
        $replaceCardRequest->cardId = $cardId;
        $replaceCardRequest->reason = $reason;

        try {
            $result = $loloPayApiObject->bankCards->ReplaceCard($replaceCardRequest);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function executeBankPayment($cardId, $beneficiaryName, $creditorIban, $creditorBic, $paymentAmount, $reference)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'beneficiaryName', $beneficiaryName, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'creditorIban', $creditorIban, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'creditorBic', $creditorBic, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'paymentAmount', $paymentAmount, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'reference', $reference, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();
        $executeBankPaymentRequest = new stdClass();
        $executeBankPaymentRequest->cardId = $cardId;
        $executeBankPaymentRequest->beneficiaryName = $beneficiaryName;
        $executeBankPaymentRequest->creditorIban = $creditorIban;
        $executeBankPaymentRequest->creditorBic = $creditorBic;
        $executeBankPaymentRequest->paymentAmount = $paymentAmount;
        $executeBankPaymentRequest->reference = $reference;

        try {
            $result = $loloPayApiObject->bankCards->ExecuteBankPayment($executeBankPaymentRequest);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getCurrencyFxQuote($cardId, $fromCurrency, $toCurrency, $amount)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'fromCurrency', $fromCurrency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'toCurrency', $toCurrency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'amount', $amount, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $getCurrencyFxQuote = new stdClass();
        $getCurrencyFxQuote->cardId = $cardId;
        $getCurrencyFxQuote->currencyFrom = $fromCurrency;
        $getCurrencyFxQuote->currencyTo = $toCurrency;
        $getCurrencyFxQuote->amount = $amount;

        try {
            $result = $loloPayApiObject->bankCards->GetCurrencyFxQuote($getCurrencyFxQuote);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function executeCardWalletsTrade($cardId, $fromCurrency, $toCurrency, $amount)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'fromCurrency', $fromCurrency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'toCurrency', $toCurrency, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'amount', $amount, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $executeCardWalletsTrade = new stdClass();
        $executeCardWalletsTrade->cardId = $cardId;
        $executeCardWalletsTrade->currencyFrom = $fromCurrency;
        $executeCardWalletsTrade->currencyTo = $toCurrency;
        $executeCardWalletsTrade->amount = $amount;

        try {
            $result = $loloPayApiObject->bankCards->ExecuteCardWalletsTrade($executeCardWalletsTrade);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getStaticErrors()
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'start', 'No params', 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            // Get errors array
            $result = $loloPayApiObject->errors->GetStaticErrors();
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function createCardRegistrationObject($params)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'params', $params, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $cardRegistration = new \LoloPay\CardRegistration();
        $cardRegistration->userId = $params['userId'];
        $cardRegistration->currency = $params['currency'];

        if (! empty($params['customTag'])) {
            $cardRegistration->customTag = $params['customTag'];
        }

        if (! empty($params['cardType'])) {
            $cardRegistration->cardType = $params['cardType'];
        }

        if (! empty($params['apiVersion'])) {
            $cardRegistration->apiVersion = $params['apiVersion'];
        }

        try {
            $result = $loloPayApiObject->cardRegistrations->Create($cardRegistration);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    /**
     * Updates card registration object at provider
     *
     * @param array $params
     * @return \LoloPay\CardRegistration()
     */
    public static function updateCardRegistrationObject($params)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'params', $params, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        $cardRegistration = new \LoloPay\CardRegistration();
        $cardRegistration->registrationData = $params['registrationData'];
        $cardRegistration->id = $params['cardRegistrationProviderId'];
        $cardRegistration->userId = $params['userId'];

        if (! empty($params['customTag'])) {
            $cardRegistration->customTag = $params['customTag'];
        }

        if (! empty($params['apiVersion'])) {
            $cardRegistration->apiVersion = $params['apiVersion'];
        }

        try {
            $result = $loloPayApiObject->cardRegistrations->Update($cardRegistration);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getDepositCardByProviderId($cardProviderId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardProviderId', $cardProviderId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->cards->GetByProviderId($cardProviderId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getPayInStatus($providerId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'providerId', $providerId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->payIns->GetPayInStatus($providerId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function createDirectPayIn($payInParameters, $defaultCurrency = 'EUR')
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payInParameters', $payInParameters, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'defaultCurrency', $defaultCurrency, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        $PayIn = new \LoloPay\PayIn();
        $PayIn->creditedWalletId = $payInParameters['CreditedWalletId'];

        $PayIn->amount = new \LoloPay\Amount();
        $PayIn->amount->currency = $defaultCurrency;
        $PayIn->amount->value = $payInParameters['DebitedFunds'];

        $PayIn->fees = new \LoloPay\Amount();
        $PayIn->fees->currency = $defaultCurrency;
        $PayIn->fees->value = $payInParameters['Fees'];

        $PayIn->feeModel = \LoloPay\FeeModel::Included;

        $PayIn->secureModeReturnURL = $payInParameters['SecureModeReturnURL'];

        $PayIn->cardId = $payInParameters['CardId'];
        $PayIn->secureMode = $payInParameters['SecureMode'];

        if (! empty($payInParameters['Tag'])) {
            $PayIn->customTag = $payInParameters['Tag'];
        }

        if (! empty($payInParameters['StatementDescriptor'])) {
            $PayIn->statementDescriptor = $payInParameters['StatementDescriptor'];
        }

        if (! empty($payInParameters['Billing'])) {
            $PayIn->billing = new \LoloPay\Address();
            $PayIn->billing->addressLine1 = $payInParameters['Billing']['AddressLine1'];
            $PayIn->billing->addressLine2 = $payInParameters['Billing']['AddressLine2'];
            $PayIn->billing->city = $payInParameters['Billing']['City'];
            $PayIn->billing->country = $payInParameters['Billing']['Country'];
            $PayIn->billing->county = $payInParameters['Billing']['County'];
            $PayIn->billing->postalCode = $payInParameters['Billing']['PostalCode'];
        }
        HDLog::AppLogMessage('HDLoloPay.php', 'createDirectPayIn', 'PayIn', get_object_vars($PayIn), 3, "L");

        $result = array();
        try {
            HDLog::AppLogMessage('HDLoloPay.php', 'createDirectPayIn', 'PayIn', $PayIn, 3, "L");
            if (property_exists($PayIn, "billing")) {
                $loloPayInsResponse = $loloPayApiObject->payIns->CreateAVSDirect($PayIn);
            } else {
                $loloPayInsResponse = $loloPayApiObject->payIns->CreateDirect($PayIn);
            }

            HDLog::AppLogMessage('HDLoloPay.php', 'createDirectPayIn', 'loloPayInsResponse', $loloPayInsResponse, 3, "L");

            $m3PayIn = new stdClass();
            foreach ($loloPayInsResponse as $loloPayInResponse) {
                if ($loloPayInResponse->type == \LoloPay\TransactionType::payIn) {
                    $m3PayIn->CreditedWalletId = $loloPayInResponse->creditedWalletId;
                    $m3PayIn->PaymentType = $loloPayInResponse->paymentType;
                    $m3PayIn->ExecutionType = $loloPayInResponse->executionType;

                    $m3PayIn->CreditedUserId = $loloPayInResponse->creditedUserId;
                    $m3PayIn->Status = $loloPayInResponse->status;
                    $m3PayIn->ResultCode = $loloPayInResponse->resultCode;
                    $m3PayIn->ResultMessage = $loloPayInResponse->resultMessage;
                    $m3PayIn->ExecutionDate = $loloPayInResponse->executionDate;
                    $m3PayIn->Type = $loloPayInResponse->type;
                    $m3PayIn->Nature = $loloPayInResponse->nature;
                    $m3PayIn->DebitedWalletId = $loloPayInResponse->debitedWalletId;
                    $m3PayIn->Id = $loloPayInResponse->id;
                    $m3PayIn->Tag = $loloPayInResponse->customTag;
                    $m3PayIn->CreationDate = $loloPayInResponse->createdAt;
                    $m3PayIn->AuthorId = $loloPayInResponse->creditedUserId;

                    $m3PayIn->PaymentDetails = new stdClass();
                    $m3PayIn->PaymentDetails->CardType = $loloPayInResponse->cardType;
                    $m3PayIn->PaymentDetails->CardId = '';

                    $m3PayIn->ExecutionDetails = new stdClass();
                    $m3PayIn->ExecutionDetails->RedirectURL = $loloPayInResponse->redirectURL;
                    $m3PayIn->ExecutionDetails->ReturnURL = $loloPayInResponse->returnURL;
                    $m3PayIn->ExecutionDetails->TemplateURL = $loloPayInResponse->templateURL;
                    $m3PayIn->ExecutionDetails->TemplateURLOptions = '';
                    $m3PayIn->ExecutionDetails->Culture = $loloPayInResponse->culture;
                    $m3PayIn->ExecutionDetails->SecureMode = $loloPayInResponse->secureMode;

                    $m3PayIn->DebitedFunds = new stdClass();
                    $m3PayIn->DebitedFunds->Currency = $loloPayInResponse->amount->currency;
                    $m3PayIn->DebitedFunds->Amount = $loloPayInResponse->amount->value;

                    $m3PayIn->CreditedFunds = new stdClass();
                    $m3PayIn->CreditedFunds->Currency = $loloPayInResponse->amount->currency;
                    $m3PayIn->CreditedFunds->Amount = $loloPayInResponse->amount->value;
                    $m3PayIn->Fees = self::getTransactionFees($loloPayInResponse);
                    $m3PayIn->SecureModeRedirectUrl = $loloPayInResponse->secureModeRedirectUrl;
                    $m3PayIn->StatementDescriptor = $loloPayInResponse->statementDescriptor;
                    $m3PayIn->SecureModeReturnUrl = $loloPayInResponse->secureModeReturnUrl;
                }
            }

            $result['payIn'] = $m3PayIn;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        HDLog::AppLogMessage('HDLoloPay.php', 'HDLoloPay.createPayIn', 'result', $result, 3, "OUT");
        return $result;
    }

    public static function deactivateDepositCard($cardId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'cardId', $cardId, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        $Card = new stdClass();
        $Card->id = $cardId;

        try {
            $result = $loloPayApiObject->cards->DeactivateCard($Card);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function createPayInRefund($payInRefundParameters, $payInProviderId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payInRefundParameters', $payInRefundParameters, 3, "IN");
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'payInProviderId', $payInProviderId, 3, "IN");

        $result = array();
        $loloPayApiObject = self::initApiObject();

        $Refund = new \LoloPay\Refund();
        $Refund->transactionId = $payInRefundParameters["TransferId"];
        $Refund->customTag = ! empty($payInRefundParameters['Tag']) ? $payInRefundParameters['Tag'] : '';

        if (! empty($payInRefundParameters['DebitedFundsAmount'])) {
            $Refund->amount = new Amount();
            $Refund->amount->value = $payInRefundParameters['DebitedFundsAmount'];
            $Refund->amount->currency = $payInRefundParameters['DebitedFundsCurrency'];
        }

        if (! empty($payInRefundParameters['FeesAmount'])) {
            $Refund->fees = new Amount();
            $Refund->fees->value = $payInRefundParameters['FeesAmount'];
            $Refund->fees->currency = $payInRefundParameters['FeesCurrency'];
        }

        if (! empty($payInRefundParameters['Reason'])) {
            $Refund->refundReasonDetails = new RefundReasonDetails();
            $Refund->refundReasonDetails->refundReasonType = RefundReasonType::OTHER;
            $Refund->refundReasonDetails->refusedReasonMessage = $payInRefundParameters['Reason'];
        }
        $Refund->id = $payInProviderId;

        try {

            HDLog::AppLogMessage('HDLoloPay.php', 'createPayInRefund', 'Refund', $Refund, 3, "L");
            $loloRefunds = $loloPayApiObject->payIns->CreateRefund($Refund);
            HDLog::AppLogMessage('HDLoloPay.php', 'createPayInRefund', 'loloRefunds', $loloRefunds, 3, "L");

            $loloPayRefund = new stdClass();
            $loloPayRefundFee = new stdClass();

            foreach ($loloRefunds as $loloRefund) {
                HDLog::AppLogMessage('HDLoloPay.php', 'createPayInRefund', 'loloRefund', $loloRefund, 3, "L");
                if ($loloRefund->type == "PAYOUT") {
                    $loloPayRefund = $loloRefund;
                }

                if ($loloRefund->type == "PAYOUT_FEE") {
                    $loloPayRefundFee = $loloRefund;
                }
            }

            $m3Refund = new stdClass();
            $m3Refund->Id = $loloPayRefund->id;
            $m3Refund->CreationDate = $loloPayRefund->createdAt;
            $m3Refund->Tag = $loloPayRefund->customTag;
            $m3Refund->DebitedFunds = new stdClass();
            $m3Refund->DebitedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->DebitedFunds->Amount = $loloPayRefund->amount->value;
            $m3Refund->CreditedFunds = new stdClass();
            $m3Refund->CreditedFunds->Currency = $loloPayRefund->amount->currency;
            $m3Refund->CreditedFunds->Amount = $loloPayRefund->amount->value;
            $m3Refund->DebitedWalletId = $loloPayRefund->debitedWalletId;
            $m3Refund->CreditedWalletId = $loloPayRefund->creditedWalletId;
            $m3Refund->CreditedUserId = $loloPayRefund->creditedUserId;
            $m3Refund->Nature = $loloPayRefund->nature;
            $m3Refund->Status = $loloPayRefund->status;
            $m3Refund->ExecutionDate = $loloPayRefund->executionDate;
            $m3Refund->Type = $loloPayRefund->type;

            $m3Refund->Fees = new stdClass();
            $m3Refund->Fees->Currency = "";
            if (isset($loloPayRefundFee->amount->currency)) {
                $m3Refund->Fees->Currency = $loloPayRefundFee->amount->currency;
            }

            $m3Refund->Fees->Amount = 0;
            if (isset($loloPayRefundFee->amount->value)) {
                $m3Refund->Fees->Amount = $loloPayRefundFee->amount->value;
            }

            $m3Refund->InitialTransactionId = $loloPayRefund->initialTransactionId;
            $m3Refund->InitialTransactionType = $loloPayRefund->initialTransactionType;
            $m3Refund->AuthorId = $loloPayRefund->debitedUserId;

            $m3Refund->RefundReason = new stdClass();

            $m3Refund->RefundReason->RefusedReasonType = "";
            if (! empty($loloPayRefund->refundReasonDetails->refundReasonType)) {
                $m3Refund->RefundReason->RefusedReasonType = $loloPayRefund->refundReasonDetails->refundReasonType;
            }

            $m3Refund->RefundReason->RefusedReasonMessage = "";
            if (! empty($loloPayRefund->refundReasonDetails->refusedReasonMessage)) {
                $m3Refund->RefundReason->RefusedReasonMessage = $loloPayRefund->refundReasonDetails->refusedReasonMessage;
            }

            $m3Refund->InitialTransactionId = $loloPayRefund->initialTransactionId;
            $m3Refund->InitialTransactionType = $loloPayRefund->initialTransactionType;
            $m3Refund->ResultCode = $loloPayRefund->resultCode;
            $m3Refund->ResultMessage = $loloPayRefund->resultMessage;

            $result['PayInRefund'] = $m3Refund;
        } catch (Exception $e) {
            self::processException($e, $result);
        }

        return $result;
    }

    public static function getProcessedCallback($resourceId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'resourceId', $resourceId, 3, "IN");

        $result = array();

        // init lolo api
        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->callbacks->GetProcessedCallback($resourceId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }

    public static function getKycDocument($documentId)
    {
        HDLog::AppLogMessage(__CLASS__, __FUNCTION__, 'documentId', $documentId, 3, "IN");

        $loloPayApiObject = self::initApiObject();

        try {
            $result = $loloPayApiObject->kycDocuments->GetKycDocument($documentId);
        } catch (Exception $e) {
            self::processException($e, $result);
        }
        return $result;
    }
}