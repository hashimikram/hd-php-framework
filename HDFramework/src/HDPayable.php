<?php
namespace HDFramework\src;

use stdClass;
use Exception;

/**
 * Interface Category | framework/src/HDPayable.php
 *
 * @author Cornel
 * @package framework
 */

/**
 * Interface for any financial provider class
 *
 * @author cornel
 * @package framework
 */
interface HDPayable
{

    /**
     * Requests bank account creation from payment provider
     *
     * @param array $bankAccountParameters
     *            <ul>
     *            <li>['UserId'] <i><u>string</u></i> The object owner's UserId </li>
     *            <li>
     *            ['Address']
     *            <ul>
     *            <li>['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['AddressLine2'] <i><u>string</u></i> The second line of the address</li>
     *            <li>['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['Country'] <i><u>string</u></i> The Country of the Address. A valid ISO 3166-1 alpha-2 format</li>
     *            <li>['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            </ul>
     *            </li>
     *            <li> ['Type'] <i><u>BankAccountType</u></i> The type of bank account (BankAccountType: IBAN, GB, US, CA, OTHER) </li>
     *            <li>
     *            case IBAN
     *            <ul>
     *            <li>['IBAN'] <i><u>string</u></i> The IBAN of the bank account</li>
     *            <li>['BIC'] <i><u>string</u></i> (optional) The BIC of the bank account</li>
     *            </ul>
     *            </li>
     *
     *            <li>
     *            case US
     *            <ul>
     *            <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. US account numbers must be digits only.</li>
     *            <li>['ABA'] <i><u>string</u></i> The ABA of the bank account. Must be numbers only, and 9 digits long</li>
     *            <li>['DepositAccountType'] <i><u>DepositAccountType</u></i> (optional) The type of account (DepositAccountType: CHECKING, SAVINGS)</li>
     *            </ul>
     *            </li>
     *
     *            <li>
     *            case CA
     *            <ul>
     *            <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. Canadian account numbers must be a maximum of 20 digits.</li>
     *            <li>['BankName'] <i><u>string</u></i> The name of the bank where the account is held. Must be letters or numbers only and maximum 50 characters long.</li>
     *            <li>['InstitutionNumber'] <i><u>string</u></i> The institution number of the bank account. Must be numbers only, and 3 or 4 digits long</li>
     *            <li>['BranchCode'] <i><u>string</u></i> The branch code of the bank where the bank account. Must be numbers only, and 5 digits long</li>
     *            </ul>
     *            </li>
     *            <li>
     *            case GB
     *            <ul>
     *            <li>['OwnerName'] <i><u>string</u></i> The name of the owner of the bank account</li>
     *            <li>['SortCode'] <i><u>string</u></i> The sort code of the bank account. Must be numbers only, and 6 digits long</li>
     *            <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. GB account numbers must be 8 digits long.</li>
     *            </ul>
     *            </li>
     *            <li>
     *            case OTHER
     *            <ul>
     *            <li>['OwnerName'] <i><u>string</u></i> The name of the owner of the bank account</li>
     *            <li>['Country'] <i><u>string</u></i> The Country of the Address</li>
     *            <li>['BIC'] <i><u>string</u></i> The BIC of the bank account</li>
     *            <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. Canadian account numbers must be a maximum of 20 digits</li>
     *            </ul>
     *            </li>
     *            </ul>
     * @return string json $return - BankAccount object
     *         <ul>
     *         <li>['UserId'] <i><u>string</u></i> The object owner's UserId </li>
     *         <li>['OwnerName'] <i><u>string</u></i> The name of the owner of the bank account</li>
     *         <li>['Id'] <i><u>string</u></i> The item's ID</li>
     *         <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *         <li>['CreationDate'] <i><u>timestamp</u></i> When the item was created</li>
     *         <li>['Active'] <i><u>boolean</u></i> Whether the bank account is active or not</li>
     *         <li>
     *         ['OwnerAddress']
     *         <ul>
     *         <li>['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *         <li>['AddressLine2'] <i><u>string</u></i> The second line of the address</li>
     *         <li>['City'] <i><u>string</u></i> The city of the address</li>
     *         <li>['Country'] <i><u>string</u></i> The Country of the Address. A valid ISO 3166-1 alpha-2 format</li>
     *         <li>['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *         <li>['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *         </ul>
     *         </li>
     *         <li> ['Type'] <i><u>BankAccountType</u></i> The type of bank account (BankAccountType: IBAN, GB, US, CA, OTHER) </li>
     *         <li>
     *         case IBAN
     *         <ul>
     *         <li>['IBAN'] <i><u>string</u></i> The IBAN of the bank account</li>
     *         <li>['BIC'] <i><u>string</u></i> (optional) The BIC of the bank account</li>
     *         </ul>
     *         </li>
     *
     *         <li>
     *         case US
     *         <ul>
     *         <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. US account numbers must be digits only.</li>
     *         <li>['ABA'] <i><u>string</u></i> The ABA of the bank account. Must be numbers only, and 9 digits long</li>
     *         <li>['DepositAccountType'] <i><u>DepositAccountType</u></i> (optional) The type of account (DepositAccountType: CHECKING, SAVINGS)</li>
     *         </ul>
     *         </li>
     *
     *         <li>
     *         case CA
     *         <ul>
     *         <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. Canadian account numbers must be a maximum of 20 digits.</li>
     *         <li>['BankName'] <i><u>string</u></i> The name of the bank where the account is held. Must be letters or numbers only and maximum 50 characters long.</li>
     *         <li>['InstitutionNumber'] <i><u>string</u></i> The institution number of the bank account. Must be numbers only, and 3 or 4 digits long</li>
     *         <li>['BranchCode'] <i><u>string</u></i> The branch code of the bank where the bank account. Must be numbers only, and 5 digits long</li>
     *         </ul>
     *         </li>
     *         <li>
     *         case GB
     *         <ul>
     *         <li>['OwnerName'] <i><u>string</u></i> The name of the owner of the bank account</li>
     *         <li>['SortCode'] <i><u>string</u></i> The sort code of the bank account. Must be numbers only, and 6 digits long</li>
     *         <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. GB account numbers must be 8 digits long.</li>
     *         </ul>
     *         </li>
     *         <li>
     *         case OTHER
     *         <ul>
     *         <li>['OwnerName'] <i><u>string</u></i> The name of the owner of the bank account</li>
     *         <li>['Country'] <i><u>string</u></i> The Country of the Address</li>
     *         <li>['BIC'] <i><u>string</u></i> The BIC of the bank account</li>
     *         <li>['AccountNumber'] <i><u>string</u></i> The account number of the bank account. Must be numbers only. Canadian account numbers must be a maximum of 20 digits</li>
     *         </ul>
     *         </li>
     *         </ul>
     * @link https://docs.mangopay.com/endpoints/v2.01/bank-accounts#e24_the-bankaccount-object
     * @throws Exception
     */
    public static function createBankAccount($bankAccountParameters);

