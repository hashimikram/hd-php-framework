<?php
namespace HDFramework\src;

use Exception;

/**
 * Interface Category | framework/src/HDCardSupplier.php
 *
 * @author Cornel
 * @package framework
 */

/**
 * Interface for any card provider class
 *
 * @author cornel
 * @package framework
 */
interface HDCardSupplier
{

    /**
     * Requests create card from payment provider
     *
     * @param array $cardParameters
     *            <ul>
     *            <li>['userId'] <i><u>string</u></i> A user's ID</li>
     *            <li>['type'] <i><u>string</u></i> Type of card: 'PHYSICAL' or 'VIRTUAL'</li>
     *            <li>['customTag'] <i><u>string</u></i> Custom data that you can add to this item</li>
     *            </ul>
     *            
     * @return string json $return - card object
     * @throws Exception
     */
    public static function createCard($cardParameters);

    /**
     * Adds Currency to attached bank card.
     *
     * @param string $cardId
     *            - id of bank card to with the currency will be added
     * @param string $currency
     *            - currency
     * @return string - id of modified card
     */
    public static function addCurrencyToCard($cardId, $currency);

    /**
     * Gets card object
     *
     * @param string $cardId
     *            - id of bank card to with the currency will be added
     * @return object - bank card object
     */
    public static function getCard($cardId);

    /**
     * Returns wallet of the provided currency for a bank card.
     *
     * @param string $cardId
     * @param string $currency
     * @return object - wallet object
     */
    public static function getCardWallet($cardId, $currency);

    /**
     * Returns transactions from a card made with a specified currency
     *
     * @param string $cardId
     *            - id of card
     * @param string $currency
     *            - currency of transactions
     * @param string $startDate
     *            - start date of transactions
     * @param string $endDate
     *            - end date of transactions
     * @return array transactions objects
     */
    public static function getCardWalletTransactions($cardId, $currency, $startDate, $endDate);

    /**
     * Upgrades a VIRTUAL card to PHYSICAL
     *
     * @param string $cardId
     * @param array $cardParameters
     *            paramters for card upgrade (example: delivery address)
     * @return string modified id
     */
    public static function upgradeCard($cardId, $cardParameters);

    /**
     * Changes status of bank card.
     *
     * @param string $cardId
     * @param string $oldStatus
     * @param string $newStatus
     */
    public static function changeCardStatus($cardId, $oldStatus, $newStatus);

    /**
     * Returns last 4 numbers of bank card.
     *
     * @param string $cardId
     */
    public static function getLocalCardNumber($cardId);

    /**
     * Return full card number
     *
     * @param string $cardId
     */
    public static function getCardNumber($cardId);

    /**
     * Return expiry data of card
     *
     * @param string $cardId
     */
    public static function getExpiryDate($cardId);

    /**
     * Return cvv card
     *
     * @param string $cardId
     */
    public static function getCvv($cardId);

    /**
     * Make a transfer to a card
     *
     * @param array $entity
     *            data needed for the transfer
     */
    public static function transferToCard($entity);

    /**
     * Send pin tu card owner
     *
     * @param string $cardId
     */
    public static function sendCardPinToUser($cardId);

    /**
     * Locks an OPEN card OR unlocks a locked card
     *
     * @param string $cardId
     * @param string $oldStatus
     * @param string $newStatus
     */
    public static function lockUnlockCard($cardId, $oldStatus, $newStatus);

    /**
     * Transfer from card
     *
     * @param string $cardIdRemote
     * @param string $currency
     * @param string $amount
     */
    public static function transferFromCard($cardId, $currency, $amount);

    /**
     * Tells supplier to update card from database records
     *
     * @param string $cardId
     * @param array $cardParameters
     *            array containing parameters for card update
     */
    public static function updateCard($cardId, $cardParameters);

    /**
     * Returns Account Card Wallets details
     */
    public static function getAccountCardWallets();

    /**
     * Replaces a card with a new one.
     *
     * @param string $cardId
     * @param string $reason
     */
    public static function replaceCard($cardId, $reason);

    /**
     * Executes a payment from card to bank account
     *
     * @param string $cardId
     * @param string $beneficiaryName
     * @param string $creditorIban
     * @param string $creditorBic
     * @param string $paymentAmount
     * @param string $reference
     */
    public static function executeBankPayment($cardId, $beneficiaryName, $creditorIban, $creditorBic, $paymentAmount, $reference);

    /**
     * Gets FX quote
     *
     * @param string $cardId
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $amount
     */
    public static function getCurrencyFxQuote($cardId, $fromCurrency, $toCurrency, $amount);

    /**
     * Executes a card wallet trade.
     *
     * @param string $cardId
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string $amount
     */
    public static function executeCardWalletsTrade($cardId, $fromCurrency, $toCurrency, $amount);
}