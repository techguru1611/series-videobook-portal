<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class Helpers
{
    public static function getAPIPaginationData($pageNo, $limit, $totalCount)
    {
        $noOfPages = (ceil($totalCount / $limit) == 0) ? 1 : ceil($totalCount / $limit);
        $atPage = (($pageNo > $noOfPages) ? $noOfPages : $pageNo);
        if (!isset($pageNo) && empty($pageNo)){
            return [
                'offset' => 0,
                'next' => false,
                'previous' => false,
                'noOfPages' => 1,
            ];
        }
        return [
            'offset' => (($atPage - 1) * $limit),
            'next' => ($noOfPages > $atPage) ? true : false,
            'previous' => ($atPage == 1) ? false : true,
            'noOfPages' => $noOfPages,
        ];
    }

    public static function generateAWSSignedUrl($objectKey){
        // generate video's signed url
        $s3 = Storage::disk('s3');
        $client = $s3->getDriver()->getAdapter()->getClient();
        $expiry = "+48 hours";

        $command = $client->getCommand('GetObject', [
            'Bucket' => 'videoseries-inex',
            'Key'    => $objectKey
        ]);

        $request = $client->createPresignedRequest($command, $expiry);

        return (string) $request->getUri();
    }
}