    /**
     * Requests bank wire creation from payment provider
     *
     * @param array $bankWireParameters
     *            <ul>
     *            <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            <li>['AuthorId'] <i><u>string</u></i> A user's ID</li>
     *            <li>['DebitedWalletId'] <i><u>string</u></i> The ID of the wallet that was debited</li>
     *            <li>['DebitedFunds']['Amount'] <i><u>string</u></i> Amount of money being debited</li>
     *            <li>['DebitedFunds']['Currency'] <i><u>string</u></i> The currency - should be ISO_4217 format</li>
     *            <li>['Fees']['Amount'] <i><u>string</u></i> Amount of money being charged</li>
     *            <li>['Fees']['Currency'] <i><u>string</u></i> The currency - should be ISO_4217 format</li>
     *            <li>['BankAccountId'] <i><u>string</u></i> An ID of a Bank Account</li>
     *            </ul>
     *
     * @return string json $return - PayOut object
     * @link https://docs.mangopay.com/endpoints/v2.01/payouts#e227_the-payout-object
     * @throws Exception
     */
    public static function createBankWire($bankWireParameters);

    /**
     * Requests creation of a business user
     *
     * @param array $legalUserParameters
     *            <ul>
     *            <li>['LegalPersonType'] <i><u>LegalPersonType</u></i> The type of legal user(LegalPersonType: BUSINESS, ORGANIZATION, SOLETRADER)</li>
     *            <li>['Name'] <i><u>string</u></i> The name of the legal user</li>
     *            <li>['LegalRepresentativeBirthday'] <i><u>timestamp</u></i> The date of birth of the company’s Legal representative person - be careful to set the right timezone (should be UTC) to avoid 00h becoming 23h (and hence interpreted as the day before)</li>
     *            <li>['LegalRepresentativeCountryOfResidence'] <i><u>string</u></i> The country of residence of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeNationality'] <i><u>CountryIso</u></i> The nationality of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeFirstName'] <i><u>string</u></i> The firstname of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeLastName'] <i><u>string</u></i> The lastname of the company’s Legal representative person</li>
     *            <li>['Email'] <i><u>string</u></i> The person's email address - must be a valid email</li>
     *            <li>['Tag'] <i><u>string</u></i> (optional) Custom data that you can add to this item</li>
     *            <li>['LegalRepresentativeEmail'] <i><u>string</u></i> (optional) The email of the company’s Legal representative person - must be a valid</li>
     *            <li>
     *            HeadquartersAddress <i><u>Address</u></i> (optional) The address of the company’s headquarters
     *            <ul>
     *            <li>['HeadquartersAddress']['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['HeadquartersAddress']['AddressLine2'] <i><u>string</u></i> (optional) The second line of the address</li>
     *            <li>['HeadquartersAddress']['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['HeadquartersAddress']['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            <li>['HeadquartersAddress']['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['HeadquartersAddress']['Country'] <i><u>CountryIso</u></i> The Country of the Address</li>
     *            </ul>
     *            </li>
     *            <li>
     *            LegalRepresentativeAddress <i><u>Address</u></i> (optional) The address of the company’s Legal representative person
     *            <ul>
     *            <li>['HeadquartersAddress']['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['HeadquartersAddress']['AddressLine2'] <i><u>string</u></i> (optional) The second line of the address</li>
     *            <li>['HeadquartersAddress']['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['HeadquartersAddress']['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            <li>['HeadquartersAddress']['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['HeadquartersAddress']['Country'] <i><u>CountryIso</u></i> The Country of the Address</li>
     *            </ul>
     *            </li>
     *            </ul>
     * @return string json $return - Legal User Object
     * @link https://docs.mangopay.com/endpoints/v2.01/users#e253_the-user-object
     * @throws Exception
     */
    public static function createLegalUser($legalUserParameters);

