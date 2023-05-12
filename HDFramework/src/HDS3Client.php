<?php
namespace HDFramework\src;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

/**
 * HD S3 Amazon client for uplaoding pictures
 *
 * Dependencies: HDApplication<br />
 * Configurations dependencies:<br />
 * - config.*.php: S3_VERSION, S3_REGION, S3_KEY, S3_SECRET, S3_FILETYPE_DOCUMENT, S3_FOLDER_LEGAL_DOCUMENTS, S3_PUBLIC_URL, S3_FILETYPE_AVATAR, S3_FOLDER_AVATARS, S3_FILETYPE_CHATPHOTOS, S3_FOLDER_CHATPHOTOS, S3_FILETYPE_CHATVIDEOS, S3_FOLDER_CHATVIDEOS, S3_FILETYPE_CHATFILES, S3_FOLDER_CHATFILES, S3_FILETYPE_CAUSESPHOTOS, S3_FOLDER_CAUSESPHOTOS, S3_FILETYPE_LEGAL_DOCUMENTS, S3_FOLDER_LEGAL_DOCUMENTS, S3_BUCKET<br />
 * <br />Release date: 16/05/2015
 *
 * @version 6.0
 * @author Alin
 * @package framework
 */
class HDS3Client
{

    private static $s3Client = null;

    public static function getS3Client()
    {
        if (! self::$s3Client) {

            // load shared configuration array
            $config = array(
                'version' => HDApplication::getConfiguration('S3_VERSION'),
                'region' => HDApplication::getConfiguration('S3_REGION'),
                'credentials' => array(
                    'key' => HDApplication::getConfiguration('S3_KEY'),
                    'secret' => HDApplication::getConfiguration('S3_SECRET')
                )
            );

            try {
                // create connection client
                self::$s3Client = new S3Client($config);

                // register the Amazon S3 stream wrapper
                self::$s3Client->registerStreamWrapper();
            } catch (S3Exception $e) {
                HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.getS3Client", "returnException", $e->getMessage(), 3, "OUT");
                return null;
            }
        }
        return self::$s3Client;
    }

    /**
     *
     * Upload file to S3 server
     *
     * @param String $fileSource
     *            File path to read data from
     * @param Integer $fileType
     *            (1 = documents, 2 = avatars, 3 = chat photos, 4 = chat videos, 5 = chats files)
     * @param String $namePrefix1
     *            File name prefix
     * @param String $namePrefix2
     *            Secondary file name prefix
     */
    public static function uploadFile($fileSource, $fileType, $fileName)
    {
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "fileSource", $fileSource, 3, "IN");
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "fileType", $fileType, 3, "IN");
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "fileName", $fileName, 3, "IN");

        if (! self::getS3Client()) {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "returnException", "Cannot create S3 client object", 3, "OUT");
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "return", "", 3, "OUT");
            return "";
        }

        // get file mime
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        $fileMime = finfo_file($finfo, $fileSource);
        finfo_close($finfo);

        // init object key
        $objectKey = "";

        // determine object key
        if ($fileType == HDApplication::getConfiguration("S3_FILETYPE_DOCUMENT")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_AVATAR")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_AVATARS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_AVATARS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATPHOTOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATPHOTOS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_CHATPHOTOS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATVIDEOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATVIDEOS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_CHATVIDEOS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATFILES")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATFILES") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_CHATFILES") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CAUSESPHOTOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CAUSESPHOTOS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_CAUSESPHOTOS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_LEGAL_DOCUMENTS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . "/{$fileName}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . '/' . $fileName;
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CARD_FEE_ICON")) {
            $fileName = explode("&", $fileName);
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CARD_FEE_ICONS") . "/{$fileName[0]}/{$fileName[1]}/{$fileName[2]}";
            $fileName = HDApplication::getConfiguration("S3_PUBLIC_URL") . '/' . HDApplication::getConfiguration("S3_FOLDER_CARD_FEE_ICONS") . '/' . $fileName[0] . '/' . $fileName[1] . '/' . $fileName[2];
        } else {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "Unknown file type", $fileType, 3, "OUT");
            return "";
        }

        // log object key
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "Upload objectKey", $objectKey, 3, "OUT");

        try {
            self::getS3Client()->upload(HDApplication::getConfiguration("S3_BUCKET"), $objectKey, fopen($fileSource, "r"), "public-read");
            self::getS3Client()->putObject(array(
                'Bucket' => HDApplication::getConfiguration("S3_BUCKET"),
                'Key' => $objectKey,
                'Body' => fopen($fileSource, "r"),
                'ContentType' => $fileMime,
                'ACL' => 'public-read'
            ));

            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "return", $fileName, 3, "OUT");
            return $fileName;
        } catch (S3Exception $e) {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "returnException", $e->getMessage(), 3, "OUT");
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "returnException", "Cannot upload file to S3", 3, "OUT");
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.uploadFile", "return", "", 3, "OUT");
            return "";
        }
    }

    /**
     *
     * Delete file from S3 bucket
     *
     * @param String $fileName
     * @param Integer $fileType
     *            (1 = documents, 2 = avatars, 3 = chat photos, 4 = chat videos, 5 = chats files)
     */
    public static function deleteFile($fileName, $fileType)
    {
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "fileName", $fileName, 3, "IN");
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "fileType", $fileType, 3, "IN");

        // init S3 client
        if (! self::getS3Client()) {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "returnException", "Cannot create S3 client object", 3, "OUT");
        }

        // init object key
        $objectKey = "";

        // determine object key
        if ($fileType == HDApplication::getConfiguration("S3_FILETYPE_DOCUMENT")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_AVATAR")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_AVATARS") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATPHOTOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATPHOTOS") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATVIDEOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATVIDEOS") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CHATFILES")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CHATFILES") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_CAUSESPHOTOS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_CAUSESPHOTOS") . "/{$fileName}";
        } elseif ($fileType == HDApplication::getConfiguration("S3_FILETYPE_LEGAL_DOCUMENTS")) {
            $objectKey = HDApplication::getConfiguration("S3_FOLDER_LEGAL_DOCUMENTS") . "/{$fileName}";
        } else {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "Unknown file type", $fileType, 3, "OUT");
            return;
        }

        // log object key
        HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "Delete objectKey", $objectKey, 3, "OUT");

        try {
            self::getS3Client()->deleteObject(array(
                'Bucket' => HDApplication::getConfiguration("S3_BUCKET"),
                'Key' => $objectKey
            ));
        } catch (S3Exception $e) {
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "returnException", $e->getMessage(), 3, "OUT");
            HDLog::AppLogMessage("HDS3Client.php", "HDS3Client.deleteFile", "returnException", "Cannot delete file from S3", 3, "OUT");
        }
    }
}