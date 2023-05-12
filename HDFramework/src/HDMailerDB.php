<?php
namespace HDFramework\src;

/**
 * Sends email
 *
 * Configurations dependencies:<br />
 * - config.*.php: EMAIL_QUEUE_TABLE
 * <br />Release date: 08/10/2017
 *
 * @version 7.0
 * @author cornel
 * @package framework
 */
class HDMailerDB
{

    /**
     *
     * Save email in email queue table before applying the template engine design
     * We just save the template name and template variables
     * Email table queue requires the following fields: fromEmail,
     *
     * @param String $fromEmail
     * @param String $fromName
     * @param String $toEmail
     * @param String $toName
     * @param String $ccEmail
     * @param String $bccEmail
     * @param
     *            Array JSON $emailAtachements
     * @param String $templateName
     * @param
     *            Array JSON $templateVars
     */
    public static function sendEmail($fromEmail, $fromName, $toEmail, $toName, $ccEmail, $bccEmail, $emailAtachements, $templateName, $templateVars)
    {
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "fromEmail", $fromEmail, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "fromName", $fromName, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "toEmail", $toEmail, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "toName", $toName, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "ccEmail", $ccEmail, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "bccEmail", $bccEmail, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "emailAtachements", $emailAtachements, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "templateName", $templateName, 3, "IN");
        HDLog::AppLogMessage("Mailer.php", "Mailer.sendEmail", "templateVars", $templateVars, 3, "IN");

        $emailTable = HDApplication::getConfiguration("EMAIL_QUEUE_TABLE");
        if (! empty($emailTable)) {
            // if mail table is configured then we use that table
            $insertArray = array();
            $insertArray["fromEmail"] = $fromEmail;
            $insertArray["fromName"] = $fromName;
            $insertArray["toEmail"] = $toEmail;
            $insertArray["toName"] = $toName;
            $insertArray["ccEmail"] = $ccEmail;
            $insertArray["bccEmail"] = $bccEmail;
            $insertArray["attachments"] = $emailAtachements;
            $insertArray["dateAdded"] = time();
            $insertArray["dateSent"] = 0;
            $insertArray["sent"] = 0;
            $insertArray["template_name"] = $templateName;
            $insertArray["template_vars"] = $templateVars;

            HDLog::AppLogMessage("HDMailerDB.php", "HDMailerDB.sendEmail", "insert", $insertArray, 3);
            HDDatabase::getConnection()->insert($emailTable, $insertArray, false, true);
            unset($insertArray);
        } else {
            HDLog::AppLogMessage("HDMailerDB.php", "HDMailerDB.sendEmail", "NO Insert", "Email queue table not set", 3);
        }

        // Check if we have to send the email right now
        HDLog::AppLogMessage("HDMailerDB.php", "HDMailerDB.sendEmail", "return", "", 3, "OUT");
    }
}