    /**
     * Requests pay in creation
     *
     * @param array $payInParameters
     *            <ul>
     *            <li>['AuthorId'] <i><u>string</u></i> A user's ID</li>
     *            <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            <li>['DebitedFunds'] <i><u>string</u></i> Amount of money being debited</li>
     *            <li>['Fees'] <i><u>string</u></i> Amount of money being charged</li>
     *            <li>['CreditedWalletId'] <i><u>string</u></i> The ID of the wallet where money will be credited</li>
     *            <li>['ReturnURL'] <i><u>string</u></i> The URL to redirect to after payment (whether successful or not)</li>
     *            <li>['Culture'] <i><u>CultureCode</u></i> The language to use for the payment page - needs to be the ISO code of the language (CultureCode: DE, EN, DA, ES, ET, FI, FR, EL, HU, IT, NL, NO, PL, PT, SK, SV, CS)</li>
     *            <li>['SecureMode'] <i><u>string</u></i> The SecureMode corresponds to '3D secure'</li>
     *            <li>['CardType'] <i><u>CardType</u></i> The type of card (CardType: CB_VISA_MASTERCARD, DINERS, MASTERPASS, MAESTRO, P24, IDEAL, BCMC, PAYLIB)</li>
     *            </ul>
     * @param string $defaultCurrency
     *            - currency for the payin, defaults to EUR
     * @return string json - PayIn object
     * @link https://docs.mangopay.com/endpoints/v2.01/payins#e264_the-payin-object
     * @throws Exception
     */
    public static function createPayIn($payInParameters, $defaultCurrency = 'EUR');

