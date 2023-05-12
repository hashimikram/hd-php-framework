<?php
namespace HDFramework\src;

/**
 * HDMail class to manage DB table based e-mails
 *
 * Dependencies: HDApplication, HDDatabase
 * Configurations dependencies:<br />
 * - config.*.php: EMAIL_QUEUE_TABLE, DBMODE, EMAIL_CRON_ACTION, DEFAULT_METHOD, URL, <br />
 * <br />Release date: 08/10/2015
 *
 * @version 7.0
 * @author Alin
 * @package framework
 */
class HDMail
{

    /**
     * Send an email by adding it to the email queue
     *
     * @param String $fromEmail
     *            = Sender email
     * @param String $fromName
     *            = Sender name
     * @param String $toEmail
     *            = Receiver email
     * @param String $toName
     *            = Receiver name
     * @param String $ccEmail
     *            = CC Receiver email
     * @param String $bccEmail
     *            = BCC Receiver email
     * @param String $emailSubject
     *            = Email subject
     * @param String $emailHTMLContent
     *            = HTML Email content
     * @param String $emailTxtContent
     *            = Text Email content
     * @param Array $emailAtachements
     *            = Paths to attachments array
     * @param boolean $sendNow
     *            = send now
     */
    public static function sendEmail($fromEmail, $fromName, $toEmail, $toName, $ccEmail, $bccEmail, $emailSubject, $emailHTMLContent, $emailTxtContent, $emailAtachements, $sendNow = false)
    {
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "fromEmail", $fromEmail, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "fromName", $fromName, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "toEmail", $toEmail, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "toName", $toName, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "ccEmail", $ccEmail, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "bccEmail", $bccEmail, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "emailSubject", $emailSubject, 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "emailHTMLContent", substr($emailHTMLContent, 0, 100) . " ...", 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "emailTxtContent", substr($emailTxtContent, 0, 100) . " ...", 3, "IN");
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "emailAtachements", $emailAtachements, 3, "IN");

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
            $insertArray["subject"] = $emailSubject;
            $insertArray["htmlContent"] = $emailHTMLContent;
            $insertArray["txtContent"] = $emailTxtContent;
            $insertArray["attachments"] = $emailAtachements;
            $insertArray["dateAdded"] = time();
            $insertArray["dateSent"] = 0;
            $insertArray["sent"] = 0;

            HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "insert", $insertArray, 3);

            // get DBMODE
            $dbType = HDApplication::getConfiguration('DBMODE');

            // insert log in table based on db type
            if ($dbType == "mongo") {
                HDDatabase::getTable($emailTable)->insert($insertArray, array(
                    "w" => 0
                ));
            } else {
                HDDatabase::getConnection()->insert($emailTable, $insertArray, false, true);
            }
            unset($insertArray);
        } else {
            HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "NO Insert", "Email queue table not set", 3);
        }

        // Check if we have to send the email right now
        if (HDApplication::getConfiguration("EMAIL_CRON_ACTION") != "" && $sendNow == true) {
            $cronLink = HDRedirect::buildLink(HDApplication::getConfiguration("EMAIL_CRON_ACTION"), HDApplication::getConfiguration("DEFAULT_METHOD"));
            HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "Execute cron email", $cronLink, 3, 'PARAM');

            // get port

            if (strpos(HDApplication::getConfiguration("URL"), "https://") === false) {
                $port = 80;
            } else {
                $port = 443;
            }

            // make an asyncron call to the link
            $parts = parse_url((string) $cronLink);
            $errno = 0;
            $errstr = "";
            $fp = fsockopen($parts['host'], $port, $errno, $errstr, 30);

            if (! $fp) {
                HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "fsockopen err", $errstr . ' : ' . $errno, 3, 'PARAM');
            } else {
                $out = "GET " . $parts['path'] . " HTTP/1.1\r\n";
                $out .= "Host: " . $parts['host'] . "\r\n";
                $out .= "Connection: Close\r\n\r\n";
                fwrite($fp, $out);
                fclose($fp);
            }
        } else {
            HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "Email not sent", "Email cron not defined", 3);
        }
        HDLog::AppLogMessage("HDMail.php", "HDMail.sendEmail", "return", "", 3, "OUT");
    }
}