    /**
     * Requests transfer creation
     *
     * @param array $transferParameters
     *            <ul>
     *            <li>['DebitedWalletId'] <i><u>string</u></i> The ID of the wallet that was debited</li>
     *            <li>['CreditedWalletId'] <i><u>string</u></i> The ID of the wallet where money will be credited</li>
     *            <li>['AuthorId'] <i><u>string</u></i> A user's ID</li>
     *            <li>['DebitedFunds']['Amount'] <i><u>string</u></i> Amount of money being debited</li>
     *            <li>['DebitedFunds']['Currency'] <i><u>string</u></i> The currency - should be ISO_4217 format</li>
     *            <li>['Fees']['Amount'] <i><u>string</u></i> Amount of money being charged</li>
     *            <li>['Fees']['Currency'] <i><u>string</u></i> The currency - should be ISO_4217 format</li>
     *            <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            </ul>
     * @return string json - Transfer object
     * @throws Exception
     * @link https://docs.mangopay.com/endpoints/v2.01/transfers#e224_the-transfer-object
     */
    public static function createTransfer($transferParameters);

    /**
     * Requests reverse of a previously created transfer
     *
     * @param array $transferRefundParameters
     *            <ul>
     *            <li>['TransferId'] <i><u>string</u></i> ID of the previously created transfer</li>
     *            <li>['AuthorId'] <i><u>string</u></i> ID of the debited wallet</li>
     *            <li>['Tag'] <i><u>string</u></i> additional information related to the current transfer</li>
     *            </ul>
     * @return string json - Refund object
     * @link https://docs.mangopay.com/endpoints/v2.01/refunds#e316_the-refund-object
     * @throws Exception
     */
    public static function createTransferRefund($transferRefundParameters);

    /**
     * Requests creation of a user
     *
     * @param array $userParameters
     *            <ul>
     *            <li>['Email'] <i><u>string</u></i> The person's email address - must be a valid email</li>
     *            <li>['FirstName'] <i><u>string</u></i> The name of the user</li>
     *            <li>['LastName'] <i><u>string</u></i> The last name of the user</li>
     *            <li>['Birthday'] <i><u>timestamp</u></i> The date of birth of the user - be careful to set the right timezone (should be UTC) to avoid 00h becoming 23h (and hence interpreted as the day before)</li>
     *            <li>['Nationality'] <i><u>CountryIso</u></i> The user’s nationality. ISO 3166-1 alpha-2 format is expected</li>
     *            <li>['CountryOfResidence'] <i><u>CountryIso</u></i> The user’s country of residence. ISO 3166-1 alpha-2 format is expected</li>
     *            <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            </ul>
     * @return string json - User object
     * @link https://docs.mangopay.com/endpoints/v2.01/users#e253_the-user-object
     * @throws Exception
     */
    public static function createUser($userParameters);

    /**
     * Requests creation of a wallet
     *
     * @param array $walletParameters
     *            <ul>
     *            <li>['Owners'] <i><u>list</u></i> An array of userIDs of who own's the wallet. For now, you only can set up a unique owner.</li>
     *            <li>['Description'] <i><u>string</u></i> A desciption of the wallet</li>
     *            <li>['Currency'] <i><u>CurrencyIso</u></i> The currency - should be ISO_4217 format</li>
     *            <li>['Tag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            </ul>
     * @return string json - Wallet object
     * @link https://docs.mangopay.com/endpoints/v2.01/wallets#e20_the-wallet-object
     * @throws Exception
     */
    public static function createWallet($walletParameters);

    /**
     * Requests list of documents
     *
     * @param string $userId
     *            - id of document owner
     *            
     * @return array List KYC Documents for a User
     * @link https://docs.mangopay.com/endpoints/v2.01/kyc-documents#e216_list-kyc-documents-for-a-user
     * @throws Exception
     */
    public static function documentsList($userId);

    /**
     * Requests edit user parameters
     *
     * @param array $userParameters
     *            <ul>
     *            <li>['Email'] <i><u>string</u></i> (optional) The person's email address - must be a valid email</li>
     *            <li>['FirstName'] <i><u>string</u></i> (optional) The name of the user</li>
     *            <li>['LastName'] <i><u>string</u></i> (optional) The last name of the user</li>
     *            <li>['Birthday'] <i><u>timestamp</u></i> (optional) The date of birth of the user - be careful to set the right timezone (should be UTC) to avoid 00h becoming 23h (and hence interpreted as the day before)</li>
     *            <li>['Nationality'] <i><u>CountryIso</u></i> (optional) The user’s nationality. ISO 3166-1 alpha-2 format is expected</li>
     *            <li>['CountryOfResidence'] <i><u>CountryIso</u></i> (optional) The user’s country of residence. ISO 3166-1 alpha-2 format is expected</li>
     *            <li>['Tag'] <i><u>string</u></i> (optional) Custom data that you can add to this item</li>
     *            <li>['Occupation'] <i><u>string</u></i> (optional) User’s occupation, ie. Work</li>
     *            <li>['IncomeRange'] <i><u>int</u></i> (optional) Could be only one of these values: 1 - for incomes <18K€),2 - for incomes between 18 and 30K€, 3 - for incomes between 30 and 50K€, 4 - for incomes between 50 and 80K€, 5 - for incomes between 80 and 120K€, 6 - for incomes >120K€</li>
     *            <li>
     *            ['Address'] (optional)
     *            <ul>
     *            <li>['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['AddressLine2'] <i><u>string</u></i> The second line of the address</li>
     *            <li>['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['Country'] <i><u>string</u></i> The Country of the Address. A valid ISO 3166-1 alpha-2 format</li>
     *            <li>['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            </ul>
     *            </li>
     *            </ul>
     * @return string json - User object
     * @link https://docs.mangopay.com/endpoints/v2.01/users#e253_the-user-object
     * @throws Exception
     */
    public static function editUser($userParameters);

    /**
     * Requests edit a business user
     *
     * @param array $userParameters
     *            <ul>
     *            <li>['LegalPersonType'] <i><u>LegalPersonType</u></i> The type of legal user(LegalPersonType: BUSINESS, ORGANIZATION, SOLETRADER)</li>
     *            <li>['Name'] <i><u>string</u></i> The name of the legal user</li>
     *            <li>['LegalRepresentativeBirthday'] <i><u>timestamp</u></i> The date of birth of the company’s Legal representative person - be careful to set the right timezone (should be UTC) to avoid 00h becoming 23h (and hence interpreted as the day before)</li>
     *            <li>['LegalRepresentativeCountryOfResidence'] <i><u>string</u></i> The country of residence of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeNationality'] <i><u>CountryIso</u></i> The nationality of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeFirstName'] <i><u>string</u></i> The firstname of the company’s Legal representative person</li>
     *            <li>['LegalRepresentativeLastName'] <i><u>string</u></i> The lastname of the company’s Legal representative person</li>
     *            <li>['Email'] <i><u>string</u></i> The person's email address - must be a valid email</li>
     *            <li>['Tag'] <i><u>string</u></i> (optional) Custom data that you can add to this item</li>
     *            <li>['LegalRepresentativeEmail'] <i><u>string</u></i> (optional) The email of the company’s Legal representative person - must be a valid</li>
     *            <li>
     *            HeadquartersAddress <i><u>Address</u></i> (optional) The address of the company’s headquarters
     *            <ul>
     *            <li>['HeadquartersAddress']['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['HeadquartersAddress']['AddressLine2'] <i><u>string</u></i> (optional) The second line of the address</li>
     *            <li>['HeadquartersAddress']['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['HeadquartersAddress']['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            <li>['HeadquartersAddress']['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['HeadquartersAddress']['Country'] <i><u>CountryIso</u></i> The Country of the Address</li>
     *            </ul>
     *            </li>
     *            <li>
     *            LegalRepresentativeAddress <i><u>Address</u></i> (optional) The address of the company’s Legal representative person
     *            <ul>
     *            <li>['HeadquartersAddress']['AddressLine1'] <i><u>string</u></i> The first line of the address</li>
     *            <li>['HeadquartersAddress']['AddressLine2'] <i><u>string</u></i> (optional) The second line of the address</li>
     *            <li>['HeadquartersAddress']['City'] <i><u>string</u></i> The city of the address</li>
     *            <li>['HeadquartersAddress']['Region'] <i><u>string</u></i> The region of the address - this is optional except if the Country is US, CA or MX</li>
     *            <li>['HeadquartersAddress']['PostalCode'] <i><u>string</u></i> The postal code of the address - can be alphanumeric, dashes or spaces</li>
     *            <li>['HeadquartersAddress']['Country'] <i><u>CountryIso</u></i> The Country of the Address</li>
     *            </ul>
     *            </li>
     *            </ul>
     * @return string json - User object
     * @link https://docs.mangopay.com/endpoints/v2.01/users#e253_the-user-object
     * @throws Exception
     */
    public static function editLegalUser($userParameters);

    /**
     * Requests pay in object
     *
     * @param string $payInId
     *            - id of pay in
     * @return string json - PayIn object
     * @link https://docs.mangopay.com/endpoints/v2.01/payins#e264_the-payin-object
     * @throws Exception
     */
    public static function getPayIn($payInId);

    /**
     * Requests pay out object
     *
     * @param string $payOutId
     *            - id of pay out record
     * @return string json - PayOut object
     * @link https://docs.mangopay.com/endpoints/v2.01/payouts#e227_the-payout-object
     * @throws Exception
     */
    public static function getPayOut($payOutId);

    /**
     * Requests settlement object
     *
     * @param string $transferId
     *            - id of the settlement transaction
     * @return string json - Settlement Transfer object
     * @link https://docs.mangopay.com/endpoints/v2.01/settlement-transfers#e237_the-settlement-transfer-object
     * @throws Exception
     */
    public static function getSettlement($transferId);

    /**
     * Requests transfer object
     *
     * @param string $transferId
     *            - id of transfer
     * @return string json - Transaction object
     * @link https://docs.mangopay.com/endpoints/v2.01/transactions#e222_the-transaction-object
     * @throws Exception
     */
    public static function getTransfer($transferId);

    /**
     * Requests refund object
     *
     * @param string $refundId
     *            - id of refund
     * @return object - Refund object
     * @link https://docs.mangopay.com/endpoints/v2.01/refunds#e316_the-refund-object
     * @throws Exception
     */
    public static function getRefund($refundId);

    /**
     * Requests a user object
     *
     * @param string $userId
     *            - id of user
     * @return string json - User object
     * @link https://docs.mangopay.com/endpoints/v2.01/users#e253_the-user-object
     * @throws Exception
     */
    public static function getUser($userId);

    /**
     * Requests a wallet object
     *
     * @param string $walletId
     *            - id o wallet owner
     * @return string json - Client Wallet object
     * @link https://docs.mangopay.com/endpoints/v2.01/client-wallets#e271_the-client-wallet-object
     * @throws Exception
     */
    public static function getWallet($walletId);

    /**
     * Requests list of transactions from a wallet to send to a mobile device
     *
     * @param string $walletId
     *            - id of wallet
     * @param array $pagination
     *            - (optional) pagination array to filter number the number of returned records
     *            <ul>
     *            <li>['page'] <i><u>string</u></i> The page to return</li>
     *            <li>['itemsPerPage'] <i><u>string</u></i> Number of items per page</li>
     *            </ul>
     * @param array $filter
     *            - (optional) filter columns criteria
     * @param array $sorting
     *            - (optional) sorting columns criteria
     * @return string json - list of Transaction object
     * @link https://docs.mangopay.com/endpoints/v2.01/transactions#e222_the-transaction-object
     * @throws Exception
     */
    public static function getWalletTransactions($walletId, $pagination = null, $filter = null, $sorting = null);

    /**
     * Requests a list of transactions from a wallet to print on web
     *
     * @param string $walletId
     *            - id of wallet
     * @param array $pagination
     *            - paginatiopn array to filter number the number of returned records
     *            <ul>
     *            <li>['page'] <i><u>string</u></i> The page to return</li>
     *            <li>['itemsPerPage'] <i><u>string</u></i> Number of items per page</li>
     *            </ul>
     * @param array $filter
     *            - array with filter properties
     *            <ul>
     *            <li>['afterDate'] <i><u>timestamp</u></i> note that the range can't be more than 6 months, and must be < 13 months ago</li>
     *            <li>['beforeDate'] <i><u>timestamp</u></i> note that the range can't be more than 6 months, and must be < 13 months ago</li>
     *            <li>['nature'] <i><u>string</u></i> (REGULAR, REFUND, REPUDIATION or SETTLEMENT)</li>
     *            <li>['status'] <i><u>string</u></i> (CREATED, SUCCEEDED or FAILED)</li>
     *            <li>['type'] <i><u>string</u></i> (PAYIN, PAYOUT or TRANSFER)</li>1
     *            </ul>
     * @param array $sorting
     *            - The column to sort against and direction ($key=>asc/desc)
     * @return string json - list of Transaction object
     * @link https://docs.mangopay.com/endpoints/v2.01/transactions#e222_the-transaction-object
     * @throws Exception
     */
    public static function getWebWalletTransactions($walletId, $pagination, $filter, $sorting);

    /**
     * Finds a transaction by Id
     *
     * @param string $transactionId
     * @return stdClass The Transaction object
     */
    public static function getTransaction($transactionId);

    /**
     * Init API object
     */
    public static function initApiObject();

    /**
     * Requests a send document to pay provider
     *
     * @param string $userId
     *            - id of user
     * @param string $uploadType
     *            - type of upload (IDENTITY_PROOF, REGISTRATION_PROOF, ARTICLES_OF_ASSOCIATION, SHAREHOLDER_DECLARATION, ADDRESS_PROOF)
     * @param string $uploadTag
     *            - Custom data that you can add to this item
     * @param string $uploadDocPath1
     *            - url of front side document picture
     * @param string $uploadDocPath2
     *            - url of back side document picture
     * @return string json - KYC Document object
     * @link https://docs.mangopay.com/endpoints/v2.01/kyc-documents#e204_the-kyc-document-object
     * @throws Exception
     */
    public static function saveDocument($userId, $uploadType, $uploadTag, $uploadDocPath1, $uploadDocPath2);

    /**
     * Generioc function to process exception
     *
     * @param object $e
     *            - exception Object
     * @param array $result
     *            - result after processing exception
     */
    public static function processException($e, &$result);

    /**
     * Retreive the ballance of a wallet
     *
     * @param string $walletId
     *            - the Id of the wallet
     * @return string wallet ballance in cents, in case of error array['error'] = 'error message';
     */
    public static function getBalanceByWalletId($walletId);

    /**
     * Retreive the list of errors from pay provider
     *
     * @return string array
     */
    public static function getStaticErrors();

    /**
     * Create a Card Registration Object
     *
     * @return array
     */
    public static function createCardRegistrationObject($params);

    /**
     * Update Card Registration Object
     *
     * @return array
     */
    public static function updateCardRegistrationObject($params);

    /**
     * Gets deposit card object from provider
     *
     * @return object deposit card
     */
    public static function getDepositCardByProviderId($cardProviderId);

    /**
     * Creates a dierct pay in.
     *
     * @param array $payInParameters
     * @param string $defaultCurrency
     */
    public static function createDirectPayIn($payInParameters, $defaultCurrency = 'EUR');

    /**
     * Deactivate a Card.
     *
     * @param string $cardId
     */
    public static function deactivateDepositCard($cardId);

    /**
     * Requests reverse of a previously created payIn
     *
     * @param array $payInRefundParameters
     */
    public static function createPayInRefund($payInRefundParameters, $payInProviderId);

    /**
     * Gets a processed callback
     *
     * @param string $resourceId
     */
    public static function getProcessedCallback($resourceId);

    /**
     * Gets a payin status
     *
     * @param string $providerId
     */
    public static function getPayInStatus($providerId);

    /**
     * Gets kyc document
     *
     * @param string $documentId
     */
    public static function getKycDocument($documentId);
}